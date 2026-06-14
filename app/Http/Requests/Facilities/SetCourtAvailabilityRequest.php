<?php

declare(strict_types=1);

namespace App\Http\Requests\Facilities;

use App\Domains\Facilities\Data\AvailabilityWindowData;
use Illuminate\Foundation\Http\FormRequest;

class SetCourtAvailabilityRequest extends FormRequest
{
    /** Route is already guarded by `can:court.manage`. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'windows' => ['present', 'array'],
            'windows.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'windows.*.opens_at' => ['required', 'date_format:H:i'],
            'windows.*.closes_at' => ['required', 'date_format:H:i', 'after:windows.*.opens_at'],
        ];
    }

    /**
     * @return list<AvailabilityWindowData>
     */
    public function toWindows(): array
    {
        /** @var array<int, array{day_of_week: int|string, opens_at: string, closes_at: string}> $windows */
        $windows = (array) $this->input('windows', []);

        return array_map(
            fn (array $w): AvailabilityWindowData => new AvailabilityWindowData(
                dayOfWeek: (int) $w['day_of_week'],
                opensAt: (string) $w['opens_at'],
                closesAt: (string) $w['closes_at'],
            ),
            array_values($windows),
        );
    }
}
