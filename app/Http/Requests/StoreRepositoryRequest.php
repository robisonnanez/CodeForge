<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepositoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'slug' => [
                'nullable',
                'string',
                'min:2',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('repositories', 'slug')->where('user_id', $this->user()?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'default_branch' => ['nullable', 'string', 'regex:/^[A-Za-z0-9._\\/-]+$/', 'max:80'],
        ];
    }
}
