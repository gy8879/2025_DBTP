<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'connect.php';
require_once 'config.php';

// PHPMailer 파일 직접 불러오기 (ZIP 수동 설치 방식)
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 로그인 체크
if (!isset($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}

$cno = $_SESSION['member_id'];
$flightNo = $_POST['flightNo'] ?? '';
$departureDateTime = $_POST['departureDateTime'] ?? '';
$seatClass = $_POST['seatClass'] ?? '';
$confirm = $_POST['confirm'] ?? '';

// 1. 남은 좌석 확인 및 가격 조회
$sql = "SELECT price, no_of_seats FROM SEATS WHERE flightNo = :flightNo AND departureDateTime = :departureDateTime AND seatClass = :seatClass";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':flightNo', $flightNo);
$stmt->bindParam(':departureDateTime', $departureDateTime);
$stmt->bindParam(':seatClass', $seatClass);
$stmt->execute();
$seat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seat || $seat['NO_OF_SEATS'] <= 0) {
    echo '<div class="alert alert-danger">예약 불가: 남은 좌석이 없습니다.</div>';
    echo '<a href="javascript:history.back()" class="btn btn-secondary mt-3">이전으로</a>';
    exit;
}

// 중복 예약 체크
$sql = "SELECT COUNT(*) FROM RESERVE WHERE flightNo = :flightNo AND departureDateTime = :departureDateTime AND seatClass = :seatClass AND cno = :cno";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':flightNo', $flightNo);
$stmt->bindParam(':departureDateTime', $departureDateTime);
$stmt->bindParam(':seatClass', $seatClass);
$stmt->bindParam(':cno', $cno);
$stmt->execute();
$count = $stmt->fetchColumn();

if ($count > 0) {
    echo '<div class="alert alert-danger">이미 예약된 항공편입니다.</div>';
    echo '<a href="main.php" class="btn btn-secondary mt-3">메인으로</a>';
    exit;
}

$price = $seat['PRICE'];

// 2단계: 결제 버튼 클릭 시 실제 예약 처리
if ($confirm === '1') {
    $conn->beginTransaction(); // 트랜잭션 시작
    // 예약 정보 RESERVE 테이블에 insert
    $sql = "INSERT INTO RESERVE (flightNo, departureDateTime, seatClass, payment, reserveDateTime, cno)
            VALUES (:flightNo, :departureDateTime, :seatClass, :payment, SYSTIMESTAMP, :cno)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':flightNo', $flightNo);
    $stmt->bindParam(':departureDateTime', $departureDateTime);
    $stmt->bindParam(':seatClass', $seatClass);
    $stmt->bindParam(':payment', $price);
    $stmt->bindParam(':cno', $cno);
    $stmt->execute();

    // SEATS 테이블 좌석수 -1
    $sql = "UPDATE SEATS SET no_of_seats = no_of_seats - 1 WHERE flightNo = :flightNo AND departureDateTime = :departureDateTime AND seatClass = :seatClass";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':flightNo', $flightNo);
    $stmt->bindParam(':departureDateTime', $departureDateTime);
    $stmt->bindParam(':seatClass', $seatClass);
    $stmt->execute();

    // 회원 이메일, 이름 조회
    $sql = "SELECT email, name FROM CUSTOMER WHERE cno = :cno";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':cno', $cno);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    // 항공편 정보 조회 (탑승권용)
    $sql = "SELECT airline, departureAirport, arrivalAirport, departureDateTime, arrivalDateTime FROM AIRPLAIN WHERE flightNo = :flightNo AND departureDateTime = :departureDateTime";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':flightNo', $flightNo);
    $stmt->bindParam(':departureDateTime', $departureDateTime);
    $stmt->execute();
    $flight = $stmt->fetch(PDO::FETCH_ASSOC);

    // 이메일 전송 (PHPMailer)
    $to = $customer['EMAIL'];
    $subject = "[CNU Airline] 탑승권 발급 안내";
    $message = "{$customer['NAME']}님, 예약이 완료되었습니다.\n" .
        "항공사: {$flight['AIRLINE']}\n" .
        "운항편명: {$flightNo}\n" .
        "출발공항: {$flight['DEPARTUREAIRPORT']}\n" .
        "도착공항: {$flight['ARRIVALAIRPORT']}\n" .
        "출발: {$flight['DEPARTUREDATETIME']}\n" .
        "도착: {$flight['ARRIVALDATETIME']}\n" .
        "좌석등급: {$seatClass}\n" .
        "결제금액: {$price}원\n";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = 'tls';
        $mail->Port = $smtp_port;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom($smtp_from_email, $smtp_from_name);
        $mail->addAddress($to, $customer['NAME']);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        $conn->commit();
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
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
        <title>예약 완료 - CNU Airline Reservation</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>
    <body>
    <div class="container mt-5" style="max-width:600px;">
        <div class="alert alert-success">
            예약이 완료되었습니다!<br>
            탑승권이 이메일(<?= htmlspecialchars($customer['EMAIL']) ?>)로 발송되었습니다.<br>
            <br>
            <b>항공사:</b> <?= htmlspecialchars($flight['AIRLINE']) ?><br>
            <b>운항편명:</b> <?= htmlspecialchars($flightNo) ?><br>
            <b>출발공항:</b> <?= htmlspecialchars($flight['DEPARTUREAIRPORT']) ?><br>
            <b>도착공항:</b> <?= htmlspecialchars($flight['ARRIVALAIRPORT']) ?><br>
            <b>출발:</b> <?= htmlspecialchars($flight['DEPARTUREDATETIME']) ?><br>
            <b>도착:</b> <?= htmlspecialchars($flight['ARRIVALDATETIME']) ?><br>
            <b>좌석등급:</b> <?= htmlspecialchars($seatClass) ?><br>
            <b>결제금액:</b> <?= number_format($price) ?>원<br>
        </div>
        <a href="main.php" class="btn btn-primary">메인으로</a>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// 1단계: 예약 정보 확인 화면
