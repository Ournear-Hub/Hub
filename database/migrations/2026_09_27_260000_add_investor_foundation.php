<?php

use App\Services\AuthorizationBootstrapper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('investors')) {
            Schema::create('investors', function (Blueprint $table) {
                $table->id();
                $table->string('business_id')->unique();
                $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
                $table->string('status')->default('pending_verification');
                $table->string('display_name');
                $table->string('contact_person')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('suspended_at')->nullable();
                $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('investment_opportunities')) {
            Schema::create('investment_opportunities', function (Blueprint $table) {
                $table->id();
                $table->string('business_id')->unique();
                $table->string('status')->default('draft');
                $table->string('title');
                $table->text('summary')->nullable();
                $table->string('sector')->nullable();
                $table->string('location')->nullable();
                $table->decimal('indicated_size', 14, 2)->nullable();
                $table->string('currency', 3)->default('INR');
                $table->date('opens_at')->nullable();
                $table->date('closes_at')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('investor_interests')) {
            Schema::create('investor_interests', function (Blueprint $table) {
                $table->id();
                $table->string('business_id')->unique();
                $table->foreignId('investor_id')->constrained('investors')->restrictOnDelete();
                $table->foreignId('investment_opportunity_id')->constrained('investment_opportunities')->restrictOnDelete();
                $table->string('status')->default('interested');
                $table->decimal('indicated_amount', 14, 2)->nullable();
                $table->string('currency', 3)->default('INR');
                $table->text('notes')->nullable();
                $table->timestamp('committed_at')->nullable();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->unique(['investor_id', 'investment_opportunity_id']);
            });
        }

        Schema::table('recruitment_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('recruitment_activities', 'investor_id')) {
                $table->foreignId('investor_id')->nullable()->constrained('investors')->nullOnDelete();
            }
            if (! Schema::hasColumn('recruitment_activities', 'investment_opportunity_id')) {
                $table->foreignId('investment_opportunity_id')->nullable()->constrained('investment_opportunities')->nullOnDelete();
            }
            if (! Schema::hasColumn('recruitment_activities', 'investor_interest_id')) {
                $table->foreignId('investor_interest_id')->nullable()->constrained('investor_interests')->nullOnDelete();
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'investor_id')) {
                $table->foreignId('investor_id')->nullable()->constrained('investors')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'investment_opportunity_id')) {
                $table->foreignId('investment_opportunity_id')->nullable()->constrained('investment_opportunities')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'investor_interest_id')) {
                $table->foreignId('investor_interest_id')->nullable()->constrained('investor_interests')->nullOnDelete();
            }
        });

        app(AuthorizationBootstrapper::class)->run();
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'investor_interest_id')) {
                $table->dropConstrainedForeignId('investor_interest_id');
            }
            if (Schema::hasColumn('tasks', 'investment_opportunity_id')) {
                $table->dropConstrainedForeignId('investment_opportunity_id');
            }
            if (Schema::hasColumn('tasks', 'investor_id')) {
                $table->dropConstrainedForeignId('investor_id');
            }
        });

        Schema::table('recruitment_activities', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_activities', 'investor_interest_id')) {
                $table->dropConstrainedForeignId('investor_interest_id');
            }
            if (Schema::hasColumn('recruitment_activities', 'investment_opportunity_id')) {
                $table->dropConstrainedForeignId('investment_opportunity_id');
            }
            if (Schema::hasColumn('recruitment_activities', 'investor_id')) {
                $table->dropConstrainedForeignId('investor_id');
            }
        });

        Schema::dropIfExists('investor_interests');
        Schema::dropIfExists('investment_opportunities');
        Schema::dropIfExists('investors');
    }
};
