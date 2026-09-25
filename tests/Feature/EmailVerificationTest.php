<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_sends_code_and_does_not_return_a_token(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Okafor',
            'email' => 'ada.okafor@example.com',
            'password' => 'password1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.requires_verification', true)
            ->assertJsonPath('data.email', 'ada.okafor@example.com')
            ->assertJsonMissingPath('data.token');

        $this->assertDatabaseHas('users', [
            'email' => 'ada.okafor@example.com',
            'verified' => 0,
        ]);

        Mail::assertSent(EmailVerificationOtpMail::class, function (EmailVerificationOtpMail $mail) {
            return $mail->hasTo('ada.okafor@example.com') && strlen($mail->code) === 6;
        });
    }

    public function test_verify_email_marks_user_verified_and_returns_token(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Okafor',
            'email' => 'ada.okafor@example.com',
            'password' => 'password1',
        ])->assertCreated();

        $code = null;
        Mail::assertSent(EmailVerificationOtpMail::class, function (EmailVerificationOtpMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $this->assertNotNull($code);

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'email' => 'ada.okafor@example.com',
            'code' => $code,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'ada.okafor@example.com')
            ->assertJsonPath('data.user.verified', true)
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertTrue(User::query()->where('email', 'ada.okafor@example.com')->first()?->isEmailVerified());
    }

    public function test_wrong_code_is_rejected(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Okafor',
            'email' => 'ada.okafor@example.com',
            'password' => 'password1',
        ])->assertCreated();

        $this->postJson('/api/v1/auth/verify-email', [
            'email' => 'ada.okafor@example.com',
            'code' => '000000',
        ])->assertStatus(422);
    }

    public function test_unverified_login_is_blocked_and_resends_code(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Okafor',
            'email' => 'ada.okafor@example.com',
            'password' => 'password1',
        ])->assertCreated();

        Mail::fake();

        $this->postJson('/api/v1/auth/login', [
            'login' => 'ada.okafor@example.com',
            'password' => 'password1',
        ])->assertStatus(403)
            ->assertJsonPath('errors.code.0', 'email_unverified');

        Mail::assertSent(EmailVerificationOtpMail::class);
    }
}
