<?php

declare(strict_types=1);

namespace App\Domains\Support\Actions;

use App\Domains\Support\Data\SubmitSupportRequestData;
use App\Domains\Support\Events\SupportRequestSubmitted;
use App\Domains\Support\Models\SupportRequest;
use Illuminate\Support\Facades\DB;

/**
 * Record a member's help-desk request for the current club and notify support.
 *
 * tenant_id is filled automatically by BelongsToTenant from the active tenancy context.
 * The SupportRequestSubmitted event fires after commit (handled by a queued listener
 * that emails the support inbox).
 */
final class SubmitSupportRequest
{
    public function handle(SubmitSupportRequestData $data): SupportRequest
    {
        return DB::transaction(function () use ($data): SupportRequest {
            $request = SupportRequest::create([
                'user_id' => $data->userId,
                'category' => $data->category,
                'subject' => $data->subject,
                'message' => $data->message,
            ]);

            SupportRequestSubmitted::dispatch($request);

            return $request;
        });
    }
}
