<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')
                ->constrained('job_listings')
                ->restrictOnDelete();
            $table->string('full_name');
            $table->string('mobile');
            $table->string('email');
            $table->string('current_location');
            $table->string('experience');
            $table->string('qualification');
            $table->string('resume_path');
            $table->text('additional_information')->nullable();
            $table->boolean('consent');
            $table->string('status')->default('New');
            $table->timestamps();

            $table->index('status');
            $table->index('email');
            $table->index('current_location');
            $table->index('experience');
            $table->index('created_at');
            $table->unique(['job_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
