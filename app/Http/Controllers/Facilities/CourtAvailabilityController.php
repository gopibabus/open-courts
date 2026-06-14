<?php

declare(strict_types=1);

namespace App\Http\Controllers\Facilities;

use App\Domains\Facilities\Actions\SetCourtAvailability;
use App\Domains\Facilities\Models\Court;
use App\Http\Controllers\Controller;
use App\Http\Requests\Facilities\SetCourtAvailabilityRequest;
use Illuminate\Http\RedirectResponse;

class CourtAvailabilityController extends Controller
{
    /**
     * Replace a court's entire weekly availability schedule. {court} is resolved via
     * route-model binding, which (through BelongsToTenant's global scope) only matches
     * a court in the current club.
     */
    public function update(
        SetCourtAvailabilityRequest $request,
        Court $court,
        SetCourtAvailability $setCourtAvailability,
    ): RedirectResponse {
        $setCourtAvailability->handle($court, $request->toWindows());

        return back();
    }
}
