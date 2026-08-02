Cách viết một security finding

Một finding tốt phải trả lời được 6 câu hỏi:

Vấn đề nằm ở đâu?
Bằng chứng nào chứng minh?
Có thể khai thác bằng cách nào?
Hậu quả là gì?
Mức độ nghiêm trọng ra sao?
Sửa và kiểm tra lại như thế nào?




## FIND-001 – Authentication chưa có rate limiter riêng

* Status: Confirmed
* Asset: User account
* Evidence:
  `app/Providers/RouteServiceProvider.php`
  `routes/api.php`
* Current control:   API sử dụng limiter chung 60 requests/phút
* Gap: Endpoint đăng nhập chưa có giới hạn riêng theo email kết hợp địa chỉ IP
* Attack path:
  Kẻ tấn công gửi liên tục nhiều mật khẩu tới `POST /api/v1/login`
* Impact: 4
* Likelihood: 4
* Score: 16
* Priority: Must
* Remediation (Khắc phục):
  Tạo rate limiter riêng cho endpoint đăng nhập
  Xác định khóa giới hạn dựa trên email đã chuẩn hóa kết hợp địa chỉ IP
  Có thể áp dụng giới hạn khoảng 5–10 lần thử/phút
* Verification:
  Gửi request đăng nhập vượt quá ngưỡng
  Hệ thống trả HTTP `429 Too Many Requests`
  Request từ email hoặc IP khác vẫn được xử lý theo limiter tương ứng

---

## FIND-002 – Nguy cơ Customer truy cập order của Customer khác

* Status: Verified control
* Asset: Customer order
* Evidence:
  `tests/Feature/Security/Orders/OrderAuthorizationTest.php`
  `app/Policies/OrderPolicy.php`
  Controller xử lý API xem chi tiết order của customer
* Current control:
  Hệ thống kiểm tra `order.user_id` với ID của người dùng đang đăng nhập
  Kiểm thử xác nhận Customer A nhận `403` khi truy cập order của Customer B
* Gap:
  Cần bảo đảm mọi endpoint liên quan đến order đều dùng cùng cơ chế kiểm tra quyền sở hữu
* Attack path:
  Customer thay đổi `{order}` trong URL thành ID order của người dùng khác
* Impact: 5
* Likelihood: 3
* Score: 15
* Priority: Must
* Remediation:
  Duy trì kiểm tra quyền sở hữu trong `OrderPolicy`
  Áp dụng policy cho các thao tác xem, hủy và cập nhật order
  Không chỉ dựa vào việc frontend ẩn dữ liệu
* Verification:
  Customer A truy cập order của Customer B nhận `403` hoặc `404`
  Customer A vẫn xem được order của chính mình với HTTP `200`

---

## FIND-003 – Nguy cơ Seller truy cập order của Seller khác

* Status: To verify
* Asset: Seller order
* Evidence:
  `app/Policies/OrderPolicy.php`
  Controller xử lý API order của seller
  `routes/api.php`
* Current control: Seller API yêu cầu xác thực và role seller
* Gap:
  Middleware role chỉ xác nhận người dùng là seller, không chứng minh order thuộc seller đó
* Attack path:
  Seller A thay đổi `{order}` trong URL thành ID order thuộc Seller B
  Gửi request xem chi tiết order qua seller API
* Impact: 5
* Likelihood: 3
* Score: 15
* Priority: Must
* Remediation:
  Kiểm tra `order.seller_id === auth()->id()`
  Áp dụng `OrderPolicy::viewSellerOrder()` hoặc policy tương đương
  Không truy vấn order chỉ bằng khóa chính mà không giới hạn seller
* Verification:
  Seller A xem order của Seller B nhận `403` hoặc `404`
  Seller A xem order của chính mình nhận `200`

---

## FIND-004 – Nguy cơ Seller cập nhật order của Seller khác

* Status: To verify
* Asset: Seller order
* Evidence:
  `app/Policies/OrderPolicy.php`
  Controller hoặc service cập nhật trạng thái order
  Route cập nhật order dành cho seller
* Current control: Endpoint yêu cầu đăng nhập và role seller
* Gap: Có thể thiếu kiểm tra quyền sở hữu trước khi cập nhật order
* Attack path:
  Seller A gửi `PATCH` hoặc `PUT` tới order thuộc Seller B
  Thay đổi trạng thái hoặc thông tin xử lý đơn hàng
* Impact: 5
* Likelihood: 3
* Score: 15
* Priority: Must
* Remediation:
  Kiểm tra quyền sở hữu bằng policy trước khi cập nhật
  Tải order theo cả `id` và `seller_id`
  Thực hiện kiểm tra trạng thái hợp lệ trong service
* Verification:
  Seller A cập nhật order Seller B nhận `403` hoặc `404`
  Dữ liệu order Seller B không thay đổi
  Seller A vẫn cập nhật được order của mình theo quy trình hợp lệ

---

