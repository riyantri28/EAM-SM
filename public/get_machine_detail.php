<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses']);
    exit;
}

require_once '../src/db.php';

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

$id = (int)$_GET['id'];

try {
    // Periksa terlebih dahulu struktur tabel aset untuk mengetahui nama kolom yang benar
    $columns = [];
    try {
        $columnsQuery = $pdo->query("DESCRIBE aset");
        while ($column = $columnsQuery->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $column['Field'];
        }
    } catch (PDOException $e) {
        // Jika gagal memeriksa kolom, kita hanya akan mengambil data dari tabel perawatan
    }
    
    // Persiapkan query berdasarkan kolom yang ada
    $query = "SELECT p.* FROM perawatan p WHERE p.id = ?";
    $hasJoin = false;
    
    // Tambahkan join ke tabel aset hanya jika ada kolom yang ingin kita ambil
    if (!empty($columns)) {
        $selectFields = ["p.*"];
        
        
        if (in_array('spesifikasi', $columns)) {
            $selectFields[] = "a.spesifikasi";
        }
        
        // Tambahkan join jika ada kolom yang perlu diambil
        if (count($selectFields) > 1) {
            $query = "SELECT " . implode(", ", $selectFields) . " FROM perawatan p";
            
            // Cek kolom untuk foreign key juga
            if (in_array('nama', $columns)) {
                $query .= " LEFT JOIN aset a ON p.nama_item = a.nama";
            } else if (in_array('nama_aset', $columns)) {
                $query .= " LEFT JOIN aset a ON p.nama_item = a.nama_aset";
            }
            
            $query .= " WHERE p.id = ?";
            $hasJoin = true;
        }
    }
    
    // Ambil data mesin
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$machine) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Data mesin tidak ditemukan']);
        exit;
    }
    
    // Ambil history perawatan (opsional, jika ada tabel untuk history)
    $history = [];
    try {
        // Periksa apakah tabel perawatan_history ada
        $tableExists = $pdo->query("SHOW TABLES LIKE 'perawatan_history'")->rowCount() > 0;
        
        if ($tableExists) {
            $stmt = $pdo->prepare("
                SELECT tanggal, keterangan FROM perawatan_history 
                WHERE mesin_id = ? 
                ORDER BY tanggal DESC
            ");
            $stmt->execute([$id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // Jika tabel history tidak ada atau query gagal, kita abaikan saja
        // dan melanjutkan tanpa data history
    }
    
    $machine['history'] = $history;
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'machine' => $machine]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan database: ' . $e->getMessage(),
        'query_info' => isset($query) ? $query : 'Query tidak tersedia'
    ]);
}
?>