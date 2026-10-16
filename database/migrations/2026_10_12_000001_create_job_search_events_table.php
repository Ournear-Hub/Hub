<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_search_events', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 120)->default('');
            $table->string('location', 120)->nullable();
            $table->unsignedSmallInteger('weight')->default(1);
            $table->timestamp('searched_at');
            $table->index(['searched_at']);
            $table->index(['keyword', 'location']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_search_events');
    }
};
