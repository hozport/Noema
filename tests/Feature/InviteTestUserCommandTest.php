<?php

namespace Tests\Feature;

use App\Mail\InvitedTestUserMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InviteTestUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_user_and_sends_mail(): void
    {
        Mail::fake();

        $this->artisan('noema:invite-test-user', [
            'email' => 'invited@example.com',
            '--no-interaction' => true,
        ])->assertSuccessful();

        Mail::assertSent(InvitedTestUserMail::class, function (InvitedTestUserMail $mail): bool {
            return $mail->hasTo('invited@example.com')
                && $mail->user->email === 'invited@example.com'
                && strlen($mail->plainPassword) >= 12;
        });

        $user = User::query()->where('email', 'invited@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotEmpty($user->folder_token);
    }

    public function test_command_rejects_duplicate_email(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'exists@example.com']);

        $this->artisan('noema:invite-test-user', [
            'email' => 'exists@example.com',
            '--no-interaction' => true,
        ])->assertFailed();

        Mail::assertNothingSent();
    }
}
