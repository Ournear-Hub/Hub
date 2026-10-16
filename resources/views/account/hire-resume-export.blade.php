<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OurNear Hire Resume</title>
    <style>
        body { font-family: system-ui, sans-serif; color: #163033; margin: 1.5rem; line-height: 1.45; font-size: 13px; }
        h1 { font-size: 1.35rem; margin: 0 0 0.25rem; }
        h2 { font-size: 0.7rem; letter-spacing: 0.12em; text-transform: uppercase; color: #2a6b73; border-bottom: 1px solid #c5dfe2; padding-bottom: 0.25rem; margin: 1.25rem 0 0.5rem; }
        .muted { color: #5c6b6d; }
        ul { margin: 0.25rem 0 0; padding-left: 1.1rem; }
        li { margin-bottom: 0.35rem; }
        .contact { margin-top: 0.35rem; }
    </style>
</head>
<body>
@php
    $personal = $preview['personal'] ?? [];
    $professional = $preview['professional'] ?? [];
    $name = trim((string) ($personal['name'] ?? ''));
    $designation = trim((string) ($personal['designation'] ?? ($professional['current_designation'] ?? '')));
    $contact = collect([$personal['email'] ?? null, $personal['mobile'] ?? null, $personal['location'] ?? null])->filter()->implode(' · ');
    $bio = trim((string) ($personal['bio'] ?? ''));
@endphp
    <header>
        @if($name !== '')<h1>{{ $name }}</h1>@endif
        @if($designation !== '')<p class="muted">{{ $designation }}</p>@endif
        @if($contact !== '')<p class="contact muted">{{ $contact }}</p>@endif
    </header>

    @if($bio !== '')
        <h2>Summary</h2>
        <p>{{ $bio }}</p>
    @endif

    @if(!empty($professional['skills']))
        <h2>Skills</h2>
        <p>{{ collect($professional['skills'])->pluck('name')->filter()->implode(', ') }}</p>
    @endif

    @if(!empty($professional['employment_histories']))
        <h2>Experience</h2>
        <ul>
            @foreach($professional['employment_histories'] as $history)
                <li>
                    <strong>{{ trim(($history['designation'] ?? '').' · '.($history['company'] ?? ''), ' ·') }}</strong>
                    @if(!empty($history['description']))
                        <div>{{ $history['description'] }}</div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if(!empty($professional['educations']))
        <h2>Education</h2>
        <ul>
            @foreach($professional['educations'] as $education)
                <li>{{ trim(($education['qualification'] ?? '').' · '.($education['institution'] ?? ''), ' ·') }}</li>
            @endforeach
        </ul>
    @endif

    <p class="muted" style="margin-top:2rem;font-size:11px;">Generated from your OurNear Hire profile. Private export — not a public profile page.</p>
</body>
</html>
