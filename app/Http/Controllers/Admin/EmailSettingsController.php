<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MailDiagnosticRequest;
use App\Http\Requests\Admin\MailDiagnosticSendTestRequest;
use App\Http\Requests\Admin\UpdateMailSettingsRequest;
use App\Models\User;
use App\Services\MailConfigurationService;
use App\Services\MailDiagnosticService;
use App\Services\WebsiteSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailSettingsController extends Controller
{
    public function update(
        UpdateMailSettingsRequest $request,
        WebsiteSettingService $websiteSettings,
        MailConfigurationService $mailConfiguration,
    ): RedirectResponse {
        $row = $websiteSettings->current();
        $mailConfiguration->updateFromAdmin($request->validated(), $row);

        return redirect()
            ->route('admin.settings.edit', ['tab' => 'email'])
            ->with('success', 'Email delivery settings saved.');
    }

    public function validateConfiguration(MailDiagnosticRequest $request, MailDiagnosticService $diagnostics): JsonResponse
    {
        $result = $diagnostics->validateConfiguration();

        return response()->json($result);
    }

    public function testConnection(MailDiagnosticRequest $request, MailDiagnosticService $diagnostics): JsonResponse
    {
        return response()->json($diagnostics->testSmtpConnection());
    }

    public function sendTest(MailDiagnosticSendTestRequest $request, MailDiagnosticService $diagnostics): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return response()->json(
            $diagnostics->sendTestEmail($user, $request->validated('recipient')),
        );
    }

    public function previewVerification(Request $request, MailDiagnosticService $diagnostics): JsonResponse
    {
        abort_unless($request->user()?->canManageWebsite() === true, 403);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $previewUser = User::factory()->make([
            'id' => 0,
            'email' => 'preview@example.test',
            'name' => 'Preview User',
        ]);

        return response()->json($diagnostics->previewVerificationEmail($previewUser));
    }

    public function prerequisites(MailDiagnosticRequest $request, MailDiagnosticService $diagnostics): JsonResponse
    {
        return response()->json([
            'items' => $diagnostics->externalPrerequisites(),
        ]);
    }
}
