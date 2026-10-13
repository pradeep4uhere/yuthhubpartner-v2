<?php

namespace App\Http\Requests;

use App\Support\EventTransactionReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'review_status' => [
                'sometimes',
                'required',
                Rule::in(EventTransactionReviewStatus::values()),
            ],

            'field_type' => [
                'sometimes',
                'required',
                'integer',
            ],

            'industry_type' => [
                'sometimes',
                'required',
                'integer',
            ],

            'comment_type' => [
                'sometimes',
                'required',
                'string',
            ],

            'comment' => [
                'sometimes',
                'required',
                'string',
            ],
        ];
    }
}

