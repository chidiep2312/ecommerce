<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Đăng ký tài khoản customer mới.
     *
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => mb_strtolower(trim($data['email'])),
            'password' => $data['password'],
            'role' => UserRole::Customer,
            'status' => UserStatus::Active,
        ]);

        $token = $user
            ->createToken('registration-token')
            ->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Xác thực thông tin đăng nhập và tạo token.
     *
     * @return array{user: User, token: string}
     *
     * @throws AuthenticationException
     */
    public function login(array $data): array
    {
        $email = mb_strtolower(trim($data['email']));

        $user = User::query()
            ->where('email', $email)
            ->first();

        if (
            $user === null ||
            ! Hash::check($data['password'], $user->password)
        ) {
            throw new AuthenticationException(
                'Email hoặc mật khẩu không chính xác.'
            );
        }

        if ($user->status === UserStatus::Locked) {
            throw new AuthenticationException(
                'Tài khoản đã bị khóa.'
            );
        }

        $deviceName = $data['device_name'] ?? 'api-token';

        $token = $user
            ->createToken($deviceName)
            ->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Thu hồi token đang được sử dụng.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}