<?php

namespace Tests\Feature;

use App\Enums\CompanyRelationshipRecruiterAssignmentStatus;
use App\Enums\StaffRoleSlug;
use App\Models\CompanyRelationshipRecruiterAssignment;
use App\Models\EmployerProfile;
use App\Models\User;
use Tests\TestCase;

class CompanyRelationshipRecruiterAssignmentTest extends TestCase
{
    public function test_admin_can_assign_internal_recruiter_to_company_and_reassign_preserves_history(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $employer = User::factory()->employer()->create();
        $employerProfile = $employer->employerProfile;
        $this->assertInstanceOf(EmployerProfile::class, $employerProfile);

        $recruiterA = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'crr-a@example.test']);
        $recruiterB = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'crr-b@example.test']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.employers.relationship-recruiter.store', $employerProfile), [
                'assigned_recruiter_user_id' => $recruiterA->id,
                'notes' => 'Primary relationship owner',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $active = CompanyRelationshipRecruiterAssignment::query()
            ->where('employer_profile_id', $employerProfile->id)
            ->where('status', CompanyRelationshipRecruiterAssignmentStatus::Active->value)
            ->first();

        $this->assertNotNull($active);
        $this->assertSame($recruiterA->id, $active->assigned_recruiter_user_id);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.employers.relationship-recruiter.store', $employerProfile), [
                'assigned_recruiter_user_id' => $recruiterB->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(2, CompanyRelationshipRecruiterAssignment::query()->where('employer_profile_id', $employerProfile->id)->count());
        $this->assertDatabaseHas('company_relationship_recruiter_assignments', [
            'employer_profile_id' => $employerProfile->id,
            'assigned_recruiter_user_id' => $recruiterB->id,
            'status' => CompanyRelationshipRecruiterAssignmentStatus::Active->value,
        ]);
        $this->assertDatabaseHas('company_relationship_recruiter_assignments', [
            'employer_profile_id' => $employerProfile->id,
            'assigned_recruiter_user_id' => $recruiterA->id,
            'status' => CompanyRelationshipRecruiterAssignmentStatus::Ended->value,
        ]);
    }

    public function test_employer_account_cannot_be_assigned_as_company_relationship_recruiter(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $employer = User::factory()->employer()->create();
        $employerProfile = $employer->employerProfile;
        $this->assertInstanceOf(EmployerProfile::class, $employerProfile);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.employers.relationship-recruiter.store', $employerProfile), [
                'assigned_recruiter_user_id' => $employer->id,
            ])
            ->assertSessionHasErrors('assigned_recruiter_user_id');
    }
}
