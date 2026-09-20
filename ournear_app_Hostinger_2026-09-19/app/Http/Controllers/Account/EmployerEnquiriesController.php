<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\EmployerEnquiry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployerEnquiriesController extends Controller
{
    public function index(Request $request): Response
    {
        $enquiries = $request->user()
            ->employerEnquiries()
            ->latest()
            ->paginate(10)
            ->through(fn (EmployerEnquiry $enquiry) => $enquiry->toAccountArray());

        return Inertia::render('Account/Enquiries', [
            'enquiries' => $enquiries,
        ]);
    }
}
