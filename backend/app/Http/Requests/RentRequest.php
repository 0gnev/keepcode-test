<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RentRequest extends FormRequest
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
            'duration.required' => 'Rental duration is required.',
            'duration.integer' => 'Rental duration must be an integer.',
            'duration.in' => 'Rental duration must be one of the following: 4, 8, 12, or 24 hours.',
        ];
    }
}
