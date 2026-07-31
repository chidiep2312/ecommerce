<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\ProductImageController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\VoucherController;
use App\Http\Controllers\Api\V1\VoucherValidationController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\AdminDashboardController;
use App\Http\Controllers\Api\V1\SellerDashboardController;
use App\Http\Controllers\Api\V1\AdminUserController;
use App\Http\Controllers\Api\V1\AdminSellerRequestController;
use App\Http\Controllers\Api\V1\SellerRequestController;
use App\Http\Controllers\Api\V1\AdminSellerController;
use App\Http\Controllers\Api\V1\SellerInventoryController;
use App\Http\Controllers\Api\V1\SellerVoucherUsageController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {

    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'NexaCart API is running.',
            'data' => [
                'application' => config('app.name'),
                'api_version' => 'v1',
            ],
            'errors' => null,
        ]);
    });
    Route::get(
        '/products/{product:slug}/reviews',
        [ReviewController::class, 'index']
    );
    /*
    |--------------------------------------------------------------------------
    | Public authentication routes
    |--------------------------------------------------------------------------
    */

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    /*
    |--------------------------------------------------------------------------
    | Protected authentication routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum', 'active.account')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    /*
|--------------------------------------------------------------------------
| Public category routes
|--------------------------------------------------------------------------
*/
    Route::get('/products', [
        ProductController::class,
        'index',
    ]);

    Route::get('/products/{product:slug}', [
        ProductController::class,
        'show',
    ]);
    Route::get('/categories', [
        CategoryController::class,
        'index',
    ]);

    Route::get('/categories/{category:slug}', [
        CategoryController::class,
        'show',
    ]);

    Route::get('/brands', [
        BrandController::class,
        'index',
    ]);

    Route::get('/brands/{brand:slug}', [
        BrandController::class,
        'show',
    ]);
    Route::middleware([
        'auth:sanctum',
        'active.account',
        'role:customer',
    ])
        ->prefix('seller-requests')
        ->group(function () {
            Route::post(
                '/',
                [SellerRequestController::class, 'store']
            );

            Route::get(
                '/current',
                [SellerRequestController::class, 'current']
            );

            Route::get(
                '/history',
                [SellerRequestController::class, 'history']
            );
        });
    Route::middleware([
        'auth:sanctum',
        'active.account',
        'role:customer',
    ])->prefix('customer')->group(function () {
        Route::get('/cart', [
            CartController::class,
            'show',
        ]);

        Route::post('/cart/items', [
            CartController::class,
            'addItem',
        ]);

        Route::patch('/cart/items/{cartItem}', [
            CartController::class,
            'updateItem',
        ]);

        Route::delete('/cart/items/{cartItem}', [
            CartController::class,
            'removeItem',
        ]);

        Route::delete('/cart', [
            CartController::class,
            'clear',
        ]);
        Route::post(
            '/vouchers/validate',
            VoucherValidationController::class
        );

        Route::post('/checkout', [
            CheckoutController::class,
            'store',
        ]);

        Route::get('/orders', [
            OrderController::class,
            'customerIndex',
        ]);

        Route::get('/orders/{order}', [
            OrderController::class,
            'show',
        ]);

        Route::patch(
            '/orders/{order}/cancel',
            [OrderController::class, 'cancel']
        );
        Route::post(
            '/products/{product}/reviews',
            [ReviewController::class, 'store']
        );

        Route::patch(
            '/reviews/{review}',
            [ReviewController::class, 'update']
        );

        Route::delete(
            '/reviews/{review}',
            [ReviewController::class, 'destroy']
        );
    });


    Route::middleware([
        'auth:sanctum',
        'active.account',
        'role:seller',
    ])->prefix('seller')->group(function () {
        Route::get('/products', [
            ProductController::class,
            'sellerIndex',
        ]);

        Route::post('/products', [
            ProductController::class,
            'store',
        ]);

        Route::get('/products/{product}', [
            ProductController::class,
            'sellerShow',
        ]);

        Route::put('/products/{product}', [
            ProductController::class,
            'update',
        ]);

        Route::patch('/products/{product}', [
            ProductController::class,
            'update',
        ]);

        Route::delete('/products/{product}', [
            ProductController::class,
            'destroy',
        ]);
        Route::post(
            '/products/{product}/images',
            [ProductImageController::class, 'store']
        );

        Route::patch(
            '/products/{product}/images/{image}/main',
            [ProductImageController::class, 'setMain']
        );

        Route::delete(
            '/products/{product}/images/{image}',
            [ProductImageController::class, 'destroy']
        );
        Route::get('/orders', [
            OrderController::class,
            'sellerIndex',
        ]);

        Route::get('/orders/{order}', [
            OrderController::class,
            'show',
        ]);

        Route::patch(
            '/orders/{order}/status',
            [OrderController::class, 'updateStatus']
        );
        Route::get(
            '/dashboard',
            SellerDashboardController::class
        );
        Route::get(
            '/inventory',
            [
                SellerInventoryController::class,
                'index',
            ]
        );

        Route::patch(
            '/inventory/{product}',
            [
                SellerInventoryController::class,
                'update',
            ]
        );
        Route::get(
            '/voucher-usages/summary',
            [
                SellerVoucherUsageController::class,
                'summary',
            ]
        );

        Route::get(
            '/voucher-usages',
            [
                SellerVoucherUsageController::class,
                'index',
            ]
        );
    });

    /*
|--------------------------------------------------------------------------
| Admin category routes
|--------------------------------------------------------------------------
*/
    Route::middleware([
        'auth:sanctum',
        'active.account',
        'role:admin',
    ])
        ->prefix('admin/seller-requests')
        ->group(function () {
            Route::get(
                '/',
                [
                    AdminSellerRequestController::class,
                    'index',
                ]
            );

            Route::get(
                '/{sellerRequest}',
                [
                    AdminSellerRequestController::class,
                    'show',
                ]
            );

            Route::patch(
                '/{sellerRequest}/approve',
                [
                    AdminSellerRequestController::class,
                    'approve',
                ]
            );

            Route::patch(
                '/{sellerRequest}/reject',
                [
                    AdminSellerRequestController::class,
                    'reject',
                ]
            );
        });
    Route::middleware([
        'auth:sanctum',
        'active.account',
        'role:admin',
    ])->prefix('admin')->group(function () {
        Route::get('/brands', [
            BrandController::class,
            'adminIndex',
        ]);

        Route::post('/brands', [
            BrandController::class,
            'store',
        ]);

        Route::put('/brands/{brand}', [
            BrandController::class,
            'update',
        ]);

        Route::patch('/brands/{brand}', [
            BrandController::class,
            'update',
        ]);

        Route::delete('/brands/{brand}', [
            BrandController::class,
            'destroy',
        ]);


        Route::get('/categories', [
            CategoryController::class,
            'adminIndex',
        ]);

        Route::post('/categories', [
            CategoryController::class,
            'store',
        ]);

        Route::put('/categories/{category}', [
            CategoryController::class,
            'update',
        ]);

        Route::patch('/categories/{category}', [
            CategoryController::class,
            'update',
        ]);

        Route::delete('/categories/{category}', [
            CategoryController::class,
            'destroy',
        ]);
        Route::apiResource(
            'vouchers',
            VoucherController::class
        );
        Route::get('/orders', [
            OrderController::class,
            'adminIndex',
        ]);

        Route::get('/orders/{order}', [
            OrderController::class,
            'show',
        ]);

        Route::patch(
            '/orders/{order}/status',
            [OrderController::class, 'updateStatus']
        );
        Route::get(
            '/dashboard',
            AdminDashboardController::class
        );

        Route::get('/users', [
            AdminUserController::class,
            'index',
        ]);

        Route::get('/users/{user}', [
            AdminUserController::class,
            'show',
        ]);

        Route::patch(
            '/users/{user}/status',
            [
                AdminUserController::class,
                'updateStatus',
            ]
        );
        Route::patch(
            '/users/{user}/role',
            [
                AdminUserController::class,
                'updateRole',
            ]
        );
        Route::get('/sellers', [
            AdminSellerController::class,
            'index',
        ]);

        Route::get('/sellers/{seller}', [
            AdminSellerController::class,
            'show',
        ]);

        Route::patch(
            '/sellers/{seller}/status',
            [
                AdminSellerController::class,
                'updateStatus',
            ]
        );

        Route::get('/vouchers', [
            VoucherController::class,
            'index',
        ]);

        Route::post('/vouchers', [
            VoucherController::class,
            'store',
        ]);
        Route::get('/vouchers/{voucher}', [
            VoucherController::class,
            'show',
        ]);

        Route::patch(
            '/vouchers/{voucher}',
            [
                VoucherController::class,
                'update',
            ]
        );
        Route::delete('/vouchers/{voucher}', [
            VoucherController::class,
            'destroy',
        ]);

        Route::get('/products', [
            ProductController::class,
            'adminIndex',
        ]);

        Route::get('/products/{product}', [
            ProductController::class,
            'adminShow',
        ]);

        Route::patch(
            '/products/{product}/suspend',
            [
                ProductController::class,
                'suspend',
            ]
        );
        Route::patch(
            '/products/{product}/restore',
            [
                ProductController::class,
                'restore',
            ]
        );
    });
});
