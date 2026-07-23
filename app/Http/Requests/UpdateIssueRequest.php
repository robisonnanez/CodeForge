<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['sometimes', 'required', Rule::in(['open', 'closed'])],
        ];
    }
}
