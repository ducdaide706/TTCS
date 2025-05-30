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

// Truy vấn thông tin chi tiết phòng ban
$sql = "SELECT MaPB, TenPhongBan, DiaChi, SoDienThoai, Email, Website, MoTa 
        FROM phongban 
        WHERE MaPB = ?";
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
    die("Phòng ban không tồn tại");
}

// Xử lý form cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenphongban = $conn->real_escape_string($_POST['tenphongban']);
    $diachi = $conn->real_escape_string($_POST['diachi']);
    $sodienthoai = $conn->real_escape_string($_POST['sodienthoai']);
    $email = $conn->real_escape_string($_POST['email']);
    $website = $conn->real_escape_string($_POST['website']);
    $mota = $conn->real_escape_string($_POST['mota']);

    // Cập nhật thông tin vào cơ sở dữ liệu
    $sql_update = "UPDATE phongban 
                   SET TenPhongBan = ?, DiaChi = ?, SoDienThoai = ?, Email = ?, Website = ?, MoTa = ? 
                   WHERE MaPB = ?";
    $stmt = $conn->prepare($sql_update);
    if ($stmt === false) {
        die("Lỗi prepare update: " . $conn->error);
    }
    $stmt->bind_param("ssssssi", $tenphongban, $diachi, $sodienthoai, $email, $website, $mota, $id);

    if ($stmt->execute()) {
        // Cập nhật thành công, chuyển hướng về trang chi tiết phòng ban
        $stmt->close();
        header("Location: /QLNhanVien_thuan/src/SuaPhongBan.php?id=$id");
        exit();
    } else {
        die("Lỗi khi cập nhật: " . $conn->error);
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
                    <h4 class="mb-0 font-size-18">Quản Lý Thông Tin Phòng Ban</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/TrangChu.php">Trang Chủ</a></li>
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/PhongBan/PhongBan.php">Quản Lý Phòng Ban</a></li>
                            <li class="breadcrumb-item active">Chi Tiết Phòng Ban</li>
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
                    <h4 class="card-title">Thông tin chi tiết phòng ban</h4>
                    <div id="basic-datatable_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="row">
                            <div class="col-xl-12">
                            	<div class="card">
                                    <div class="card-body">
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="form-group">
                                                <label for="simpleinput">Tên Phòng Ban</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Tên phòng ban" required name="tenphongban" value="<?php echo htmlspecialchars($detail[0]['TenPhongBan']); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Địa Chỉ</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Địa chỉ" required name="diachi" value="<?php echo htmlspecialchars($detail[0]['DiaChi']); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Số Điện Thoại</label>
                                                <input type="number" id="simpleinput" class="form-control" placeholder="Số điện thoại" required name="sodienthoai" value="<?php echo htmlspecialchars($detail[0]['SoDienThoai']); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Email</label>
                                                <input type="email" id="simpleinput" class="form-control" placeholder="Email" required name="email" value="<?php echo htmlspecialchars($detail[0]['Email']); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Website</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Website" required name="website" value="<?php echo htmlspecialchars($detail[0]['Website']); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Mô Tả</label>
                                                <textarea class="form-control" required id="exampleFormControlTextarea1" rows="3" placeholder="Mô tả" name="mota"><?php echo htmlspecialchars($detail[0]['MoTa']); ?></textarea>
                                            </div>
                                            <div class="form-group row mt-4">
                                                <div class="col-12 text-left">
                                                    <a href="/QLNhanVien_thuan/src/PhongBan/PhongBan.php" class="btn btn-success waves-effect waves-light">Quay Lại</a>
                                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Cập Nhật Thông Tin</button>
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