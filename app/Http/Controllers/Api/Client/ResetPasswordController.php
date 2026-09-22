<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\Concerns\ResetsPasswordViaOtp;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Otp;
use Illuminate\Database\Eloquent\Model;

class ResetPasswordController extends Controller
{
    use ResetsPasswordViaOtp;

    protected function otpUserType(): string
    {
        return Otp::TYPE_CLIENT;
    }

    protected function findAccount(string $normalizedPhone): ?Model
    {
        return Client::where('phone', $normalizedPhone)->first();
    }

    protected function accountMissingMessage(): string
    {
        return 'Client with this phone number was not found';
    }
}
