<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserRoleRequest;
use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Http\Requests\User\UserFilterRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $adminUserService
    ) {}

    public function index(
        UserFilterRequest $request
    ): AnonymousResourceCollection {
        $users = $this->adminUserService
            ->paginate(
                $request->validated()
            );

        return AdminUserResource::collection(
            $users
        );
    }

    public function show(
        User $user
    ): AdminUserResource {
        $user = $this->adminUserService
            ->loadDetail($user);

        return new AdminUserResource($user);
    }

    public function updateStatus(
        UpdateUserStatusRequest $request,
        User $user
    ): JsonResponse {
        $data = $request->validated();

        $newStatus = UserStatus::from(
            $data['status']
        );

        $user = $this->adminUserService
            ->updateStatus(
                $request->user(),
                $user,
                $newStatus
            );

        return response()->json([
            'success' => true,
            'message'
                => 'Cập nhật trạng thái tài khoản thành công.',
            'data'
                => new AdminUserResource($user),
            'errors' => null,
        ]);
    }

    public function updateRole(
        UpdateUserRoleRequest $request,
        User $user
    ): JsonResponse {
        $data = $request->validated();

        $newRole = UserRole::from(
            $data['role']
        );

        $user = $this->adminUserService
            ->updateRole(
                $request->user(),
                $user,
                $newRole
            );

        return response()->json([
            'success' => true,
            'message'
                => 'Cập nhật vai trò tài khoản thành công.',
            'data'
                => new AdminUserResource($user),
            'errors' => null,
        ]);
    }
}