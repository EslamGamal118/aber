<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The models / controllers use columns that no migration ever created
     * (menus.provider_id, menu_partitions.menu_id, favorites.client_id /
     * provider_id, providers.latitude / longitude). Every step is guarded so
     * this is a no-op on databases where the columns were added by hand.
     */
    public function up(): void
    {
        // Menus belong to a provider (VendorMenuController, ProvidersController)
        Schema::table('menus', function (Blueprint $table) {
            if (!Schema::hasColumn('menus', 'provider_id')) {
                $table->unsignedBigInteger('provider_id')->nullable()->index()->after('id');
            }
        });

        // Partitions are queried through menu_id (legacy column is menuId)
        Schema::table('menu_partitions', function (Blueprint $table) {
            if (!Schema::hasColumn('menu_partitions', 'menu_id')) {
                $table->unsignedBigInteger('menu_id')->nullable()->index()->after('id');
            }
            if (Schema::hasColumn('menu_partitions', 'menuId')) {
                $table->unsignedBigInteger('menuId')->nullable()->change();
            }
        });

        // Favorites are client -> provider (FavoriteController); legacy columns are car based
        $favoritesUserFk = collect(Schema::getForeignKeys('favorites'))
            ->first(fn ($fk) => $fk['columns'] === ['userId']);

        if ($favoritesUserFk) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->dropForeign(['userId']);
            });
        }

        Schema::table('favorites', function (Blueprint $table) {
            if (!Schema::hasColumn('favorites', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('favorites', 'provider_id')) {
                $table->unsignedBigInteger('provider_id')->nullable()->index()->after('client_id');
            }
            if (Schema::hasColumn('favorites', 'userId')) {
                $table->unsignedBigInteger('userId')->nullable()->change();
            }
        });

        // Provider geolocation (nearby search)
        Schema::table('providers', function (Blueprint $table) {
            if (!Schema::hasColumn('providers', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('address');
            }
            if (!Schema::hasColumn('providers', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropColumn(['client_id', 'provider_id']);
        });
        Schema::table('menu_partitions', function (Blueprint $table) {
            $table->dropColumn('menu_id');
        });
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('provider_id');
        });
    }
};
