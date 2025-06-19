<?php
session_start();
require_once 'connect.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $passwd = $_POST['passwd'] ?? '';

    try {
        // 이메일로 회원 조회
        $stmt = $conn->prepare("SELECT * FROM CUSTOMER WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // 비밀번호 비교 (암호화 X)
            if ($passwd === $row['PASSWD']) {
                $_SESSION['member_id'] = $row['CNO'];
                $_SESSION['email'] = $row['EMAIL'];
                $_SESSION['name'] = $row['NAME'];
                header('Location: main.php');
                exit;
            } else {
                $_SESSION['error'] = "비밀번호가 일치하지 않습니다.";
                header('Location: login.php');
                exit;
            }
        } else {
            $_SESSION['error'] = "존재하지 않는 이메일입니다.";
            header('Location: login.php');
            exit;
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "데이터베이스 오류: " . $e->getMessage();
        header('Location: login.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}
?> 