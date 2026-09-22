<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * OTPs are scoped per audience: AuthController@sendOtp stores the requested
     * type and ClientsController@register / VendorAuthController@register only
     * accept an OTP verified for their own type ("client" / "vendor").
     * The column was used by the code but never created by a migration.
     */
    public function up(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            if (!Schema::hasColumn('otps', 'user_type')) {
                $table->string('user_type', 20)->nullable()->after('verified')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            if (Schema::hasColumn('otps', 'user_type')) {
                $table->dropColumn('user_type');
            }
        });
    }
};
