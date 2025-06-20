<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once '../src/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    
    if ($aksi === 'edit') {
        // Ambil semua data dari form
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $terakhir_dirawat = $_POST['terakhir_dirawat'] ?? '';
        $jadwal_berikutnya = $_POST['jadwal_berikutnya'] ?? '';
        $jenis_perawatan = $_POST['jenis_perawatan'] ?? '';
        $kerusakan = $_POST['kerusakan'] ?? '';
        $komponen_digunakan = $_POST['komponen_digunakan'] ?? '';
        $status_perawatan = isset($_POST['status_perawatan']) ? (int)$_POST['status_perawatan'] : 0;
        
        // Debug - log data yang diterima
        error_log("Updating maintenance #$id: status=$status_perawatan, jenis=$jenis_perawatan");
        
        try {
            // Gabungkan jenis perawatan dan kerusakan ke field keterangan
            $keterangan = "Jenis: $jenis_perawatan\n\nKerusakan: $kerusakan\n\nKomponen: $komponen_digunakan";
            
            // Cek apakah tabel memiliki kolom jenis_perawatan dan komponen_digunakan
            $hasColumns = true;
            try {
                $stmt = $pdo->query("DESCRIBE perawatan");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $hasJenisColumn = in_array('jenis_perawatan', $columns);
                $hasKomponenColumn = in_array('komponen_digunakan', $columns);
                
                error_log("Table structure check: jenis_perawatan=" . 
                         ($hasJenisColumn ? "yes" : "no") . 
                         ", komponen_digunakan=" . 
                         ($hasKomponenColumn ? "yes" : "no"));
            } catch (PDOException $e) {
                $hasColumns = false;
                error_log("Couldn't check columns: " . $e->getMessage());
            }
            
            // Persiapkan query berdasarkan struktur tabel
            if ($hasColumns && $hasJenisColumn && $hasKomponenColumn) {
                // Tabel memiliki kolom khusus untuk jenis_perawatan dan komponen
                $query = "
                    UPDATE perawatan 
                    SET terakhir_dirawat = ?, 
                        jadwal_berikutnya = ?,
                        keterangan = ?,
                        status_perawatan = ?,
                        jenis_perawatan = ?,
                        komponen_digunakan = ?
                    WHERE id = ?
                ";
                $params = [
                    $terakhir_dirawat,
                    $jadwal_berikutnya,
                    $kerusakan, // Keterangan hanya berisi kerusakan
                    $status_perawatan,
                    $jenis_perawatan,
                    $komponen_digunakan,
                    $id
                ];
            } else {
                // Tabel hanya memiliki field keterangan
                $query = "
                    UPDATE perawatan 
                    SET terakhir_dirawat = ?, 
                        jadwal_berikutnya = ?,
                        keterangan = ?,
                        status_perawatan = ?
                    WHERE id = ?
                ";
                $params = [
                    $terakhir_dirawat,
                    $jadwal_berikutnya,
                    $keterangan, // Semua informasi digabung di keterangan
                    $status_perawatan,
                    $id
                ];
            }
            
            // Eksekusi query
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                $_SESSION['success'] = "Data perawatan berhasil diperbarui";
                error_log("Update successful for ID: $id");
            } else {
                $_SESSION['error'] = "Gagal memperbarui data perawatan";
                error_log("Update failed for ID: $id");
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Terjadi kesalahan database: " . $e->getMessage();
            error_log("Database error: " . $e->getMessage());
        }
    } else {
        $_SESSION['error'] = "Aksi tidak valid";
        error_log("Invalid action: $aksi");
    }
}

// Redirect kembali ke halaman maintenance
header('Location: maintenance.php');
exit;
?>