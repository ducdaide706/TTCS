<?php
session_start();

// Kiểm tra nếu admin chưa đăng nhập thì chuyển hướng về trang đăng nhập
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

// Truy vấn tổng số nhân viên
$sql_nhanvien = "SELECT COUNT(*) as total FROM nhanvien";
$result_nhanvien = $conn->query($sql_nhanvien);
$tongnhanvien = $result_nhanvien->fetch_assoc()['total'];

// Truy vấn tổng số phòng ban
$sql_phongban = "SELECT COUNT(*) as total FROM phongban";
$result_phongban = $conn->query($sql_phongban);
$tongphongban = $result_phongban->fetch_assoc()['total'];

// Truy vấn tổng số bảng lương
$sql_bangluong = "SELECT COUNT(*) as total FROM luong";
$result_bangluong = $conn->query($sql_bangluong);
$tongbangluong = $result_bangluong->fetch_assoc()['total'];

// Truy vấn số nhân viên đã nhận lương trong tháng hiện tại
$current_month = date('m');
$current_year = date('Y');
$sql_traluong = "SELECT COUNT(*) as total FROM traluong WHERE Thang = ? AND Nam = ?";
$stmt_traluong = $conn->prepare($sql_traluong);
$stmt_traluong->bind_param("ii", $current_month, $current_year);
$stmt_traluong->execute();
$result_traluong = $stmt_traluong->get_result();
$tongtraluong = $result_traluong->fetch_assoc()['total'];
$stmt_traluong->close();

// Truy vấn danh sách nhân viên có sinh nhật hôm nay 
$current_date = date('Y-m-d'); 
$sql_birthday = "SELECT HoTen FROM nhanvien WHERE DAY(NgaySinh) = DAY(?) AND MONTH(NgaySinh) = MONTH(?)";
$stmt_birthday = $conn->prepare($sql_birthday);
$stmt_birthday->bind_param("ss", $current_date, $current_date);
$stmt_birthday->execute();
$result_birthday = $stmt_birthday->get_result();
$birthdays = $result_birthday->fetch_all(MYSQLI_ASSOC);
$stmt_birthday->close();

// Xử lý hiển thị sinh nhật
$birthday_message = "Hôm nay không có sinh nhật nào";
if (count($birthdays) > 0) {
    if (count($birthdays) == 1) {
        $birthday_message = "Hôm nay là sinh nhật của " . htmlspecialchars($birthdays[0]['HoTen']);
    } elseif (count($birthdays) <= 3) {
        $names = array_map(function($person) { return htmlspecialchars($person['HoTen']); }, $birthdays);
        $birthday_message = "Hôm nay là sinh nhật của " . implode(", ", $names);
    } else {
        $first_name = htmlspecialchars($birthdays[0]['HoTen']);
        $others_count = count($birthdays) - 1;
        $birthday_message = "Hôm nay là sinh nhật của $first_name và $others_count người khác";
    }
}

$conn->close();

require(__DIR__.'/layouts/header.php');
?>

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0 font-size-18">Trang Chủ</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="">Hệ Thống</a></li>
                            <li class="breadcrumb-item active">Trang Chủ</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <i class="bx bxs-id-card float-right m-0 h2 text-muted"></i>
                        <h6 class="text-muted text-uppercase mt-0">SỐ NHÂN VIÊN</h6>
                        <h3 class="mb-3" data-plugin="counterup">
                            <?php echo $tongnhanvien; ?> nhân viên
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <i class="bx bxs-book-content float-right m-0 h2 text-muted"></i>
                        <h6 class="text-muted text-uppercase mt-0">SỐ PHÒNG BAN</h6>
                        <h3 class="mb-3">
                            <?php echo $tongphongban; ?> phòng ban
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <i class="bx bx-barcode float-right m-0 h2 text-muted"></i>
                        <h6 class="text-muted text-uppercase mt-0">SỐ BẢNG LƯƠNG</h6>
                        <h3 class="mb-3">
                            <?php echo $tongbangluong; ?> bảng lương
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <i class="bx bxs-credit-card float-right m-0 h2 text-muted"></i>
                        <h6 class="text-muted text-uppercase mt-0">ĐÃ NHẬN LƯƠNG THÁNG <?php echo date('m'); ?></h6>
                        <h3 class="mb-3" data-plugin="counterup">
                            <?php echo $tongtraluong; ?> nhân viên
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <i class="bx bxs-cake float-right m-0 h2 text-muted"></i>
                        <h6 class="text-muted text-uppercase mt-0">SINH NHẬT HÔM NAY</h6>
                        <h3 class="mb-3">
                            <?php echo $birthday_message; ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- container-fluid -->
</div>

<?php require(__DIR__.'/layouts/footer.php'); ?>