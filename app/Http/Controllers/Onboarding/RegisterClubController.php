<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Domains\Tenancy\Actions\RegisterClub;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\RegisterClubRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class RegisterClubController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/register-club', [
            'centralDomain' => config('tenancy.central_domain'),
        ]);
    }

    public function store(RegisterClubRequest $request, RegisterClub $registerClub): HttpResponse
    {
        $data = $request->toData();

        $owner = $registerClub->handle($data);

        Auth::login($owner);

        // The club lives on a different origin (subdomain), so use Inertia::location:
        // a full-page visit for Inertia XHR requests, a normal 302 otherwise.
        return Inertia::location($this->clubUrl($request, $data->slug));
    }

    /**
     * Build the absolute URL of the new club's subdomain, preserving the current
     * scheme and (non-default) port so it works under `artisan serve` and Docker alike.
     */
    private function clubUrl(RegisterClubRequest $request, string $slug): string
    {
        $port = $request->getPort();
        $suffix = in_array($port, [80, 443, null], true) ? '' : ':'.$port;

        return $request->getScheme().'://'.$slug.'.'.config('tenancy.central_domain').$suffix.'/';
    }
}
