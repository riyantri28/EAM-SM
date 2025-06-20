<?php
session_start();
$conn = new mysqli("localhost", "root", "", "enterprise_db");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Ambil data user berdasarkan username
$stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        // Simpan data user ke session
        $_SESSION['user'] = [
            'id' => $row['id'],
            'username' => $row['username']
        ];
        header('Location: dashboard.php');
        exit;
    } else {
        echo "Login gagal. Username atau password salah.";
    }
} else {
    echo "Login gagal. Username atau password salah.";
}
$conn->close();