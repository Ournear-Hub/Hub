<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_interviews', function (Blueprint $table) {
            $table->string('candidate_response', 32)->nullable()->after('confirmed_at');
            $table->timestamp('candidate_responded_at')->nullable()->after('candidate_response');
            $table->text('candidate_response_note')->nullable()->after('candidate_responded_at');
        });

        Schema::create('application_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('candidate_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->string('status', 32)->default('pending');
            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('response_note')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_correction_requests');

        Schema::table('application_interviews', function (Blueprint $table) {
            $table->dropColumn([
                'candidate_response',
                'candidate_responded_at',
                'candidate_response_note',
            ]);
        });
    }
};
