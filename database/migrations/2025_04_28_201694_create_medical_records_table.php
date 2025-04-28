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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->enum('record_type', ['medical_report', 'lab_result', 'vaccination', 'prescription']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_url')->nullable();
            $table->foreignId('provider_id')->nullable()->constrained('healthcare_providers')->nullOnDelete();
            $table->foreignId('hospital_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->enum('status', ['normal', 'abnormal', 'critical', 'pending'])->default('normal');
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};