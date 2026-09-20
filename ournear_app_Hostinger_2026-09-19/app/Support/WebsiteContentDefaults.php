<?php

namespace App\Support;

class WebsiteContentDefaults
{
    /**
     * @return list<array{slug: string, title: string, sections: list<array<string, mixed>>}>
     */
    public static function pages(): array
    {
        return [
            [
                'slug' => 'home',
                'title' => 'Home',
                'sections' => [
                    [
                        'key' => 'hero',
                        'label' => 'Hero Section',
                        'heading' => "RIGHT PEOPLE.\nRIGHT TRAINING.\nBETTER FUTURE.",
                        'body' => 'OurNear Talent supplies capable people, practical training, and project support so employers can deliver with confidence.',
                        'primary_button_label' => 'Find Jobs',
                        'secondary_button_label' => 'For Employers',
                        'image_path' => '',
                    ],
                    [
                        'key' => 'what_we_do',
                        'label' => 'What We Do',
                        'heading' => 'What We Do',
                        'body' => 'We connect the right people with the right work, and support them with the right training.',
                    ],
                    [
                        'key' => 'core_services',
                        'label' => 'Core Services',
                        'heading' => 'Core Services',
                        'body' => 'Workforce, training, and project support designed around delivery.',
                    ],
                    [
                        'key' => 'industries',
                        'label' => 'Industries',
                        'heading' => 'Industries We Serve',
                        'body' => 'Practical talent support for demanding operational environments.',
                    ],
                    [
                        'key' => 'how_we_work',
                        'label' => 'How We Work',
                        'heading' => 'How We Work',
                        'body' => 'A clear process from understanding the need to ongoing support.',
                    ],
                    [
                        'key' => 'why_choose_us',
                        'label' => 'Why Choose Us',
                        'heading' => 'Why Choose OurNear Talent',
                        'body' => 'We measure success by the quality of the match and the result delivered.',
                    ],
                    [
                        'key' => 'our_commitment',
                        'label' => 'Our Commitment',
                        'heading' => 'Our Commitment',
                        'body' => 'Clear communication, dependable delivery, and accountability to the result.',
                        'image_path' => '',
                    ],
                ],
            ],
            [
                'slug' => 'about',
                'title' => 'About Us',
                'sections' => [
                    [
                        'key' => 'hero',
                        'label' => 'About Hero / Our Story',
                        'heading' => 'Our Story',
                        'body' => 'OurNear Talent exists to put the right people, with the right training, into work that delivers the right results.',
                        'image_path' => '',
                    ],
                    [
                        'key' => 'who_we_are',
                        'label' => 'Who We Are',
                        'heading' => 'Who We Are',
                        'body' => 'A talent partner for employers who need capable people and for candidates who want a clear next step.',
                    ],
                    [
                        'key' => 'what_we_do',
                        'label' => 'What We Do',
                        'heading' => 'What We Do',
                        'body' => 'We supply people, training, and project support as one connected service.',
                    ],
                    [
                        'key' => 'our_approach',
                        'label' => 'Our Approach',
                        'heading' => 'Our Approach',
                        'body' => 'People, training, and performance working together.',
                    ],
                    [
                        'key' => 'industries',
                        'label' => 'Industries We Serve',
                        'heading' => 'Industries We Serve',
                        'body' => 'We support operational and project environments where capability matters.',
                    ],
                    [
                        'key' => 'why_choose_us',
                        'label' => 'Why Choose OurNear Talent',
                        'heading' => 'Why Choose OurNear Talent',
                        'body' => 'Reliable people, practical training, and delivery that holds up on site.',
                    ],
                    [
                        'key' => 'our_commitment',
                        'label' => 'Our Commitment',
                        'heading' => 'Our Commitment',
                        'body' => 'Honest advice, dependable follow-through, and respect for every employer and candidate.',
                        'image_path' => '',
                    ],
                ],
            ],
            [
                'slug' => 'services',
                'title' => 'Services',
                'sections' => [
                    [
                        'key' => 'hero',
                        'label' => 'Services Hero',
                        'heading' => 'Services',
                        'subtitle' => 'People. Training. Delivery.',
                        'body' => 'Recruitment, training, competency, and project support designed around the result you need.',
                        'image_path' => '',
                    ],
                    [
                        'key' => 'core_services',
                        'label' => 'Core Services',
                        'heading' => 'Core Services',
                        'body' => 'Six connected services that help employers staff, train, and deliver.',
                    ],
                    [
                        'key' => 'workforce',
                        'label' => 'Workers, Staff & Professionals',
                        'heading' => 'Workers, Staff & Professionals We Provide',
                        'body' => 'From site labour to professional and management support.',
                    ],
                    [
                        'key' => 'training',
                        'label' => 'Training for Workers & Staff',
                        'heading' => 'Training for Workers & Staff',
                        'body' => 'Practical training aligned to the work people actually do.',
                    ],
                    [
                        'key' => 'delivery_process',
                        'label' => 'Employee Evaluation & Delivery',
                        'heading' => 'Employee Evaluation & Delivery Process',
                        'body' => 'A structured path from requirement to deployment.',
                    ],
                    [
                        'key' => 'competency_process',
                        'label' => 'Training & Competency Management',
                        'heading' => 'Training & Competency Management Process',
                        'body' => 'Assess, train, test, report, and improve.',
                    ],
                    [
                        'key' => 'project_support',
                        'label' => 'Project Support & Management',
                        'heading' => 'Project Support & Management',
                        'body' => 'End-to-end support so projects stay staffed and productive.',
                    ],
                    [
                        'key' => 'industries',
                        'label' => 'Industries',
                        'heading' => 'Industries We Serve',
                        'body' => 'Operational sectors where the right people and training make a measurable difference.',
                    ],
                    [
                        'key' => 'why_choose_us',
                        'label' => 'Why Choose Us',
                        'heading' => 'Why Choose Us',
                        'body' => 'A delivery-focused approach to workforce and training.',
                    ],
                    [
                        'key' => 'cta',
                        'label' => 'CTA Section',
                        'heading' => 'Talk to OurNear Talent',
                        'body' => 'Tell us about the role, the team, or the training outcome you need.',
                        'primary_button_label' => 'Contact us',
                        'image_path' => '',
                    ],
                ],
            ],
            [
                'slug' => 'for-employers',
                'title' => 'For Employers',
                'sections' => [
                    [
                        'key' => 'hero',
                        'label' => 'Hero',
                        'heading' => 'For Employers',
                        'body' => 'Hire with confidence. We help you find the right people and support them with the right training so you get the right results.',
                        'image_path' => '',
                    ],
                    [
                        'key' => 'what_we_provide',
                        'label' => 'What We Provide Employers',
                        'heading' => 'What We Provide Employers',
                        'body' => 'Workforce, training, assessment, and ongoing support.',
                    ],
                    [
                        'key' => 'how_we_deliver',
                        'label' => 'How We Deliver',
                        'heading' => 'How We Deliver',
                        'body' => 'A practical process from brief to follow-up.',
                    ],
                    [
                        'key' => 'what_employers_get',
                        'label' => 'What Employers Get',
                        'heading' => 'What Employers Get',
                        'body' => 'Clear matching, job-ready people, and accountable delivery.',
                    ],
                    [
                        'key' => 'workforce',
                        'label' => 'Workforce We Can Provide',
                        'heading' => 'Workforce We Can Provide',
                        'body' => 'Flexible supply across labour, technical, supervisory, and professional roles.',
                    ],
                    [
                        'key' => 'industries',
                        'label' => 'Industries We Serve',
                        'heading' => 'Industries We Serve',
                        'body' => 'Support for projects and operations that need capable people quickly.',
                    ],
                    [
                        'key' => 'get_in_touch',
                        'label' => 'Get In Touch',
                        'heading' => 'Get In Touch',
                        'body' => 'Submit a workforce or project request and we will follow up.',
                    ],
                ],
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact Us',
                'sections' => [
                    [
                        'key' => 'hero',
                        'label' => 'Hero',
                        'heading' => 'Contact Us',
                        'body' => 'Reach OurNear Talent about jobs, training, or employer support.',
                        'image_path' => '',
                    ],
                    [
                        'key' => 'details',
                        'label' => 'Contact Information',
                        'heading' => 'How to reach us',
                        'body' => 'Call, message, or send an enquiry. We will point you to the right next step.',
                        'extra' => [
                            'phone' => 'Office phone — to be published',
                            'whatsapp' => 'WhatsApp — to be published',
                            'email' => 'Office email — to be published',
                            'address' => 'Office address — to be published',
                            'hours' => 'Monday to Friday, 9:00am – 6:00pm',
                            'help' => 'Use the form for recruitment, training, or general enquiries.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'sections' => [
                    [
                        'key' => 'content',
                        'label' => 'Privacy Policy',
                        'heading' => 'Privacy Policy',
                        'body' => "This page explains how OurNear Talent collects and uses personal information when you contact us or apply for a role.\n\nInformation we collect\nContact details and application information you choose to provide through the website.\n\nHow we use it\nTo respond to enquiries and to consider applications for roles. This wording can be updated by an administrator and is not a substitute for legal advice.",
                    ],
                ],
            ],
            [
                'slug' => 'terms-and-conditions',
                'title' => 'Terms & Conditions',
                'sections' => [
                    [
                        'key' => 'content',
                        'label' => 'Terms & Conditions',
                        'heading' => 'Terms & Conditions',
                        'body' => "These terms cover use of the OurNear Talent website, including information about our services, jobs, and contact routes.\n\nUsing this website\nContent is provided for information about OurNear Talent services, jobs, and contact routes. Job applications and enquiries are subject to the processes described on this website.\n\nThis wording can be updated by an administrator and is not a substitute for legal advice.",
                    ],
                ],
            ],
        ];
    }

    /**
     * Shared and page-specific item groups.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function groups(): array
    {
        return [
            'industries' => [
                ['title' => 'Mining', 'description' => 'Operational and project roles in mining environments.', 'icon' => 'industry'],
                ['title' => 'Infrastructure', 'description' => 'Civil, utilities, and infrastructure project support.', 'icon' => 'industry'],
                ['title' => 'Construction', 'description' => 'Site, trade, and project construction workforce.', 'icon' => 'industry'],
            ],
            'core_services' => [
                ['title' => 'Manpower Supply', 'description' => 'Reliable people for permanent, contract, and project roles.', 'icon' => 'people', 'bullets' => ['Screened candidates', 'Role-fit matching']],
                ['title' => 'Providing Trainers', 'description' => 'Trainers who can build capability in the workplace.', 'icon' => 'trainer', 'bullets' => ['Practical delivery', 'Site-ready methods']],
                ['title' => 'Competency Assessment', 'description' => 'Clear evaluation of skills against the work required.', 'icon' => 'assess', 'bullets' => ['Structured checks', 'Evidence-based outcomes']],
                ['title' => 'Training & Development', 'description' => 'Training that matches real job requirements.', 'icon' => 'train', 'bullets' => ['Job-focused modules', 'Measurable progress']],
                ['title' => 'Project Support & Management', 'description' => 'Support that keeps workforce delivery on track.', 'icon' => 'project', 'bullets' => ['Planning support', 'On-site coordination']],
                ['title' => 'Safety & Productivity Improvement', 'description' => 'Safer work and better output through people and process.', 'icon' => 'safety', 'bullets' => ['Compliance focus', 'Productivity habits']],
            ],
            'benefits' => [
                ['title' => 'Verified & Reliable Workforce', 'description' => 'People who have been checked against the role.', 'icon' => 'check'],
                ['title' => 'Skilled & Job-Ready People', 'description' => 'Capability that can start work with confidence.', 'icon' => 'skill'],
                ['title' => 'Rigorous Evaluation & Competency Assessment', 'description' => 'Assessment before deployment, not after problems appear.', 'icon' => 'evaluate'],
                ['title' => 'Safety & Compliance Focus', 'description' => 'A practical emphasis on safe, compliant work.', 'icon' => 'safety'],
                ['title' => 'Faster Workforce Deployment', 'description' => 'A clear process that reduces delay.', 'icon' => 'speed'],
                ['title' => 'Productivity Improvement', 'description' => 'People and training that support output.', 'icon' => 'output'],
                ['title' => 'Long-Term Project Support', 'description' => 'We stay involved beyond the first placement.', 'icon' => 'support'],
                ['title' => 'Cost-Effective Workforce Solutions', 'description' => 'The right people and training without unnecessary waste.', 'icon' => 'value'],
            ],
            'home_what_we_do' => [
                ['title' => 'Right People', 'description' => 'Candidates who fit the role, the team, and the work.', 'icon' => 'people'],
                ['title' => 'Right Training', 'description' => 'Capability building that supports real workplace performance.', 'icon' => 'train'],
                ['title' => 'Project Support', 'description' => 'Practical support so delivery stays on track.', 'icon' => 'project'],
            ],
            'home_how_we_work' => [
                ['title' => 'Understand', 'description' => 'We listen to the role, the team, and the result you need.'],
                ['title' => 'Evaluate', 'description' => 'We assess people against the work, not just a job title.'],
                ['title' => 'Train', 'description' => 'We close skill gaps with practical, job-focused training.'],
                ['title' => 'Deploy', 'description' => 'We place people ready to contribute on day one.'],
                ['title' => 'Support', 'description' => 'We stay involved so the placement holds.'],
            ],
            'home_commitment' => [
                ['title' => 'Clear communication', 'description' => 'Employers and candidates know where things stand.'],
                ['title' => 'Dependable delivery', 'description' => 'We stay accountable to the agreed result.'],
                ['title' => 'Quality of match', 'description' => 'We measure success by fit, not volume.'],
            ],
            'about_who_we_are' => [
                ['title' => 'Industry Focused', 'description' => 'We understand operational and project environments.'],
                ['title' => 'Experienced Team', 'description' => 'Practical experience in people, training, and delivery.'],
                ['title' => 'Results Driven', 'description' => 'We stay focused on the outcome, not just filling a vacancy.'],
            ],
            'about_what_we_do' => [
                ['title' => 'Right People', 'description' => 'Recruitment and supply of capable workers, staff, and professionals.', 'bullets' => ['Workforce supply', 'Role matching']],
                ['title' => 'Right Training', 'description' => 'Training and competency support that matches workplace needs.', 'bullets' => ['Trainer supply', 'Competency assessment']],
            ],
            'about_approach' => [
                ['title' => 'People', 'description' => 'Find and place the right people for the work.'],
                ['title' => 'Training', 'description' => 'Build the capability the role actually requires.'],
                ['title' => 'Performance', 'description' => 'Support delivery, safety, and productivity after placement.'],
            ],
            'about_commitment' => [
                ['title' => 'Honest advice', 'description' => 'We say what we can deliver.'],
                ['title' => 'Follow-through', 'description' => 'We stay with the assignment after go-live.'],
            ],
            'services_workforce' => [
                ['title' => 'Workforce & Labour'],
                ['title' => 'Equipment Operators'],
                ['title' => 'Drivers'],
                ['title' => 'Technical & Maintenance'],
                ['title' => 'Supervisory & Site Staff'],
                ['title' => 'Engineering & Professionals'],
                ['title' => 'Management & Support'],
            ],
            'services_training' => [
                ['title' => 'Induction and workplace basics', 'description' => 'Core training so people can start safely.'],
                ['title' => 'Trade and technical skills', 'description' => 'Training aligned to the tools and tasks on site.'],
                ['title' => 'Supervisory capability', 'description' => 'Support for team leaders and site supervisors.'],
                ['title' => 'Safety and compliance', 'description' => 'Practical training that supports safe delivery.'],
            ],
            'services_delivery_process' => [
                ['title' => 'Brief', 'description' => 'Understand the role, site, and result.'],
                ['title' => 'Source', 'description' => 'Identify people who can do the work.'],
                ['title' => 'Evaluate', 'description' => 'Check capability before deployment.'],
                ['title' => 'Deploy', 'description' => 'Place people with a clear handover.'],
                ['title' => 'Review', 'description' => 'Follow up on fit and performance.'],
            ],
            'services_competency_process' => [
                ['title' => 'Assess', 'description' => 'Establish current capability.'],
                ['title' => 'Provide Training', 'description' => 'Close identified gaps.'],
                ['title' => 'Test Competency', 'description' => 'Confirm people can do the work.'],
                ['title' => 'Report', 'description' => 'Share clear outcomes with the employer.'],
                ['title' => 'Improve', 'description' => 'Adjust training and deployment from evidence.'],
            ],
            'services_project_support' => [
                ['title' => 'Plan', 'description' => 'Define workforce and training needs.'],
                ['title' => 'Staff', 'description' => 'Supply the people the project requires.'],
                ['title' => 'Enable', 'description' => 'Train and assess for the work ahead.'],
                ['title' => 'Manage', 'description' => 'Keep delivery, safety, and communication in view.'],
                ['title' => 'Sustain', 'description' => 'Support replacements and ongoing performance.'],
            ],
            'employer_offerings' => [
                ['title' => 'Workforce Supply', 'description' => 'People for permanent, contract, and project needs.'],
                ['title' => 'Trainers & Training', 'description' => 'Trainers and programmes matched to the workplace.'],
                ['title' => 'Competency Assessment', 'description' => 'Evidence that people can perform the role.'],
                ['title' => 'Workforce Deployment', 'description' => 'A structured path from brief to start date.'],
                ['title' => 'Safety & Productivity', 'description' => 'Support that protects people and output.'],
                ['title' => 'Continuous Support', 'description' => 'Follow-up after people are on site.'],
            ],
            'employer_how_we_deliver' => [
                ['title' => 'Understand the brief'],
                ['title' => 'Define the role and outcome'],
                ['title' => 'Source suitable people'],
                ['title' => 'Evaluate capability'],
                ['title' => 'Train where needed'],
                ['title' => 'Deploy to site or workplace'],
                ['title' => 'Support the placement'],
                ['title' => 'Review and improve'],
            ],
            'employer_workforce' => [
                ['title' => 'Workforce & Labour'],
                ['title' => 'Operators and drivers'],
                ['title' => 'Technical and maintenance'],
                ['title' => 'Supervisory staff'],
                ['title' => 'Professional and management support'],
            ],
        ];
    }

    /**
     * Map page section keys to shared item groups.
     *
     * @return array<string, array<string, string>>
     */
    public static function sectionGroups(): array
    {
        return [
            'home' => [
                'what_we_do' => 'home_what_we_do',
                'core_services' => 'core_services',
                'industries' => 'industries',
                'how_we_work' => 'home_how_we_work',
                'why_choose_us' => 'benefits',
                'our_commitment' => 'home_commitment',
            ],
            'about' => [
                'who_we_are' => 'about_who_we_are',
                'what_we_do' => 'about_what_we_do',
                'our_approach' => 'about_approach',
                'industries' => 'industries',
                'why_choose_us' => 'benefits',
                'our_commitment' => 'about_commitment',
            ],
            'services' => [
                'core_services' => 'core_services',
                'workforce' => 'services_workforce',
                'training' => 'services_training',
                'delivery_process' => 'services_delivery_process',
                'competency_process' => 'services_competency_process',
                'project_support' => 'services_project_support',
                'industries' => 'industries',
                'why_choose_us' => 'benefits',
            ],
            'for-employers' => [
                'what_we_provide' => 'employer_offerings',
                'how_we_deliver' => 'employer_how_we_deliver',
                'what_employers_get' => 'benefits',
                'workforce' => 'employer_workforce',
                'industries' => 'industries',
            ],
            'contact' => [],
            'privacy-policy' => [],
            'terms-and-conditions' => [],
        ];
    }
}