## FIND-005 – Nguy cơ Seller cập nhật product của Seller khác

* Status: To verify
* Asset: Product
* Evidence:
  `app/Policies/ProductPolicy.php`
  Controller quản lý product của seller
  Route cập nhật product dành cho seller
* Current control:Endpoint yêu cầu role seller
* Gap:Role seller không đủ để xác định quyền sở hữu product
* Attack path:
  Seller A thay ID product trong URL bằng product thuộc Seller B
  Gửi request thay đổi giá, tồn kho, trạng thái hoặc nội dung product
* Impact: 5
* Likelihood: 4
* Score: 20
* Priority: Must
* Remediation:
  Kiểm tra `product.seller_id === auth()->id()`
  Áp dụng `ProductPolicy::update()`
  Không nhận `seller_id` từ client trong payload cập nhật
* Verification:
  Seller A cập nhật product Seller B nhận `403` hoặc `404`
  Product Seller B không thay đổi trong database
  Seller A cập nhật product của mình thành công

---

## FIND-006 – Nguy cơ Customer cập nhật cart item của Customer khác

* Status: To verify
* Asset: Shopping cart
* Evidence:

  Model và controller xử lý `CartItem`
  Route cập nhật hoặc xóa cart item
  Policy hoặc service quản lý giỏ hàng
* Current control: Endpoint yêu cầu customer đăng nhập
* Gap: Có thể thiếu kiểm tra cart item thuộc giỏ hàng của người dùng hiện tại
* Attack path:
  Customer A thay `{cartItem}` bằng ID cart item của Customer B
  Gửi request cập nhật quantity hoặc xóa cart item
* Impact: 3
* Likelihood: 4
* Score: 12
* Priority: Must
* Remediation:
  Truy vấn cart item thông qua cart của người dùng đăng nhập
  Kiểm tra `cart.user_id === auth()->id()`
  Áp dụng policy cho thao tác update và delete
* Verification:
  Customer A cập nhật cart item Customer B nhận `403` hoặc `404`
  Quantity của cart item Customer B không thay đổi
  Customer A vẫn cập nhật được cart item của mình

---


## FIND-007 – Tài khoản inactive có thể truy cập protected API

* Status: Verified control
* Asset: Protected API and user account
* Evidence:

  Middleware kiểm tra trạng thái tài khoản
  Response: `Tài khoản đã bị khóa hoặc không còn hoạt động.`
* Current control:
  Tài khoản không active bị middleware chặn với HTTP `403`
* Gap:
  Cần bảo đảm middleware được gắn vào toàn bộ protected API, không chỉ một số nhóm route
* Attack path:
  Người dùng bị khóa tiếp tục sử dụng access token cũ để gọi API
* Impact: 4
* Likelihood: 3
* Score: 12
* Priority: Must
* Remediation:
  Gắn middleware kiểm tra trạng thái sau `auth:sanctum`
  Áp dụng nhất quán cho customer, seller và admin API
  Cân nhắc thu hồi token khi khóa tài khoản
* Verification:
  Active user gọi protected API thành công
  Inactive hoặc blocked user nhận `403`
  Guest không có token nhận `401`

---

## FIND-008 – Customer có thể gọi Seller hoặc Admin API

* Status: Verified control
* Asset: Seller and admin functions
* Evidence:

  Middleware kiểm tra role
  Các route group customer, seller và admin trong `routes/api.php`
* Current control:

  Các nhóm route có thể đã sử dụng middleware role
* Gap:
  Cần xác minh middleware so sánh đúng enum và được áp dụng cho mọi route nhạy cảm
* Attack path:
  Customer gửi trực tiếp request tới seller API hoặc admin API
  Không đi qua giao diện frontend
* Impact: 5
* Likelihood: 4
* Score: 20
* Priority: Must
* Remediation:
  Áp dụng middleware role riêng cho từng nhóm route
  Kiểm tra enum role nhất quán
  Bổ sung policy cho từng tài nguyên, không chỉ dựa vào role
* Verification:
  Customer gọi seller API nhận `403`
  Customer gọi admin API nhận `403`
  Seller hợp lệ gọi seller API thành công
  Admin hợp lệ gọi admin API thành công

---

## FIND-009 – Seller có thể gọi Admin API

* Status: Verified control
* Asset: Administrative functions
* Evidence:
  Admin route group trong `routes/api.php`
  Middleware kiểm tra role
* Current control:
  Admin API yêu cầu xác thực
* Gap:
  Xác thực chỉ chứng minh danh tính, không chứng minh người dùng có role admin
* Attack path:
  Seller sử dụng token hợp lệ gửi request trực tiếp đến admin API
* Impact: 5
* Likelihood: 3
* Score: 15
* Priority: Must
* Remediation:

  Gắn middleware `role:admin` cho toàn bộ admin route
  Bổ sung policy hoặc gate cho chức năng nhạy cảm
  Không kiểm tra role chỉ ở frontend
