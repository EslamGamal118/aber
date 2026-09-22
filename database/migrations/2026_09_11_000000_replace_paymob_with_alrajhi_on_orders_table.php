<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Al Rajhi Bank becomes the single payment gateway:
     *  - drop the Paymob specific column
     *  - store the Al Rajhi PaymentID / paid timestamp
     *  - default every order to the "alrajhi" payment method
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'paymob_order_id')) {
                $table->dropColumn('paymob_order_id');
            }

            if (!Schema::hasColumn('orders', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('transaction_id');
            }

            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_link');
            }

            $table->string('payment_method')->default('alrajhi')->change();
            $table->string('payment_status')->default('pending')->change();
        });

        DB::table('orders')->whereNull('payment_method')->orWhere('payment_method', '')->update(['payment_method' => 'alrajhi']);
        DB::table('orders')->whereNull('payment_status')->update(['payment_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'paymob_order_id')) {
                $table->unsignedBigInteger('paymob_order_id')->nullable();
            }

            if (Schema::hasColumn('orders', 'payment_reference')) {
                $table->dropColumn('payment_reference');
            }

            if (Schema::hasColumn('orders', 'paid_at')) {
                $table->dropColumn('paid_at');
            }

            $table->string('payment_method')->nullable()->default(null)->change();
            $table->string('payment_status')->nullable()->default(null)->change();
        });
    }
};
