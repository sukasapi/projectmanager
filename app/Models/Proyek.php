<?php

namespace App\Models;

use App\Actions\SnapshotPipeline;
use App\Enums\ProjectStatus;
use App\Observers\ProyekObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Project/Episode — akar hierarki produksi.
 */
#[ObservedBy([ProyekObserver::class])]
class Proyek extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kf_proyek';

    protected $fillable = [
        'name',
        'description',
        'status',
        'client_id',
        'series_id',
        'team_lead_id',
        'published_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function teamLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->closed_at === null;
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    public function isDraft(): bool
    {
        return $this->published_at === null;
    }

    /** Boleh SETUP (assign artis, tambah scene/shot): Supervisor/Super Admin atau Team Lead. Episode CLOSED read-only. */
    public function dapatDikelola(?User $user): bool
    {
        if (! $user || $this->isClosed()) {
            return false;
        }

        return $user->isSupervisory() || $this->team_lead_id === $user->id;
    }

    /** Boleh me-REVIEW (approve/reject/minta revisi): hanya Supervisor & Team Lead (Super Admin read-only). CLOSED read-only. */
    public function dapatReview(?User $user): bool
    {
        if (! $user || $this->isClosed()) {
            return false;
        }

        return $user->role === 'Supervisor' || $this->team_lead_id === $user->id;
    }

    /** Boleh membuka kembali episode yang sudah ditutup: hanya Super Admin atau Supervisor. */
    public function dapatDibukaKembali(?User $user): bool
    {
        return $user?->isSupervisory() ?? false;
    }

    /** ID seluruh artis yang ditugaskan di episode ini (shot-task, tahap, aset). */
    public function assignedUserIds(): Collection
    {
        $shot = TugasShot::query()
            ->whereIn('shot_id', Shot::whereIn('scene_id', $this->adegan()->pluck('id'))->pluck('id'))
            ->with('artists:id')
            ->get()
            ->flatMap(fn ($t) => $t->artists->pluck('id'));

        $tahap = $this->tugasTahap()->whereNotNull('artist_id')->pluck('artist_id');
        $aset = $this->aset()->whereNotNull('artist_id')->pluck('artist_id');

        return $shot->merge($tahap)->merge($aset)->unique()->values();
    }

    /**
     * Episode tempat seorang user ditugaskan (shot-task pivot / aset / pra / pasca).
     * Dipakai untuk membatasi pilihan episode non-supervisor. Lihat PIPELINE.md.
     *
     * @param  Builder<Proyek>  $query
     */
    public function scopeUntukUser(Builder $query, int $userId): void
    {
        $query->where(function (Builder $q) use ($userId) {
            $q->whereHas('adegan.shot.tugasShot.artists', fn (Builder $w) => $w->where('kf_pengguna.id', $userId))
                ->orWhereHas('aset', fn (Builder $w) => $w->where('artist_id', $userId))
                ->orWhereHas('tugasTahap', fn (Builder $w) => $w->where('artist_id', $userId))
                ->orWhereHas('tugasPraproduksi', fn (Builder $w) => $w->where('artist_id', $userId))
                ->orWhereHas('tugasPascaproduksi', fn (Builder $w) => $w->where('artist_id', $userId));
        });
    }

    /** @return BelongsTo<Klien, $this> */
    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class, 'client_id');
    }

    /** @return BelongsTo<Seri, $this> */
    public function seri(): BelongsTo
    {
        return $this->belongsTo(Seri::class, 'series_id');
    }

    /** @return HasMany<Adegan, $this> */
    public function adegan(): HasMany
    {
        return $this->hasMany(Adegan::class, 'project_id');
    }

    /** @return HasMany<Aset, $this> */
    public function aset(): HasMany
    {
        return $this->hasMany(Aset::class, 'project_id');
    }

    /** @return HasMany<TugasPraproduksi, $this> */
    public function tugasPraproduksi(): HasMany
    {
        return $this->hasMany(TugasPraproduksi::class, 'project_id');
    }

    /** @return HasMany<TugasPascaproduksi, $this> */
    public function tugasPascaproduksi(): HasMany
    {
        return $this->hasMany(TugasPascaproduksi::class, 'project_id');
    }

    /** Tugas tahap level-episode (Pra/Pasca, configurable). @return HasMany<TugasTahap, $this> */
    public function tugasTahap(): HasMany
    {
        return $this->hasMany(TugasTahap::class, 'project_id');
    }

    /** Snapshot pipeline milik episode ini (dibekukan saat dibuat). @return HasMany<Tahap, $this> */
    public function tahap(): HasMany
    {
        return $this->hasMany(Tahap::class, 'project_id');
    }

    /**
     * Self-heal: bila episode kehilangan snapshot pipeline (mis. gagal transien saat
     * dibuat), bekukan ulang dari template global. Idempoten. Lihat SnapshotPipeline.
     */
    public function pastikanPipeline(): void
    {
        if ($this->tahap()->doesntExist()) {
            app(SnapshotPipeline::class)->untukEpisode($this);
        }
    }
}
