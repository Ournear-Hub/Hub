<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SiteContactService;
use Illuminate\Http\JsonResponse;

class PublicSiteContactController extends Controller
{
    public function show(SiteContactService $contact): JsonResponse
    {
        return response()->json([
            'data' => $contact->apiPayload(),
        ]);
    }
}
