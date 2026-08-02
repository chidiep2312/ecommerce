POST      _ignition/execute-solution ignition.executeSolution › Spatie\Laravel…
  GET|HEAD  _ignition/health-check ignition.healthCheck › Spatie\LaravelIgnition…
  POST      _ignition/update-config ignition.updateConfig › Spatie\LaravelIgniti…
  GET|HEAD  api/user ............................................................
  GET|HEAD  api/v1/admin/brands ............... Api\V1\BrandController@adminIndex
  POST      api/v1/admin/brands .................... Api\V1\BrandController@store
  PUT       api/v1/admin/brands/{brand} ........... Api\V1\BrandController@update
  PATCH     api/v1/admin/brands/{brand} ........... Api\V1\BrandController@update
  DELETE    api/v1/admin/brands/{brand} .......... Api\V1\BrandController@destroy
  GET|HEAD  api/v1/admin/categories ........ Api\V1\CategoryController@adminIndex
  POST      api/v1/admin/categories ............. Api\V1\CategoryController@store
  PUT       api/v1/admin/categories/{category} . Api\V1\CategoryController@update
  PATCH     api/v1/admin/categories/{category} . Api\V1\CategoryController@update
  DELETE    api/v1/admin/categories/{category} Api\V1\CategoryController@destroy
  GET|HEAD  api/v1/admin/dashboard .............. Api\V1\AdminDashboardController
  GET|HEAD  api/v1/admin/orders ............... Api\V1\OrderController@adminIndex
  GET|HEAD  api/v1/admin/orders/{order} ............. Api\V1\OrderController@show
  PATCH     api/v1/admin/orders/{order}/status Api\V1\OrderController@updateStat…
  GET|HEAD  api/v1/admin/products ........... Api\V1\ProductController@adminIndex
  GET|HEAD  api/v1/admin/products/{product} .. Api\V1\ProductController@adminShow
  PATCH     api/v1/admin/products/{product}/restore Api\V1\ProductController@res…
  PATCH     api/v1/admin/products/{product}/suspend Api\V1\ProductController@sus…
  GET|HEAD  api/v1/admin/seller-requests Api\V1\AdminSellerRequestController@ind…
  GET|HEAD  api/v1/admin/seller-requests/{sellerRequest} Api\V1\AdminSellerReque…
  PATCH     api/v1/admin/seller-requests/{sellerRequest}/approve Api\V1\AdminSel…
  PATCH     api/v1/admin/seller-requests/{sellerRequest}/reject Api\V1\AdminSell…
  GET|HEAD  api/v1/admin/sellers ............. Api\V1\AdminSellerController@index
  GET|HEAD  api/v1/admin/sellers/{seller} ..... Api\V1\AdminSellerController@show
  PATCH     api/v1/admin/sellers/{seller}/status Api\V1\AdminSellerController@up…
  GET|HEAD  api/v1/admin/users ................. Api\V1\AdminUserController@index
  GET|HEAD  api/v1/admin/users/{user} ........... Api\V1\AdminUserController@show
  PATCH     api/v1/admin/users/{user}/role Api\V1\AdminUserController@updateRole
  PATCH     api/v1/admin/users/{user}/status Api\V1\AdminUserController@updateSt…
  GET|HEAD  api/v1/admin/vouchers ................ Api\V1\VoucherController@index
  POST      api/v1/admin/vouchers ................ Api\V1\VoucherController@store
  GET|HEAD  api/v1/admin/vouchers/{voucher} ....... Api\V1\VoucherController@show
  PATCH     api/v1/admin/vouchers/{voucher} ..... Api\V1\VoucherController@update
  DELETE    api/v1/admin/vouchers/{voucher} .... Api\V1\VoucherController@destroy
  GET|HEAD  api/v1/brands .......................... Api\V1\BrandController@index
  GET|HEAD  api/v1/brands/{brand} ................... Api\V1\BrandController@show
  GET|HEAD  api/v1/categories ................... Api\V1\CategoryController@index
  GET|HEAD  api/v1/categories/{category} ......... Api\V1\CategoryController@show
  GET|HEAD  api/v1/customer/cart ..................... Api\V1\CartController@show
  DELETE    api/v1/customer/cart .................... Api\V1\CartController@clear
  POST      api/v1/customer/cart/items ............ Api\V1\CartController@addItem
  PATCH     api/v1/customer/cart/items/{cartItem} Api\V1\CartController@updateIt…
  DELETE    api/v1/customer/cart/items/{cartItem} Api\V1\CartController@removeIt…
  POST      api/v1/customer/checkout ............ Api\V1\CheckoutController@store
  GET|HEAD  api/v1/customer/orders ......... Api\V1\OrderController@customerIndex
  GET|HEAD  api/v1/customer/orders/{order} .......... Api\V1\OrderController@show
  PATCH     api/v1/customer/orders/{order}/cancel . Api\V1\OrderController@cancel
  POST      api/v1/customer/products/{product}/reviews Api\V1\ReviewController@s…
  PATCH     api/v1/customer/reviews/{review} ..... Api\V1\ReviewController@update
  DELETE    api/v1/customer/reviews/{review} .... Api\V1\ReviewController@destroy
  POST      api/v1/customer/vouchers/validate Api\V1\VoucherValidationController
  GET|HEAD  api/v1/health .......................................................
  POST      api/v1/login ............................ Api\V1\AuthController@login
  POST      api/v1/logout .......................... Api\V1\AuthController@logout
  GET|HEAD  api/v1/products ...................... Api\V1\ProductController@index
  GET|HEAD  api/v1/products/{product} ............. Api\V1\ProductController@show
  GET|HEAD  api/v1/products/{product}/reviews ..... Api\V1\ReviewController@index
  GET|HEAD  api/v1/profile ........................ Api\V1\AuthController@profile
  POST      api/v1/register ...................... Api\V1\AuthController@register
  POST      api/v1/seller-requests ......... Api\V1\SellerRequestController@store
  GET|HEAD  api/v1/seller-requests/current Api\V1\SellerRequestController@current
  GET|HEAD  api/v1/seller-requests/history Api\V1\SellerRequestController@history
  GET|HEAD  api/v1/seller/dashboard ............ Api\V1\SellerDashboardController
  GET|HEAD  api/v1/seller/inventory ...... Api\V1\SellerInventoryController@index
  PATCH     api/v1/seller/inventory/{product} Api\V1\SellerInventoryController@u…
  GET|HEAD  api/v1/seller/orders ............. Api\V1\OrderController@sellerIndex
  GET|HEAD  api/v1/seller/orders/{order} ............ Api\V1\OrderController@show
  PATCH     api/v1/seller/orders/{order}/status Api\V1\OrderController@updateSta…
  GET|HEAD  api/v1/seller/products ......... Api\V1\ProductController@sellerIndex
  POST      api/v1/seller/products ............... Api\V1\ProductController@store
  GET|HEAD  api/v1/seller/products/{product} Api\V1\ProductController@sellerShow
  PUT       api/v1/seller/products/{product} .... Api\V1\ProductController@update
  PATCH     api/v1/seller/products/{product} .... Api\V1\ProductController@update
  DELETE    api/v1/seller/products/{product} ... Api\V1\ProductController@destroy
  POST      api/v1/seller/products/{product}/images Api\V1\ProductImageControlle…
  DELETE    api/v1/seller/products/{product}/images/{image} Api\V1\ProductImageC…
  PATCH     api/v1/seller/products/{product}/images/{image}/main Api\V1\ProductI…
  GET|HEAD  api/v1/seller/voucher-usages Api\V1\SellerVoucherUsageController@ind…
  GET|HEAD  api/v1/seller/voucher-usages/summary Api\V1\SellerVoucherUsageContro…
  GET|HEAD  sanctum/csrf-cookie sanctum.csrf-cookie › Laravel\Sanctum › CsrfCook…

