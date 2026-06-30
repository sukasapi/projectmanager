<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Form "lupa kata sandi" — kirim tautan reset ke email.
 * Pesan generik (anti-enumerasi email). Mail sinkron (cPanel-safe, tanpa daemon).
 */
#[Layout('components.layouts.guest')]
class LupaPassword extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    public string $status = '';

    public function kirim(): void
    {
        $this->validate();

        // Broker mengirim notifikasi ResetPassword bila email terdaftar.
        Password::sendResetLink(['email' => $this->email]);

        // Selalu pesan generik agar tidak membocorkan email yang terdaftar.
        $this->status = 'Jika email terdaftar, tautan reset kata sandi telah dikirim. Silakan cek kotak masuk Anda.';
        $this->reset('email');
    }

    public function render()
    {
        return view('livewire.auth.lupa-password');
    }
}
