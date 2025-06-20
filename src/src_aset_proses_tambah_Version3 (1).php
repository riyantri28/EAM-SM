<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../public/login.php');
    exit;
}
require_once 'db.php';

$stmt = $pdo->prepare("INSERT INTO aset (nama, jenis, produsen, harga, tanggal_beli, garansi, lokasi) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $_POST['nama'],
    $_POST['jenis'],
    $_POST['produsen'],
    $_POST['harga'],
    $_POST['tanggal_beli'],
    $_POST['garansi'],
    $_POST['lokasi']
]);

header('Location: ../public/aset_list.php');
exit;
?>