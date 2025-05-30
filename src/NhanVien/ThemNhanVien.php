<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: /QLNhanVien_thuan/src/DangNhap.php");
    exit();
}

$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "hrm_nhanvien";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

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

$_SESSION['chucvu'] = 3;
$_SESSION['maphongban'] = null;

$sql_chucvu = "SELECT MaCV, TenCV FROM chucvu";
$result_chucvu = $conn->query($sql_chucvu);
$chucvu = $result_chucvu ? $result_chucvu->fetch_all(MYSQLI_ASSOC) : [];

$sql_phongban = "SELECT MaPB, TenPhongBan FROM phongban";
$result_phongban = $conn->query($sql_phongban);
$phongban = $result_phongban ? $result_phongban->fetch_all(MYSQLI_ASSOC) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoten = $conn->real_escape_string($_POST['hoten']);
    $ngaysinh = $conn->real_escape_string($_POST['ngaysinh']);
    $gioitinh = (int)$_POST['gioitinh'];
    $dantoc = $conn->real_escape_string($_POST['dantoc']);
    $quequan = $conn->real_escape_string($_POST['quequan']);
    $ngaybatdaulam = $conn->real_escape_string($_POST['ngaybatdaulam']);
    $sodienthoai = $conn->real_escape_string($_POST['sodienthoai']);
    $email = $conn->real_escape_string($_POST['email']);
    $tinhtrang = (int)$_POST['tinhtrang'];
    $chucvu = (int)$_POST['chucvu'];
    $phongban = (int)$_POST['phongban'];

    $avatar = '/QLNhanVien_thuan/public/admin/images/avatars/default.png';
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../public/admin/images/avatars/';
        $avatar_name = uniqid() . '_' . basename($_FILES['avatar']['name']);
        $avatar_path = $upload_dir . $avatar_name;

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)) {
            $avatar = '/QLNhanVien_thuan/public/admin/images/avatars/' . $avatar_name;
        } else {
            die("Lỗi khi tải ảnh lên");
        }
    }

    $sql_insert = "INSERT INTO nhanvien (HoTen, NgaySinh, GioiTinh, DanToc, QueQuan, NgayBatDauLam, SoDienThoai, Email, TinhTrang, MaCV, MaPB, Avatar) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_insert);
    if ($stmt === false) {
        die("Lỗi prepare insert: " . $conn->error);
    }
    $stmt->bind_param("ssisssisiiis", $hoten, $ngaysinh, $gioitinh, $dantoc, $quequan, $ngaybatdaulam, $sodienthoai, $email, $tinhtrang, $chucvu, $phongban, $avatar);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: /QLNhanVien_thuan/src/NhanVien/NhanVien.php");
        exit();
    } else {
        die("Lỗi khi thêm nhân viên: " . $conn->error);
    }
}

require(__DIR__ . '/../layouts/header.php');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0 font-size-18">Quản Lý Thông Tin Nhân Viên</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/TrangChu.php">Trang Chủ</a></li>
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/NhanVien.php">Quản Lý Nhân Viên</a></li>
                            <li class="breadcrumb-item active">Thêm Nhân Viên Mới</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Thông tin chi tiết nhân viên</h4>
                    <div class="text-center mb-3">
                        <img id="output" src="/QLNhanVien_thuan/uploads/avatar.jpg" style="width: 160px; height: 160px; border-radius: 50%;">
                        <div class="mt-2">
                            <label for="selectfile" class="btn btn-sm btn-secondary"><i class="fa-solid fa-camera"></i> Chọn ảnh</label>
                            <input onchange="loadFile(event)" class="d-none" type="file" id="selectfile" name="avatar">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-4">
                            <label>Tên Nhân Viên</label>
                            <input type="text" class="form-control" required name="hoten">
                        </div>
                        <div class="col-4">
                            <label>Ngày Sinh</label>
                            <input type="date" class="form-control" required name="ngaysinh">
                        </div>
                        <div class="col-4">
                            <label>Giới Tính</label>
                            <select name="gioitinh" required class="form-control">
                                <option value="1" selected>Nam</option>
                                <option value="0">Nữ</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-4">
                            <label>Dân Tộc</label>
                            <input type="text" class="form-control" required name="dantoc">
                        </div>
                        <div class="col-4">
                            <label>Quê Quán</label>
                            <input type="text" class="form-control" required name="quequan">
                        </div>
                        <div class="col-4">
                            <label>Ngày Bắt Đầu Làm</label>
                            <input type="date" class="form-control" required name="ngaybatdaulam">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-4">
                            <label>Số Điện Thoại</label>
                            <input type="number" class="form-control" required name="sodienthoai">
                        </div>
                        <div class="col-4">
                            <label>Email</label>
                            <input type="email" class="form-control" required name="email">
                        </div>
                        <div class="col-4">
                            <label>Tình Trạng</label>
                            <select name="tinhtrang" required class="form-control">
                                <option value="1" selected>Đang làm việc</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-4">
                            <label>Chức Vụ</label>
                            <select name="chucvu" required class="form-control">
                                <?php foreach ($chucvu as $cv): ?>
                                    <option value="<?php echo htmlspecialchars($cv['MaCV']); ?>">
                                        <?php echo htmlspecialchars($cv['TenCV']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label>Phòng Ban</label>
                            <select name="phongban" required class="form-control">
                                <?php foreach ($phongban as $pb): ?>
                                    <option value="<?php echo htmlspecialchars($pb['MaPB']); ?>">
                                        <?php echo htmlspecialchars($pb['TenPhongBan']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row mt-4">
                        <div class="col-12 text-left">
                            <a href="/QLNhanVien_thuan/src/NhanVien.php" class="btn btn-success">Quay Lại</a>
                            <button type="submit" class="btn btn-primary">Thêm Thông Tin</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.table th, .table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
    font-size: 15px;
}
</style>

<script>
var loadFile = function(event) {
    var output = document.getElementById('output');
    output.src = URL.createObjectURL(event.target.files[0]);
    output.onload = function() {
        URL.revokeObjectURL(output.src);
    }
};
</script>

<?php
require(__DIR__ . '/../layouts/footer.php');
$conn->close();
?>
