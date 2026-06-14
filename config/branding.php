<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Branding / app metadata — the single source of truth
|--------------------------------------------------------------------------
|
| Configure the app's identity here: name, tagline, description and brand
| artwork. The React frontend reads these via Inertia's shared `branding`
| prop (see App\Http\Middleware\HandleInertiaRequests); the blade root and
| mail read them via config('branding.*') / config('app.name'). Swap the
| logo files in public/ to re-skin the whole app from one place.
|
*/

return [
    'name' => env('APP_NAME', 'Open Courts'),

    // Where in-app Help requests are delivered (the support inbox).
    'support_email' => env('SUPPORT_EMAIL', 'support@opencourts.test'),

    'tagline' => 'Book a court. Round up the neighbours. Play.',

    'description' => 'The community court-booking platform — reserve courts, run tournaments, and manage your teams and members, all in one place.',

    // Brand artwork in public/. Replace these files to change the logo everywhere.
    'logo' => 'logo1.png',       // for light backgrounds
    'logo_dark' => 'logo2.png',  // for dark backgrounds
    'favicon' => 'favicon.ico',
];
