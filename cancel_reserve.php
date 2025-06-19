<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'connect.php';

if (!isset($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}

$cno = $_SESSION['member_id'];
$flightNo = $_POST['flightNo'] ?? '';
$departureDateTime = $_POST['departureDateTime'] ?? '';
$seatClass = $_POST['seatClass'] ?? '';
$confirm = $_POST['confirm'] ?? '';

// 1. 예약 정보 조회
$sql = "SELECT * FROM RESERVE WHERE flightNo = :flightNo AND departureDateTime = :departureDateTime AND seatClass = :seatClass AND cno = :cno";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':flightNo', $flightNo);
$stmt->bindParam(':departureDateTime', $departureDateTime);
$stmt->bindParam(':seatClass', $seatClass);
$stmt->bindParam(':cno', $cno);
$stmt->execute();
$reserve = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reserve) {
    echo '<div class="alert alert-danger">예약 정보를 찾을 수 없습니다.</div>';
    echo '<a href="reservation_list.php" class="btn btn-secondary mt-3">내역으로</a>';
    exit;
}

$payment = $reserve['PAYMENT'];
$flight_date = substr($departureDateTime, 0, 10);
$today = date('Y-m-d');
$diff = (int)((strtotime($flight_date) - strtotime($today)) / 86400);

// 2. 위약금 계산
if ($diff > 15) {
    $penalty = 150000;
} elseif ($diff >= 4) {
    $penalty = 180000;
} elseif ($diff >= 1) {
    $penalty = 250000;
} else {
    $penalty = $payment; // 당일: 전액
}
$refund = max(0, $payment - $penalty);

if ($confirm !== '1') {
    // 1단계: 취소 정보 안내 및 확인
    ?>
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <title>예약 취소 확인 - CNU Airline Reservation</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>
    <body>
    <div class="container mt-5" style="max-width:600px;">
        <div class="alert alert-warning">
            <b>정말 이 예약을 취소하시겠습니까?</b><br>
            <b>결제금액:</b> <?= number_format($payment) ?>원<br>
            <b>취소 위약금:</b> <?= number_format($penalty) ?>원<br>
            <b>환불금액:</b> <?= number_format($refund) ?>원<br>
        </div>
        <form method="post">
            <input type="hidden" name="flightNo" value="<?= htmlspecialchars($flightNo) ?>">
            <input type="hidden" name="departureDateTime" value="<?= htmlspecialchars($departureDateTime) ?>">
            <input type="hidden" name="seatClass" value="<?= htmlspecialchars($seatClass) ?>">
            <input type="hidden" name="confirm" value="1">
            <button type="submit" class="btn btn-danger">네, 취소합니다</button>
            <a href="reservation_list.php" class="btn btn-secondary">아니오, 돌아가기</a>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// 2단계: 실제 취소 처리
$conn->beginTransaction();
try {
    // RESERVE 삭제
    $sql = "DELETE FROM RESERVE WHERE flightNo = :flightNo AND departureDateTime = :departureDateTime AND seatClass = :seatClass AND cno = :cno";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':flightNo', $flightNo);
    $stmt->bindParam(':departureDateTime', $departureDateTime);
    $stmt->bindParam(':seatClass', $seatClass);
    $stmt->bindParam(':cno', $cno);
    $stmt->execute();

    // CANCEL insert
    $sql = "INSERT INTO CANCEL (flightNo, departureDateTime, seatClass, refund, cancelDateTime, cno)
            VALUES (:flightNo, :departureDateTime, :seatClass, :refund, SYSTIMESTAMP, :cno)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':flightNo', $flightNo);
    $stmt->bindParam(':departureDateTime', $departureDateTime);
    $stmt->bindParam(':seatClass', $seatClass);
    $stmt->bindParam(':refund', $refund);
    $stmt->bindParam(':cno', $cno);
    $stmt->execute();

    // SEATS 좌석수 +1
    $sql = "UPDATE SEATS SET no_of_seats = no_of_seats + 1 WHERE flightNo = :flightNo AND departureDateTime = :departureDateTime AND seatClass = :seatClass";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':flightNo', $flightNo);
    $stmt->bindParam(':departureDateTime', $departureDateTime);
    $stmt->bindParam(':seatClass', $seatClass);
    $stmt->execute();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollBack();
    echo '<div class="alert alert-danger">취소 처리 중 오류가 발생했습니다.<br>';
    echo '에러: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<a href="reservation_list.php" class="btn btn-secondary mt-3">내역으로</a>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>예약 취소 완료 - CNU Airline Reservation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5" style="max-width:600px;">
    <div class="alert alert-success">
        예약이 성공적으로 취소되었습니다.<br>
        <b>결제금액:</b> <?= number_format($payment) ?>원<br>
        <b>취소 위약금:</b> <?= number_format($penalty) ?>원<br>
        <b>환불금액:</b> <?= number_format($refund) ?>원<br>
    </div>
    <a href="reservation_list.php" class="btn btn-primary">내역으로</a>
</div>
</body>
</html> 