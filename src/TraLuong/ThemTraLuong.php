<?php
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

// Lấy danh sách phòng ban
$sql_phongban = "SELECT MaPB, TenPhongBan FROM phongban";
$result_phongban = $conn->query($sql_phongban);
$phongban = $result_phongban ? $result_phongban->fetch_all(MYSQLI_ASSOC) : [];

// Kiểm tra $_SESSION['maphongban']
$has_maphongban = isset($_SESSION['maphongban']) && !empty($_SESSION['maphongban']);
if (!$has_maphongban) {
    // Nếu không có $_SESSION['maphongban'], hiển thị tất cả phòng ban
    $error_maphongban = "Không tìm thấy phòng ban của bạn. Hiển thị tất cả phòng ban.";
}

// Lấy danh sách chức vụ
$sql_chucvu = "SELECT MaCV, TenCV FROM chucvu";
$result_chucvu = $conn->query($sql_chucvu);
$chucvu = $result_chucvu ? $result_chucvu->fetch_all(MYSQLI_ASSOC) : [];

// Xử lý thêm bảng lương
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $phongban = isset($_POST['phongban']) ? intval($_POST['phongban']) : 0;
    $chucvu = isset($_POST['chucvu']) ? intval($_POST['chucvu']) : 0;
    $hoten = isset($_POST['hoten']) ? trim($conn->real_escape_string($_POST['hoten'])) : '';
    $thuong = isset($_POST['thuong']) ? floatval($_POST['thuong']) : 0;
    $phat = isset($_POST['phat']) ? floatval($_POST['phat']) : 0;
    $phuCapKhac = isset($_POST['phucapkhac']) ? floatval($_POST['phucapkhac']) : 0;

    // Validate dữ liệu
    if ($phongban <= 0 || $chucvu <= 0 || empty($hoten)) {
        $error = "Vui lòng chọn phòng ban, chức vụ và nhập họ tên hợp lệ.";
    } elseif ($thuong < 0 || $phat < 0 || $phuCapKhac < 0) {
        $error = "Thưởng, phạt và phụ cấp khác không được nhỏ hơn 0.";
    } else {
        // Kiểm tra nhân viên có tồn tại với họ tên, phòng ban và chức vụ được chọn không
        $sql_check_nhanvien = "SELECT MaNV, MaCV FROM nhanvien WHERE HoTen = ? AND MaPB = ? AND MaCV = ? AND TinhTrang = 1";
        $stmt = $conn->prepare($sql_check_nhanvien);
        $stmt->bind_param("sii", $hoten, $phongban, $chucvu);
        $stmt->execute();
        $result_nhanvien = $stmt->get_result();
        if ($result_nhanvien->num_rows === 0) {
            $error = "Nhân viên '$hoten' không tồn tại trong phòng ban và chức vụ được chọn.";
        } else {
            $nhanvien_info = $result_nhanvien->fetch_assoc();
            $nhanvien = $nhanvien_info['MaNV'];

            // Lấy thông tin lương cơ bản từ bảng chucvu
            $sql_chucvu = "SELECT LuongCoBan FROM chucvu WHERE MaCV = ?";
            $stmt = $conn->prepare($sql_chucvu);
            $stmt->bind_param("i", $nhanvien_info['MaCV']);
            $stmt->execute();
            $result_chucvu = $stmt->get_result();
            $chucvu_info = $result_chucvu->fetch_assoc();
            $stmt->close();

            if (!$chucvu_info) {
                $error = "Không tìm thấy thông tin chức vụ của nhân viên.";
            } else {
                // Lấy thông tin lương từ bảng luong
                $sql_luong = "SELECT HeSoLuong, ThuongPhuCap, HeSoPhuCap FROM luong WHERE MaNV = ?";
                $stmt = $conn->prepare($sql_luong);
                $stmt->bind_param("i", $nhanvien);
                $stmt->execute();
                $result_luong = $stmt->get_result();
                $luong_info = $result_luong->fetch_assoc();
                $stmt->close();

                // Kiểm tra xem nhân viên đã có bảng lương trong tháng này chưa
                $thang = date('n'); // Tháng hiện tại: 5 (May)
                $nam = date('Y');   // Năm hiện tại: 2025
                $sql_check = "SELECT MaTraLuong FROM traluong WHERE MaNV = ? AND Thang = ? AND Nam = ?";
                $stmt = $conn->prepare($sql_check);
                $stmt->bind_param("iii", $nhanvien, $thang, $nam);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $error = "Nhân viên '$hoten' đã được trả lương trong tháng này.";
                } else {
                    // Tính tổng lương
                    $luongCoBan = floatval($chucvu_info['LuongCoBan']);
                    $heSoLuong = isset($luong_info['HeSoLuong']) ? floatval($luong_info['HeSoLuong']) : 1;
                    $thuongPhuCap = isset($luong_info['ThuongPhuCap']) ? floatval($luong_info['ThuongPhuCap']) : 0;
                    $heSoPhuCap = isset($luong_info['HeSoPhuCap']) ? floatval($luong_info['HeSoPhuCap']) : 0;
                    $tongLuong = $luongCoBan * $heSoLuong + $thuongPhuCap + ($luongCoBan * $heSoPhuCap) + $thuong + $phuCapKhac - $phat;

                    // Thêm bản ghi vào bảng traluong
                    $sql_insert = "INSERT INTO traluong (MaNV, Thang, Nam, Thuong, Phat, PhuCapKhac, TongLuong) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql_insert);
                    if ($stmt === false) {
                        $error = "Lỗi chuẩn bị truy vấn: " . $conn->error;
                    } else {
                        $stmt->bind_param("iiiiddi", $nhanvien, $thang, $nam, $thuong, $phat, $phuCapKhac, $tongLuong);
                        if ($stmt->execute()) {
                            $success = "Thêm bảng lương cho nhân viên '$hoten' thành công!";
                        } else {
                            $error = "Lỗi khi thêm bảng lương: " . $conn->error;
                        }
                        $stmt->close();
                    }
                }
            }
        }
        $stmt->close();
    }
}

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
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/TraLuong/TraLuong.php">Quản Lý Bảng Lương</a></li>
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
                    <h4 class="card-title">Thông tin chi tiết bảng lương</h4>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_maphongban)): ?>
                        <div class="alert alert-warning"><?php echo htmlspecialchars($error_maphongban); ?></div>
                    <?php endif; ?>
                    <div id="basic-datatable_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-body">
                                        <form method="POST" enctype="multipart/form-data" id="formThemTraLuong">
                                            <div class="form-group">
                                                <label for="phongbanSelect">Tên Phòng Ban</label>
                                                <select name="phongban" required class="form-control" id="phongbanSelect">
                                                    <option value="">-- Chọn Phòng Ban --</option>
                                                    <?php foreach ($phongban as $value): ?>
                                                        <?php if (!$has_maphongban || $_SESSION['maphongban'] == $value["MaPB"]) { ?>
                                                            <option value="<?php echo htmlspecialchars($value["MaPB"]); ?>" <?php echo isset($_POST['phongban']) && $_POST['phongban'] == $value["MaPB"] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($value["TenPhongBan"]); ?>
                                                            </option>
                                                        <?php } ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="chucvuSelect">Chức Vụ</label>
                                                <select name="chucvu" required class="form-control" id="chucvuSelect">
                                                    <option value="">-- Chọn Chức Vụ --</option>
                                                    <?php foreach ($chucvu as $value): ?>
                                                        <?php if ($value["MaCV"] != 3 && $value["MaCV"] != 4) { ?>
                                                            <option value="<?php echo htmlspecialchars($value["MaCV"]); ?>" <?php echo isset($_POST['chucvu']) && $_POST['chucvu'] == $value["MaCV"] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($value["TenCV"]); ?>
                                                            </option>
                                                        <?php } ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="hoten">Họ Tên</label>
                                                <input type="text" id="hoten" class="form-control" placeholder="Nhập họ tên nhân viên" required name="hoten" value="<?php echo isset($_POST['hoten']) ? htmlspecialchars($_POST['hoten']) : ''; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="thuong">Thưởng</label>
                                                <input type="number" id="thuong" class="form-control" placeholder="Thưởng" required name="thuong" value="<?php echo isset($_POST['thuong']) ? htmlspecialchars($_POST['thuong']) : ''; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="phat">Phạt</label>
                                                <input type="number" id="phat" class="form-control" placeholder="Phạt" required name="phat" value="<?php echo isset($_POST['phat']) ? htmlspecialchars($_POST['phat']) : ''; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="phucapkhac">Phụ Cấp Khác</label>
                                                <input type="number" id="phucapkhac" class="form-control" placeholder="Phụ cấp khác" required name="phucapkhac" value="<?php echo isset($_POST['phucapkhac']) ? htmlspecialchars($_POST['phucapkhac']) : ''; ?>">
                                            </div>
                                            <div class="form-group row mt-4">
                                                <div class="col-12 text-left">
                                                    <a href="/QLNhanVien_thuan/src/TraLuong/TraLuong.php" class="btn btn-success waves-effect waves-light">Quay Lại</a>
                                                    <button type="submit" name="submit" class="btn btn-primary waves-effect waves-light">Trả Lương Nhân Viên</button>
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