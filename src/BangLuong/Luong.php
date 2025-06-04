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

// Xử lý tìm kiếm
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Xử lý phân trang
$records_per_page = 10; // Số bản ghi mỗi trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Đếm tổng số bảng lương
$sql_count = "SELECT COUNT(*) as total FROM luong l JOIN nhanvien nv ON l.MaNV = nv.MaNV WHERE nv.HoTen LIKE ?";
$search_param = "%" . $search_query . "%";
$stmt = $conn->prepare($sql_count);
$stmt->bind_param("s", $search_param);
$stmt->execute();
$result_count = $stmt->get_result();
if (!$result_count) {
    die("Lỗi truy vấn đếm tổng số bảng lương: " . $conn->error);
}
$total_records = $result_count->fetch_assoc()['total'];
$totalPages = ceil($total_records / $records_per_page);

// Truy vấn danh sách bảng lương với thông tin liên quan
$sql = "SELECT l.MaLuong, nv.HoTen, nv.NgaySinh, nv.GioiTinh, pb.TenPhongBan, cv.TenCV, cv.LuongCoBan, l.HeSoLuong, l.ThuongPhuCap, l.HeSoPhuCap 
        FROM luong l
        JOIN nhanvien nv ON l.MaNV = nv.MaNV
        JOIN phongban pb ON nv.MaPB = pb.MaPB
        JOIN chucvu cv ON nv.MaCV = cv.MaCV
        WHERE nv.HoTen LIKE ?
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi prepare: " . $conn->error);
}
$stmt->bind_param("sii", $search_param, $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require(__DIR__ . '/../layouts/header.php');
?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0 font-size-18">Quản Lý Thông Tin Bảng Lương</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/QLNhanVien_thuan/src/TrangChu.php">Trang Chủ</a></li>
                            <li class="breadcrumb-item active">Quản Lý Bảng Lương</li>
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
                    <h4 class="card-title">Danh sách thông tin bảng lương</h4>
                    <div id="basic-datatable_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="row mb-3">
                            <div class="col-sm-12 col-md-6">
                                <form method="GET" action="">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Tìm theo tên nhân viên...">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit">Tìm</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th>STT</th>
                                                <th>Tên Nhân Viên</th>
                                                <th>Ngày Sinh</th>
                                                <th>Giới Tính</th>
                                                <th>Tên Phòng Ban</th>
                                                <th>Chức Vụ</th>
                                                <th>Lương Cơ Bản</th>
                                                <th>Hệ Số Lương</th>
                                                <th>Thưởng Phụ Cấp</th>
                                                <th>Hệ Số Phụ Cấp</th>
                                                <th>Xem Chi Tiết</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($list as $key => $value): ?>
                                                <tr role="row" class="odd">
                                                    <td><?php echo $key + 1 + $offset; ?></td>
                                                    <td><?php echo htmlspecialchars($value['HoTen']); ?></td>
                                                    <td><?php echo date("d/m/Y", strtotime($value['NgaySinh'])); ?></td>
                                                    <td>
                                                        <?php 
                                                            if ($value['GioiTinh'] == 1) {
                                                                echo "Nam"; 
                                                            } else {
                                                                echo "Nữ"; 
                                                            }
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($value['TenPhongBan']); ?></td>
                                                    <td><?php echo htmlspecialchars($value['TenCV']); ?></td>
                                                    <td><?php echo number_format($value['LuongCoBan']); ?> VNĐ / tháng</td>
                                                    <td>x <?php echo htmlspecialchars($value['HeSoLuong']); ?></td>
                                                    <td><?php echo number_format($value['ThuongPhuCap']); ?> VNĐ / tháng</td>
                                                    <td>x <?php echo htmlspecialchars($value['HeSoPhuCap']); ?></td>
                                                    <td>
                                                        <a class="btn btn-secondary" style="width: 100%;" href="/QLNhanVien_thuan/src/BangLuong/SuaBangLuong.php?id=<?php echo $value['MaLuong']; ?>">XEM</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="dataTables_paginate paging_simple_numbers" id="basic-datatable_paginate">
                                    <ul class="pagination pagination-rounded">
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li style="margin-right: 5px;" class="paginate_button page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a href="/QLNhanVien_thuan/src/BangLuong/BangLuong.php?path=bang-luong&page=<?php echo $i; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" class="page-link"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
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