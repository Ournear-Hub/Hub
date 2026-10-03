<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_leads')) {
            Schema::create('crm_leads', function (Blueprint $table) {
                $table->id();
                $table->string('company_name');
                $table->string('contact_person')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile')->nullable();
                $table->string('website')->nullable();
                $table->string('source')->default('manual');
                $table->text('notes')->nullable();
                $table->foreignId('employer_enquiry_id')->nullable()->constrained('employer_enquiries')->nullOnDelete();
                $table->foreignId('contact_enquiry_id')->nullable()->constrained('contact_enquiries')->nullOnDelete();
                $table->foreignId('employer_profile_id')->nullable()->constrained('employer_profiles')->nullOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('crm_opportunities')) {
            Schema::create('crm_opportunities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('crm_lead_id')->constrained('crm_leads')->cascadeOnDelete();
                $table->foreignId('employer_profile_id')->nullable()->constrained('employer_profiles')->nullOnDelete();
                $table->string('title');
                $table->string('status')->default('new_lead');
                $table->text('notes')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamp('converted_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('recruitment_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('recruitment_activities', 'crm_lead_id')) {
                $table->foreignId('crm_lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            }
            if (! Schema::hasColumn('recruitment_activities', 'crm_opportunity_id')) {
                $table->foreignId('crm_opportunity_id')->nullable()->constrained('crm_opportunities')->nullOnDelete();
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'employer_profile_id')) {
                $table->foreignId('employer_profile_id')->nullable()->constrained('employer_profiles')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'employer_enquiry_id')) {
                $table->foreignId('employer_enquiry_id')->nullable()->constrained('employer_enquiries')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'crm_lead_id')) {
                $table->foreignId('crm_lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'crm_opportunity_id')) {
                $table->foreignId('crm_opportunity_id')->nullable()->constrained('crm_opportunities')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            foreach (['crm_opportunity_id', 'crm_lead_id', 'employer_enquiry_id', 'employer_profile_id'] as $column) {
                if (Schema::hasColumn('tasks', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });

        Schema::table('recruitment_activities', function (Blueprint $table) {
            foreach (['crm_opportunity_id', 'crm_lead_id'] as $column) {
                if (Schema::hasColumn('recruitment_activities', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });

        Schema::dropIfExists('crm_opportunities');
        Schema::dropIfExists('crm_leads');
    }
};
