<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>회원가입 - CNU Airline Reservation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5" style="max-width:500px;">
    <h2 class="mb-4 text-center">회원가입</h2>
    <form action="register_process.php" method="post">
        <div class="mb-3">
            <label for="cno" class="form-label">회원번호</label>
            <input type="text" class="form-control" id="cno" name="cno" placeholder="예: C007" required>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">이름</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="passwd" class="form-label">비밀번호</label>
            <input type="password" class="form-control" id="passwd" name="passwd" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">이메일</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="passportnumber" class="form-label">여권번호</label>
            <input type="text" class="form-control" id="passportnumber" name="passportnumber" required>
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">회원가입</button>
            <a href="login.php" class="btn btn-link">이미 계정이 있으신가요? 로그인</a>
        </div>
    </form>
</div>
</body>
</html> 