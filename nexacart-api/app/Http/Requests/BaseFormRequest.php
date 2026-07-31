<?php

namespace App\Http\Requests;

use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

abstract class BaseFormRequest extends FormRequest
{
    protected function failedValidation(
        Validator $validator
    ): void {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Dữ liệu không hợp lệ.',
                errors: $validator->errors(),
                status: JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            )
        );
    }
}