<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenewRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'duration' => 'required|integer|in:4,8,12,24',
        ];
    }

    public function messages()
    {
        return [
            'duration.required' => 'Renewal duration is required.',
            'duration.integer' => 'Renewal duration must be an integer.',
            'duration.in' => 'Renewal duration must be one of the following: 4, 8, 12, or 24 hours.',
        ];
    }
}
