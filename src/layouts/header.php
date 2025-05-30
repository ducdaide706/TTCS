<?php
session_start();

// Kiểm tra nếu chưa đăng nhập thì chuyển hướng về trang đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header("Location: DangNhap.php");
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

// Lấy thông tin phân quyền của tài khoản
$taikhoan = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT PhanQuyen, HoTen FROM taikhoan WHERE TaiKhoan = ?");
$stmt->bind_param("s", $taikhoan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $_SESSION['hoten'] = $user['HoTen']; // Lưu họ tên vào session
    $phanquyen = $user['PhanQuyen'];
} else {
    // Nếu không tìm thấy tài khoản, đăng xuất và chuyển hướng
    session_unset();
    session_destroy();
    header("Location: DangNhap.php");
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Quản Lý Nhân Viên - Hệ Thống Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="MyraStudio" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="/QLNhanVien_thuan/public/admin/images/favicon.ico">

    <!-- App css -->
    <link href="/QLNhanVien_thuan/public/admin/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="/QLNhanVien_thuan/public/admin/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="/QLNhanVien_thuan/public/admin/css/theme.min.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <!-- Begin page -->
    <div id="layout-wrapper">
        <header id="page-topbar">
            <div class="navbar-header">
                <div class="d-flex align-items-left">
                    <button type="button" class="btn btn-sm mr-2 d-lg-none px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                        <i class="fa fa-fw fa-bars"></i>
                    </button>

                    <?php if ($phanquyen == 3) { ?>
                        <div class="dropdown d-none d-sm-inline-block">
                            <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="mdi mdi-plus"></i> Thêm Mới
                                <i class="mdi mdi-chevron-down d-none d-sm-inline-block"></i>
                            </button>
                            <div class="dropdown-menu" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 70px, 0px);">
                                <a href="/QLNhanVien_thuan/src/NhanVien/ThemNhanVien.php" class="dropdown-item notify-item">Nhân Viên</a>
                                <a href="/QLNhanVien_thuan/src/PhongBan/ThemPhongBan.php" class="dropdown-item notify-item">Phòng Ban</a>
                                <a href="/QLNhanVien_thuan/src/ChucVu/ThemChucVu.php" class="dropdown-item notify-item">Chức Vụ</a>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="d-flex align-items-center">
                    <div class="dropdown d-inline-block ml-2">
                        <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span>Chào, <?php echo htmlspecialchars($_SESSION['hoten']); ?></span>
                            <i class="mdi mdi-chevron-down d-none d-sm-inline-block"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item d-flex align-items-center justify-content-between" href="DangXuat.php">
                                <span>Đăng Xuất</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- ========== Left Sidebar Start ========== -->
        <div class="vertical-menu">
            <div data-simplebar class="h-100">
                <div class="navbar-brand-box">
                    <a href="" class="logo">
                        <img src=""/>
                    </a>
                </div>

                <!-- Sidemenu -->
                <div id="sidebar-menu">
                    <ul class="metismenu list-unstyled" id="side-menu">
                        <li class="menu-title">TỔNG QUAN HỆ THỐNG</li>
                        <li>
                            <a href="/QLNhanVien_thuan/src/TrangChu.php" class="waves-effect"><i class='bx bx-home-smile'></i><span>Trang Chủ</span></a>
                        </li>

                        <li class="menu-title">QUẢN LÝ THÔNG TIN</li>
                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect"><i class="bx bxs-id-card"></i><span>Nhân Viên</span></a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="/QLNhanVien_thuan/src/NhanVien/NhanVien.php">Danh Sách</a></li>
                                <?php if ($phanquyen == 3) { ?>
                                    <li><a href="/QLNhanVien_thuan/src/NhanVien/ThemNhanVien.php">Thêm Mới</a></li>
                                <?php } ?>
                            </ul>
                        </li>

                        <li>
                            <?php if ($phanquyen == 3) { ?>
                                <a href="javascript: void(0);" class="has-arrow waves-effect"><i class="bx bxs-book-content"></i><span>Phòng Ban</span></a>
                                <ul class="sub-menu" aria-expanded="false">
                                    <li><a href="/QLNhanVien_thuan/src/PhongBan/PhongBan.php">Danh Sách</a></li>
                                    <li><a href="/QLNhanVien_thuan/src/PhongBan/ThemPhongBan.php">Thêm Mới</a></li>
                                </ul>
                            <?php } else { ?>
                                <a href=""><i class="bx bxs-book-content"></i><span>Phòng Ban</span></a>
                            <?php } ?>
                        </li>

                        <li>
                            <?php if ($phanquyen == 3) { ?>
                                <a href="javascript: void(0);" class="has-arrow waves-effect"><i class="bx bx-layer"></i><span>Chức Vụ</span></a>
                                <ul class="sub-menu" aria-expanded="false">
                                    <li><a href="/QLNhanVien_thuan/src/ChucVu/ChucVu.php">Danh Sách</a></li>
                                    <li><a href="/QLNhanVien_thuan/src/ChucVu/ThemChucVu.php">Thêm Mới</a></li>
                                </ul>
                            <?php } else { ?>
                                <a href=""><i class="bx bx-layer"></i><span>Chức Vụ</span></a>
                            <?php } ?>
                        </li>

                        <li class="menu-title">QUẢN LÝ TRẢ LƯƠNG</li>
                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect"><i class="bx bx-barcode"></i><span>Bảng Lương</span></a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="/QLNhanVien_thuan/src/BangLuong/Luong.php">Danh Sách</a></li>
                                <?php if ($phanquyen == 3) { ?>
                                    <li><a href="/QLNhanVien_thuan/src/BangLuong/ThemBangLuong.php">Thêm Mới</a></li>
                                <?php } ?>
                            </ul>
                        </li>

                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect"><i class="bx bxs-credit-card"></i><span>Trả Lương</span></a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="/QLNhanVien_thuan/src/TraLuong/TraLuong.php">Danh Sách</a></li>
                                <?php if ($phanquyen == 3) { ?>
                                    <li><a href="/QLNhanVien_thuan/src/TraLuong/ThemTraLuong.php">Thêm Mới</a></li>
                                <?php } ?>
                            </ul>
                        </li>
                    </ul>
                </div>
                <!-- Sidebar -->
            </div>
        </div>
        <!-- Left Sidebar End -->

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">