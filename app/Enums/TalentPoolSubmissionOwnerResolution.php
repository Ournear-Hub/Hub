<?php

namespace App\Enums;

enum TalentPoolSubmissionOwnerResolution: string
{
    case CandidateAssignment = 'candidate_assignment';
    case CompanyAssignment = 'company_assignment';
    case CandidateOverCompanyConflict = 'candidate_over_company_conflict';
    case ExplicitAdmin = 'explicit_admin';
    case Unassigned = 'unassigned';

    public function label(): string
    {
        return match ($this) {
            self::CandidateAssignment => 'Candidate assignment owner',
            self::CompanyAssignment => 'Company relationship owner',
            self::CandidateOverCompanyConflict => 'Candidate owner (company owner also recorded)',
            self::ExplicitAdmin => 'Explicitly selected by admin',
            self::Unassigned => 'No OurNear owner assigned',
        };
    }
}
