<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('partition_id')->nullable()->constrained('menu_partitions')->onDelete('set null')->after('id');
            $table->foreignId('menu_id')->nullable()->constrained('menus')->onDelete('set null')->after('partition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

        });
    }
};
