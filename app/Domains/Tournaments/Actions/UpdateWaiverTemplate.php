<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Data\WaiverTemplateData;
use App\Domains\Tournaments\Events\WaiverTemplateUpdated;
use App\Domains\Tournaments\Models\ClubWaiverTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Create or update the current club's waiver template (one row per club). tenant_id is filled
 * by BelongsToTenant on create. WaiverTemplateUpdated fires after commit. Past signatures are
 * untouched — each TournamentWaiver snapshots the clauses it was signed against.
 */
final class UpdateWaiverTemplate
{
    public function handle(WaiverTemplateData $data): ClubWaiverTemplate
    {
        return DB::transaction(function () use ($data): ClubWaiverTemplate {
            $template = ClubWaiverTemplate::current() ?? new ClubWaiverTemplate;
            $template->clauses = $data->clauses;
            $template->save();

            WaiverTemplateUpdated::dispatch($template);

            return $template;
        });
    }
}
