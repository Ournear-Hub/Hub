<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key', 160)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('channel', 32);
            $table->string('audience', 32);
            $table->string('subject', 500)->nullable();
            $table->text('body');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_required')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['channel', 'is_active']);
            $table->index(['audience', 'is_active']);
        });

        Schema::create('message_template_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_template_id')->constrained('message_templates')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        app(\App\Services\MessageTemplateService::class)->syncSystemTemplates();
    }

    public function down(): void
    {
        Schema::dropIfExists('message_template_audits');
        Schema::dropIfExists('message_templates');
    }
};
