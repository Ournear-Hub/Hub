<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FreelancerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFreelancerStatusRequest;
use App\Models\Freelancer;
use App\Services\FreelancerFileService;
use App\Services\FreelancerIntakeService;
use App\Support\FreelancerQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FreelancersController extends Controller
{
    public function index(Request $request, FreelancerQuery $freelancers): Response
    {
        $this->authorize('viewAny', Freelancer::class);

        $page = $freelancers->adminIndex($request)->through(
            fn (Freelancer $freelancer) => $freelancer->toAdminListArray()
        );

        return Inertia::render('Admin/Freelancers/Index', [
            'freelancers' => $page,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'industry_id' => $request->string('industry_id')->toString(),
                'location_id' => $request->string('location_id')->toString(),
            ],
            'options' => $freelancers->filterOptions(),
        ]);
    }

    public function show(Freelancer $freelancer, FreelancerIntakeService $intake): Response
    {
        $this->authorize('view', $freelancer);

        return Inertia::render('Admin/Freelancers/Show', [
            'freelancer' => $freelancer->toAdminDetailArray($intake->possibleMatches($freelancer)),
            'statuses' => FreelancerStatus::options(),
        ]);
    }

    public function updateStatus(
        UpdateFreelancerStatusRequest $request,
        Freelancer $freelancer,
        FreelancerIntakeService $intake,
    ): RedirectResponse {
        $intake->updateStatus(
            $freelancer,
            $request->enum('status', FreelancerStatus::class),
            $request->user(),
        );

        return back()->with('success', 'Freelancer status updated.');
    }

    public function resume(Freelancer $freelancer, FreelancerFileService $files): StreamedResponse
    {
        $this->authorize('download', $freelancer);

        return $files->download(
            $freelancer->getAttributes()['resume_path'] ?? null,
            $freelancer->resume_original_name ?: ($freelancer->business_id.'.pdf'),
            (string) ($freelancer->getAttributes()['resume_disk'] ?? FreelancerFileService::DISK),
        );
    }

    public function certificate(Freelancer $freelancer, FreelancerFileService $files): StreamedResponse
    {
        $this->authorize('download', $freelancer);

        return $files->download(
            $freelancer->getAttributes()['certificate_path'] ?? null,
            $freelancer->certificate_original_name ?: ($freelancer->business_id.'-certificate.pdf'),
            (string) ($freelancer->getAttributes()['certificate_disk'] ?? FreelancerFileService::DISK),
        );
    }
}
