<?php
session_start();

// Xóa session cũ để đảm bảo không sử dụng session từ lần đăng nhập trước
session_unset();
session_destroy();
session_start();

// Kiểm tra nếu đã đăng nhập thì chuyển hướng đến trang chủ
if (isset($_SESSION['admin_id'])) {
    header("Location: TrangChu.php");
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

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $taikhoan = trim($_POST['taikhoan']);
    $matkhau = md5(trim($_POST['matkhau'])); // Mã hóa mật khẩu bằng MD5

    // Sử dụng prepared statement để kiểm tra tài khoản
    $stmt = $conn->prepare("SELECT TaiKhoan, HoTen FROM taikhoan WHERE TaiKhoan = ? AND MatKhau = ? AND PhanQuyen = 3");
    $stmt->bind_param("ss", $taikhoan, $matkhau);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Đăng nhập thành công
        $row = $result->fetch_assoc();
        $_SESSION['admin_id'] = $row['TaiKhoan'];
        $_SESSION['admin_name'] = $row['HoTen'];
        header("Location: TrangChu.php");
        exit();
    } else {
        $error = "Tài khoản hoặc mật khẩu không đúng, hoặc bạn không có quyền admin!";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Quản Lý Nhân Viên - Đăng Nhập Hệ Thống Admin!</title>
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

    <body class="bg-primary">
        <div>
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex align-items-center min-vh-100">
                            <div class="w-100 d-block my-5">
                                <div class="row justify-content-center">
                                    <div class="col-md-8 col-lg-5">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="text-center mb-4 mt-3">
                                                    <a href="#">
                                                        <span><img src="" alt="" height="26"></span>
                                                    </a>
                                                </div>
                                                <form method="POST" class="p-2">
                                                    <?php if (!empty($error)) { ?>
                                                        <p style="text-align: center; font-weight: bold; color: red;"><?php echo $error; ?></p>
                                                    <?php } ?>
                                                    <div class="form-group">
                                                        <label for="emailaddress">Tài khoản</label>
                                                        <input class="form-control" type="text" id="emailaddress" required name="taikhoan" placeholder="Nhập tài khoản...">
                                                    </div>
                                                    <div class="form-group">
                                                        <a href="pages-recoverpw.html" class="text-muted float-right">Quên mật khẩu?</a>
                                                        <label for="password">Mật khẩu</label>
                                                        <input class="form-control" type="password" required id="password" name="matkhau" placeholder="Nhập mật khẩu...">
                                                    </div>
                                                    <div class="mb-3 text-center">
                                                        <button class="btn btn-primary btn-block" type="submit"> Đăng Nhập </button>
                                                    </div>
                                                </form>
                                            </div>
                                            <!-- end card-body -->
                                        </div>
                                    </div>
                                    <!-- end col -->
                                </div>
                                <!-- end row -->
                            </div> <!-- end .w-100 -->
                        </div> <!-- end .d-flex -->
                    </div> <!-- end col-->
                </div> <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end page -->

        <!-- jQuery and JS files -->
        <script src="/QLNhanVien_thuan/public/admin/js/jquery.min.js"></script>
        <script src="/QLNhanVien_thuan/public/admin/js/bootstrap.bundle.min.js"></script>
        <script src="/QLNhanVien_thuan/public/admin/js/metismenu.min.js"></script>
        <script src="/QLNhanVien_thuan/public/admin/js/waves.js"></script>
        <script src="/QLNhanVien_thuan/public/admin/js/simplebar.min.js"></script>

        <!-- App js -->
        <script src="/QLNhanVien_thuan/public/admin/js/theme.js"></script>
    </body>
</html>