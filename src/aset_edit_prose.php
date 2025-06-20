<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: ../public/login.php'); exit; }
require_once '../src/db.php';

$id = intval($_POST['id']);
$nama = $_POST['nama'];
$jenis = $_POST['jenis'];
$produsen = $_POST['produsen'];
$harga = $_POST['harga'];
$tanggal_beli = $_POST['tanggal_beli'];
$garansi = $_POST['garansi'];
$lokasi = $_POST['lokasi'];

$stmt = $pdo->prepare("UPDATE aset SET nama=?, jenis=?, produsen=?, harga=?, tanggal_beli=?, garansi=?, lokasi=? WHERE id=?");
$stmt->execute([$nama, $jenis, $produsen, $harga, $tanggal_beli, $garansi, $lokasi, $id]);
header('Location: ../public/aset_list.php?edit=success');
exit;