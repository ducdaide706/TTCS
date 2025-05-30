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

// Kiểm tra xem chức vụ có nằm trong danh sách không được xóa không
if (in_array($id, [1, 3, 4])) {
    $conn->close();
    echo '<div style="text-align: center; margin-top: 20px;">';
    echo '<p style="color: red; font-size: 18px;">Không thể xóa chức vụ này vì đây là chức vụ hệ thống!</p>';
    echo '<a href="/QLNhanVien_thuan/index.php?path=chuc-vu" style="text-decoration: none; color: #fff; background-color: #28a745; padding: 10px 20px; border-radius: 5px;">Quay lại danh sách chức vụ</a>';
    echo '</div>';
    exit();
}

// Kiểm tra xem có nhân viên nào thuộc chức vụ này không
$sql_check = "SELECT COUNT(*) as total FROM nhanvien WHERE MaCV = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$total_nhanvien = $result->fetch_assoc()['total'];
$stmt->close();

if ($total_nhanvien > 0) {
    $conn->close();
    echo '<div style="text-align: center; margin-top: 20px;">';
    echo '<p style="color: red; font-size: 18px;">Không thể xóa chức vụ này vì vẫn còn ' . $total_nhanvien . ' nhân viên thuộc chức vụ!</p>';
    echo '<a href="/QLNhanVien_thuan/src/ChucVu.php" style="text-decoration: none; color: #fff; background-color: #28a745; padding: 10px 20px; border-radius: 5px;">Quay lại danh sách chức vụ</a>';
    echo '</div>';
    exit();
}

// Xóa chức vụ
$sql_delete = "DELETE FROM chucvu WHERE MaCV = ?";
$stmt = $conn->prepare($sql_delete);
if ($stmt === false) {
    die("Lỗi prepare: " . $conn->error);
}
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    // Xóa thành công, chuyển hướng về danh sách chức vụ
    $stmt->close();
    $conn->close();
    header("Location: /QLNhanVien_thuan/src/ChucVu/ChucVu.php");
    exit();
} else {
    die("Lỗi khi xóa chức vụ: " . $conn->error);
}
$stmt->close();
$conn->close();
?>