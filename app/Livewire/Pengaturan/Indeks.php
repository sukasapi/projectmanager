<?php

namespace App\Livewire\Pengaturan;

use App\Models\Perusahaan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

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

    // --- SMTP (Super Admin) ---
    public string $smtp_host = '';

    public ?int $smtp_port = null;

    public string $smtp_username = '';

    public string $smtp_password = ''; // kosong = tetap

    public string $smtp_encryption = '';

    public string $smtp_from_address = '';

    public string $smtp_from_name = '';

    public string $ujiEmail = '';

    public ?string $ujiHasil = null;

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

            // SMTP (password sengaja tidak dimuat demi keamanan; kosongkan = tetap).
            $this->smtp_host = $p->smtp_host ?? '';
            $this->smtp_port = $p->smtp_port;
            $this->smtp_username = $p->smtp_username ?? '';
            $this->smtp_encryption = $p->smtp_encryption ?? '';
            $this->smtp_from_address = $p->smtp_from_address ?? '';
            $this->smtp_from_name = $p->smtp_from_name ?? '';
            $this->ujiEmail = auth()->user()->email;
        }
    }

    /** Simpan konfigurasi SMTP (password hanya diperbarui bila diisi). */
    public function simpanSmtp(): void
    {
        abort_unless(Gate::allows('manage-config'), 403);

        $v = $this->validate([
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],
        ], attributes: ['smtp_host' => 'host SMTP', 'smtp_from_address' => 'email pengirim']);

        $data = [
            'smtp_host' => $v['smtp_host'] ?: null,
            'smtp_port' => $v['smtp_port'] ?: null,
            'smtp_username' => $v['smtp_username'] ?: null,
            'smtp_encryption' => $v['smtp_encryption'] ?: null,
            'smtp_from_address' => $v['smtp_from_address'] ?: null,
            'smtp_from_name' => $v['smtp_from_name'] ?: null,
        ];

        // Password: perbarui hanya bila diisi; bila host dikosongkan (SMTP dimatikan), bersihkan password.
        if (! $v['smtp_host']) {
            $data['smtp_password'] = null;
        } elseif ($this->smtp_password !== '') {
            $data['smtp_password'] = $this->smtp_password;
        }

        Perusahaan::current()->update($data);

        $this->smtp_password = '';
        $this->dispatch('smtp-tersimpan');
        $this->dispatch('toast', message: 'Konfigurasi SMTP disimpan.');
    }

    /** Kirim email uji memakai nilai SMTP di form (password dari form bila diisi, else tersimpan). */
    public function kirimUji(): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        $this->ujiHasil = null;

        $this->validate([
            'ujiEmail' => ['required', 'email'],
            'smtp_host' => ['required', 'string'],
        ], attributes: ['ujiEmail' => 'email tujuan uji', 'smtp_host' => 'host SMTP']);

        $pw = $this->smtp_password !== '' ? $this->smtp_password : Perusahaan::current()->smtp_password;

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $this->smtp_host,
            'mail.mailers.smtp.port' => (int) ($this->smtp_port ?: 587),
            'mail.mailers.smtp.username' => $this->smtp_username ?: null,
            'mail.mailers.smtp.password' => $pw ?: null,
            'mail.mailers.smtp.encryption' => $this->smtp_encryption ?: null,
        ]);
        if ($this->smtp_from_address) {
            config(['mail.from.address' => $this->smtp_from_address, 'mail.from.name' => $this->smtp_from_name ?: Perusahaan::current()->appName()]);
        }

        try {
            $app = Perusahaan::current()->appName();
            Mail::raw("Email uji dari {$app}. Konfigurasi SMTP Anda berfungsi.", fn ($m) => $m->to($this->ujiEmail)->subject("Uji SMTP — {$app}"));
            $this->ujiHasil = 'ok';
            $this->dispatch('toast', message: "Email uji terkirim ke {$this->ujiEmail}.");
        } catch (Throwable $e) {
            $this->ujiHasil = 'gagal';
            $this->addError('ujiEmail', 'Gagal mengirim: '.$e->getMessage());
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
