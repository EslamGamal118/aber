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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('image')->nullable();
            $table->string('code')->unique()->nullable(); // e.g. SAVE20, AB2025
            $table->enum('discount_type', ['fixed', 'percentage']);
            $table->decimal('discount_value', 8, 2); // fixed amount or percentage
            $table->unsignedBigInteger('provider_id')->nullable(); // null = global coupon
            $table->unsignedBigInteger('created_by_id');
            $table->enum('created_by_type', ['admin', 'provider']);
            $table->integer('max_uses')->nullable(); // total allowed uses
            $table->integer('uses')->default(0); // how many times it’s been used
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            
            $table->foreign('provider_id')->references('id')->on('providers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
