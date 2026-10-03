<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employer_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('mobile_number');
            $table->string('email');
            $table->string('industry');
            $table->string('project_location');
            $table->string('workforce_type');
            $table->unsignedInteger('number_of_people');
            $table->text('required_skills');
            $table->date('expected_start_date')->nullable();
            $table->boolean('training_required')->default(false);
            $table->text('additional_requirements')->nullable();
            $table->string('status')->default('New');
            $table->json('internal_notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('industry');
            $table->index('project_location');
            $table->index('workforce_type');
            $table->index('email');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_enquiries');
    }
};
