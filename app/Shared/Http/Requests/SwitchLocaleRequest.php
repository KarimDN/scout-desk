<?php

namespace App\Shared\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SwitchLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public action: any visitor may choose their interface language.
        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(config('scoutdesk.locales'))],
        ];
    }
}