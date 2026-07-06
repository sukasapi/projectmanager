<?php

namespace App\Livewire\Tim;

use App\Enums\EmploymentType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Daftar & kelola anggota tim (artis terdaftar). Akses halaman = Gate view-tim
 * (Supervisor/Super Admin atau Team Lead). Penambahan/perubahan peran mengikuti
 * matriks jenjang (User::bolehMenetapkanPeran): Super Admin > Supervisor > Team Lead.
 * Penghapusan keras diganti nonaktif demi integritas penugasan.
 * Lihat UI.md §8.14 & 2026-07-01_peran-team-lead-hak-akses.md.
 */
#[Layout('components.layouts.app')]
class DaftarTim extends Component
{
    public string $cari = '';

    public string $filterTipe = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'Artis';

    public string $jabatan = '';

    public string $phone = '';

    public string $whatsapp = '';

    public string $address = '';

    public ?string $latitude = null;

    public ?string $longitude = null;

    public string $employmentType = '';

    /** Kapasitas hari kerja per minggu (Tier C3, default 5). */
    public ?int $kapasitasHari = 5;

    public string $password = '';

    public bool $isActive = true;

    public function mount(): void
    {
        // Akses Tim & Artis: Supervisor/Super Admin atau Team Lead (Gate view-tim).
        abort_unless(Gate::allows('view-tim'), 403);
        $this->employmentType = EmploymentType::CONTRACT->value;
    }

    /** Gerbang dasar membuka halaman/aksi kelola. */
    private function pastikanBolehKelola(): void
    {
        abort_unless(Gate::allows('view-tim'), 403);
    }

    /** Aktor hanya boleh mengelola user yang peran-nya dalam wewenangnya (anti-eskalasi). */
    private function pastikanBolehKelolaUser(User $target): void
    {
        abort_unless(auth()->user()->bolehMenetapkanPeran($target->role), 403);
    }

    public function create(): void
    {
        $this->pastikanBolehKelola();
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->pastikanBolehKelola();
        $user = User::findOrFail($id);
        $this->pastikanBolehKelolaUser($user);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role ?? 'Artis';
        $this->jabatan = $user->jabatan ?? '';
        $this->phone = $user->phone ?? '';
        $this->whatsapp = $user->whatsapp ?? '';
        $this->address = $user->address ?? '';
        $this->latitude = $user->latitude !== null ? (string) $user->latitude : null;
        $this->longitude = $user->longitude !== null ? (string) $user->longitude : null;
        $this->employmentType = $user->employment_type?->value ?? EmploymentType::CONTRACT->value;
        $this->kapasitasHari = $user->kapasitas_hari ?? 5;
        $this->isActive = (bool) $user->is_active;
        $this->password = '';
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->pastikanBolehKelola();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('kf_pengguna', 'email')->ignore($this->editingId)],
            'role' => ['nullable', 'string', 'max:100'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'employmentType' => ['required', Rule::in(array_column(EmploymentType::cases(), 'value'))],
            'kapasitasHari' => ['nullable', 'integer', 'min:0', 'max:7'],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
            'isActive' => ['boolean'],
        ], attributes: [
            'name' => 'nama', 'employmentType' => 'jenis kepegawaian', 'password' => 'kata sandi',
        ]);

        $aktor = auth()->user();

        // Anti-eskalasi saat edit: aktor harus berwenang atas peran user saat ini.
        if ($this->editingId) {
            $existing = User::findOrFail($this->editingId);
            $this->pastikanBolehKelolaUser($existing);
        }

        // Aktor harus berwenang menetapkan peran TARGET (matriks jenjang).
        if (! $aktor->bolehMenetapkanPeran($validated['role'] ?? null)) {
            $this->addError('role', 'Anda tidak berwenang menetapkan peran tersebut.');

            return;
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'] ?: null,
            'jabatan' => $validated['jabatan'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'whatsapp' => $validated['whatsapp'] ?: null,
            'address' => $validated['address'] ?: null,
            'latitude' => $validated['latitude'] !== '' ? $validated['latitude'] : null,
            'longitude' => $validated['longitude'] !== '' ? $validated['longitude'] : null,
            'employment_type' => $validated['employmentType'],
            'kapasitas_hari' => $validated['kapasitasHari'] ?? 5,
            'is_active' => $validated['isActive'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        User::updateOrCreate(['id' => $this->editingId], $data);

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('tim-tersimpan');
    }

    /** Aktif/nonaktif anggota (pengganti hapus — jaga integritas penugasan & riwayat). */
    public function toggleAktif(int $id): void
    {
        $this->pastikanBolehKelola();
        $user = User::findOrFail($id);
        $this->pastikanBolehKelolaUser($user);
        $user->update(['is_active' => ! $user->is_active]);
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'jabatan', 'phone', 'whatsapp', 'address', 'latitude', 'longitude', 'password']);
        $this->role = 'Artis';
        $this->employmentType = EmploymentType::CONTRACT->value;
        $this->kapasitasHari = 5;
        $this->isActive = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $anggota = User::query()
            ->when($this->cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->cari}%")
                ->orWhere('email', 'like', "%{$this->cari}%")
                ->orWhere('role', 'like', "%{$this->cari}%")
                ->orWhere('jabatan', 'like', "%{$this->cari}%")))
            ->when($this->filterTipe !== '', fn ($q) => $q->where('employment_type', $this->filterTipe))
            ->withCount(['tugasShot', 'tugasPraproduksi', 'aset', 'tugasPascaproduksi'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('livewire.tim.daftar-tim', [
            'anggota' => $anggota,
            'daftarTipe' => EmploymentType::cases(),
            'bisaKelola' => Gate::allows('view-tim'),
            'peranOpsi' => auth()->user()->peranDapatDitetapkan(),
            'jabatanOpsi' => User::JABATAN_UMUM,
        ]);
    }
}
