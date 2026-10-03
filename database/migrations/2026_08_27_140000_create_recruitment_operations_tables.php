<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employer_enquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->text('body')->nullable();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('type');
            $table->index('created_at');
        });

        Schema::create('application_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('mode');
            $table->string('location_or_link')->nullable();
            $table->string('interviewer_name');
            $table->string('outcome')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_interviews');
        Schema::dropIfExists('recruitment_activities');
    }
};
