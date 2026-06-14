<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use App\Domains\Support\Data\SubmitSupportRequestData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a member's help-desk submission. The route is already gated by `auth`
 * (any club member may ask for help); this checks the shape only.
 */
class StoreSupportRequestRequest extends FormRequest
{
    /** The categories a member can file a request under. */
    public const CATEGORIES = ['booking', 'courts', 'tournaments', 'membership', 'billing', 'other'];

    /** Route is already guarded by `auth`. */
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
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function toData(): SubmitSupportRequestData
    {
        return new SubmitSupportRequestData(
            userId: (int) $this->user()->id,
            category: (string) $this->string('category'),
            subject: (string) $this->string('subject'),
            message: (string) $this->string('message'),
        );
    }
}
