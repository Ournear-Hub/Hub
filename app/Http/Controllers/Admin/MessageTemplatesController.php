<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MessageTemplateAudience;
use App\Enums\MessageTemplateChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreviewMessageTemplateRequest;
use App\Http\Requests\Admin\StoreMessageTemplateRequest;
use App\Http\Requests\Admin\UpdateMessageTemplateRequest;
use App\Models\MessageTemplate;
use App\Services\MessageTemplateService;
use App\Support\MessageTemplateVariableCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageTemplatesController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MessageTemplate::class);

        $query = MessageTemplate::query()->orderBy('name');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('template_key', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($channel = MessageTemplateChannel::tryFrom((string) $request->query('channel', ''))) {
            $query->where('channel', $channel);
        }

        if ($audience = MessageTemplateAudience::tryFrom((string) $request->query('audience', ''))) {
            $query->where('audience', $audience);
        }

        $status = (string) $request->query('status', '');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return Inertia::render('Admin/MessageTemplates/Index', [
            'templates' => $query->get()->map(fn (MessageTemplate $template) => $template->toAdminArray())->all(),
            'filters' => [
                'q' => $search ?? '',
                'channel' => $channel?->value ?? '',
                'audience' => $audience?->value ?? '',
                'status' => $status,
            ],
            'options' => [
                'channels' => array_map(
                    fn (MessageTemplateChannel $item) => ['value' => $item->value, 'label' => $item->label()],
                    MessageTemplateChannel::cases(),
                ),
                'audiences' => array_map(
                    fn (MessageTemplateAudience $item) => ['value' => $item->value, 'label' => $item->label()],
                    MessageTemplateAudience::cases(),
                ),
            ],
            'variable_catalog' => MessageTemplateVariableCatalog::globalVariables(),
            'can_manage' => $request->user()?->can('create', MessageTemplate::class) === true,
        ]);
    }

    public function store(StoreMessageTemplateRequest $request, MessageTemplateService $templates): RedirectResponse
    {
        $data = $request->validated();
        $data['variables'] = MessageTemplateVariableCatalog::globalVariables();
        $templates->saveTemplate(null, $data, $request->user());

        return back()->with('success', 'Message template created.');
    }

    public function update(
        UpdateMessageTemplateRequest $request,
        MessageTemplate $messageTemplate,
        MessageTemplateService $templates,
    ): RedirectResponse {
        $data = $request->validated();
        if ($messageTemplate->is_system_required) {
            $data['variables'] = $messageTemplate->variableList();
        } else {
            $data['variables'] = MessageTemplateVariableCatalog::globalVariables();
        }

        $templates->saveTemplate($messageTemplate, $data, $request->user());

        return back()->with('success', 'Message template updated.');
    }

    public function toggleActive(
        Request $request,
        MessageTemplate $messageTemplate,
        MessageTemplateService $templates,
    ): RedirectResponse {
        $this->authorize('update', $messageTemplate);

        $request->validate(['is_active' => ['required', 'boolean']]);
        $templates->setActive($messageTemplate, (bool) $request->boolean('is_active'), $request->user());

        return back()->with('success', 'Template status updated.');
    }

    public function preview(PreviewMessageTemplateRequest $request, MessageTemplateService $templates): JsonResponse
    {
        $data = $request->validated();
        $channel = MessageTemplateChannel::from($data['channel']);
        $rendered = $templates->preview(
            $channel,
            isset($data['subject']) ? (string) $data['subject'] : null,
            (string) $data['body'],
        );

        return response()->json($rendered);
    }
}
