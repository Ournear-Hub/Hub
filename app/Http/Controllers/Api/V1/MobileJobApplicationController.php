<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use App\Services\ApplicationSubmissionService;
use App\Services\SubmissionMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobileJobApplicationController extends Controller
{
    public function store(
        Request $request,
        Job $job,
        ApplicationSubmissionService $submissions,
        SubmissionMailService $mail,
    ): JsonResponse {
        abort_unless($job->isPubliclyVisible(), 404);

        $user = $request->user();
        abort_unless($user instanceof User && $user->isCandidate(), 403);

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Verify your email to apply for jobs.',
                'error_code' => 'email_verification_required',
                'errors' => [
                    'email' => [
                        'Verify your email to apply for jobs. You can continue browsing jobs and completing your profile while you verify your email.',
                    ],
                ],
            ], 403);
        }

        try {
            $application = $submissions->submitFromProfile($job, $user);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $status = isset($errors['email']) ? 403 : 422;
            $payload = [
                'message' => collect($errors)->flatten()->first() ?: 'Unable to submit application.',
                'errors' => $errors,
            ];

            if (isset($errors['email'])) {
                $payload['error_code'] = 'email_verification_required';
            }

            return response()->json($payload, $status);
        }

        $mail->notifyApplication($application);

        return response()->json([
            'message' => 'Application submitted.',
            'data' => [
                'application_id' => $application->id,
                'business_id' => $application->business_id,
                'job_id' => $job->id,
                'job_slug' => $job->slug,
                'status' => $application->status->toCandidateStatusLabel(),
            ],
        ], 201);
    }
}
