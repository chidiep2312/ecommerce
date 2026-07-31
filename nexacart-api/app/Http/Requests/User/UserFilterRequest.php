<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => [
                'nullable',
                'string',
                'max:255',
            ],

            'role' => [
                'nullable',
                Rule::enum(UserRole::class),
            ],

            'status' => [
                'nullable',
                Rule::enum(UserStatus::class),
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'newest',
                    'oldest',
                    'name_asc',
                    'name_desc',
                ]),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string'
                => 'Từ khóa tìm kiếm phải là chuỗi.',

            'keyword.max'
                => 'Từ khóa tìm kiếm tối đa 255 ký tự.',

            'sort.in'
                => 'Kiểu sắp xếp không hợp lệ.',

            'per_page.integer'
                => 'Số bản ghi trên mỗi trang phải là số nguyên.',

            'per_page.min'
                => 'Mỗi trang phải có ít nhất 1 bản ghi.',

            'per_page.max'
                => 'Mỗi trang chỉ được có tối đa 100 bản ghi.',
        ];
    }
}