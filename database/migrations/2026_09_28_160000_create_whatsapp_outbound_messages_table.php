<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_outbound_messages')) {
            return;
        }

        Schema::create('whatsapp_outbound_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('provider_message_id', 128)->nullable()->unique();
            $table->string('purpose', 32);
            $table->string('identity', 16);
            $table->string('recipient', 32);
            $table->string('template_name', 128);
            $table->string('language', 16);
            $table->string('status', 16);
            $table->string('company_sender', 32)->nullable();
            $table->unsignedInteger('provider_error_code')->nullable();
            $table->timestamp('last_status_at')->nullable();
            $table->timestamps();

            $table->index(['recipient', 'status']);
            $table->index('purpose');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_outbound_messages');
    }
};
