<?php

namespace App\Http\Middleware;
use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa đăng nhập.',
                'data' => null,
                'errors' => null,
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
         if ($user->status !== UserStatus::Active) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'success' => false,
                'message' => 'Tài khoản đã bị khóa hoặc không còn hoạt động.',
                'data' => null,
                'errors' => null,
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
