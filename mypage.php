<?php
session_start();
if (!isset($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'connect.php';
$name = $_SESSION['name'] ?? '';
$email = $_SESSION['email'] ?? '';
$cno = $_SESSION['member_id'] ?? '';

// DB에서 passportNumber 조회
$passportNumber = '';
if ($cno) {
    $stmt = $conn->prepare("SELECT passportNumber FROM CUSTOMER WHERE cno = :cno");
    $stmt->bindParam(':cno', $cno);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $passportNumber = $row['PASSPORTNUMBER'];
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>마이페이지 - CNU Airline Reservation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5" style="max-width:500px;">
    <h2 class="mb-4 text-center">마이페이지</h2>
    <div class="card mb-4">
        <div class="card-body text-center">
            <h5 class="card-title mb-3">안녕하세요, <b><?= htmlspecialchars($name) ?></b>님!</h5>
            <p class="card-text">이메일: <?= htmlspecialchars($email) ?></p>
            <p class="card-text">여권번호: <?= htmlspecialchars($passportNumber) ?></p>
        </div>
    </div>
    <?php if ($cno !== 'C000'): ?>
        <div class="d-grid gap-2 mb-3">
            <a href="reservation_list.php" class="btn btn-primary btn-lg">내 예약/취소 내역</a>
        </div>
    <?php endif; ?>
    <?php if ($cno === 'C000'): ?>
        <div class="d-grid gap-2 mb-3">
            <a href="admin_stats.php" class="btn btn-warning btn-lg">통계정보조회</a>
        </div>
    <?php endif; ?>
    <div class="text-center">
        <a href="main.php" class="btn btn-secondary">메인으로</a>
    </div>
</div>
</body>
</html> 