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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->enum('type', ['personal', 'company'])->default('personal');
            $table->string('commercial_register')->nullable();
            $table->string('id_card')->nullable();
            $table->enum('status', ['pending', 'active', 'rejected', 'blocked'])->default('pending');
            $table->text('status_message')->nullable();
            $table->string('avatar')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->text('bio')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
