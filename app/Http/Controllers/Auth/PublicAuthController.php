<?php

namespace App\Http\Controllers\Auth;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class PublicAuthController extends Controller
{
    public function showVerificationNotice(Request $request)
    {
        $email = $request->query('email');

        abort_if(blank($email), 404);

        return view('auth.verify-email', [
            'email' => $email,
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email:rfc,dns', 'regex:/^[^@\s]+@[^@\s]+\.[^@\s]+$/'],
            'password' => ['required'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email harus valid, contoh: nama@email.com.',
            'email.regex'       => 'Format email harus valid, contoh: nama@email.com.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = $request->user();

            if (! $user->hasVerifiedEmail()) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Akun belum terverifikasi. Silakan cek email Anda.',
                ])->with([
                    'open_auth_modal' => 'login',
                    'unverified_email' => $user->email,
                ])->withInput($request->only('email'));
            }

            $request->session()->regenerate();

            return redirect('/admin');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
            'password' => 'Email atau password salah.',
        ])->with('open_auth_modal', 'login');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email:rfc,dns', 'regex:/^[^@\s]+@[^@\s]+\.[^@\s]+$/', 'unique:users,email'],
            'password'              => ['required', 'min:8', 'confirmed'],
            'cf-turnstile-response' => [
                app()->environment('local') ? 'nullable' : 'required'
            ],
        ], [
            'name.required'                  => 'Nama lengkap wajib diisi.',
            'name.string'                    => 'Nama lengkap harus berupa teks.',
            'name.max'                       => 'Nama lengkap tidak boleh lebih dari 255 karakter.',
            'email.required'                 => 'Email wajib diisi.',
            'email.email'                    => 'Format email harus valid, contoh: nama@email.com.',
            'email.regex'                    => 'Format email harus valid, contoh: nama@email.com.',
            'email.unique'                   => 'Email sudah terdaftar.',
            'password.required'              => 'Password wajib diisi.',
            'password.min'                   => 'Password minimal 8 karakter.',
            'password.confirmed'             => 'Konfirmasi password tidak sesuai.',
            'cf-turnstile-response.required' => 'Captcha wajib diisi.',
        ]);

        if (! app()->environment('local')) {
            // 🔐 Verifikasi Turnstile ke Cloudflare
            $verify = Http::asForm()->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                [
                    'secret'   => config('services.turnstile.secret_key'),
                    'response' => $data['cf-turnstile-response'],
                    'remoteip' => $request->ip(),
                ]
            );

            if (! $verify->json('success')) {
                return back()
                    ->withErrors(['cf-turnstile-response' => 'Verifikasi captcha gagal.'])
                    ->withInput();
            }
        }

        // ✅ Lanjut jika captcha valid
        $initialPushTokens = Setting::getIntValue('initial_push_tokens', 10);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'push_tokens' => $initialPushTokens,
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        return redirect('/')
            ->with('success', 'Pendaftaran berhasil! Silakan cek email Anda untuk verifikasi akun sebelum login.');
    }

    public function resendVerificationEmail(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc,dns', 'regex:/^[^@\s]+@[^@\s]+\.[^@\s]+$/'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email harus valid, contoh: nama@email.com.',
            'email.regex' => 'Format email harus valid, contoh: nama@email.com.',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'Akun dengan email tersebut tidak ditemukan.',
            ])->withInput();
        }

        if ($user->hasVerifiedEmail()) {
            return redirect('/')->with('success', 'Email Anda sudah terverifikasi. Silakan login.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('success', 'Email verifikasi berhasil dikirim ulang. Silakan cek inbox atau folder spam Anda.');
    }

    public function verifyEmail(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect('/')->with('success', 'Email Anda sudah terverifikasi. Silakan login.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect('/')->with('success', 'Verifikasi email berhasil. Silakan login untuk melanjutkan.');
    }
}
