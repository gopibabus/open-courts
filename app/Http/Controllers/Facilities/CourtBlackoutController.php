<?php

declare(strict_types=1);

namespace App\Http\Controllers\Facilities;

use App\Domains\Facilities\Actions\AddCourtBlackout;
use App\Domains\Facilities\Models\CourtBlackout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Facilities\StoreCourtBlackoutRequest;
use Illuminate\Http\RedirectResponse;

class CourtBlackoutController extends Controller
{
    public function store(StoreCourtBlackoutRequest $request, AddCourtBlackout $addCourtBlackout): RedirectResponse
    {
        $addCourtBlackout->handle($request->toData());

        return back();
    }

    /**
     * {blackout} is resolved via route-model binding, tenant-scoped by the model's
     * global scope — a blackout from another club can never be resolved here.
     */
    public function destroy(CourtBlackout $blackout): RedirectResponse
    {
        $blackout->delete();

        return back();
    }
}
