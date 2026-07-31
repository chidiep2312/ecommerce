<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'comment' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required'
                => 'Điểm đánh giá không được để trống.',

            'rating.integer'
                => 'Điểm đánh giá phải là số nguyên.',

            'rating.min'
                => 'Điểm đánh giá tối thiểu là 1.',

            'rating.max'
                => 'Điểm đánh giá tối đa là 5.',

            'comment.max'
                => 'Nội dung đánh giá tối đa 2000 ký tự.',
        ];
    }
}