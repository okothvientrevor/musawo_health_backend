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
        Schema::create('lab_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('provider_id')->nullable()->constrained('healthcare_providers')->nullOnDelete();
            $table->foreignId('laboratory_id')->constrained()->onDelete('cascade');
            $table->json('tests_requested');
            $table->enum('urgency_level', ['routine', 'urgent', 'emergency'])->default('routine');
            $table->text('notes')->nullable();
            $table->enum('status', ['requested', 'processing', 'completed', 'cancelled'])->default('requested');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_requests');
    }
};