<?php
session_start();

// Kiểm tra nếu chưa đăng nhập hoặc không phải admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: /QLNhanVien_thuan/src/DangNhap.php");
    exit();
}

// Kết nối cơ sở dữ liệu
$servername = "127.0.0.1";
$username = "root";
$password = "";
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

// Kiểm tra xem có nhân viên nào thuộc phòng ban này không
$sql_check = "SELECT COUNT(*) as total FROM nhanvien WHERE MaPB = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$total_nhanvien = $result->fetch_assoc()['total'];
$stmt->close();

if ($total_nhanvien > 0) {
    $conn->close();
    die("Không thể xóa phòng ban này vì vẫn còn $total_nhanvien nhân viên thuộc phòng ban!");
}

// Xóa phòng ban
$sql_delete = "DELETE FROM phongban WHERE MaPB = ?";
$stmt = $conn->prepare($sql_delete);
if ($stmt === false) {
    die("Lỗi prepare: " . $conn->error);
}
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    // Xóa thành công, chuyển hướng về danh sách phòng ban
    $stmt->close();
    $conn->close();
    header("Location: /QLNhanVien_thuan/src/PhongBan/PhongBan.php");
    exit();
} else {
    die("Lỗi khi xóa phòng ban: " . $conn->error);
}
$stmt->close();
$conn->close();
?>