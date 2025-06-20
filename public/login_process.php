<?php
session_start();
require_once '../src/db.php';

header('Content-Type: application/json');

// Validasi metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
    exit;
}

// Ambil data login
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Validasi input
if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username dan password harus diisi']);
    exit;
}

try {
    // Cari user di database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verifikasi password (coba kedua metode)
    $passwordValid = false;
    
    if ($user) {
        // Metode 1: Coba dengan password_verify (untuk password yang sudah di-hash)
        if (function_exists('password_verify') && password_verify($password, $user['password'])) {
            $passwordValid = true;
        }
        // Metode 2: Coba dengan plain text (untuk kompatibilitas dengan sistem lama)
        else if ($password === $user['password']) {
            $passwordValid = true;
        }
    }
    
    if ($user && $passwordValid) {
        // Login berhasil
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'] ?? $user['username'],
            'role' => $user['role'] ?? 'user'
        ];
        
        // HAPUS kode logging yang bermasalah - jangan masukkan ke tabel users!
        
        // Kirim respons sukses
        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil',
            'redirect' => 'dashboard.php',
            'user' => [
                'name' => $_SESSION['user']['name'],
                'role' => $_SESSION['user']['role']
            ]
        ]);
    } else {
        // Login gagal - password salah atau username tidak ditemukan
        echo json_encode(['success' => false, 'message' => 'Username atau password salah']);
    }
} catch (PDOException $e) {
    // Error database
    error_log("Database error in login: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.']);
}
?>