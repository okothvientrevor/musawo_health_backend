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
        Schema::create('healthcare_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('specialty');
            $table->string('license_number')->unique();
            $table->integer('years_experience');
            $table->text('bio')->nullable();
            $table->text('education')->nullable();
            $table->foreignId('hospital_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('consultation_fee', 10, 2);
            $table->decimal('rating', 3, 1)->default(0);
            $table->boolean('supports_video')->default(true);
            $table->foreignId('district_id')->constrained()->onDelete('cascade');
            $table->json('location_coordinates')->nullable(); // MySQL doesn't support point directly, so using JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('healthcare_providers');
    }
};