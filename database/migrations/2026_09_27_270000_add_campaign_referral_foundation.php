<?php

use App\Services\AuthorizationBootstrapper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campaigns')) {
            Schema::create('campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('business_id')->unique();
                $table->string('status')->default('draft');
                $table->string('name');
                $table->text('summary')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('referral_links')) {
            Schema::create('referral_links', function (Blueprint $table) {
                $table->id();
                $table->string('business_id')->unique();
                $table->string('code', 16)->unique();
                $table->string('status')->default('active');
                $table->string('path');
                $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
                $table->foreignId('job_id')->nullable()->constrained('job_listings')->nullOnDelete();
                $table->foreignId('referrer_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('share_events')) {
            Schema::create('share_events', function (Blueprint $table) {
                $table->id();
                $table->string('business_id')->unique();
                $table->string('channel');
                $table->string('path');
                $table->foreignId('job_id')->nullable()->constrained('job_listings')->nullOnDelete();
                $table->foreignId('referral_link_id')->nullable()->constrained('referral_links')->nullOnDelete();
                $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('referrals')) {
            Schema::create('referrals', function (Blueprint $table) {
                $table->id();
                $table->string('business_id')->unique();
                $table->string('conversion_type');
                $table->boolean('is_primary')->default(true);
                $table->foreignId('referral_link_id')->constrained('referral_links')->restrictOnDelete();
                $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
                $table->foreignId('referrer_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('referred_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
                $table->foreignId('job_id')->nullable()->constrained('job_listings')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique('application_id');
                $table->index(['referred_user_id', 'conversion_type']);
            });
        }

        Schema::table('recruitment_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('recruitment_activities', 'campaign_id')) {
                $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            }
            if (! Schema::hasColumn('recruitment_activities', 'referral_link_id')) {
                $table->foreignId('referral_link_id')->nullable()->constrained('referral_links')->nullOnDelete();
            }
            if (! Schema::hasColumn('recruitment_activities', 'share_event_id')) {
                $table->foreignId('share_event_id')->nullable()->constrained('share_events')->nullOnDelete();
            }
            if (! Schema::hasColumn('recruitment_activities', 'referral_id')) {
                $table->foreignId('referral_id')->nullable()->constrained('referrals')->nullOnDelete();
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'campaign_id')) {
                $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'referral_link_id')) {
                $table->foreignId('referral_link_id')->nullable()->constrained('referral_links')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'referral_id')) {
                $table->foreignId('referral_id')->nullable()->constrained('referrals')->nullOnDelete();
            }
        });

        app(AuthorizationBootstrapper::class)->run();
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'referral_id')) {
                $table->dropConstrainedForeignId('referral_id');
            }
            if (Schema::hasColumn('tasks', 'referral_link_id')) {
                $table->dropConstrainedForeignId('referral_link_id');
            }
            if (Schema::hasColumn('tasks', 'campaign_id')) {
                $table->dropConstrainedForeignId('campaign_id');
            }
        });

        Schema::table('recruitment_activities', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_activities', 'referral_id')) {
                $table->dropConstrainedForeignId('referral_id');
            }
            if (Schema::hasColumn('recruitment_activities', 'share_event_id')) {
                $table->dropConstrainedForeignId('share_event_id');
            }
            if (Schema::hasColumn('recruitment_activities', 'referral_link_id')) {
                $table->dropConstrainedForeignId('referral_link_id');
            }
            if (Schema::hasColumn('recruitment_activities', 'campaign_id')) {
                $table->dropConstrainedForeignId('campaign_id');
            }
        });

        Schema::dropIfExists('referrals');
        Schema::dropIfExists('share_events');
        Schema::dropIfExists('referral_links');
        Schema::dropIfExists('campaigns');
    }
};
