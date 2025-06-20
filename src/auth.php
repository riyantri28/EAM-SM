<?php
session_start();
require_once 'db.php';
$username = $_POST['username'];
$password = $_POST['password'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
    $_SESSION['user'] = $user;
    header('Location: ../public/dashboard.php');
    exit;
} else {
    echo "Login gagal!";
}
?>