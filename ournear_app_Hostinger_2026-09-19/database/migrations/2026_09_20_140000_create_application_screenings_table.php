<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')
                ->unique()
                ->constrained('applications')
                ->restrictOnDelete();
            $table->string('decision');
            $table->text('notes')->nullable();
            $table->foreignId('rejection_reason_id')
                ->nullable()
                ->constrained('rejection_reasons')
                ->nullOnDelete();
            $table->foreignId('screened_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('screened_at')->nullable();
            $table->foreignId('shortlisted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('shortlisted_at')->nullable();
            $table->timestamps();

            $table->index('decision');
            $table->index('shortlisted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_screenings');
    }
};
