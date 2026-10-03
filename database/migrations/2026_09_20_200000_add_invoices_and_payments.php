<?php

use App\Enums\BusinessIdPrefix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('business_id', 32)->unique();
            $table->foreignId('employer_profile_id')->constrained('employer_profiles')->restrictOnDelete();
            $table->foreignId('job_id')->constrained('job_listings')->restrictOnDelete();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->foreignId('employer_submission_id')->constrained('employer_submissions')->restrictOnDelete();
            $table->foreignId('placement_id')->constrained('placements')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('replacement_id')->nullable()->constrained('replacements')->nullOnDelete();
            $table->string('status');
            $table->char('currency', 3);
            $table->decimal('amount', 12, 2);
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->date('billed_at');
            $table->date('due_at');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('billed_at');
            $table->index('due_at');
            $table->index('employer_profile_id');
            $table->index('employee_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('business_id', 32)->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->foreignId('employer_profile_id')->constrained('employer_profiles')->restrictOnDelete();
            $table->char('currency', 3);
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            $table->string('method');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('paid_at');
            $table->index('invoice_id');
            $table->index('employer_profile_id');
        });

        Schema::table('recruitment_activities', function (Blueprint $table) {
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('invoices')
                ->nullOnDelete();
            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('payments')
                ->nullOnDelete();
        });

        $now = now();
        foreach ([BusinessIdPrefix::Invoice, BusinessIdPrefix::Payment] as $prefix) {
            DB::table('business_id_sequences')->insertOrIgnore([
                'prefix' => $prefix->value,
                'next_number' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('recruitment_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
            $table->dropConstrainedForeignId('payment_id');
        });

        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
    }
};
