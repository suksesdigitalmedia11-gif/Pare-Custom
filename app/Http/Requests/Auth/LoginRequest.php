<?php

namespace App\Http\Requests\Auth;

use App\Models\ShiftAutoClose;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Authenticate dulu tanpa cek blocking
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }
        
        $user = Auth::user();

        // Cek status aktif akun
        if (! $user->is_active) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Akun Anda telah dinonaktifkan oleh Owner. Silakan hubungi administrator.',
            ]);
        }

        // Setelah authenticate berhasil, cek blocking HANYA untuk admin dan kepala_toko
        if (in_array($user->usertype, ['admin', 'kepala_toko'])) {
            $this->checkShiftBlocking();
        }

        RateLimiter::clear($this->throttleKey());
    }
    
    /**
     * Check if admin/kepala_toko login is blocked due to auto-closed shift
     * Hanya dipanggil setelah authenticate berhasil
     */
    private function checkShiftBlocking(): void
    {
        // Cek apakah ada shift yang di-auto-close dan masih blocked
        $hasBlockedShift = ShiftAutoClose::where('is_blocked', true)
            ->whereDate('auto_closed_date', '>=', now()->subDays(7)) // Cek 7 hari terakhir
            ->exists();
        
        if ($hasBlockedShift) {
            // Logout user yang baru login
            Auth::logout();
            $this->session()->invalidate();
            $this->session()->regenerateToken();
            
            throw ValidationException::withMessages([
                'email' => 'Login diblokir: Shift tidak ditutup pada hari sebelumnya. Silakan minta approval ke akun Finance untuk mengaktifkan kembali akses login.',
            ]);
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
