<?php

namespace Tests\Feature;

use App\Models\Otp;
use App\Services\ForJawalyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_resending_for_another_audience_updates_the_same_phone_record(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'client'])->assertOk();
        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'vendor'])->assertOk();

        $this->assertSame(1, Otp::where('phone', '0559999999')->count());
        $this->assertSame('vendor', Otp::where('phone', '0559999999')->value('user_type'));
    }

    public function test_send_otp_rejects_unknown_type(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0559999999', 'type' => 'admin'])
            ->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('otps', 0);
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
}
