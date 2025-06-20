<?php
session_start();
require_once '../src/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $terakhir_dirawat = $_POST['terakhir_dirawat'] ?? '';
    $jadwal_berikutnya = $_POST['jadwal_berikutnya'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';

    try {
        $stmt = $pdo->prepare("
            UPDATE perawatan 
            SET terakhir_dirawat = ?, 
                jadwal_berikutnya = ?, 
                keterangan = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $terakhir_dirawat,
            $jadwal_berikutnya,
            $keterangan,
            $id
        ]);

        $_SESSION['success'] = "Data berhasil diperbarui";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal memperbarui data: " . $e->getMessage();
    }
}

header('Location: maintenance.php');
exit;