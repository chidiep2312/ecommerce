<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa đăng nhập.',
                'data' => null,
                'errors' => null,
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $currentRole = $user->role->value;

        if (! in_array($currentRole, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập chức năng này.',
                'data' => null,
                'errors' => null,
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}