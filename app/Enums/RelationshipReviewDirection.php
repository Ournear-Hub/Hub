<?php

namespace App\Enums;

enum RelationshipReviewDirection: string
{
    case EmployerToWorker = 'employer_to_worker';
    case WorkerToEmployer = 'worker_to_employer';

    public function label(): string
    {
        return match ($this) {
            self::EmployerToWorker => 'Employer → Worker',
            self::WorkerToEmployer => 'Worker → Employer',
        };
    }
}
