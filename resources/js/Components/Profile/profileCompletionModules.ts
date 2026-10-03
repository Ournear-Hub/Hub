import type { AccountCompletionItem, AccountProfile } from '@/types/account';

export type ProfileModuleId =
    | 'basic'
    | 'about'
    | 'professional'
    | 'experience'
    | 'education'
    | 'skills'
    | 'projects'
    | 'certifications'
    | 'languages'
    | 'preferences'
    | 'resume';

export type ProfileModuleStatus = 'complete' | 'in_progress' | 'not_started';

export type ProfileModuleCard = {
    id: ProfileModuleId;
    title: string;
    description: string;
    summary: string;
    status: ProfileModuleStatus;
    completionKeys: string[];
};

function itemMap(items: AccountCompletionItem[]): Record<string, boolean> {
    return Object.fromEntries(items.map((item) => [item.key, item.complete]));
}

function keysComplete(map: Record<string, boolean>, keys: string[]): boolean {
    if (keys.length === 0) {
        return true;
    }
    return keys.every((key) => map[key] === true);
}

function keysAnyComplete(map: Record<string, boolean>, keys: string[]): boolean {
    return keys.some((key) => map[key] === true);
}

function statusFromKeys(map: Record<string, boolean>, keys: string[], hasContent: boolean): ProfileModuleStatus {
    if (keys.length > 0 && keysComplete(map, keys)) {
        return 'complete';
    }
    if (hasContent || (keys.length > 0 && keysAnyComplete(map, keys))) {
        return 'in_progress';
    }
    return 'not_started';
}

export function buildProfileModules(profile: AccountProfile): ProfileModuleCard[] {
    const map = itemMap(profile.completion_items ?? []);
    const pro = profile.professional;

    const experienceCount = pro?.employment_histories?.length ?? 0;
    const educationCount = pro?.educations?.length ?? 0;
    const skillCount = pro?.skills?.length ?? 0;
    const projectCount = pro?.projects?.length ?? 0;
    const certCount = pro?.certifications?.length ?? 0;
    const languageCount = pro?.languages?.length ?? 0;

    return [
        {
            id: 'basic',
            title: 'Basic information',
            description: 'Name, username, photo, location, and mobile.',
            summary: [profile.name, profile.location].filter(Boolean).join(' · ') || 'Add your contact basics',
            status: statusFromKeys(map, ['name', 'username', 'photo', 'location', 'mobile'], Boolean(profile.name)),
            completionKeys: ['name', 'username', 'photo', 'location', 'mobile'],
        },
        {
            id: 'about',
            title: 'Professional summary',
            description: 'Headline and short bio recruiters see first.',
            summary: profile.professional_headline || profile.headline || profile.bio?.slice(0, 80) || 'Add headline and bio',
            status: statusFromKeys(map, ['bio'], Boolean(profile.bio || profile.professional_headline)),
            completionKeys: ['bio'],
        },
        {
            id: 'professional',
            title: 'Professional profile',
            description: 'Current role, industry, and experience level.',
            summary:
                [pro?.current_designation, pro?.industry].filter(Boolean).join(' · ') ||
                'Add designation and industry',
            status: statusFromKeys(map, ['current_role', 'experience', 'industry_or_function'], Boolean(pro?.current_designation)),
            completionKeys: ['current_role', 'experience', 'industry_or_function'],
        },
        {
            id: 'experience',
            title: 'Experience',
            description: 'Work history and roles.',
            summary: experienceCount > 0 ? `${experienceCount} position${experienceCount === 1 ? '' : 's'}` : 'Add work experience',
            status: statusFromKeys(map, ['employment_history'], experienceCount > 0),
            completionKeys: ['employment_history'],
        },
        {
            id: 'education',
            title: 'Education',
            description: 'Degrees, institutions, and qualifications.',
            summary:
                educationCount > 0
                    ? pro?.educations?.[0]?.qualification || pro?.educations?.[0]?.institution || 'Education added'
                    : pro?.qualification || 'Add education',
            status: educationCount > 0 || Boolean(pro?.qualification) ? 'complete' : 'not_started',
            completionKeys: [],
        },
        {
            id: 'skills',
            title: 'Skills',
            description: 'Key skills for matching and search.',
            summary: skillCount > 0 ? `${skillCount} skill${skillCount === 1 ? '' : 's'}` : 'Add skills',
            status: statusFromKeys(map, ['skills'], skillCount > 0),
            completionKeys: ['skills'],
        },
        {
            id: 'projects',
            title: 'Projects',
            description: 'Relevant projects and outcomes.',
            summary: projectCount > 0 ? `${projectCount} project${projectCount === 1 ? '' : 's'}` : 'Add projects',
            status: projectCount > 0 ? 'complete' : 'not_started',
            completionKeys: [],
        },
        {
            id: 'certifications',
            title: 'Certifications',
            description: 'Licenses, certificates, and training.',
            summary: certCount > 0 ? `${certCount} certification${certCount === 1 ? '' : 's'}` : 'Add certifications',
            status: certCount > 0 ? 'complete' : 'not_started',
            completionKeys: [],
        },
        {
            id: 'languages',
            title: 'Languages',
            description: 'Languages you speak and proficiency.',
            summary: languageCount > 0 ? `${languageCount} language${languageCount === 1 ? '' : 's'}` : 'Add languages',
            status: languageCount > 0 ? 'complete' : 'not_started',
            completionKeys: [],
        },
        {
            id: 'preferences',
            title: 'Job preferences',
            description: 'Preferred locations and employment types.',
            summary:
                (pro?.preferred_locations?.length ?? 0) > 0
                    ? `${pro?.preferred_locations?.length} preferred location(s)`
                    : 'Add job preferences',
            status: (pro?.preferred_locations?.length ?? 0) > 0 ? 'in_progress' : 'not_started',
            completionKeys: [],
        },
        {
            id: 'resume',
            title: 'My Resume',
            description: 'Uploaded resume used for applications.',
            summary: 'Manage in resume library',
            status: 'not_started',
            completionKeys: [],
        },
    ];
}

export function nextIncompleteModule(modules: ProfileModuleCard[], after?: ProfileModuleId | null): ProfileModuleCard | null {
    const skipUntilFound = after !== null && after !== undefined;
    let passed = !skipUntilFound;

    for (const module of modules) {
        if (module.id === 'resume') {
            continue;
        }
        if (!passed) {
            if (module.id === after) {
                passed = true;
            }
            continue;
        }
        if (module.status !== 'complete') {
            return module;
        }
    }

    return modules.find((module) => module.id !== 'resume' && module.status !== 'complete') ?? null;
}

export function hashToModuleId(hash: string): ProfileModuleId | null {
    const normalized = hash.replace('#', '').trim();
    const allowed: ProfileModuleId[] = [
        'basic',
        'about',
        'professional',
        'experience',
        'education',
        'skills',
        'projects',
        'certifications',
        'languages',
        'preferences',
    ];
    return allowed.includes(normalized as ProfileModuleId) ? (normalized as ProfileModuleId) : null;
}
