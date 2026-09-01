<?php

namespace App\Http\Requests\Auth;

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
    public function rules()
    {
        return [
            'fsysuserid' => ['required', 'string'],
            'password' => ['required', 'string'],
            'captcha' => ['required', 'string', 'size:4'],
        ];
    }

    public function messages()
    {
        return [
            'captcha.required' => 'Kode captcha wajib diisi.',
            'captcha.size' => 'Kode captcha harus 4 karakter.',
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

        $sessionCaptcha = (string) session('captcha_code', '');
        $inputCaptcha = strtolower(trim((string) $this->input('captcha', '')));

        // Invalidate session captcha immediately after attempt
        session()->forget('captcha_code');

        if ($sessionCaptcha === '' || $inputCaptcha !== $sessionCaptcha) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'captcha' => 'Kode captcha salah.',
            ]);
        }

        if (! Auth::attempt($this->only('fsysuserid', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'password' => 'Username atau password salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
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
            'fsysuserid' => trans('auth.throttle', [
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
        return Str::transliterate(Str::lower($this->string('fsysuserid')).'|'.$this->ip());
    }
}
