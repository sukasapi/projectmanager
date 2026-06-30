<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Form atur ulang kata sandi dari tautan email (token + email).
 */
#[Layout('components.layouts.guest')]
class ResetPassword extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    /** Catatan: jangan beri nama `reset()` (bentrok dengan Livewire\Component::reset). */
    public function aturUlang(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ], attributes: ['password' => 'kata sandi']);

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordResetEvent($user));
            }
        );

        if ($status === Password::PasswordReset) {
            session()->flash('status', 'Kata sandi berhasil diperbarui. Silakan masuk dengan kata sandi baru.');
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $this->addError('email', __($status));
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
