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
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->foreignId('district_id')->constrained()->onDelete('cascade');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->json('services')->nullable();
            $table->boolean('has_emergency')->default(false);
            $table->json('coordinates')->nullable(); // MySQL doesn't support point directly, so using JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospitals');
    }
};