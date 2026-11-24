<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Halaman masuk (desain welcome prototipe, email + password).
     */
    public function showLoginForm(): RedirectResponse|View
    {
        return view('auth.login');
    }

    /**
     * Autentikasi dengan rate limiting sederhana (5x/menit per email+IP).
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $key = mb_strtolower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        if ($user->using_default_password) {
            return redirect()->route('password.edit')
                ->with('toast', 'Anda memakai password bawaan — silakan ganti password.');
        }

        return redirect()->intended(route($user->homeRoute()));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Halaman ganti password (paritas akunView() prototipe).
     */
    public function editPassword(): View
    {
        return view('auth.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'old' => ['required'],
            'new' => ['required', 'min:6'],
            'confirm' => ['required'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! Auth::validate(['email' => $user->email, 'password' => $data['old']])) {
            throw ValidationException::withMessages(['old' => 'Password lama salah.']);
        }
        if ($data['new'] !== $data['confirm']) {
            throw ValidationException::withMessages(['confirm' => 'Ulangi password baru dengan sama persis.']);
        }
        if ($data['new'] === $data['old']) {
            throw ValidationException::withMessages(['new' => 'Password baru harus berbeda.']);
        }

        $user->update(['password' => $data['new'], 'using_default_password' => false]);

        return redirect()->route($user->homeRoute())->with('toast', 'Password berhasil diganti');
    }
}
