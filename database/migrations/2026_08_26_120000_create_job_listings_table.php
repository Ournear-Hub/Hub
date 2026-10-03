<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recruitment listings. Laravel's queue already uses the `jobs` table.
     */
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('project');
            $table->string('industry');
            $table->string('category');
            $table->string('location');
            $table->string('employment_type');
            $table->string('experience');
            $table->string('salary')->nullable();
            $table->unsignedInteger('vacancy')->default(1);
            $table->date('posted_date');
            $table->date('closing_date')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('active')->default(true);
            $table->string('publishing_status')->default('draft');
            $table->text('description');
            $table->text('responsibilities')->nullable();
            $table->text('requirements')->nullable();
            $table->text('what_we_offer')->nullable();
            $table->timestamps();

            $table->index('industry');
            $table->index('category');
            $table->index('location');
            $table->index('employment_type');
            $table->index('experience');
            $table->index('featured');
            $table->index('active');
            $table->index('publishing_status');
            $table->index('posted_date');
            $table->index('closing_date');
            $table->index(['active', 'publishing_status', 'closing_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
