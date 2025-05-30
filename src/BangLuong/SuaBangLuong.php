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

// Truy vấn thông tin chi tiết bảng lương
$sql = "SELECT l.MaLuong, nv.HoTen, nv.NgaySinh, nv.GioiTinh, pb.TenPhongBan, cv.TenCV, cv.LuongCoBan, l.HeSoLuong, l.ThuongPhuCap, l.HeSoPhuCap 
        FROM luong l
        JOIN nhanvien nv ON l.MaNV = nv.MaNV
        JOIN phongban pb ON nv.MaPB = pb.MaPB
        JOIN chucvu cv ON nv.MaCV = cv.MaCV
        WHERE l.MaLuong = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi prepare: " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$detail = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($detail)) {
    die("Bảng lương không tồn tại");
}

// Xử lý cập nhật bảng lương
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $heSoLuong = isset($_POST['hesoluong']) ? trim($_POST['hesoluong']) : '';
    $thuongPhuCap = isset($_POST['thuongphucap']) ? trim($_POST['thuongphucap']) : '';
    $heSoPhuCap = isset($_POST['hesophucap']) ? trim($_POST['hesophucap']) : '';

    // Validate dữ liệu
    if (empty($heSoLuong) || empty($thuongPhuCap) || empty($heSoPhuCap)) {
        die("Vui lòng điền đầy đủ thông tin");
    }

    $heSoLuong = floatval($heSoLuong);
    $thuongPhuCap = intval($thuongPhuCap);
    $heSoPhuCap = floatval($heSoPhuCap);

    if ($heSoLuong <= 0 || $thuongPhuCap < 0 || $heSoPhuCap < 0) {
        die("Dữ liệu không hợp lệ: Hệ số lương phải lớn hơn 0, các giá trị khác không được âm");
    }

    $sql_update = "UPDATE luong SET HeSoLuong = ?, ThuongPhuCap = ?, HeSoPhuCap = ? WHERE MaLuong = ?";
    $stmt = $conn->prepare($sql_update);
    if ($stmt === false) {
        die("Lỗi prepare update: " . $conn->error);
    }
    $stmt->bind_param("didi", $heSoLuong, $thuongPhuCap, $heSoPhuCap, $id);

    if ($stmt->execute()) {
        // Cập nhật thành công, lưu thông báo vào session
        $_SESSION['success'] = "Cập nhật bảng lương thành công!";
        $stmt->close();
        $conn->close();
        header("Location: /QLNhanVien_thuan/src/SuaBangLuong.php?id=" . $id);
        exit();
    } else {
        die("Lỗi khi cập nhật bảng lương: " . $conn->error);
    }
    $stmt->close();
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
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/BangLuong/Luong.php">Quản Lý Bảng Lương</a></li>
                            <li class="breadcrumb-item active">Chi Tiết Bảng Lương</li>
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
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    <div id="basic-datatable_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-body">
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="form-group">
                                                <label for="simpleinput">Tên Nhân Viên</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Tên nhân viên" required="" value="<?php echo htmlspecialchars($detail[0]["HoTen"]); ?>" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Ngày Sinh</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="NgaySinh" required="" value="<?php echo date("d/m/Y", strtotime($detail[0]["NgaySinh"])); ?>" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Giới Tính</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Giới tính" required="" value="<?php echo $detail[0]["GioiTinh"] == 1 ? 'Nam' : 'Nữ'; ?>" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Tên Phòng Ban</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Tên phòng ban" required="" value="<?php echo htmlspecialchars($detail[0]["TenPhongBan"]); ?>" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Chức Vụ</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Chức vụ" required="" value="<?php echo htmlspecialchars($detail[0]["TenCV"]); ?>" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Lương Cơ Bản</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Lương cơ bản" required="" value="<?php echo number_format($detail[0]["LuongCoBan"]); ?> VNĐ / tháng" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Hệ Số Lương</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Hệ số lương" required="" value="<?php echo htmlspecialchars($detail[0]["HeSoLuong"]); ?>" name="hesoluong">
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Thưởng Phụ Cấp</label>
                                                <input type="number" id="simpleinput" class="form-control" placeholder="Thưởng phụ cấp" required="" value="<?php echo htmlspecialchars($detail[0]["ThuongPhuCap"]); ?>" name="thuongphucap">
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Hệ Số Phụ Cấp</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Hệ số phụ cấp" required="" value="<?php echo htmlspecialchars($detail[0]["HeSoPhuCap"]); ?>" name="hesophucap">
                                            </div>
                                            <div class="form-group row mt-4">
                                                <div class="col-12 text-left">
                                                    <a href="/QLNhanVien_thuan/src/BangLuong/Luong.php" class="btn btn-success waves-effect waves-light">Quay Lại</a>
                                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Cập Nhật Bảng Lương</button>
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
</style>
<?php
require(__DIR__ . '/../layouts/footer.php');
$conn->close();
?>