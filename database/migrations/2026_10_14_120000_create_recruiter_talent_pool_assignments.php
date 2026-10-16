<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recruiter_talent_pool_assignments')) {
            Schema::create('recruiter_talent_pool_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_user_id');
                $table->foreignId('assigned_recruiter_user_id');
                $table->foreignId('assigned_by_user_id');
                $table->foreignId('application_id')->nullable();
                $table->foreignId('job_id')->nullable();
                $table->foreignId('employer_enquiry_id')->nullable();
                $table->string('status', 32);
                $table->text('admin_notes')->nullable();
                $table->text('recruiter_notes')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();

                $table->foreign('candidate_user_id', 'rtp_assign_candidate_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('assigned_recruiter_user_id', 'rtp_assign_recruiter_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('assigned_by_user_id', 'rtp_assign_assigned_by_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('application_id', 'rtp_assign_application_fk')
                    ->references('id')->on('applications')->nullOnDelete();
                $table->foreign('job_id', 'rtp_assign_job_fk')
                    ->references('id')->on('job_listings')->nullOnDelete();
                $table->foreign('employer_enquiry_id', 'rtp_assign_enquiry_fk')
                    ->references('id')->on('employer_enquiries')->nullOnDelete();

                $table->index(['assigned_recruiter_user_id', 'status'], 'rtp_assign_recruiter_status_idx');
                $table->index(['candidate_user_id', 'status'], 'rtp_assign_candidate_status_idx');
                $table->index('assigned_by_user_id', 'rtp_assign_assigned_by_idx');
            });
        } else {
            $this->reconcileExistingAssignmentsTable();
        }

        if (! Schema::hasTable('recruiter_talent_pool_assignment_status_events')) {
            Schema::create('recruiter_talent_pool_assignment_status_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('recruiter_talent_pool_assignment_id')
                    ->constrained('recruiter_talent_pool_assignments', indexName: 'rtp_assignment_status_events_assignment_fk')
                    ->cascadeOnDelete();
                $table->string('from_status', 32)->nullable();
                $table->string('to_status', 32);
                $table->text('notes')->nullable();
                $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['recruiter_talent_pool_assignment_id', 'created_at'], 'rtp_assignment_status_events_timeline_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recruiter_talent_pool_assignment_status_events');
        Schema::dropIfExists('recruiter_talent_pool_assignments');
    }

    private function reconcileExistingAssignmentsTable(): void
    {
        $this->addForeignKeyIfMissing(
            'recruiter_talent_pool_assignments',
            'assigned_recruiter_user_id',
            'users',
            'CASCADE',
            'rtp_assign_recruiter_user_fk',
        );
        $this->addForeignKeyIfMissing(
            'recruiter_talent_pool_assignments',
            'assigned_by_user_id',
            'users',
            'CASCADE',
            'rtp_assign_assigned_by_user_fk',
        );
        $this->addForeignKeyIfMissing(
            'recruiter_talent_pool_assignments',
            'application_id',
            'applications',
            'SET NULL',
            'rtp_assign_application_fk',
        );
        $this->addForeignKeyIfMissing(
            'recruiter_talent_pool_assignments',
            'job_id',
            'job_listings',
            'SET NULL',
            'rtp_assign_job_fk',
        );
        $this->addForeignKeyIfMissing(
            'recruiter_talent_pool_assignments',
            'employer_enquiry_id',
            'employer_enquiries',
            'SET NULL',
            'rtp_assign_enquiry_fk',
        );

        $this->addIndexIfMissing(
            'recruiter_talent_pool_assignments',
            ['assigned_recruiter_user_id', 'status'],
            'rtp_assign_recruiter_status_idx',
        );
        $this->addIndexIfMissing(
            'recruiter_talent_pool_assignments',
            ['candidate_user_id', 'status'],
            'rtp_assign_candidate_status_idx',
        );
        $this->addIndexIfMissing(
            'recruiter_talent_pool_assignments',
            ['assigned_by_user_id'],
            'rtp_assign_assigned_by_idx',
        );
    }

    private function addForeignKeyIfMissing(
        string $table,
        string $column,
        string $referencedTable,
        string $onDelete,
        string $constraintName,
    ): void {
        if ($this->columnHasForeignKey($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $onDelete, $constraintName) {
            $foreign = $blueprint->foreign($column, $constraintName)->references('id')->on($referencedTable);
            if ($onDelete === 'SET NULL') {
                $foreign->nullOnDelete();
            } else {
                $foreign->cascadeOnDelete();
            }
        });
    }

    private function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function columnHasForeignKey(string $table, string $column): bool
    {
        $database = Schema::getConnection()->getDatabaseName();

        $row = DB::selectOne(
            'SELECT 1 AS present FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
             AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1',
            [$database, $table, $column],
        );

        return $row !== null;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = Schema::getConnection()->getDatabaseName();

        $row = DB::selectOne(
            'SELECT 1 AS present FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$database, $table, $indexName],
        );

        return $row !== null;
    }
};
