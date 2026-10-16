/** Public jobs workspace with one job selected in the split-view detail panel. */
export function publicJobsWorkspaceWithJob(slug: string): string {
    const params = new URLSearchParams({ job: slug });

    return `/jobs?${params.toString()}`;
}

/** Public jobs board filtered to one employer profile (canonical server param). */
export function publicJobsForEmployer(employerProfileId: number): string {
    const params = new URLSearchParams({ employer: String(employerProfileId) });

    return `/jobs?${params.toString()}`;
}

/** Filter by public company label shown on job cards. */
export function publicJobsForCompanyName(companyName: string): string {
    const params = new URLSearchParams({ company: companyName });

    return `/jobs?${params.toString()}`;
}
