<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payment-first order workflow.
     *
     *  - Adds the checkout columns the application relies on but that no
     *    migration ever created (total, provider_id, reference,
     *    promoCodeDiscount). Every step is guarded so it is a no-op on
     *    databases where the columns were added by hand.
     *  - Relaxes legacy single-product NOT NULL columns that the multi-item
     *    checkout never fills, and drops the wrong userId -> users FK
     *    (userId holds clients.id).
     *  - New orders default to "pending_payment" (hidden from providers until
     *    Al Rajhi confirms the capture).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'total')) {
                $table->decimal('total', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'provider_id')) {
                $table->unsignedBigInteger('provider_id')->nullable()->index();
            }
            if (!Schema::hasColumn('orders', 'reference')) {
                $table->string('reference', 32)->nullable()->index();
            }
            if (!Schema::hasColumn('orders', 'promoCodeDiscount')) {
                $table->decimal('promoCodeDiscount', 10, 2)->default(0);
            }
        });

        // orders.userId stores clients.id (Order::client()), but the original
        // migration constrained it to the unrelated "users" table.
        $userFk = collect(Schema::getForeignKeys('orders'))
            ->first(fn ($fk) => $fk['columns'] === ['userId'] && $fk['foreign_table'] === 'users');

        if ($userFk) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['userId']); // by column: works on MySQL and SQLite
                $table->index('userId');
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            foreach (['type', 'elementName', 'buyerName'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->string($column)->nullable()->change();
                }
            }
            if (Schema::hasColumn('orders', 'quantity')) {
                $table->integer('quantity')->nullable()->default(1)->change();
            }
            if (Schema::hasColumn('orders', 'carId')) {
                $table->unsignedBigInteger('carId')->nullable()->change();
            }

            $table->string('status')->default('pending_payment')->change();
        });

        // Existing unpaid orders were created before the payment-first workflow;
        // keep them out of the provider feed.
        DB::table('orders')
            ->where('is_paid', false)
            ->where('status', 'pending')
            ->update(['status' => 'pending_payment']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default(null)->change();
        });

        DB::table('orders')->where('status', 'pending_payment')->update(['status' => 'pending']);
    }
};
