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

// Truy vấn thông tin chi tiết nhân viên
$sql = "SELECT n.*, p.TenPhongBan, c.TenCV
        FROM nhanvien n
        LEFT JOIN phongban p ON n.MaPB = p.MaPB
        LEFT JOIN chucvu c ON n.MaCV = c.MaCV
        WHERE n.MaNV = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi prepare: " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$detail = $result->fetch_all(MYSQLI_ASSOC);
if (empty($detail)) {
    die("Nhân viên không tồn tại");
}
$detail = $detail; // Đảm bảo $detail là mảng chứa thông tin nhân viên

// Lấy danh sách chức vụ và phòng ban
$sql_chucvu = "SELECT MaCV, TenCV FROM chucvu";
$result_chucvu = $conn->query($sql_chucvu);
$chucvu = $result_chucvu ? $result_chucvu->fetch_all(MYSQLI_ASSOC) : [];

$sql_phongban = "SELECT MaPB, TenPhongBan FROM phongban";
$result_phongban = $conn->query($sql_phongban);
$phongban = $result_phongban ? $result_phongban->fetch_all(MYSQLI_ASSOC) : [];

$stmt->close();

// Xử lý form cập nhật thông tin
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
    $trinhdohocvan = isset($_POST['trinhdohocvan']) ? (int)$_POST['trinhdohocvan'] : null;

    // Xử lý ảnh avatar
    $avatar = $detail[0]['Avatar'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../public/admin/images/avatars/';
        $avatar_name = uniqid() . '_' . basename($_FILES['avatar']['name']);
        $avatar_path = $upload_dir . $avatar_name;

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)) {
            $avatar = '/QLNhanVien_thuan/public/admin/images/avatars/' . $avatar_name;
            // Xóa ảnh cũ nếu tồn tại (trừ trường hợp mặc định)
            if ($detail[0]['Avatar'] && $detail[0]['Avatar'] !== '/QLNhanVien_thuan/public/admin/images/avatars/default.png') {
                unlink(__DIR__ . '/../' . ltrim($detail[0]['Avatar'], '/'));
            }
        } else {
            die("Lỗi khi tải ảnh lên");
        }
    }

    // Cập nhật thông tin vào cơ sở dữ liệu
    $sql_update = "UPDATE nhanvien SET HoTen = ?, NgaySinh = ?, GioiTinh = ?, DanToc = ?, QueQuan = ?, NgayBatDauLam = ?, SoDienThoai = ?, Email = ?, TinhTrang = ?, MaCV = ?, MaPB = ?, Avatar = ? WHERE MaNV = ?";
    $stmt = $conn->prepare($sql_update);
    if ($stmt === false) {
        die("Lỗi prepare update: " . $conn->error);
    }
    $stmt->bind_param("ssisssisiiisi", $hoten, $ngaysinh, $gioitinh, $dantoc, $quequan, $ngaybatdaulam, $sodienthoai, $email, $tinhtrang, $chucvu, $phongban, $avatar, $id);

    if ($stmt->execute()) {
        // Cập nhật thành công, làm mới dữ liệu
        $stmt->close();
        header("Location: /QLNhanVien_thuan/src/NhanVien/SuaNhanVien.php?id=$id");
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
                    <h4 class="mb-0 font-size-18">Quản Lý Thông Tin Nhân Viên</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/TrangChu.php">Trang Chủ</a></li>
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/NhanVien/NhanVien.php">Quản Lý Nhân Viên</a></li>
                            <li class="breadcrumb-item active">Chi Tiết Nhân Viên</li>
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
                    <h4 class="card-title">Thông tin chi tiết nhân viên</h4>
                    <div id="basic-datatable_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="row">
                            <div class="col-xl-12">
                            	<div class="card">
                                    <div class="card-body">
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="form-group">
                                                <div class="mb-3" style="width: 160px; height: 160px; margin-right: auto; margin-left: auto;">
                                                    <img style="width: 100%; height: 100%; border-radius: 50%;" src="<?php echo htmlspecialchars($detail[0]['Avatar'] ?: '/QLNhanVien_thuan/public/admin/images/avatars/default.png'); ?>" id="output">
                                                </div>
                                                <div style="text-align: center;">
                                                    <label for="selectfile" class="btn"><p style="font-weight: bold;"><i class="fa-solid fa-camera"></i> Chọn ảnh</p></label>
                                                    <input onchange="loadFile(event)" class="d-none" type="file" id="selectfile" name="avatar">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-4">
                                                    <label for="simpleinput">Tên Nhân Viên</label>
                                                    <input type="text" id="simpleinput" class="form-control" placeholder="Tên nhân viên" required name="hoten" value="<?php echo htmlspecialchars($detail[0]['HoTen']); ?>">
                                                </div>
                                                <div class="col-4">
                                                    <label for="simpleinput">Ngày Sinh</label>
                                                    <input type="date" id="simpleinput" class="form-control" placeholder="Ngày sinh" required name="ngaysinh" value="<?php echo htmlspecialchars($detail[0]['NgaySinh']); ?>">
                                                </div>
                                                <div class="col-4">
                                                    <label for="simpleinput">Giới Tính</label>
                                                    <select name="gioitinh" required class="form-control">
                                                        <option value="1" <?php echo $detail[0]['GioiTinh'] == 1 ? 'selected' : ''; ?>>Nam</option>
                                                        <option value="0" <?php echo $detail[0]['GioiTinh'] == 0 ? 'selected' : ''; ?>>Nữ</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-4">
                                                    <label for="simpleinput">Dân Tộc</label>
                                                    <input type="text" id="simpleinput" class="form-control" placeholder="Dân tộc" required name="dantoc" value="<?php echo htmlspecialchars($detail[0]['DanToc']); ?>">
                                                </div>
                                                <div class="col-4">
                                                    <label for="simpleinput">Quê Quán</label>
                                                    <input type="text" id="simpleinput" class="form-control" placeholder="Quê quán" required name="quequan" value="<?php echo htmlspecialchars($detail[0]['QueQuan']); ?>">
                                                </div>
                                                <div class="col-4">
                                                    <label for="simpleinput">Ngày Bắt Đầu Làm</label>
                                                    <input type="date" id="simpleinput" class="form-control" placeholder="Ngày bắt đầu làm" required name="ngaybatdaulam" value="<?php echo htmlspecialchars($detail[0]['NgayBatDauLam']); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-4">
                                                    <label for="simpleinput">Số Điện Thoại</label>
                                                    <input type="number" id="simpleinput" class="form-control" placeholder="Số điện thoại" required name="sodienthoai" value="<?php echo htmlspecialchars($detail[0]['SoDienThoai']); ?>">
                                                </div>
                                                <div class="col-4">
                                                    <label for="simpleinput">Email</label>
                                                    <input type="email" id="simpleinput" class="form-control" placeholder="Email" required name="email" value="<?php echo htmlspecialchars($detail[0]['Email']); ?>">
                                                </div>
                                                <div class="col-4">
                                                    <label for="simpleinput">Tình Trạng</label>
                                                    <select name="tinhtrang" required class="form-control">
                                                        <option value="1" <?php echo $detail[0]['TinhTrang'] == 1 ? 'selected' : ''; ?>>Đang làm việc</option>
                                                        <option value="0" <?php echo $detail[0]['TinhTrang'] == 0 ? 'selected' : ''; ?>>Đã nghỉ việc</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-4">
                                                    <label for="simpleinput">Chức Vụ</label>
                                                    <select name="chucvu" required class="form-control">
                                                        <?php foreach ($chucvu as $cv): ?>
                                                            <option value="<?php echo htmlspecialchars($cv['MaCV']); ?>" <?php echo $detail[0]['MaCV'] == $cv['MaCV'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cv['TenCV']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-4">
                                                    <label for="simpleinput">Phòng Ban</label>
                                                    <select name="phongban" required class="form-control">
                                                        <?php foreach ($phongban as $pb): ?>
                                                            <option value="<?php echo htmlspecialchars($pb['MaPB']); ?>" <?php echo $detail[0]['MaPB'] == $pb['MaPB'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($pb['TenPhongBan']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group row mt-4">
                                                <div class="col-12 text-left">
                                                    <a href="/QLNhanVien_thuan/src/NhanVien/NhanVien.php" class="btn btn-success waves-effect waves-light">Quay Lại</a>
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
<script>
  var loadFile = function(event) {
    var output = document.getElementById('output');
    output.src = URL.createObjectURL(event.target.files[0]);
    output.onload = function() {
      URL.revokeObjectURL(output.src); // free memory
    }
  };
</script>
<?php
require(__DIR__ . '/../layouts/footer.php');
$conn->close();
?>