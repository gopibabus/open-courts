<?php

declare(strict_types=1);

namespace App\Http\Controllers\Support;

use App\Domains\Support\Actions\SubmitSupportRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreSupportRequestRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The in-app Help page — a few FAQs plus a form any authenticated club member can use
 * to file a support request. Submitting records a SupportRequest (tenant-scoped) and
 * fires SupportRequestSubmitted, whose queued listener emails the support inbox.
 */
class HelpController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('help/index', [
            'categories' => StoreSupportRequestRequest::CATEGORIES,
        ]);
    }

    public function store(StoreSupportRequestRequest $request, SubmitSupportRequest $submit): RedirectResponse
    {
        $submit->handle($request->toData());

        return back()->with('status', "Thanks — we've received your request and will be in touch.");
    }
}
