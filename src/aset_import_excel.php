<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../public/login.php');
    exit;
}
require_once 'db.php';

if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] == 0) {
    $ext = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));
    if ($ext == 'csv') {
        $file = fopen($_FILES['file_excel']['tmp_name'], 'r');
        $rowNum = 0;
        try {
            while (($row = fgetcsv($file)) !== false) {
                $rowNum++;
                if ($rowNum == 1) continue; // Lewati header

                // Sesuai dengan kolom di database
                $jenis_mesin = $row[0] ?? '';
                $jumlah_unit = intval($row[1] ?? 1);
                $kondisi_baik = intval($row[2] ?? 0);
                $kondisi_rusak = intval($row[3] ?? 0);
                $produsen = $row[4] ?? '';
                $harga = floatval($row[5] ?? 0);
                $tanggal_beli = $row[6] ? date('Y-m-d', strtotime($row[6])) : null;
                $garansi = intval($row[7] ?? 0);
                $lokasi = $row[8] ?? '';
                $status = 'Tersedia';

                // Debug: print data yang akan diinsert
                error_log("Row $rowNum: " . json_encode([
                    'jenis_mesin' => $jenis_mesin,
                    'jumlah_unit' => $jumlah_unit,
                    'kondisi_baik' => $kondisi_baik,
                    'kondisi_rusak' => $kondisi_rusak,
                    'produsen' => $produsen,
                    'harga' => $harga,
                    'tanggal_beli' => $tanggal_beli,
                    'garansi' => $garansi,
                    'lokasi' => $lokasi,
                    'status' => $status
                ]));

                if ($jenis_mesin) {
                    $stmt = $pdo->prepare("INSERT INTO aset (jenis_mesin, jumlah_unit, kondisi_baik, kondisi_rusak, produsen, harga, tanggal_beli, garansi, lokasi, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $result = $stmt->execute([
                        $jenis_mesin, 
                        $jumlah_unit, 
                        $kondisi_baik, 
                        $kondisi_rusak, 
                        $produsen, 
                        $harga, 
                        $tanggal_beli, 
                        $garansi, 
                        $lokasi, 
                        $status
                    ]);

                    if (!$result) {
                        throw new PDOException("Error inserting row $rowNum: " . json_encode($stmt->errorInfo()));
                    }
                }
            }
            fclose($file);
            header('Location: ../public/aset_list.php?import=success');
            exit;
        } catch (PDOException $e) {
            error_log("Import error: " . $e->getMessage());
            header('Location: ../public/aset_list.php?import=fail&message=' . urlencode($e->getMessage()));
            exit;
        }
    } else {
        header('Location: ../public/aset_list.php?import=fail&message=format');
        exit;
    }
} else {
    header('Location: ../public/aset_list.php?import=fail&message=upload');
    exit;
}