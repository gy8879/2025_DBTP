<?php
session_start();
$is_logged_in = isset($_SESSION['member_id']);
$name = $is_logged_in ? $_SESSION['name'] : '';
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>CNU Airline Reservation - 메인</title>
    <!-- Bootstrap CDN (원하면 사용) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5" style="max-width:600px;">
    <h1 class="mb-4 text-center">CNU Airline Reservation</h1>
    <?php if ($is_logged_in): ?>
        <div class="alert alert-success text-center">
            <b><?= htmlspecialchars($name) ?></b>님, 환영합니다!
        </div>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-body">
            <form action="search.php" method="get">
                <div class="row mb-3">
                    <div class="col">
                        <label for="from" class="form-label">출발공항</label>
                        <input type="text" class="form-control" id="from" name="from" placeholder="예: 인천" required>
                    </div>
                    <div class="col">
                        <label for="to" class="form-label">도착공항</label>
                        <input type="text" class="form-control" id="to" name="to" placeholder="예: 뉴욕" required>
                    </div>
        </div>
                <div class="row mb-3">
                    <div class="col">
            <label for="date" class="form-label">출발날짜</label>
            <input type="date" class="form-control" id="date" name="date" required>
        </div>
                    <div class="col">
            <label for="seat_class" class="form-label">좌석등급</label>
            <select class="form-select" id="seat_class" name="seat_class" required>
                            <option value="all">전체</option>
                <option value="Economy">이코노미</option>
                <option value="Business">비즈니스</option>
            </select>
        </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">항공기 검색</button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center mt-4">
        <?php if ($is_logged_in): ?>
            <a href="mypage.php" class="btn btn-success btn-lg me-2">마이페이지</a>
            <a href="logout.php" class="btn btn-outline-danger">로그아웃</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline-primary">로그인</a>
            <a href="register.php" class="btn btn-outline-secondary">회원가입</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html> 