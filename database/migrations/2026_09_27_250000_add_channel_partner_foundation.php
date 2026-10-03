<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('channel_partners')) {
            Schema::create('channel_partners', function (Blueprint $table) {
                $table->id();
                $table->string('business_id')->unique();
                $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
                $table->string('status')->default('pending');
                $table->string('display_name');
                $table->string('contact_person')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('suspended_at')->nullable();
                $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('channel_partner_agreements')) {
            Schema::create('channel_partner_agreements', function (Blueprint $table) {
                $table->id();
                $table->string('business_id')->unique();
                $table->foreignId('channel_partner_id')->constrained('channel_partners')->cascadeOnDelete();
                $table->string('status')->default('draft');
                $table->decimal('commission_percent', 5, 2)->default(30);
                $table->unsignedTinyInteger('duration_months')->default(3);
                $table->string('eligible_basis')->default('received_payments');
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->text('terms')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employer_attributions')) {
            Schema::create('employer_attributions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('channel_partner_id')->constrained('channel_partners')->restrictOnDelete();
                $table->foreignId('employer_profile_id')->constrained('employer_profiles')->restrictOnDelete();
                $table->foreignId('crm_lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
                $table->foreignId('crm_opportunity_id')->nullable()->constrained('crm_opportunities')->nullOnDelete();
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->unique(['channel_partner_id', 'employer_profile_id'], 'employer_attributions_partner_company_unique');
                $table->index(['employer_profile_id', 'status']);
            });
        }

        if (! Schema::hasTable('commission_payments')) {
            Schema::create('commission_payments', function (Blueprint $table) {
                $table->id();
                $table->string('business_id')->unique();
                $table->foreignId('channel_partner_id')->constrained('channel_partners')->restrictOnDelete();
                $table->string('currency', 3)->default('INR');
                $table->decimal('amount', 12, 2);
                $table->date('paid_at');
                $table->string('method')->default('Bank transfer');
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commission_entries')) {
            Schema::create('commission_entries', function (Blueprint $table) {
                $table->id();
                $table->string('business_id')->unique();
                $table->foreignId('channel_partner_id')->constrained('channel_partners')->restrictOnDelete();
                $table->foreignId('channel_partner_agreement_id')->constrained('channel_partner_agreements')->restrictOnDelete();
                $table->foreignId('employer_attribution_id')->constrained('employer_attributions')->restrictOnDelete();
                $table->foreignId('payment_id')->unique()->constrained('payments')->restrictOnDelete();
                $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
                $table->foreignId('employer_profile_id')->constrained('employer_profiles')->restrictOnDelete();
                $table->foreignId('commission_payment_id')->nullable()->constrained('commission_payments')->nullOnDelete();
                $table->string('currency', 3)->default('INR');
                $table->decimal('basis_amount', 12, 2);
                $table->decimal('rate', 5, 2);
                $table->decimal('commission_amount', 12, 2);
                $table->string('status')->default('pending');
                $table->date('window_starts_at')->nullable();
                $table->date('window_ends_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('voided_at')->nullable();
                $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        Schema::table('crm_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('crm_leads', 'channel_partner_id')) {
                $table->foreignId('channel_partner_id')->nullable()->constrained('channel_partners')->nullOnDelete();
            }
        });

        Schema::table('recruitment_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('recruitment_activities', 'channel_partner_id')) {
                $table->foreignId('channel_partner_id')->nullable()->constrained('channel_partners')->nullOnDelete();
            }
            if (! Schema::hasColumn('recruitment_activities', 'partner_agreement_id')) {
                $table->foreignId('partner_agreement_id')->nullable()->constrained('channel_partner_agreements')->nullOnDelete();
            }
            if (! Schema::hasColumn('recruitment_activities', 'commission_entry_id')) {
                $table->foreignId('commission_entry_id')->nullable()->constrained('commission_entries')->nullOnDelete();
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'channel_partner_id')) {
                $table->foreignId('channel_partner_id')->nullable()->constrained('channel_partners')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'commission_entry_id')) {
                $table->foreignId('commission_entry_id')->nullable()->constrained('commission_entries')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'commission_entry_id')) {
                $table->dropConstrainedForeignId('commission_entry_id');
            }
            if (Schema::hasColumn('tasks', 'channel_partner_id')) {
                $table->dropConstrainedForeignId('channel_partner_id');
            }
        });

        Schema::table('recruitment_activities', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_activities', 'commission_entry_id')) {
                $table->dropConstrainedForeignId('commission_entry_id');
            }
            if (Schema::hasColumn('recruitment_activities', 'partner_agreement_id')) {
                $table->dropConstrainedForeignId('partner_agreement_id');
            }
            if (Schema::hasColumn('recruitment_activities', 'channel_partner_id')) {
                $table->dropConstrainedForeignId('channel_partner_id');
            }
        });

        Schema::table('crm_leads', function (Blueprint $table) {
            if (Schema::hasColumn('crm_leads', 'channel_partner_id')) {
                $table->dropConstrainedForeignId('channel_partner_id');
            }
        });

        Schema::dropIfExists('commission_entries');
        Schema::dropIfExists('commission_payments');
        Schema::dropIfExists('employer_attributions');
        Schema::dropIfExists('channel_partner_agreements');
        Schema::dropIfExists('channel_partners');
    }
};
