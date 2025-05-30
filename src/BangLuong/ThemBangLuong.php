<?php
// Ngăn xuất ra bất kỳ nội dung nào trước JSON
ob_start();
session_start();

// Kiểm tra nếu chưa đăng nhập hoặc không phải admin
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_id'] === '') {
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

// Truy vấn danh sách phòng ban
$sql_phongban = "SELECT MaPB, TenPhongBan FROM phongban";
$result_phongban = $conn->query($sql_phongban);
$phongban = $result_phongban ? $result_phongban->fetch_all(MYSQLI_ASSOC) : [];

// Truy vấn danh sách chức vụ
$sql_chucvu = "SELECT MaCV, TenCV FROM chucvu";
$result_chucvu = $conn->query($sql_chucvu);
$chucvu = $result_chucvu ? $result_chucvu->fetch_all(MYSQLI_ASSOC) : [];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phongBanId = isset($_POST['phongban']) ? $conn->real_escape_string($_POST['phongban']) : '';
    $chucVuId = isset($_POST['chucvu']) ? $conn->real_escape_string($_POST['chucvu']) : '';
    $hoTen = isset($_POST['nhanvien']) ? trim($conn->real_escape_string($_POST['nhanvien'])) : '';
    $heSoLuong = isset($_POST['hesoluong']) ? floatval($_POST['hesoluong']) : 0;
    $thuongPhuCap = isset($_POST['thuongphucap']) ? intval($_POST['thuongphucap']) : 0;
    $heSoPhuCap = isset($_POST['hesophucap']) ? floatval($_POST['hesophucap']) : 0;

    // Validate dữ liệu đầu vào
    if (empty($phongBanId) || empty($chucVuId) || empty($hoTen) || $heSoLuong <= 0 || $thuongPhuCap < 0 || $heSoPhuCap < 0) {
        $error = "Vui lòng điền đầy đủ thông tin và đảm bảo các giá trị hợp lệ.";
    } else {
        // Kiểm tra xem nhân viên có tồn tại với phòng ban và chức vụ được chọn không
        $sql_check_nhanvien = "SELECT MaNV FROM nhanvien WHERE HoTen = ? AND MaPB = ? AND MaCV = ?";
        $stmt = $conn->prepare($sql_check_nhanvien);
        $stmt->bind_param("sii", $hoTen, $phongBanId, $chucVuId);
        $stmt->execute();
        $result_nhanvien = $stmt->get_result();
        if ($result_nhanvien->num_rows === 0) {
            $error = "Nhân viên '$hoTen' không tồn tại trong phòng ban và chức vụ được chọn.";
        } else {
            $nhanvien = $result_nhanvien->fetch_assoc();
            $maNV = $nhanvien['MaNV'];

            // Kiểm tra xem nhân viên đã có bảng lương chưa
            $sql_check_luong = "SELECT MaLuong FROM luong WHERE MaNV = ?";
            $stmt = $conn->prepare($sql_check_luong);
            $stmt->bind_param("i", $maNV);
            $stmt->execute();
            $result_luong = $stmt->get_result();
            if ($result_luong->num_rows > 0) {
                $error = "Nhân viên '$hoTen' đã có bảng lương, không thể thêm mới.";
            } else {
                // Thêm bảng lương
                $sql_insert = "INSERT INTO luong (MaNV, HeSoLuong, ThuongPhuCap, HeSoPhuCap) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql_insert);
                if ($stmt === false) {
                    $error = "Lỗi chuẩn bị truy vấn: " . $conn->error;
                } else {
                    $stmt->bind_param("idid", $maNV, $heSoLuong, $thuongPhuCap, $heSoPhuCap);
                    if ($stmt->execute()) {
                        $success = "Thêm bảng lương cho nhân viên '$hoTen' thành công!";
                    } else {
                        $error = "Lỗi khi thêm bảng lương: " . $conn->error;
                    }
                    $stmt->close();
                }
            }
            $stmt->close();
        }
        $stmt->close();
    }
}

ob_end_clean();
require(__DIR__ . '/../layouts/header.php');
?>

<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0 font-size-18">Quản Lý Thông Tin Bảng Lương</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/TrangChu.php">Trang Chủ</a></li>
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/BangLuong/Luong.php">Quản Lý Bảng Lương</a></li>
                            <li class="breadcrumb-item active">Thêm Bảng Lương Mới</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Thêm bảng lương mới</h4>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <div id="basic-datatable_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-body">
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="form-group">
                                                <label for="phongbanSelect">Tên Phòng Ban</label>
                                                <select name="phongban" required class="form-control" id="phongbanSelect">
                                                    <option value="">-- Chọn Phòng Ban --</option>
                                                    <?php foreach ($phongban as $value): ?>
                                                        <option value="<?php echo htmlspecialchars($value["MaPB"]); ?>">
                                                            <?php echo htmlspecialchars($value["TenPhongBan"]); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="chucvuSelect">Chức Vụ</label>
                                                <select name="chucvu" required class="form-control" id="chucvuSelect">
                                                    <option value="">-- Chọn Chức Vụ --</option>
                                                    <?php foreach ($chucvu as $value): ?>
                                                        <option value="<?php echo htmlspecialchars($value["MaCV"]); ?>">
                                                            <?php echo htmlspecialchars($value["TenCV"]); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="nhanvien">Họ Tên</label>
                                                <input type="text" id="nhanvien" class="form-control" placeholder="Nhập tên nhân viên" required name="nhanvien">
                                            </div>
                                            <div class="form-group">
                                                <label for="hesoluong">Hệ Số Lương</label>
                                                <input type="number" step="0.01" id="hesoluong" class="form-control" placeholder="Hệ số lương" required name="hesoluong">
                                            </div>
                                            <div class="form-group">
                                                <label for="thuongphucap">Thưởng Phụ Cấp</label>
                                                <input type="number" id="thuongphucap" class="form-control" placeholder="Thưởng phụ cấp" required name="thuongphucap">
                                            </div>
                                            <div class="form-group">
                                                <label for="hesophucap">Hệ Số Phụ Cấp</label>
                                                <input type="number" step="0.01" id="hesophucap" class="form-control" placeholder="Hệ số phụ cấp" required name="hesophucap">
                                            </div>
                                            <div class="form-group row mt-4">
                                                <div class="col-12 text-left">
                                                    <a href="/QLNhanVien_thuan/src/BangLuong/Luong.php" class="btn btn-success waves-effect waves-light">Quay Lại</a>
                                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Thêm Bảng Lương</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    .table th, .table td {
        padding: 0.75rem;
        vertical-align: middle;
        border-top: 1px solid #dee2e6;
        font-size: 15px;
    }
    .alert {
        margin-bottom: 20px;
    }
</style>

<?php require(__DIR__ . '/../layouts/footer.php'); ?>
<?php
$conn->close();
?>