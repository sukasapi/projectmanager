<?php

namespace App\Livewire\Logbook;

use App\Enums\StatusLogbook;
use App\Models\Logbook;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Logbook harian milik pengguna login (Freelance & Intern). Entri DRAFT bisa
 * diedit/hapus; setelah Dikirim terkunci & menunggu review. Lihat UI.md §8.23.
 */
#[Layout('components.layouts.app')]
class Harian extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $tanggal = '';

    public string $jamMulai = '09:00';

    public string $jamSelesai = '12:00';

    public ?int $shotTaskId = null;

    public string $deskripsi = '';

    public string $outputUrl = '';

    public function mount(): void
    {
        $this->tanggal = Carbon::now(config('kehadiran.timezone'))->toDateString();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $logbook = Logbook::where('user_id', auth()->id())->findOrFail($id);

        abort_unless($logbook->status->dapatDiedit(), 403);

        $tz = config('kehadiran.timezone');
        $this->editingId = $logbook->id;
        $this->tanggal = $logbook->tanggal->timezone($tz)->toDateString();
        $this->jamMulai = $logbook->jam_mulai->timezone($tz)->format('H:i');
        $this->jamSelesai = $logbook->jam_selesai->timezone($tz)->format('H:i');
        $this->shotTaskId = $logbook->shot_task_id;
        $this->deskripsi = $logbook->deskripsi;
        $this->outputUrl = $logbook->output_url ?? '';
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'tanggal' => ['required', 'date'],
            'jamMulai' => ['required', 'date_format:H:i'],
            'jamSelesai' => ['required', 'date_format:H:i', 'after:jamMulai'],
            'shotTaskId' => ['nullable', 'integer', 'exists:kf_tugas_shot,id'],
            'deskripsi' => ['required', 'string', 'max:2000'],
            'outputUrl' => ['nullable', 'url', 'max:255'],
        ], attributes: [
            'jamSelesai' => 'jam selesai',
            'deskripsi' => 'deskripsi pekerjaan',
        ]);

        $tz = config('kehadiran.timezone');
        $mulai = Carbon::createFromFormat('Y-m-d H:i', $validated['tanggal'].' '.$validated['jamMulai'], $tz)->utc();
        $selesai = Carbon::createFromFormat('Y-m-d H:i', $validated['tanggal'].' '.$validated['jamSelesai'], $tz)->utc();

        $logbook = $this->editingId
            ? Logbook::where('user_id', auth()->id())->findOrFail($this->editingId)
            : new Logbook(['user_id' => auth()->id()]);

        // Hanya entri DRAFT yang boleh disimpan ulang.
        if ($this->editingId) {
            abort_unless($logbook->status->dapatDiedit(), 403);
        }

        $logbook->fill([
            'user_id' => auth()->id(),
            'tanggal' => $validated['tanggal'],
            'jam_mulai' => $mulai,
            'jam_selesai' => $selesai,
            'shot_task_id' => $validated['shotTaskId'] ?: null,
            'deskripsi' => $validated['deskripsi'],
            'output_url' => $validated['outputUrl'] ?: null,
            'status' => StatusLogbook::DRAFT->value,
        ])->save();

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('logbook-tersimpan');
    }

    /** Kirim entri DRAFT untuk direview (terkunci setelahnya). */
    public function submit(int $id): void
    {
        $logbook = Logbook::where('user_id', auth()->id())->findOrFail($id);

        if ($logbook->status->dapatDiedit()) {
            $logbook->update(['status' => StatusLogbook::DIKIRIM->value]);
        }
    }

    public function delete(int $id): void
    {
        $logbook = Logbook::where('user_id', auth()->id())->find($id);

        if ($logbook && $logbook->status->dapatDiedit()) {
            $logbook->delete();
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'shotTaskId', 'deskripsi', 'outputUrl']);
        $this->tanggal = Carbon::now(config('kehadiran.timezone'))->toDateString();
        $this->jamMulai = '09:00';
        $this->jamSelesai = '12:00';
        $this->resetErrorBag();
    }

    public function render()
    {
        $entri = Logbook::where('user_id', auth()->id())
            ->with('tugasShot.shot')
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_mulai')
            ->limit(60)
            ->get();

        // Shot-task yang ditugaskan ke pengguna ini (untuk dropdown tautan tugas).
        $tugasSaya = auth()->user()->tugasShot()->with('shot')->get();

        return view('livewire.logbook.harian', [
            'entri' => $entri,
            'tugasSaya' => $tugasSaya,
            'tz' => config('kehadiran.timezone'),
            'totalMenit' => (int) $entri->sum('durasi_menit'),
        ]);
    }
}
