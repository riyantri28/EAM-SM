<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../public/login.php');
    exit;
}
require_once 'db.php';

$jenis = $_POST['jenis_mesin'];
$jumlah = $_POST['jumlah_unit']; // perbaiki: tidak ada spasi
$kondisi_baik = $_POST['kondisi_baik'];
$kondisi_rusak = $_POST['kondisi_rusak'];
$produsen = $_POST['produsen'];
$harga = $_POST['harga'];
$tanggal_beli = $_POST['tanggal_beli'];
$garansi = $_POST['garansi'];
$lokasi = $_POST['lokasi'];
$status = 'Tersedia';

// Simpan aset ke database
$stmt = $pdo->prepare("INSERT INTO aset (jenis_mesin, jumlah_unit, kondisi_baik, kondisi_rusak, produsen, harga, tanggal_beli, garansi, lokasi, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$jenis, $jumlah, $kondisi_baik, $kondisi_rusak, $produsen, $harga, $tanggal_beli, $garansi, $lokasi, $status]);

// Simpan log aktivitas ke database
$admin = $_SESSION['user']['username'];
$aksi = 'tambah aset';
$item = $jenis;
$stmtLog = $pdo->prepare("INSERT INTO log_aktivitas (tanggal, admin, aksi, item) VALUES (NOW(), ?, ?, ?)");
$stmtLog->execute([$admin, $aksi, $item]);

header('Location: ../public/aset_list.php');
exit;