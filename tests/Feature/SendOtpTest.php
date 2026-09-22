<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Otp;
use App\Services\ForJawalyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class SendOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $sms = Mockery::mock(ForJawalyService::class);
        $sms->shouldReceive('sendSMS')->andReturn(['code' => 200]);
        $this->app->instance(ForJawalyService::class, $sms);

        config([
            'otp.bypassed_numbers' => ['0500000001', '0500000002'],
            'otp.bypass_code' => '1234',
            'otp.resend_cooldown_seconds' => 0,
        ]);
    }

    public function test_send_otp_stores_user_type(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'client'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('otps', [
            'phone' => '0559999999',
            'user_type' => 'client',
            'verified' => false,
        ]);
    }

    public function test_send_otp_normalises_every_accepted_phone_format(): void
    {
        foreach (['+966559999999', '966559999999', '00966559999999', '559999999', '+966 55 999 9999'] as $format) {
            Otp::query()->delete();

            $this->postJson('/api/auth/send-otp', ['phone' => $format, 'type' => 'client'])
                ->assertOk()
                ->assertJsonPath('phone', '0559999999');

            $this->assertSame(1, Otp::where('phone', '0559999999')->count(), "format: {$format}");
        }
    }

    public function test_resending_for_another_audience_keeps_both_records(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'client'])->assertOk();
        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'vendor'])->assertOk();

        // A vendor resend must no longer overwrite the pending client OTP.
        $this->assertSame(2, Otp::where('phone', '0559999999')->count());
        $this->assertSame(1, Otp::where('phone', '0559999999')->where('user_type', 'client')->count());
        $this->assertSame(1, Otp::where('phone', '0559999999')->where('user_type', 'vendor')->count());
    }

    public function test_send_otp_rejects_unknown_type(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'admin'])
            ->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('otps', 0);
    }

    public function test_send_otp_honours_the_resend_cooldown(): void
    {
        config(['otp.resend_cooldown_seconds' => 60]);

        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'client'])->assertOk();

        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'client'])
            ->assertStatus(429)
            ->assertJsonPath('success', false);
    }

    public function test_verify_otp_marks_record_verified(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'client'])->assertOk();
        $code = Otp::where('phone', '0559999999')->value('code');

        $this->postJson('/api/auth/verify-otp', ['phone' => '0559999999', 'otp' => $code])
            ->assertOk()
            ->assertJsonPath('isVerified', true);

        $this->assertDatabaseHas('otps', ['phone' => '0559999999', 'verified' => true, 'user_type' => 'client']);
    }

    public function test_verify_otp_matches_a_differently_formatted_phone(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'client'])->assertOk();
        $code = Otp::where('phone', '0559999999')->value('code');

        $this->postJson('/api/auth/verify-otp', ['phone' => '+966559999999', 'otp' => $code])
            ->assertOk()
            ->assertJsonPath('isVerified', true);

        $this->assertDatabaseHas('otps', ['phone' => '0559999999', 'verified' => true]);
    }

    public function test_verify_otp_rejects_a_wrong_code_and_burns_an_attempt(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'client'])->assertOk();
        $code = Otp::where('phone', '0559999999')->value('code');
        $wrong = str_pad((string) ((((int) $code) + 1) % 10000), 4, '0', STR_PAD_LEFT);

        $this->postJson('/api/auth/verify-otp', ['phone' => '0559999999', 'otp' => $wrong])
            ->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('otps', ['phone' => '0559999999', 'verified' => false, 'attempts' => 1]);
    }

    public function test_verify_otp_locks_out_after_max_attempts(): void
    {
        config(['otp.max_attempts' => 3]);

        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'client'])->assertOk();
        $code = Otp::where('phone', '0559999999')->value('code');
        $wrong = str_pad((string) ((((int) $code) + 1) % 10000), 4, '0', STR_PAD_LEFT);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/verify-otp', ['phone' => '0559999999', 'otp' => $wrong]);
        }

        // Even the correct code is refused once the budget is spent.
        $this->postJson('/api/auth/verify-otp', ['phone' => '0559999999', 'otp' => $code])
            ->assertStatus(429);

        $this->assertDatabaseHas('otps', ['phone' => '0559999999', 'verified' => false]);
    }

    public function test_verify_otp_rejects_an_expired_code(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'client'])->assertOk();
        $code = Otp::where('phone', '0559999999')->value('code');

        $this->travel(11)->minutes();

        $this->postJson('/api/auth/verify-otp', ['phone' => '0559999999', 'otp' => $code])
            ->assertStatus(400)
            ->assertJsonPath('message', 'OTP has expired. Please request a new one.');
    }

    /* ----------------------------------------------------------------------
     | Bypassed (test) numbers — the regression that broke registration.
     | -------------------------------------------------------------------- */

    public function test_bypassed_number_gets_the_fixed_code_without_hitting_the_gateway(): void
    {
        $sms = Mockery::mock(ForJawalyService::class);
        $sms->shouldNotReceive('sendSMS');
        $this->app->instance(ForJawalyService::class, $sms);

        $this->postJson('/api/auth/send-otp', ['phone' => '0500000001', 'type' => 'client'])
            ->assertOk()
            ->assertJsonPath('code', '1234');

        $this->assertDatabaseHas('otps', ['phone' => '0500000001', 'code' => '1234', 'user_type' => 'client']);
    }

    public function test_verifying_a_bypassed_number_persists_verified_and_user_type(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0500000001', 'type' => 'client'])->assertOk();

        $this->postJson('/api/auth/verify-otp', ['phone' => '0500000001', 'otp' => '1234'])
            ->assertOk()
            ->assertJsonPath('isVerified', true);

        // This write is what used to be skipped, leaving register() to fail.
        $this->assertDatabaseHas('otps', [
            'phone' => '0500000001',
            'verified' => true,
            'user_type' => 'client',
        ]);
    }

    public function test_verifying_a_bypassed_number_works_without_a_prior_send(): void
    {
        $this->postJson('/api/auth/verify-otp', ['phone' => '+966500000002', 'otp' => '1234'])
            ->assertOk()
            ->assertJsonPath('isVerified', true);

        $this->assertDatabaseHas('otps', ['phone' => '0500000002', 'verified' => true, 'user_type' => 'client']);
        $this->assertDatabaseHas('otps', ['phone' => '0500000002', 'verified' => true, 'user_type' => 'vendor']);
    }

    public function test_bypassed_number_rejects_the_wrong_code(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0500000001', 'type' => 'client'])->assertOk();

        $this->postJson('/api/auth/verify-otp', ['phone' => '0500000001', 'otp' => '9999'])
            ->assertStatus(400);

        $this->assertDatabaseHas('otps', ['phone' => '0500000001', 'verified' => false]);
    }

    /* ----------------------------------------------------------------------
     | Registration
     | -------------------------------------------------------------------- */

    public function test_client_can_register_after_verifying_a_bypassed_number(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0500000001', 'type' => 'client'])->assertOk();
        $this->postJson('/api/auth/verify-otp', ['phone' => '0500000001', 'otp' => '1234'])->assertOk();

        $this->postJson('/api/client/register', $this->registrationPayload('0500000001'))
            ->assertCreated()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('clients', ['phone' => '0500000001']);
    }

    public function test_client_can_register_when_each_step_uses_a_different_phone_format(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '559999999', 'type' => 'client'])->assertOk();
        $code = Otp::where('phone', '0559999999')->value('code');
        $this->postJson('/api/auth/verify-otp', ['phone' => '+966559999999', 'otp' => $code])->assertOk();

        $this->postJson('/api/client/register', $this->registrationPayload('00966559999999'))
            ->assertCreated();

        $this->assertDatabaseHas('clients', ['phone' => '0559999999']);
    }

    public function test_registration_is_refused_without_a_verified_otp(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'client'])->assertOk();

        $this->postJson('/api/client/register', $this->registrationPayload('0559999999'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'يجب التحقق من رقم الهاتف أولاً');
    }

    public function test_a_vendor_otp_cannot_authorise_a_client_registration(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'vendor'])->assertOk();
        $code = Otp::where('phone', '0559999999')->where('user_type', 'vendor')->value('code');
        $this->postJson('/api/auth/verify-otp', ['phone' => '0559999999', 'otp' => $code, 'type' => 'vendor'])->assertOk();

        $this->postJson('/api/client/register', $this->registrationPayload('0559999999'))
            ->assertStatus(422);
    }

    public function test_a_verified_otp_is_single_use(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0500000001', 'type' => 'client'])->assertOk();
        $this->postJson('/api/auth/verify-otp', ['phone' => '0500000001', 'otp' => '1234'])->assertOk();

        $this->postJson('/api/client/register', $this->registrationPayload('0500000001'))->assertCreated();
        Client::where('phone', '0500000001')->delete();

        // The OTP was burned by the first registration.
        $this->postJson('/api/client/register', $this->registrationPayload('0500000001', 'second@example.com'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'يجب التحقق من رقم الهاتف أولاً');
    }

    public function test_a_stale_verification_can_no_longer_be_redeemed(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0500000001', 'type' => 'client'])->assertOk();
        $this->postJson('/api/auth/verify-otp', ['phone' => '0500000001', 'otp' => '1234'])->assertOk();

        $this->travelTo(Carbon::now()->addMinutes(31));

        $this->postJson('/api/client/register', $this->registrationPayload('0500000001'))
            ->assertStatus(422);
    }

    /* ----------------------------------------------------------------------
     | Password reset
     | -------------------------------------------------------------------- */

    public function test_password_reset_requires_a_verified_otp(): void
    {
        Client::create([
            'name' => 'Existing',
            'email' => 'existing@example.com',
            'password' => bcrypt('old-password'),
            'phone' => '0551000001',
        ]);

        // No OTP verified: the endpoint used to reset the password anyway.
        $this->putJson('/api/client/reset-password', [
            'phone' => '0551000001',
            'new_password' => 'attacker-password',
        ])->assertStatus(422);

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('old-password', Client::where('phone', '0551000001')->value('password'))
        );
    }

    public function test_password_reset_succeeds_after_verification(): void
    {
        Client::create([
            'name' => 'Existing',
            'email' => 'existing@example.com',
            'password' => bcrypt('old-password'),
            'phone' => '0551000001',
        ]);

        $this->postJson('/api/client/reset-password/otp', ['phone' => '+966551000001'])->assertOk();
        $code = Otp::where('phone', '0551000001')->value('code');
        $this->postJson('/api/client/reset-password/verify-otp', ['phone' => '0551000001', 'otp' => $code])->assertOk();

        $this->putJson('/api/client/reset-password', [
            'phone' => '0551000001',
            'new_password' => 'new-password',
        ])->assertOk();

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('new-password', Client::where('phone', '0551000001')->value('password'))
        );
    }

    private function registrationPayload(string $phone, string $email = 'new@example.com'): array
    {
        return [
            'name' => 'New Client',
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone' => $phone,
        ];
    }
}
