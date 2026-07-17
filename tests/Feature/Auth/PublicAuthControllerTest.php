<?php

use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

uses(RefreshDatabase::class);

describe('showVerificationNotice', function () {
    it('returns verify-email view with email query param', function () {
        $response = $this->get(route('verification.notice', [
            'email' => 'test@gmail.com',
        ]));

        $response
            ->assertOk()
            ->assertViewIs('auth.verify-email')
            ->assertViewHas('email', 'test@gmail.com');
    });

    it('returns 404 when email query param is missing', function () {
        $this->get(route('verification.notice'))
            ->assertNotFound();
    });
});

describe('login', function () {
    it('logs in verified user successfully', function () {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('login.public'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    });

    it('rejects login for unverified user', function () {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $response = $this->from('/')
            ->post(route('login.public'), [
                'email' => $user->email,
                'password' => 'password123',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('email')
            ->assertSessionHas('open_auth_modal', 'login')
            ->assertSessionHas('unverified_email', $user->email);

        $this->assertGuest();
    });

    it('rejects invalid credentials', function () {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->from('/')
            ->post(route('login.public'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email',
                'password',
            ]);

        $this->assertGuest();
    });

    it('validates required fields', function () {
        $response = $this->from('/')
            ->post(route('login.public'), []);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email',
                'password',
            ]);
    });
});

describe('logout', function () {
    it('logs out authenticated user', function () {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('logout.public'));

        $response->assertRedirect('/');

        $this->assertGuest();
    });
});

describe('register', function () {
    beforeEach(function () {
        Setting::factory()->create([
            'key' => 'initial_push_tokens',
            'value' => 10,
        ]);
    });

    it('registers user successfully in local environment', function () {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        Event::fake();

        Http::fake([
            'https://challenges.cloudflare.com/*' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $response = $this->post(route('register.public'), [
            'name' => 'John Doe',
            'email' => 'john@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response
            ->assertRedirect(route('verification.notice', ['email' => 'john@gmail.com']));

        expect(User::where('email', 'john@gmail.com')->exists())
            ->toBeTrue();

        $user = User::where('email', 'john@gmail.com')->first();

        expect($user->push_tokens)->toBe(10);

        Event::assertDispatched(Registered::class);
    });

    it('fails when captcha verification fails in non local environment', function () {
        config()->set('app.env', 'production');
        config()->set('services.turnstile', [
            'site_key' => '1x00000000000000000000AA',
            'secret_key' => '1x0000000000000000000000000000000AA',
        ]);

        Http::fake([
            'https://challenges.cloudflare.com/*' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]),
        ]);

        Log::spy();

        $response = $this->from('/')
            ->post(route('register.public'), [
                'name' => 'John Doe',
                'email' => 'john@gmail.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'cf-turnstile-response' => 'invalid-token',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('cf-turnstile-response');

        expect(User::count())->toBe(0);

        Log::shouldHaveReceived('warning');
    });

    it('registers successfully when captcha passes in non local environment', function () {
        Event::fake();

        config()->set('app.env', 'production');

        Http::fake([
            'https://challenges.cloudflare.com/*' => Http::response([
                'success' => true,
            ]),
        ]);

        $response = $this->post(route('register.public'), [
            'name' => 'John Doe',
            'email' => 'john@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cf-turnstile-response' => 'valid-token',
        ]);

        $response->assertRedirect(route('verification.notice', ['email' => 'john@gmail.com']));

        expect(User::where('email', 'john@gmail.com')->exists())
            ->toBeTrue();

        Event::assertDispatched(Registered::class);
    });

    it('validates email uniqueness', function () {
        User::factory()->create([
            'email' => 'john@gmail.com',
        ]);

        $response = $this->from('/')
            ->post(route('register.public'), [
                'name' => 'John Doe',
                'email' => 'john@gmail.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('email');
    });
});

describe('logTurnstileClientError', function () {
    it('logs turnstile client error and returns json response', function () {
        Log::spy();

        $response = $this->post(route('turnstile.client-error'), [
            'code' => 'network-error',
            'origin' => 'frontend',
            'hostname' => 'gmail.com',
            'href' => 'https://gmail.com/register',
            'sitekey_preview' => 'abc123',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        Log::shouldHaveReceived('warning')
            ->once();
    });

    it('validates payload fields', function () {
        $response = $this->post(route('turnstile.client-error'), [
            'code' => str_repeat('a', 101),
        ]);

        $response->assertSessionHasErrors('code');
    });
});

describe('resendVerificationEmail', function () {
    it('resends verification email for unverified user', function () {
        $user = User::factory()->unverified()->create();

        $user->notify = Mockery::mock();

        $response = $this->from('/')
            ->post(route('verification.send'), [
                'email' => $user->email,
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHas(
                'success',
                'Email verifikasi berhasil dikirim ulang. Silakan cek inbox atau folder spam Anda.'
            );
    });

    it('returns error when user is not found', function () {
        $response = $this->from('/')
            ->post(route('verification.send'), [
                'email' => 'notfound@gmail.com',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('email');
    });

    it('redirects when email already verified', function () {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('verification.send'), [
            'email' => $user->email,
        ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHas(
                'success',
                'Email Anda sudah terverifikasi. Silakan login.'
            );
    });
});

describe('verifyEmail', function () {
    it('verifies user email successfully', function () {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->get($url);

        $response
            ->assertRedirect('/')
            ->assertSessionHas(
                'success',
                'Verifikasi email berhasil. Silakan login untuk melanjutkan.'
            );

        expect($user->fresh()->hasVerifiedEmail())
            ->toBeTrue();

        Event::assertDispatched(Verified::class);
    });

    it('redirects when email already verified', function () {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->get($url);

        $response
            ->assertRedirect('/')
            ->assertSessionHas(
                'success',
                'Email Anda sudah terverifikasi. Silakan login.'
            );
    });

    it('returns 403 for invalid hash', function () {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => 'invalid-hash',
            ]
        );

        $this->get($url)
            ->assertForbidden();
    });

    it('returns 403 for invalid signature', function () {
        $user = User::factory()->unverified()->create();

        $url = route('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($url)
            ->assertForbidden();
    });
});
