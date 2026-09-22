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
        Schema::create('images', function (Blueprint $table) {
            $table->id();  // Primary key
            $table->unsignedBigInteger('user_id');  // Foreign key reference to users table
            $table->string('image');  // Store the filename of the image
            $table->string('type');  // Image type (client or vendor)
            $table->timestamps();  // Created at & Updated at
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
