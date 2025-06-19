<?php
session_start();
require_once 'connect.php';

function format_time($datetime_str) {
    // 09:00:00.000000 또는 09:00:00 형식에서 시:분만 추출
    if (preg_match('/(\d{1,2}):(\d{2}):\d{2}(\.\d+)?/', $datetime_str, $m)) {
        // 0으로 시작하는 시는 한 자리로
        $h = ltrim($m[1], '0');
        if ($h === '') $h = '0';
        return $h . ':' . $m[2];
    }
    // strtotime이 가능하면 시:분
    $t = strtotime($datetime_str);
    if ($t !== false) return date('G:i', $t);
    return $datetime_str;
}

// 입력값 받기
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';
$seat_class = $_GET['seat_class'] ?? 'all';
$sort = $_GET['sort'] ?? 'price';

// 정렬 기준 결정
$order_by = 's.price ASC';
if ($sort === 'departure') $order_by = 'a.departureDateTime ASC';
if ($sort === 'arrival') $order_by = 'a.arrivalDateTime ASC';

// 테이블 구조에 맞는 검색 쿼리
$sql = "
SELECT 
    a.airline, a.flightNo, a.departureAirport, a.arrivalAirport,
    a.departureDateTime, a.arrivalDateTime,
    s.seatClass, s.price, s.no_of_seats
FROM AIRPLAIN a
JOIN SEATS s ON a.flightNo = s.flightNo AND a.departureDateTime = s.departureDateTime
WHERE a.departureAirport = :from_airport
  AND a.arrivalAirport = :to_airport
  AND TO_CHAR(a.departureDateTime, 'YYYY-MM-DD') = :departure_date
";
if ($seat_class !== 'all') {
    $sql .= "  AND s.seatClass = :seat_class\n";
}
$sql .= "ORDER BY $order_by\n";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':from_airport', $from);
$stmt->bindParam(':to_airport', $to);
$stmt->bindParam(':departure_date', $date);
if ($seat_class !== 'all') {
    $stmt->bindParam(':seat_class', $seat_class);
}
$stmt->execute();
$results = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>항공기 검색 결과 - CNU Airline Reservation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">항공기 검색 결과</h2>
    <a href="main.php" class="btn btn-secondary mb-3">← 메인으로</a>
    <form method="get" action="search.php" class="mb-3">
        <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
        <input type="hidden" name="to" value="<?= htmlspecialchars($to) ?>">
        <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
        <input type="hidden" name="seat_class" value="<?= htmlspecialchars($seat_class) ?>">
        <label for="sort" class="form-label">정렬 기준:</label>
        <select name="sort" id="sort" class="form-select d-inline-block w-auto">
            <option value="price" <?= $sort == 'price' ? 'selected' : '' ?>>요금</option>
            <option value="departure" <?= $sort == 'departure' ? 'selected' : '' ?>>출발날짜시간</option>
            <option value="arrival" <?= $sort == 'arrival' ? 'selected' : '' ?>>도착날짜시간</option>
        </select>
        <button type="submit" class="btn btn-outline-primary btn-sm">정렬</button>
    </form>
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>항공사</th>
                <th>운항편명</th>
                <th>출발공항</th>
                <th>도착공항</th>
                <th>출발날짜시간</th>
                <th>도착날짜시간</th>
                <th>좌석등급</th>
                <th>요금</th>
                <th>남은좌석</th>
                <th>예약</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($results) > 0): ?>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['AIRLINE']) ?></td>
                    <td><?= htmlspecialchars($row['FLIGHTNO']) ?></td>
                    <td><?= htmlspecialchars($row['DEPARTUREAIRPORT']) ?></td>
                    <td><?= htmlspecialchars($row['ARRIVALAIRPORT']) ?></td>
                    <td><?= htmlspecialchars(substr($row['DEPARTUREDATETIME'], 0, 10)) ?> <?= htmlspecialchars(format_time($row['DEPARTUREDATETIME'])) ?></td>
                    <td><?= htmlspecialchars(substr($row['ARRIVALDATETIME'], 0, 10)) ?> <?= htmlspecialchars(format_time($row['ARRIVALDATETIME'])) ?></td>
                    <td><?= htmlspecialchars($row['SEATCLASS']) ?></td>
                    <td><?= number_format($row['PRICE']) ?>원</td>
                    <td><?= htmlspecialchars($row['NO_OF_SEATS']) ?></td>
                    <td>
                        <?php if ($row['NO_OF_SEATS'] > 0): ?>
                            <form action="reserve.php" method="post" style="margin:0;">
                                <input type="hidden" name="flightNo" value="<?= htmlspecialchars($row['FLIGHTNO']) ?>">
                                <input type="hidden" name="departureDateTime" value="<?= htmlspecialchars($row['DEPARTUREDATETIME']) ?>">
                                <input type="hidden" name="seatClass" value="<?= htmlspecialchars($row['SEATCLASS']) ?>">
                                <button type="submit" class="btn btn-primary btn-sm">예약하러가기</button>
                            </form>
                        <?php else: ?>
                            <span class="text-danger">매진</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="10" class="text-center">검색 결과가 없습니다.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html> 