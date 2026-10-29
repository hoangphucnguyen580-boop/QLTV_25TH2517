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
    <link rel="stylesheet" href="assets/fonts/fonts.css">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-body">
<main class="choose-wrap">
    <div class="choose-title">
        <div class="logo"><img class="ic" src="assets/icons/den/book.svg" alt="" width="30" height="30">Thư viện</div>
        <h1>Hệ thống quản lý mượn - trả sách</h1>
    </div>
    <div class="choose-grid">
        <section class="choose-card">
            <div class="choose-top tt">
                <img class="ic" src="assets/icons/trang/journal-text.svg" alt="" width="28" height="28">
                <h2>Thủ thư</h2>
            </div>
            <div class="choose-body">
                <p>Quản lý sách, độc giả, lập phiếu mượn, nhận trả sách, xem thống kê.</p>
                <a href="admin/" class="btn btn-primary">Vào khu quản lý</a>
            </div>
        </section>
        <section class="choose-card">
            <div class="choose-top dg">
                <img class="ic" src="assets/icons/den/person.svg" alt="" width="28" height="28">
                <h2>Độc giả</h2>
            </div>
            <div class="choose-body">
                <p>Xem sách đang mượn, hạn trả và lịch sử mượn - trả của bạn.</p>
                <a href="tracuu/" class="btn btn-outline-primary">Tra cứu</a>
            </div>
        </section>
    </div>
</main>
</body>
</html>
