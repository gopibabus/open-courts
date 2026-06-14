<?php

declare(strict_types=1);

namespace App\Http\Controllers\Facilities;

use App\Domains\Facilities\Actions\CreateCourt;
use App\Domains\Facilities\Actions\UpdateCourt;
use App\Domains\Facilities\Models\Court;
use App\Http\Controllers\Controller;
use App\Http\Requests\Facilities\StoreCourtRequest;
use App\Http\Requests\Facilities\UpdateCourtRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourtController extends Controller
{
    /**
     * List every court in the current club with its weekly windows and any blackouts.
     * Visible to any authenticated member (no court.manage needed).
     */
    public function index(): Response
    {
        $courts = Court::query()
            ->with([
                'availability' => fn ($q) => $q->orderBy('day_of_week')->orderBy('opens_at'),
                'blackouts' => fn ($q) => $q->orderBy('starts_at'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Court $court) => [
                'id' => $court->id,
                'name' => $court->name,
                'surface' => $court->surface,
                'is_active' => $court->is_active,
                'availability' => $court->availability->map(fn ($w) => [
                    'id' => $w->id,
                    'day_of_week' => $w->day_of_week,
                    'opens_at' => $w->opens_at?->format('H:i'),
                    'closes_at' => $w->closes_at?->format('H:i'),
                ])->values(),
                'blackouts' => $court->blackouts->map(fn ($b) => [
                    'id' => $b->id,
                    'starts_at' => $b->starts_at?->toIso8601String(),
                    'ends_at' => $b->ends_at?->toIso8601String(),
                    'reason' => $b->reason,
                ])->values(),
            ]);

        return Inertia::render('facilities/courts/index', [
            'courts' => $courts,
            'canManage' => $this->userCanManage(),
        ]);
    }

    public function store(StoreCourtRequest $request, CreateCourt $createCourt): RedirectResponse
    {
        $createCourt->handle($request->toData());

        return back();
    }

    public function update(UpdateCourtRequest $request, Court $court, UpdateCourt $updateCourt): RedirectResponse
    {
        $updateCourt->handle($court, $request->toData());

        return back();
    }

    public function destroy(Court $court): RedirectResponse
    {
        $court->delete();

        return back();
    }

    private function userCanManage(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('court.manage');
    }
}
