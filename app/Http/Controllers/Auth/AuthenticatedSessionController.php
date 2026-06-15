<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): HttpResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $target = redirect()->intended($this->postLoginRedirect($request))->getTargetUrl();

        // A normal 302 to a DIFFERENT origin (e.g. an "intended" club-subdomain URL) can't be
        // followed by the Inertia XHR — it leaves a blank screen until a manual refresh. Force
        // a full-page visit in that case (the documented cross-subdomain rule).
        if ($this->isCrossOrigin($request, $target)) {
            return Inertia::location($target);
        }

        return redirect()->to($target);
    }

    /**
     * Where to land after login when there's no "intended" URL:
     *  - platform admins → the platform clubs list (their home; not the empty central dashboard),
     *  - other central users → the central dashboard,
     *  - club-subdomain users → "/" (the club dashboard; central-only "/dashboard" would 404).
     */
    private function postLoginRedirect(Request $request): string
    {
        $isCentral = in_array($request->getHost(), config('tenancy.central_domains', []), true);

        if (! $isCentral) {
            return '/';
        }

        return $request->user()?->is_platform_admin
            ? route('platform.clubs.index', absolute: false)
            : route('dashboard', absolute: false);
    }

    /** Whether the redirect target is on a different origin than the current request. */
    private function isCrossOrigin(Request $request, string $target): bool
    {
        $host = parse_url($target, PHP_URL_HOST);

        return $host !== null && $host !== $request->getHost();
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
