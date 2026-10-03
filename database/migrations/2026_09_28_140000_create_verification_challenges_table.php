<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('verification_challenges')) {
            return;
        }

        Schema::create('verification_challenges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 16);
            $table->string('destination', 32);
            $table->string('code_hash', 64);
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->timestamp('used_at')->nullable();
            $table->string('requested_ip', 45)->nullable();
            $table->string('verified_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'channel', 'destination'], 'verification_challenges_user_channel_dest');
            $table->index(['channel', 'destination', 'expires_at'], 'verification_challenges_lookup');
            $table->index('used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_challenges');
    }
};
