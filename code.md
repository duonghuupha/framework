| Loại API       | HTTP Code | message ví dụ                               | data chứa gì         |
| -------------- | --------- | ------------------------------------------- | -------------------- |
| Đăng nhập      | 200       | "Đăng nhập thành công"                      | user info + token    |
| Đăng ký        | 201       | "Tạo tài khoản thành công"                  | user info            |
| Lấy danh sách  | 200       | "Lấy danh sách thành công"                  | mảng dữ liệu         |
| Lấy chi tiết   | 200       | "Lấy thông tin thành công"                  | object               |
| Thêm mới       | 201       | "Thêm mới thành công"                       | dữ liệu vừa thêm     |
| Cập nhật       | 200       | "Cập nhật thành công"                       | dữ liệu vừa cập nhật |
| Xóa            | 200       | "Xóa thành công"                            | id đã xóa            |
| Lỗi dữ liệu    | 400       | "Thiếu tham số" hoặc "Dữ liệu không hợp lệ" | mảng lỗi (nếu có)    |
| Lỗi xác thực   | 401       | "Không có quyền truy cập"                   | null                 |
| Không tìm thấy | 404       | "Không tìm thấy dữ liệu"                    | null                 |
