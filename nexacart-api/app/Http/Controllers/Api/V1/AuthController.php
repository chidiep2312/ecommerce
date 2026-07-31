<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(
            $request->validated()
        );

        return response()->json([
       'data'=> [
                'user' => new UserResource(
                    $result['user']
                ),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
            'message'=>'Đăng ký tài khoản thành công.',
            'status'=> JsonResponse::HTTP_CREATED
     ]   );
    }

    public function login(
        LoginRequest $request
    ): JsonResponse {
        $result = $this->authService->login(
            $request->validated()
        );

        return response()->json([
            'data'=> [
                'user' => new UserResource(
                    $result['user']
                ),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
            'message'=> 'Đăng nhập thành công.',
            'status'=> JsonResponse::HTTP_OK
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin tài khoản thành công.',
            'data' => new UserResource($request->user()),
            'errors' => null,
        ]);
    }

    public function logout(
        Request $request
    ): JsonResponse {
        $this->authService->logout(
            $request->user()
        );

        return response()->json([
            'message'=> 'Đăng xuất thành công.'
       ] );
    }
}
