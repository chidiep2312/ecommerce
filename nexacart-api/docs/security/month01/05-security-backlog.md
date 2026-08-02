--MUST
| ID      | Công việc                                     | Điều kiện hoàn thành                                                                  |
| ------- | --------------------------------------------- | ------------------------------------------------------------------------------------- |
| SEC-001 V| Viết security regression tests                | Có test ownership, role, account status, login, checkout, voucher và order transition |
| SEC-002 V| Tạo limiter riêng cho authentication          | Login sai vượt ngưỡng trả `429`; giới hạn theo email + IP                             |
| SEC-003 V| Xóa hoặc sửa `/api/user`                      | Chỉ còn endpoint versioned và mọi profile route đều qua `active.account`              |
| SEC-004 V| Quản lý vòng đời Sanctum token                | Token có expiration; giới hạn thiết bị/token;        |
| SEC-005 V| Loại `seller_id` khỏi trust boundary checkout | Server suy ra seller từ cart; xác minh seller có role Seller và đang Active           |
| SEC-006 | Chuẩn hóa money và idempotency                | Không dùng `float`; checkout retry không tạo order/trừ kho hai lần                    |
| SEC-007 | Tạo Stock Movement và audit trail             | Mọi nhập, bán, hủy, hoàn và điều chỉnh đều có before/after, actor, reason             |

--SHOULD
| ID      | Công việc                          | Điều kiện hoàn thành                                                      |
| ------- | ---------------------------------- | ------------------------------------------------------------------------- |
| SEC-008 V| Xóa route voucher trùng            | `route:list` chỉ còn một route cho mỗi method/path                        |
| SEC-009 | Hardening cấu hình production      | CORS từ env, `APP_DEBUG=false`, HTTPS, TrustHosts, secret checklist       |
| SEC-010 | Chuẩn hóa exception và request ID  | Mọi lỗi có format chung; lỗi 500 không lộ chi tiết; log có correlation ID |
| SEC-011 | Hardening upload và file lifecycle | Re-encode ảnh, quota, kiểm tra lỗi lưu/xóa file, test file giả            |
| SEC-012 | Tạo CI security gate               | Chạy test, Pint, `composer validate`, `composer audit`; có `SECURITY.md`  |


--LATER
| ID      | Công việc                                | Lý do để sau                                                                    |
| ------- | ---------------------------------------- | ------------------------------------------------------------------------------- |
| SEC-013 | MFA, email verification, forgot password | Là module xác thực mở rộng, làm sau khi login/token hiện tại ổn định            |
| SEC-014 | Centralized logging và alerting          | Cần thêm hạ tầng log, dashboard và kênh cảnh báo                                |
| SEC-015 | Payment/Webhook security                 | Áp dụng khi tích hợp VNPay/MoMo/Stripe: HMAC, replay protection, reconciliation |