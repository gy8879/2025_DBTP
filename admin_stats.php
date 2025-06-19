<?php
session_start();
if (!isset($_SESSION['member_id']) || $_SESSION['member_id'] !== 'C000') {
    echo '<div style="margin:50px auto;max-width:500px;text-align:center;" class="alert alert-danger">관리자만 접근 가능합니다.</div>';
    exit;
}
require_once 'connect.php';

// 1. 노선별 예약/매출 통계 (그룹 함수)
$sql1 = "SELECT a.departureAirport AS 출발공항, a.arrivalAirport AS 도착공항, COUNT(*) AS 예약건수, SUM(r.payment) AS 총매출
         FROM RESERVE r
         JOIN AIRPLAIN a ON r.flightNo = a.flightNo AND r.departureDateTime = a.departureDateTime
         GROUP BY a.departureAirport, a.arrivalAirport
         ORDER BY 총매출 DESC";
$stmt1 = $conn->prepare($sql1);
$stmt1->execute();
$stats1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

// 2. 항공사별 월별 매출 (그룹 함수)
$sql2 = "SELECT a.airline AS 항공사, TO_CHAR(r.reserveDateTime, 'YYYY-MM') AS 예약월, SUM(r.payment) AS 월매출
         FROM RESERVE r
         JOIN AIRPLAIN a ON r.flightNo = a.flightNo AND r.departureDateTime = a.departureDateTime
         GROUP BY a.airline, TO_CHAR(r.reserveDateTime, 'YYYY-MM')
         ORDER BY a.airline, 예약월";
$stmt2 = $conn->prepare($sql2);
$stmt2->execute();
$stats2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// 3. 월별 예약/누적 예약 통계 (윈도우 함수)
$sql3 = "SELECT TO_CHAR(r.reserveDateTime, 'YYYY-MM') AS 예약월, COUNT(*) AS 월별예약건수,
         SUM(COUNT(*)) OVER (ORDER BY TO_CHAR(r.reserveDateTime, 'YYYY-MM')) AS 누적예약건수
         FROM RESERVE r
         GROUP BY TO_CHAR(r.reserveDateTime, 'YYYY-MM')
         ORDER BY 예약월";
$stmt3 = $conn->prepare($sql3);
$stmt3->execute();
$stats3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);

// 4. 항공편별 매출 순위 (윈도우 함수)
$sql4 = "SELECT r.flightNo, a.airline, a.departureAirport, a.arrivalAirport, SUM(r.payment) AS 총매출,
         RANK() OVER (ORDER BY SUM(r.payment) DESC) AS 매출순위
         FROM RESERVE r
         JOIN AIRPLAIN a ON r.flightNo = a.flightNo AND r.departureDateTime = a.departureDateTime
         GROUP BY r.flightNo, a.airline, a.departureAirport, a.arrivalAirport
         ORDER BY 매출순위";
$stmt4 = $conn->prepare($sql4);
$stmt4->execute();
$stats4 = $stmt4->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>관리자 통계</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>노선별 예약/매출 통계 (그룹 함수)</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>출발공항</th>
                <th>도착공항</th>
                <th>예약건수</th>
                <th>총매출</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($stats1 as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['출발공항']) ?></td>
                <td><?= htmlspecialchars($row['도착공항']) ?></td>
                <td><?= htmlspecialchars($row['예약건수']) ?></td>
                <td><?= number_format($row['총매출']) ?>원</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="mt-5">항공사별 월별 매출 (그룹 함수)</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>항공사</th>
                <th>예약월</th>
                <th>월매출</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($stats2 as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['항공사']) ?></td>
                <td><?= htmlspecialchars($row['예약월']) ?></td>
                <td><?= number_format($row['월매출']) ?>원</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="mt-5">월별 예약/누적 예약 통계 (윈도우 함수)</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>예약월</th>
                <th>월별예약건수</th>
                <th>누적예약건수</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($stats3 as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['예약월']) ?></td>
                <td><?= htmlspecialchars($row['월별예약건수']) ?></td>
                <td><?= htmlspecialchars($row['누적예약건수']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="mt-5">항공편별 매출 순위 (윈도우 함수)</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>순위</th>
                <th>항공편</th>
                <th>항공사</th>
                <th>출발공항</th>
                <th>도착공항</th>
                <th>총매출</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($stats4 as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['매출순위']) ?></td>
                <td><?= htmlspecialchars($row['FLIGHTNO']) ?></td>
                <td><?= htmlspecialchars($row['AIRLINE']) ?></td>
                <td><?= htmlspecialchars($row['DEPARTUREAIRPORT']) ?></td>
                <td><?= htmlspecialchars($row['ARRIVALAIRPORT']) ?></td>
                <td><?= number_format($row['총매출']) ?>원</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html> 