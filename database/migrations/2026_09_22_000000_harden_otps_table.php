<?php

use App\Models\Otp;
use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the `otps` table match how the OTP pipeline actually uses it:
 *
 *  - phone is stored NORMALISED, so send / verify / register all hit the same
 *    row no matter which format the client app posted.
 *  - the unique key moves from (phone) to (phone, user_type): a client OTP and
 *    a vendor OTP for the same number are independent, instead of the second
 *    send silently overwriting the first.
 *  - attempts        caps brute force against a 4-digit code.
 *  - verified_at     lets register/reset require a *recent* verification
 *                    instead of a `verified` flag that stays true forever.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Normalise every stored phone, and give rows without an audience a
        //    concrete one so the composite unique key can be enforced (MySQL
        //    treats NULLs as distinct, which would let duplicates back in).
        foreach (DB::table('otps')->get() as $row) {
            DB::table('otps')->where('id', $row->id)->update([
                'phone' => PhoneNumber::normalize($row->phone),
                'user_type' => in_array($row->user_type ?? '', Otp::TYPES, true)
                    ? $row->user_type
                    : Otp::TYPE_CLIENT,
            ]);
        }

        // 2. Normalising can collapse two rows onto the same key. Keep the
        //    newest row per (phone, user_type) and drop the stale duplicates.
        $keep = DB::table('otps')
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('phone', 'user_type')
            ->pluck('id');

        DB::table('otps')->whereNotIn('id', $keep)->delete();

        // 3. Schema.
        Schema::table('otps', function (Blueprint $table) {
            if (! Schema::hasColumn('otps', 'attempts')) {
                $table->unsignedTinyInteger('attempts')->default(0)->after('verified');
            }
            if (! Schema::hasColumn('otps', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('attempts');
            }
        });

        Schema::table('otps', function (Blueprint $table) {
            $table->string('user_type', 20)->default(Otp::TYPE_CLIENT)->change();
        });

        Schema::table('otps', function (Blueprint $table) {
            if ($this->hasIndex('otps_phone_unique')) {
                $table->dropUnique('otps_phone_unique');
            }
        });

        Schema::table('otps', function (Blueprint $table) {
            if (! $this->hasIndex('otps_phone_user_type_unique')) {
                $table->unique(['phone', 'user_type'], 'otps_phone_user_type_unique');
            }
        });

        // 4. Rows that were already verified predate `verified_at`; backdate
        //    them so they are treated as stale rather than instantly redeemable.
        DB::table('otps')
            ->where('verified', true)
            ->whereNull('verified_at')
            ->update(['verified_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            if ($this->hasIndex('otps_phone_user_type_unique')) {
                $table->dropUnique('otps_phone_user_type_unique');
            }
        });

        // Restoring the single-column unique key requires one row per phone.
        $keep = DB::table('otps')
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('phone')
            ->pluck('id');

        DB::table('otps')->whereNotIn('id', $keep)->delete();

        Schema::table('otps', function (Blueprint $table) {
            $table->unique('phone', 'otps_phone_unique');
            $table->string('user_type', 20)->nullable()->default(null)->change();
        });

        Schema::table('otps', function (Blueprint $table) {
            foreach (['attempts', 'verified_at'] as $column) {
                if (Schema::hasColumn('otps', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function hasIndex(string $name): bool
    {
        return collect(Schema::getIndexes('otps'))
            ->contains(fn ($index) => ($index['name'] ?? null) === $name);
    }
};
