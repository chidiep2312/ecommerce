<?php

namespace App\Exceptions;

use App\Exceptions\EmptyCartException;
use App\Exceptions\BrandHasProductsException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\ProductUnavailableException;
use App\Exceptions\ProductAlreadyReviewedException;
use App\Exceptions\ProductNotPurchasedException;
use App\Exceptions\InvalidOrderStatusTransitionException;
use App\Exceptions\CannotModifyOwnAccountException;
use App\Exceptions\InvalidUserRoleTransitionException;


class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (
            BrandHasProductsException $exception,
            $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'data' => null,
                    'errors' => null,
                ], 409);
            }

            return null;
        });

        $this->renderable(function (
            ProductUnavailableException $exception,
            $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'data' => null,
                    'errors' => null,
                ], 409);
            }

            return null;
        });
        $this->renderable(function (
            EmptyCartException $exception,
            $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'data' => null,
                    'errors' => null,
                ], 409);
            }

            return null;
        });
        $this->renderable(function (
            InsufficientStockException $exception,
            $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'data' => null,
                    'errors' => null,
                ], 409);
            }

            return null;
        });
        $this->renderable(function (
            InvalidVoucherException $exception,
            $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'data' => null,
                    'errors' => null,
                ], 422);
            }

            return null;
        });

        $this->renderable(function (
            ProductNotPurchasedException $exception,
            $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'data' => null,
                    'errors' => null,
                ], 403);
            }

            return null;
        });

        $this->renderable(function (
            ProductAlreadyReviewedException $exception,
            $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'data' => null,
                    'errors' => null,
                ], 409);
            }

            return null;
        });
        $this->renderable(function (
            InvalidOrderStatusTransitionException $exception,
            $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'data' => null,
                    'errors' => null,
                ], 409);
            }

            return null;
        });
        $this->renderable(function (
            CannotModifyOwnAccountException $exception,
            $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'data' => null,
                    'errors' => null,
                ], 409);
            }

            return null;
        });

        $this->renderable(function (
            InvalidUserRoleTransitionException $exception,
            $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'data' => null,
                    'errors' => null,
                ], 409);
            }

            return null;
        });
    }
}
