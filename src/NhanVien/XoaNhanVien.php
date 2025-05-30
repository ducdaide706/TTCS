<?php
session_start();

// Kiểm tra nếu chưa đăng nhập hoặc không phải admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: /QLNhanVien_thuan/src/DangNhap.php");
    exit();
}

// Kết nối cơ sở dữ liệu
$servername = "127.0.0.1";
$username = "root"; // Thay bằng username MySQL của bạn
$password = ""; // Thay bằng password MySQL của bạn
$dbname = "hrm_nhanvien";

$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra phân quyền
$taikhoan = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT PhanQuyen FROM taikhoan WHERE TaiKhoan = ?");
$stmt->bind_param("s", $taikhoan);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1 || $result->fetch_assoc()['PhanQuyen'] !== 3) {
    $stmt->close();
    $conn->close();
    header("Location: /QLNhanVien_thuan/src/DangNhap.php");
    exit();
}
$stmt->close();

// Lấy id từ URL
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("ID không hợp lệ");
}

// Xóa các bản ghi liên quan trong luong và traluong
$sql_delete_luong = "DELETE FROM luong WHERE MaNV = ?";
$stmt = $conn->prepare($sql_delete_luong);
if ($stmt === false) {
    die("Lỗi prepare luong: " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

$sql_delete_traluong = "DELETE FROM traluong WHERE MaNV = ?";
$stmt = $conn->prepare($sql_delete_traluong);
if ($stmt === false) {
    die("Lỗi prepare traluong: " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Xóa nhân viên
$sql_delete_nhanvien = "DELETE FROM nhanvien WHERE MaNV = ?";
$stmt = $conn->prepare($sql_delete_nhanvien);
if ($stmt === false) {
    die("Lỗi prepare nhanvien: " . $conn->error);
}
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    // Xóa thành công, chuyển hướng về danh sách nhân viên
    $stmt->close();
    $conn->close();
    header("Location: /QLNhanVien_thuan/src/NhanVien/NhanVien.php");
    exit();
} else {
    die("Lỗi khi xóa nhân viên: " . $conn->error);
}
$stmt->close();
$conn->close();
?>