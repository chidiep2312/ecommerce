# NexaCart

NexaCart là dự án website thương mại điện tử đa người bán (**multi-vendor e-commerce**) được xây dựng với **Laravel REST API** và **Vue.js 3**.

Dự án tập trung vào các bài toán backend thực tế như **authentication, authorization, checkout, quản lý đơn hàng, thanh toán, phân quyền Seller/Admin, database transaction, testing và deployment**.

## Tech Stack

### Backend

* PHP
* Laravel
* RESTful API
* Laravel Sanctum
* MySQL
* PHPUnit

### Frontend

* Vue.js 3
* Pinia
* Axios
* Vite

### Deployment

* Ubuntu VPS
* Nginx
* Git / GitHub

## Main Features

### Customer

* Đăng ký, đăng nhập
* Xem và tìm kiếm sản phẩm
* Quản lý giỏ hàng
* Checkout và đặt hàng
* Theo dõi trạng thái đơn hàng
* Thanh toán qua VNPay mô phỏng

### Seller

* Đăng ký trở thành người bán
* Quản lý sản phẩm
* Quản lý đơn hàng thuộc cửa hàng
* Xác nhận và cập nhật trạng thái đơn hàng
* Theo dõi doanh thu và sản phẩm bán chạy

### Admin

* Quản lý người dùng
* Quản lý Seller
* Phê duyệt yêu cầu trở thành Seller
* Quản lý đơn hàng toàn hệ thống
* Theo dõi dashboard và số liệu hoạt động

## Backend Highlights

Một số nội dung kỹ thuật chính được triển khai trong dự án:

* RESTful API với Validation và Resource
* Authentication bằng Laravel Sanctum
* Role-based Authorization cho Customer, Seller và Admin
* Kiểm tra quyền sở hữu tài nguyên giữa các Seller
* Tách business logic khỏi Controller bằng Service Layer
* Database Transaction cho các nghiệp vụ checkout
* `lockForUpdate()` để hạn chế race condition khi xử lý tồn kho
* Order State Transition để kiểm soát trạng thái đơn hàng
* Kiểm tra giá sản phẩm tại server để chống price tampering
* Idempotency cho checkout
* Rate Limiting cho các API nhạy cảm
* Feature Test và Security Test bằng PHPUnit
* Tích hợp Fake VNPay Gateway theo Payment Gateway abstraction
* Deploy ứng dụng lên Ubuntu VPS với Nginx

## Security Focus

Ngoài chức năng thương mại điện tử, dự án cũng được sử dụng để thực hành các vấn đề Application Security:

* Broken Access Control
* Cross-seller resource isolation
* Authentication & Authorization
* Input Validation
* Price Tampering
* Invalid Order State Transition
* Brute-force Protection
* Secure Checkout Flow

## Demo account
Customer:customer@nexacart.test
Seller:seller@nexacart.test
Admin:admin@nexacart.test
Password: password123
