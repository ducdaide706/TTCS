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

// Truy vấn thông tin chi tiết chức vụ
$sql = "SELECT MaCV, TenCV, MoTa, LuongCoBan 
        FROM chucvu 
        WHERE MaCV = ?";
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
    die("Chức vụ không tồn tại");
}

// Xử lý form cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenchucvu = $conn->real_escape_string($_POST['tenchucvu']);
    $mota = $conn->real_escape_string($_POST['mota']);
    $luongcoban = (int)$_POST['luongcoban'];

    // Cập nhật thông tin vào cơ sở dữ liệu
    $sql_update = "UPDATE chucvu 
                   SET TenCV = ?, MoTa = ?, LuongCoBan = ? 
                   WHERE MaCV = ?";
    $stmt = $conn->prepare($sql_update);
    if ($stmt === false) {
        die("Lỗi prepare update: " . $conn->error);
    }
    $stmt->bind_param("ssii", $tenchucvu, $mota, $luongcoban, $id);

    if ($stmt->execute()) {
        // Cập nhật thành công, chuyển hướng về trang chi tiết chức vụ
        $stmt->close();
        header("Location: /QLNhanVien_thuan/src/ChucVu/SuaChucVu.php?id=$id");
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
                    <h4 class="mb-0 font-size-18">Quản Lý Thông Tin Chức Vụ</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/TrangChu.php">Trang Chủ</a></li>
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/ChucVu/ChucVu.php">Quản Lý Chức Vụ</a></li>
                            <li class="breadcrumb-item active">Chi Tiết Chức Vụ</li>
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
                    <h4 class="card-title">Thông tin chi tiết chức vụ</h4>
                    <div id="basic-datatable_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-body">
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="form-group">
                                                <label for="simpleinput">Tên Chức Vụ</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Tên chức vụ" required name="tenchucvu" value="<?php echo htmlspecialchars($detail[0]['TenCV']); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Mô Tả</label>
                                                <input type="text" id="simpleinput" class="form-control" placeholder="Mô tả" required name="mota" value="<?php echo htmlspecialchars($detail[0]['MoTa']); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="simpleinput">Lương Cơ Bản</label>
                                                <input type="number" id="simpleinput" class="form-control" placeholder="Lương cơ bản" required name="luongcoban" value="<?php echo htmlspecialchars($detail[0]['LuongCoBan']); ?>">
                                            </div>
                                            <div class="form-group row mt-4">
                                                <div class="col-12 text-left">
                                                    <a href="/QLNhanVien_thuan/src/ChucVu/ChucVu.php" class="btn btn-success waves-effect waves-light">Quay Lại</a>
                                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Cập Nhật Chức Vụ</button>
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