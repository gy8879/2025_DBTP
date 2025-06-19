<?php
session_start();
if (isset($_SESSION['member_id'])) {
    header('Location: main.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인 - CNU Airline Reservation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5" style="max-width:400px;">
    <h2 class="mb-4 text-center">로그인</h2>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger text-center">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    <form action="login_process.php" method="post">
        <div class="mb-3">
            <label for="email" class="form-label">이메일</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="이메일을 입력하세요" required>
        </div>
        <div class="mb-3">
            <label for="passwd" class="form-label">비밀번호</label>
            <input type="password" class="form-control" id="passwd" name="passwd" required>
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">로그인</button>
            <a href="register.php" class="btn btn-link">회원가입</a>
        </div>
    </form>
</div>
</body>
</html> 