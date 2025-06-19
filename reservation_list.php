<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}

$cno = $_SESSION['member_id'];
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$type = $_GET['type'] ?? 'all'; // all, reserve, cancel

// 예약 내역 조회
$reserve_sql = "
SELECT r.*, a.airline, a.departureAirport, a.arrivalAirport, 
       TO_CHAR(a.departureDateTime, 'YYYY-MM-DD HH24:MI:SS') AS flight_departure, 
       a.arrivalDateTime AS flight_arrival
FROM RESERVE r
JOIN AIRPLAIN a ON r.flightNo = a.flightNo AND r.departureDateTime = a.departureDateTime
WHERE r.cno = :cno
  AND r.reserveDateTime BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') AND TO_DATE(:end_date, 'YYYY-MM-DD') + 1
ORDER BY r.reserveDateTime DESC
";
$reserve_stmt = $conn->prepare($reserve_sql);
$reserve_stmt->bindParam(':cno', $cno);
$reserve_stmt->bindParam(':start_date', $start_date);
$reserve_stmt->bindParam(':end_date', $end_date);
$reserve_stmt->execute();
$reserves = $reserve_stmt->fetchAll();

// 취소 내역 조회
$cancel_sql = "
SELECT c.*, a.airline, a.departureAirport, a.arrivalAirport, a.departureDateTime AS flight_departure, a.arrivalDateTime AS flight_arrival
FROM CANCEL c
JOIN AIRPLAIN a ON c.flightNo = a.flightNo AND c.departureDateTime = a.departureDateTime
WHERE c.cno = :cno
  AND c.cancelDateTime BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') AND TO_DATE(:end_date, 'YYYY-MM-DD') + 1
ORDER BY c.cancelDateTime DESC
";
$cancel_stmt = $conn->prepare($cancel_sql);
$cancel_stmt->bindParam(':cno', $cno);
$cancel_stmt->bindParam(':start_date', $start_date);
$cancel_stmt->bindParam(':end_date', $end_date);
$cancel_stmt->execute();
$cancels = $cancel_stmt->fetchAll();

function format_time($datetime_str) {
    if (preg_match('/(\d{1,2}):(\d{2}):\d{2}(\.\d+)?/', $datetime_str, $m)) {
        $h = ltrim($m[1], '0');
        if ($h === '') $h = '0';
        return $h . ':' . $m[2];
    }
    $t = strtotime($datetime_str);
    if ($t !== false) return date('G:i', $t);
    return $datetime_str;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>예약/취소 내역 - CNU Airline Reservation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">예약/취소 내역 조회</h2>
    <form method="get" class="row g-2 mb-4">
        <div class="col-auto">
            <label for="start_date" class="form-label">시작일</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="col-auto">
            <label for="end_date" class="form-label">종료일</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="col-auto">
            <label for="type" class="form-label">구분</label>
            <select class="form-select" id="type" name="type">
                <option value="all" <?= $type=='all'?'selected':'' ?>>전체</option>
                <option value="reserve" <?= $type=='reserve'?'selected':'' ?>>예약</option>
                <option value="cancel" <?= $type=='cancel'?'selected':'' ?>>취소</option>
            </select>
        </div>
        <div class="col-auto align-self-end">
            <button type="submit" class="btn btn-primary">조회</button>
        </div>
    </form>
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>구분</th>
                <th>항공사</th>
                <th>운항편명</th>
                <th>출발공항</th>
                <th>도착공항</th>
                <th>출발날짜시간</th>
                <th>도착날짜시간</th>
                <th>좌석등급</th>
                <th>결제/환불금액</th>
                <th>예약/취소일시</th>
                <th>취소</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($type == 'all' || $type == 'reserve') {
            foreach ($reserves as $row) {
                echo '<tr>';
                echo '<td>예약</td>';
                echo '<td>' . htmlspecialchars($row['AIRLINE']) . '</td>';
                echo '<td>' . htmlspecialchars($row['FLIGHTNO']) . '</td>';
                echo '<td>' . htmlspecialchars($row['DEPARTUREAIRPORT']) . '</td>';
                echo '<td>' . htmlspecialchars($row['ARRIVALAIRPORT']) . '</td>';
                echo '<td>' . htmlspecialchars(substr($row['FLIGHT_DEPARTURE'],0,10)) . ' ' . htmlspecialchars(format_time($row['FLIGHT_DEPARTURE'])) . '</td>';
                echo '<td>' . htmlspecialchars(substr($row['FLIGHT_ARRIVAL'],0,10)) . ' ' . htmlspecialchars(format_time($row['FLIGHT_ARRIVAL'])) . '</td>';
                echo '<td>' . htmlspecialchars($row['SEATCLASS']) . '</td>';
                echo '<td>' . number_format($row['PAYMENT']) . '원</td>';
                echo '<td>' . htmlspecialchars($row['RESERVEDATETIME']) . '</td>';
                echo '<td>';
                echo '<form action="cancel_reserve.php" method="post" style="margin:0;">';
                echo '<input type="hidden" name="flightNo" value="' . htmlspecialchars($row['FLIGHTNO']) . '">' ;
                echo '<input type="hidden" name="departureDateTime" value="' . htmlspecialchars($row['FLIGHT_DEPARTURE']) . '">';
                echo '<input type="hidden" name="seatClass" value="' . htmlspecialchars($row['SEATCLASS']) . '">';
                echo '<button type="submit" class="btn btn-danger btn-sm">취소</button>';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
        }
        if ($type == 'all' || $type == 'cancel') {
            foreach ($cancels as $row) {
                echo '<tr>';
                echo '<td>취소</td>';
                echo '<td>' . htmlspecialchars($row['AIRLINE']) . '</td>';
                echo '<td>' . htmlspecialchars($row['FLIGHTNO']) . '</td>';
                echo '<td>' . htmlspecialchars($row['DEPARTUREAIRPORT']) . '</td>';
                echo '<td>' . htmlspecialchars($row['ARRIVALAIRPORT']) . '</td>';
                echo '<td>' . htmlspecialchars(substr($row['FLIGHT_DEPARTURE'],0,10)) . ' ' . htmlspecialchars(format_time($row['FLIGHT_DEPARTURE'])) . '</td>';
                echo '<td>' . htmlspecialchars(substr($row['FLIGHT_ARRIVAL'],0,10)) . ' ' . htmlspecialchars(format_time($row['FLIGHT_ARRIVAL'])) . '</td>';
                echo '<td>' . htmlspecialchars($row['SEATCLASS']) . '</td>';
                echo '<td>' . number_format($row['REFUND']) . '원</td>';
                echo '<td>' . htmlspecialchars($row['CANCELDATETIME']) . '</td>';
                echo '<td>-</td>';
                echo '</tr>';
            }
        }
        if (count($reserves) == 0 && count($cancels) == 0) {
            echo '<tr><td colspan="11" class="text-center">내역이 없습니다.</td></tr>';
        }
        ?>
        </tbody>
    </table>
    <a href="main.php" class="btn btn-secondary">메인으로</a>
</div>
</body>
</html> 