* Verification:

  Seller gọi admin API nhận `403`
  Customer gọi admin API nhận `403`
  Admin active gọi cùng endpoint thành công

---

## FIND-010 – Checkout có thể tin tưởng giá do client cung cấp

* Status: Verified control
* Asset: Order total and revenue
* Evidence:
  `app/Services/CheckoutService.php`
  Checkout request
  Logic tạo `order_items`
* Current control:Product được lấy từ database khi checkout
* Gap:

  * Cần xác minh backend không sử dụng `price`, `subtotal`, `discount` hoặc `total` do client gửi
* Attack path:

  Client sửa request checkout và gửi giá product thấp hơn giá database
  Backend tạo order dựa trên giá giả
* Impact: 5
* Likelihood: 5
* Score: 25
* Priority: Must
* Remediation:

  Chỉ nhận `product_id` và `quantity` từ client
  Đọc giá hiện tại từ database trong transaction
  Lưu snapshot giá database vào `order_items`
  Tính subtotal, discount và total hoàn toàn ở backend
* Verification:

  Client gửi giá giả, ví dụ `1.000`
  Product trong database có giá `500.000`
  `order_items.price` vẫn bằng `500.000`
  Tổng order được tính từ giá database

---

## FIND-011 – Checkout có thể tạo order khi không đủ stock

* Status: Verified control
* Asset: Product inventory and order integrity
* Evidence:
  `app/Services/CheckoutService.php`
  Logic `lockForUpdate()`
  Exception xử lý không đủ tồn kho
* Current control:
  Checkout có kiểm tra stock và sử dụng database transaction
* Gap: Cần xác minh order và order item không được tạo một phần khi stock không đủ
* Attack path:

  Customer checkout quantity lớn hơn stock hiện tại
  Hoặc gửi đồng thời nhiều request checkout
* Impact: 5
* Likelihood: 4
* Score: 20
* Priority: Must
* Remediation:

  Khóa product bằng `lockForUpdate()`
  Kiểm tra stock bên trong transaction
  Ném exception trước khi tạo order
  Rollback toàn bộ transaction khi một product không đủ stock
* Verification:

  Quantity yêu cầu lớn hơn stock
  API trả `422` hoặc mã lỗi nghiệp vụ tương ứng
  Không có order mới
  Không có order item mới
  Stock không thay đổi

---

## FIND-012 – Voucher hết lượt có thể tiếp tục được sử dụng

* Status: To verify
* Asset: Voucher budget and order discount
* Evidence:

  * `app/Services/VoucherService.php`
  * `app/Services/CheckoutService.php`
  * Bảng `vouchers` và `voucher_usages`
* Current control:

  * Voucher có `usage_limit`, `used_count` và giới hạn theo user
* Gap:

  * Cần xác minh giới hạn được kiểm tra và cập nhật nguyên tử trong transaction
* Attack path:

  * Nhiều customer đồng thời sử dụng voucher khi chỉ còn một lượt
  * Hoặc client gửi voucher đã có `used_count >= usage_limit`
* Impact: 4
* Likelihood: 4
* Score: 16
* Priority: Must
* Remediation:

  * Khóa voucher bằng `lockForUpdate()`
  * Kiểm tra `used_count < usage_limit`
  * Kiểm tra thời gian hiệu lực, trạng thái và giới hạn theo user
  * Chỉ tăng `used_count` khi order được tạo thành công
* Verification:

  * Voucher có `used_count` bằng `usage_limit`
  * Checkout bị từ chối
  * Không tạo `voucher_usage`
  * Không tăng `used_count`
  * Order không nhận discount từ voucher

---

## FIND-013 – Order có thể chuyển trạng thái trái quy trình

* Status: To verify
* Asset: Order workflow
* Evidence:
  Enum `OrderStatus`
  Service hoặc controller cập nhật trạng thái order
  Seller order API
* Current control:

  Request có thể giới hạn status bằng enum
* Gap:

  Kiểm tra enum chỉ xác nhận trạng thái tồn tại, không xác nhận bước chuyển trạng thái hợp lệ
* Attack path:

  Seller chuyển trực tiếp order từ `pending` sang `completed`
  Chuyển order đã `cancelled` trở lại `shipping`
  Customer hoặc seller gửi trạng thái không phù hợp với role
* Impact: 4
* Likelihood: 4
* Score: 16
* Priority: Must
* Remediation:

  Xây dựng bảng chuyển trạng thái hợp lệ
  Kiểm tra trạng thái hiện tại, trạng thái đích và role thực hiện
  Cập nhật timestamp tương ứng trong cùng transaction
  Từ chối trạng thái không hợp lệ bằng lỗi nghiệp vụ
* Verification:

  Thử chuyển `pending → completed`
  API trả `422` hoặc lỗi nghiệp vụ tương ứng
  Status trong database vẫn là `pending`
  Chuyển `pending → confirmed` bởi seller sở hữu order thành công
