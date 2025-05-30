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

// Lấy MaTraLuong từ URL
$maTraLuong = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($maTraLuong <= 0) {
    header("Location: /QLNhanVien_thuan/src/TraLuong/TraLuong.php");
    exit();
}

// Truy vấn thông tin chi tiết trả lương
$sql = "SELECT t.MaTraLuong, t.MaNV, n.HoTen, n.NgaySinh, n.GioiTinh, p.TenPhongBan, c.TenCV, c.LuongCoBan, l.HeSoLuong, l.ThuongPhuCap, l.HeSoPhuCap, t.Thuong, t.Phat, t.PhuCapKhac, t.TongLuong
        FROM traluong t
        LEFT JOIN nhanvien n ON t.MaNV = n.MaNV
        LEFT JOIN phongban p ON n.MaPB = p.MaPB
        LEFT JOIN chucvu c ON n.MaCV = c.MaCV
        LEFT JOIN luong l ON t.MaNV = l.MaNV
        WHERE t.MaTraLuong = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}
$stmt->bind_param("i", $maTraLuong);
$stmt->execute();
$result = $stmt->get_result();
$detail = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Kiểm tra nếu không tìm thấy bản ghi
if (empty($detail)) {
    header("Location: /QLNhanVien_thuan/src/TraLuong/TraLuong.php");
    exit();
}

// Xử lý cập nhật thông tin trả lương
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $thuong = isset($_POST['thuong']) ? floatval($_POST['thuong']) : 0;
    $phat = isset($_POST['phat']) ? floatval($_POST['phat']) : 0;
    $phuCapKhac = isset($_POST['phucapkhac']) ? floatval($_POST['phucapkhac']) : 0;

    // Validate dữ liệu
    if ($thuong < 0 || $phat < 0 || $phuCapKhac < 0) {
        $error = "Thưởng, phạt và phụ cấp khác không được nhỏ hơn 0.";
    } else {
        // Tính lại tổng lương
        $luongCoBan = floatval($detail[0]['LuongCoBan']);
        $heSoLuong = floatval($detail[0]['HeSoLuong'] ?? 1); // Giá trị mặc định nếu NULL
        $thuongPhuCap = floatval($detail[0]['ThuongPhuCap'] ?? 0); // Giá trị mặc định nếu NULL
        $heSoPhuCap = floatval($detail[0]['HeSoPhuCap'] ?? 0); // Giá trị mặc định nếu NULL
        $tongLuong = $luongCoBan * $heSoLuong + $thuongPhuCap + ($luongCoBan * $heSoPhuCap) + $thuong + $phuCapKhac - $phat;

        // Cập nhật bản ghi trong bảng traluong
        $sql_update = "UPDATE traluong SET Thuong = ?, Phat = ?, PhuCapKhac = ?, TongLuong = ? WHERE MaTraLuong = ?";
        $stmt = $conn->prepare($sql_update);
        if ($stmt === false) {
            $error = "Lỗi chuẩn bị truy vấn: " . $conn->error;
        } else {
            $stmt->bind_param("ddidi", $thuong, $phat, $phuCapKhac, $tongLuong, $maTraLuong);
            if ($stmt->execute()) {
                $success = "Cập nhật thông tin trả lương thành công!";
                // Cập nhật lại dữ liệu hiển thị
                $detail[0]['Thuong'] = $thuong;
                $detail[0]['Phat'] = $phat;
                $detail[0]['PhuCapKhac'] = $phuCapKhac;
                $detail[0]['TongLuong'] = $tongLuong;
            } else {
                $error = "Lỗi khi cập nhật: " . $conn->error;
            }
            $stmt->close();
        }
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
                    <h4 class="mb-0 font-size-18">Quản Lý Thông Tin Trả Lương</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/TrangChu.php">Trang Chủ</a></li>
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/TraLuong/TraLuong.php">Quản Lý Trả Lương</a></li>
                            <li class="breadcrumb-item active">Chi Tiết Trả Lương</li>
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
                    <h4 class="card-title">Thông tin chi tiết trả lương</h4>
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
                                                <label for="simpleinput">Tên Nhân Viên</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Tên nhân viên" required value="<?php echo htmlspecialchars($detail[0]["HoTen"]); ?>" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Ngày Sinh</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Ngày sinh" required value="<?php echo date("d/m/Y", strtotime($detail[0]["NgaySinh"])); ?>" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Giới Tính</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Giới tính" required value="<?php echo $detail[0]["GioiTinh"] == 1 ? 'Nam' : 'Nữ'; ?>" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Tên Phòng Ban</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Tên phòng ban" required value="<?php echo htmlspecialchars($detail[0]["TenPhongBan"]); ?>" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Chức Vụ</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Chức vụ" required value="<?php echo htmlspecialchars($detail[0]["TenCV"]); ?>" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Lương Cơ Bản</label>
                                                <input type="number" id="simpleinput" class="form-control" placeholder="Lương cơ bản" required value="<?php echo number_format($detail[0]["LuongCoBan"]); ?>" name="luongcoban" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Hệ Số Lương</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Hệ số lương" required value="<?php echo $detail[0]["HeSoLuong"] ?? 'Không có'; ?>" name="hesoluong" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Thưởng Phụ Cấp</label>
                                                <input type="number" id="simpleinput" class="form-control" placeholder="Thưởng phụ cấp" required value="<?php echo number_format($detail[0]["ThuongPhuCap"] ?? 0); ?>" name="thuongphucap" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Hệ Số Phụ Cấp</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Hệ số phụ cấp" required value="<?php echo $detail[0]["HeSoPhuCap"] ?? 'Không có'; ?>" name="hesophucap" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Thưởng</label>
                                                <input type="number" id="simpleinput" class="form-control" placeholder="Thưởng" required value="<?php echo number_format($detail[0]["Thuong"]); ?>" name="thuong">
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Phạt</label>
                                                <input type="number" id="simpleinput" class="form-control" placeholder="Phạt" required value="<?php echo number_format($detail[0]["Phat"]); ?>" name="phat">
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Phụ Cấp Khác</label>
                                                <input type="number" id="simpleinput" class="form-control" placeholder="Phụ cấp khác" required value="<?php echo number_format($detail[0]["PhuCapKhac"]); ?>" name="phucapkhac">
                                            </div>
                                            <div class="form-group row mt-4">
                                                <div class="col-12 text-left">
                                                    <a href="/QLNhanVien_thuan/src/TraLuong/TraLuong.php" class="btn btn-success waves-effect waves-light">Quay Lại</a>
                                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Cập Nhật Trả Lương</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- end card body-->
            </div> <!-- end card -->
        </div><!-- end col-->
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
<?php
require(__DIR__ . '/../layouts/footer.php');
$conn->close();
?>