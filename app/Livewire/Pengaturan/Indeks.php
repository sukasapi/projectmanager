<?php

namespace App\Livewire\Pengaturan;

use App\Models\Perusahaan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pengaturan: akun pribadi (nama/email/kata sandi) untuk semua pengguna, info
 * kebijakan kehadiran (read-only), dan pintasan admin. Lihat UI.md §8.18 (+ §8.3 Profil).
 */
#[Layout('components.layouts.app')]
class Indeks extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    // --- Profil perusahaan (admin) ---
    public string $comp_name = '';

    public string $comp_legal_name = '';

    public string $comp_email = '';

    public string $comp_phone = '';

    public string $comp_website = '';

    public string $comp_address = '';

    public string $comp_tagline = '';

    // --- Kebijakan kehadiran (Super Admin) ---
    public string $comp_jam_masuk = '09:00';

    public string $comp_jam_pulang = '17:00';

    public int $comp_toleransi = 15;

    // --- Tampilan aplikasi (Super Admin) ---
    public string $comp_app_name = '';

    public string $comp_footer = '';

    public $logoFile = null;

    public $loginFile = null;

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;

        if (Gate::allows('manage-config')) {
            $p = Perusahaan::current();
            $this->comp_name = $p->name ?? '';
            $this->comp_legal_name = $p->legal_name ?? '';
            $this->comp_email = $p->email ?? '';
            $this->comp_phone = $p->phone ?? '';
            $this->comp_website = $p->website ?? '';
            $this->comp_address = $p->address ?? '';
            $this->comp_tagline = $p->tagline ?? '';
            $this->comp_jam_masuk = $p->jamMasuk();
            $this->comp_jam_pulang = $p->jamPulang();
            $this->comp_toleransi = $p->toleransiMenit();
            $this->comp_app_name = $p->app_name ?? '';
            $this->comp_footer = $p->footer_text ?? '';
        }
    }

    public function simpanPerusahaan(): void
    {
        abort_unless(Gate::allows('manage-config'), 403);

        $validated = $this->validate([
            'comp_name' => ['required', 'string', 'max:255'],
            'comp_legal_name' => ['nullable', 'string', 'max:255'],
            'comp_email' => ['nullable', 'email', 'max:255'],
            'comp_phone' => ['nullable', 'string', 'max:30'],
            'comp_website' => ['nullable', 'url', 'max:255'],
            'comp_address' => ['nullable', 'string', 'max:500'],
            'comp_tagline' => ['nullable', 'string', 'max:255'],
            'comp_jam_masuk' => ['required', 'date_format:H:i'],
            'comp_jam_pulang' => ['required', 'date_format:H:i', 'after:comp_jam_masuk'],
            'comp_toleransi' => ['required', 'integer', 'min:0', 'max:240'],
            'comp_app_name' => ['nullable', 'string', 'max:100'],
            'comp_footer' => ['nullable', 'string', 'max:255'],
            'logoFile' => ['nullable', 'image', 'max:2048'],
            'loginFile' => ['nullable', 'image', 'max:4096'],
        ], attributes: [
            'comp_name' => 'nama perusahaan',
            'comp_jam_pulang' => 'jam pulang',
            'comp_jam_masuk' => 'jam masuk',
            'comp_app_name' => 'nama aplikasi',
        ]);

        $data = [
            'name' => $validated['comp_name'],
            'legal_name' => $validated['comp_legal_name'] ?: null,
            'email' => $validated['comp_email'] ?: null,
            'phone' => $validated['comp_phone'] ?: null,
            'website' => $validated['comp_website'] ?: null,
            'address' => $validated['comp_address'] ?: null,
            'tagline' => $validated['comp_tagline'] ?: null,
            'jam_masuk' => $validated['comp_jam_masuk'],
            'jam_pulang' => $validated['comp_jam_pulang'],
            'toleransi_menit' => $validated['comp_toleransi'],
            'app_name' => $validated['comp_app_name'] ?: null,
            'footer_text' => $validated['comp_footer'] ?: null,
        ];

        if ($this->logoFile) {
            $data['logo_path'] = $this->logoFile->store('branding', 'public');
        }
        if ($this->loginFile) {
            $data['login_image_path'] = $this->loginFile->store('branding', 'public');
        }

        Perusahaan::current()->update($data);

        $this->reset(['logoFile', 'loginFile']);
        $this->dispatch('perusahaan-tersimpan');
    }

    public function simpanProfil(): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('kf_pengguna', 'email')->ignore($user->id)],
        ], attributes: ['name' => 'nama']);

        $user->update($validated);

        $this->dispatch('profil-tersimpan');
    }

    public function ubahPassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ], attributes: [
            'currentPassword' => 'kata sandi saat ini',
            'newPassword' => 'kata sandi baru',
        ]);

        auth()->user()->update(['password' => Hash::make($this->newPassword)]);

        $this->reset(['currentPassword', 'newPassword', 'newPassword_confirmation']);
        $this->dispatch('password-tersimpan');
    }

    public function render()
    {
        $p = Perusahaan::current();

        return view('livewire.pengaturan.indeks', [
            'user' => auth()->user(),
            'perusahaan' => $p,
            'isAdmin' => Gate::allows('manage-tim'),
            'isSuperAdmin' => Gate::allows('manage-config'),
            'kebijakan' => [
                'jam_masuk' => $p->jamMasuk(),
                'jam_pulang' => $p->jamPulang(),
                'toleransi' => $p->toleransiMenit(),
                'timezone' => config('kehadiran.timezone'),
                'geotag' => config('kehadiran.geotag_aktif'),
            ],
        ]);
    }
}