// 항공편 정보 조회 (탑승권용)
$sql = "SELECT airline, departureAirport, arrivalAirport, departureDateTime, arrivalDateTime FROM AIRPLAIN WHERE flightNo = :flightNo AND departureDateTime = :departureDateTime";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':flightNo', $flightNo);
$stmt->bindParam(':departureDateTime', $departureDateTime);
$stmt->execute();
$flight = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>예약 정보 확인 - CNU Airline Reservation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5" style="max-width:600px;">
    <div class="alert alert-info">
        <h4>예약 정보를 확인해 주세요</h4>
        <b>항공사:</b> <?= htmlspecialchars($flight['AIRLINE']) ?><br>
        <b>운항편명:</b> <?= htmlspecialchars($flightNo) ?><br>
        <b>출발공항:</b> <?= htmlspecialchars($flight['DEPARTUREAIRPORT']) ?><br>
        <b>도착공항:</b> <?= htmlspecialchars($flight['ARRIVALAIRPORT']) ?><br>
        <b>출발:</b> <?= htmlspecialchars($flight['DEPARTUREDATETIME']) ?><br>
        <b>도착:</b> <?= htmlspecialchars($flight['ARRIVALDATETIME']) ?><br>
        <b>좌석등급:</b> <?= htmlspecialchars($seatClass) ?><br>
        <b>결제금액:</b> <?= number_format($price) ?>원<br>
    </div>
    <form method="post">
        <input type="hidden" name="flightNo" value="<?= htmlspecialchars($flightNo) ?>">
        <input type="hidden" name="departureDateTime" value="<?= htmlspecialchars($departureDateTime) ?>">
        <input type="hidden" name="seatClass" value="<?= htmlspecialchars($seatClass) ?>">
        <input type="hidden" name="confirm" value="1">
        <button type="submit" class="btn btn-primary btn-lg">결제</button>
        <a href="main.php" class="btn btn-secondary btn-lg">취소</a>
    </form>
</div>
</body>
</html> 