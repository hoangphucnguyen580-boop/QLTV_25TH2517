<?php
// Trang chọn: thủ thư vào khu quản lý, độc giả vào khu tra cứu.
// Trang này chỉ có hai đường link, không đọc CSDL, không cần đăng nhập.
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thư viện</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
<main class="container">
    <h1 class="h3 text-center mb-4">Hệ thống quản lý mượn - trả sách</h1>
    <div class="row justify-content-center g-4">
        <div class="col-12 col-md-5 col-lg-4">
            <div class="card h-100 text-center shadow-sm">
                <div class="card-body p-4 d-flex flex-column">
                    <h2 class="h5">Thủ thư</h2>
                    <p class="text-muted">Quản lý sách, độc giả, lập phiếu mượn, nhận trả sách, xem thống kê.</p>
                    <a href="admin/" class="btn btn-primary mt-auto">Vào khu quản lý</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-5 col-lg-4">
            <div class="card h-100 text-center shadow-sm">
                <div class="card-body p-4 d-flex flex-column">
                    <h2 class="h5">Độc giả</h2>
                    <p class="text-muted">Xem sách đang mượn, hạn trả và lịch sử mượn - trả của bạn.</p>
                    <a href="tracuu/" class="btn btn-success mt-auto">Tra cứu</a>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
