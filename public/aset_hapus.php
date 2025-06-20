<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
require_once '../src/db.php';

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("DELETE FROM aset WHERE id=?");
$stmt->execute([$id]);
header('Location: aset_list.php?hapus=success');
exit;