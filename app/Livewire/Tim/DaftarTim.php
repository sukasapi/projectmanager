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
 * Daftar & kelola anggota tim (artis terdaftar). Semua boleh melihat direktori;
 * aksi kelola (buat/edit/aktif-nonaktif) hanya untuk peran Supervisor (Gate manage-tim).
 * Penghapusan keras diganti nonaktif demi menjaga integritas penugasan. Lihat UI.md §8.14.
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

    public string $role = '';

    public string $employmentType = '';

    public string $password = '';

    public bool $isActive = true;

    public function mount(): void
    {
        $this->employmentType = EmploymentType::CONTRACT->value;
    }

    private function pastikanBolehKelola(): void
    {
        abort_unless(Gate::allows('manage-tim'), 403);
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

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role ?? '';
        $this->employmentType = $user->employment_type?->value ?? EmploymentType::CONTRACT->value;
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
            'employmentType' => ['required', Rule::in(array_column(EmploymentType::cases(), 'value'))],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
            'isActive' => ['boolean'],
        ], attributes: [
            'name' => 'nama', 'employmentType' => 'jenis kepegawaian', 'password' => 'kata sandi',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'] ?: null,
            'employment_type' => $validated['employmentType'],
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
        $user->update(['is_active' => ! $user->is_active]);
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'role', 'password']);
        $this->employmentType = EmploymentType::CONTRACT->value;
        $this->isActive = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $anggota = User::query()
            ->when($this->cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->cari}%")
                ->orWhere('email', 'like', "%{$this->cari}%")
                ->orWhere('role', 'like', "%{$this->cari}%")))
            ->when($this->filterTipe !== '', fn ($q) => $q->where('employment_type', $this->filterTipe))
            ->withCount(['tugasShot', 'tugasPraproduksi', 'aset', 'tugasPascaproduksi'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('livewire.tim.daftar-tim', [
            'anggota' => $anggota,
            'daftarTipe' => EmploymentType::cases(),
            'bisaKelola' => Gate::allows('manage-tim'),
        ]);
    }
}
