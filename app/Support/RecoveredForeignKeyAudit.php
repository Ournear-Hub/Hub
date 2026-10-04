<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class RecoveredForeignKeyAudit
{
    /**
     * @return list<array{
     *     table: string,
     *     column: string,
     *     referenced_table: string,
     *     referenced_column: string,
     *     deterministic_mapping: string,
     *     nullable: bool
     * }>
     */
    public static function checks(): array
    {
        $checks = [
            [
                'table' => 'applications',
                'column' => 'user_id',
                'referenced_table' => 'users',
                'referenced_column' => 'id',
                'deterministic_mapping' => 'Exact users.email match on applications.email, or storage/app/recovery/user_id_map.json',
                'nullable' => false,
            ],
            [
                'table' => 'applications',
                'column' => 'job_id',
                'referenced_table' => 'job_listings',
                'referenced_column' => 'id',
                'deterministic_mapping' => 'storage/app/recovery/job_id_map.json (legacy job_listings id → current id)',
                'nullable' => false,
            ],
            [
                'table' => 'candidate_profiles',
                'column' => 'user_id',
                'referenced_table' => 'users',
                'referenced_column' => 'id',
                'deterministic_mapping' => 'Exact users.email on linked user row, or user_id_map.json',
                'nullable' => false,
            ],
            [
                'table' => 'employer_profiles',
                'column' => 'user_id',
                'referenced_table' => 'users',
                'referenced_column' => 'id',
                'deterministic_mapping' => 'Exact users.email on linked user row, or user_id_map.json',
                'nullable' => false,
            ],
            [
                'table' => 'page_section_items',
                'column' => 'section_id',
                'referenced_table' => 'website_sections',
                'referenced_column' => 'id',
                'deterministic_mapping' => 'MANUAL REVIEW — restore missing website_sections or re-link CMS rows',
                'nullable' => false,
            ],
            [
                'table' => 'page_section_items',
                'column' => 'content_item_id',
                'referenced_table' => 'content_items',
                'referenced_column' => 'id',
                'deterministic_mapping' => 'MANUAL REVIEW — restore missing content_items or re-link CMS rows',
                'nullable' => false,
            ],
        ];

        if (Schema::hasTable('recruitment_activities')) {
            $recruitmentFk = [
                ['column' => 'application_id', 'referenced_table' => 'applications'],
                ['column' => 'employer_enquiry_id', 'referenced_table' => 'employer_enquiries'],
                ['column' => 'actor_id', 'referenced_table' => 'users'],
                ['column' => 'employer_submission_id', 'referenced_table' => 'employer_submissions'],
                ['column' => 'employer_profile_id', 'referenced_table' => 'employer_profiles'],
                ['column' => 'placement_id', 'referenced_table' => 'placements'],
                ['column' => 'employee_id', 'referenced_table' => 'employees'],
                ['column' => 'replacement_id', 'referenced_table' => 'replacements'],
                ['column' => 'invoice_id', 'referenced_table' => 'invoices'],
                ['column' => 'payment_id', 'referenced_table' => 'payments'],
                ['column' => 'interview_id', 'referenced_table' => 'application_interviews'],
                ['column' => 'task_id', 'referenced_table' => 'tasks'],
                ['column' => 'crm_lead_id', 'referenced_table' => 'crm_leads'],
                ['column' => 'crm_opportunity_id', 'referenced_table' => 'crm_opportunities'],
                ['column' => 'channel_partner_id', 'referenced_table' => 'channel_partners'],
                ['column' => 'partner_agreement_id', 'referenced_table' => 'channel_partner_agreements'],
                ['column' => 'commission_entry_id', 'referenced_table' => 'commission_entries'],
                ['column' => 'investor_id', 'referenced_table' => 'investors'],
                ['column' => 'investment_opportunity_id', 'referenced_table' => 'investment_opportunities'],
                ['column' => 'investor_interest_id', 'referenced_table' => 'investor_interests'],
                ['column' => 'campaign_id', 'referenced_table' => 'campaigns'],
                ['column' => 'referral_link_id', 'referenced_table' => 'referral_links'],
                ['column' => 'share_event_id', 'referenced_table' => 'share_events'],
                ['column' => 'referral_id', 'referenced_table' => 'referrals'],
            ];

            foreach ($recruitmentFk as $fk) {
                if (! Schema::hasColumn('recruitment_activities', $fk['column'])) {
                    continue;
                }

                $checks[] = [
                    'table' => 'recruitment_activities',
                    'column' => $fk['column'],
                    'referenced_table' => $fk['referenced_table'],
                    'referenced_column' => 'id',
                    'deterministic_mapping' => 'Resolve parent row recovery first; FK follows parent entity map',
                    'nullable' => true,
                ];
            }
        }

        return $checks;
    }

    /**
     * @return array{
     *     table: string,
     *     column: string,
     *     referenced_table: string,
     *     orphan_count: int,
     *     orphan_ids: list<int|string>,
     *     deterministic_mapping: string,
     *     nullable: bool
     * }
     */
    public static function auditCheck(array $check, int $sampleLimit = 50): array
    {
        $table = $check['table'];
        $column = $check['column'];
        $referencedTable = $check['referenced_table'];
        $referencedColumn = $check['referenced_column'];

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return [
                'table' => $table,
                'column' => $column,
                'referenced_table' => $referencedTable,
                'orphan_count' => 0,
                'orphan_ids' => [],
                'deterministic_mapping' => $check['deterministic_mapping'],
                'nullable' => $check['nullable'],
                'skipped' => true,
            ];
        }

        if (! Schema::hasTable($referencedTable)) {
            return [
                'table' => $table,
                'column' => $column,
                'referenced_table' => $referencedTable,
                'orphan_count' => 0,
                'orphan_ids' => [],
                'deterministic_mapping' => $check['deterministic_mapping'],
                'nullable' => $check['nullable'],
                'skipped' => true,
            ];
        }

        $query = DB::table($table.' as child')
            ->leftJoin($referencedTable.' as parent', 'child.'.$column, '=', 'parent.'.$referencedColumn)
            ->whereNull('parent.'.$referencedColumn)
            ->whereNotNull('child.'.$column);

        if ($check['nullable']) {
            $query->where('child.'.$column, '!=', 0);
        }

        $orphanCount = (clone $query)->count('child.id');

        /** @var Collection<int, int|string> $ids */
        $ids = (clone $query)
            ->orderBy('child.id')
            ->limit($sampleLimit)
            ->pluck('child.id');

        return [
            'table' => $table,
            'column' => $column,
            'referenced_table' => $referencedTable,
            'orphan_count' => (int) $orphanCount,
            'orphan_ids' => $ids->values()->all(),
            'deterministic_mapping' => $check['deterministic_mapping'],
            'nullable' => (bool) $check['nullable'],
            'skipped' => false,
        ];
    }
}
