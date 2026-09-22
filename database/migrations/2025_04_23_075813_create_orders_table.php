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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->foreignId('elementId')->nullable();
            $table->string('elementName');
            $table->text('elementDescription')->nullable();
            $table->string('priceSizes')->nullable();
            $table->integer('quantity');
            $table->foreignId('paymentId')->nullable();
            $table->string('imageUrl')->nullable();
            $table->string('buyerName');
            $table->string('status');
            $table->foreignId('userId')->constrained('users')->onDelete('cascade');
            $table->foreignId('promoCodeId')->nullable();
            $table->string('size')->nullable();
            $table->string('salad')->nullable();
            $table->text('additions')->nullable();
            $table->decimal('additionPrice', 10, 2)->nullable();
            $table->foreignId('carId')->constrained('cars')->onDelete('cascade');
            $table->foreignId('customerId')->nullable();
            $table->string('pushToken')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
