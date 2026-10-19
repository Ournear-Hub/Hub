<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\DevelopmentProgrammeApplication;
use App\Models\User;
use App\Support\DevelopmentProgramme;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DevelopmentProgrammeApplicationController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $this->candidate($request);
        $selected = $request->string('programme')->toString();
        $application = DevelopmentProgrammeApplication::query()->where('user_id', $user->id)->first();

        return Inertia::render('Account/DevelopmentProgramme/Apply', [
            'programmes' => $this->programmes(),
            'selected_programme' => DevelopmentProgramme::isSlug($selected) ? $selected : ($application?->programme ?? ''),
            'application' => $application?->toAccountArray(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->candidate($request);
        $validated = $request->validate([
            'programme' => ['required', 'string', 'in:'.implode(',', DevelopmentProgramme::slugs())],
            'motivation' => ['nullable', 'string', 'max:2000'],
        ]);

        $created = false;

        $saved = DB::transaction(function () use ($user, $validated, &$created): bool {
            $existing = DevelopmentProgrammeApplication::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if (! $existing->canEdit()) {
                    return false;
                }

                $existing->update([
                    'programme' => $validated['programme'],
                    'motivation' => $validated['motivation'] ?? null,
                ]);

                return true;
            }

            try {
                DevelopmentProgrammeApplication::query()->create([
                    'user_id' => $user->id,
                    'programme' => $validated['programme'],
                    'motivation' => $validated['motivation'] ?? null,
                    'status' => DevelopmentProgramme::StatusSubmitted,
                ]);
                $created = true;
            } catch (UniqueConstraintViolationException) {
                $existing = DevelopmentProgrammeApplication::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing === null || ! $existing->canEdit()) {
                    return false;
                }

                $existing->update([
                    'programme' => $validated['programme'],
                    'motivation' => $validated['motivation'] ?? null,
                ]);
            }

            return true;
        });

        if (! $saved) {
            return back()->with('error', 'This application can no longer be changed.');
        }

        return redirect()
            ->route('account.development-programme.show')
            ->with('success', $created
                ? 'Development Programme application submitted. Our team will review it.'
                : 'Development Programme application updated.');
    }

    private function candidate(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isCandidate(), 403);

        return $user;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function programmes(): array
    {
        return array_map(
            fn (string $slug) => ['value' => $slug, 'label' => DevelopmentProgramme::label($slug)],
            DevelopmentProgramme::slugs(),
        );
    }
}
