<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Api\Concerns\ResetsPasswordViaOtp;
use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Model;

class ForgetPasswordController extends Controller
{
    use ResetsPasswordViaOtp;

    protected function otpUserType(): string
    {
        return Otp::TYPE_VENDOR;
    }

    protected function findAccount(string $normalizedPhone): ?Model
    {
        return Provider::where('phone', $normalizedPhone)->first();
    }

    protected function accountMissingMessage(): string
    {
        return 'Vendor with this phone number was not found';
    }
}
