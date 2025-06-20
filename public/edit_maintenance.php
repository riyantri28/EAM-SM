<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once '../src/db.php';

if (!isset($_GET['id'])) {
    echo "ID perawatan tidak ditemukan.";
    exit;
}

$id = intval($_GET['id']);

try {
    // Perbaikan query - ambil data perawatan tanpa JOIN yang bermasalah dulu
    $stmt = $pdo->prepare("
        SELECT * FROM perawatan WHERE id = ?
    ");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo "Data perawatan tidak ditemukan.";
        exit;
    }
    
    // Coba ambil data spesifikasi dari aset jika diperlukan (opsional)
    // Pastikan dulu struktur tabel aset
    $spesifikasi = "";
    try {
        // Asumsi bahwa yang benar adalah kolom nama (bukan nama_aset)
        // Dan kita hanya mencoba jika nama_item memiliki nilai
        if (!empty($data['nama_item'])) {
            $stmt_aset = $pdo->prepare("
                SELECT spesifikasi FROM aset WHERE nama = ? LIMIT 1
            ");
            $stmt_aset->execute([$data['nama_item']]);
            $aset_data = $stmt_aset->fetch(PDO::FETCH_ASSOC);
            if ($aset_data) {
                $spesifikasi = $aset_data['spesifikasi'];
            }
        }
    } catch (PDOException $e) {
        // Jika gagal, kita abaikan saja error ini untuk sementara
        // dan melanjutkan tanpa data spesifikasi
    }
    
    // Tambahkan spesifikasi ke data
    $data['spesifikasi'] = $spesifikasi;
    
} catch (PDOException $e) {
    echo "Terjadi kesalahan database: " . $e->getMessage();
    exit;
}

// Pecah keterangan menjadi jenis perawatan dan kerusakan jika perlu
$jenis_perawatan = $data['jenis_perawatan'] ?? 'Schedule Maintenance';
$kerusakan = $data['kerusakan'] ?? '';
$komponen = $data['komponen_digunakan'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Perawatan Mesin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --border-color: #e2e8f0;
            --bg-color: #f8fafc;
            --text-color: #1e293b;
        }
        
        body { 
            background: linear-gradient(135deg,rgb(22, 37, 102) 0%,rgb(54, 38, 70) 100%); 
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--text-color);
        }
        
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 800px;
            padding: 30px;
            position: relative;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .form-header h2 {
            font-size: 24px;
            color: var(--primary-color);
            margin: 0;
        }
        
        .form-header .form-icon {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 24px;
            color: var(--primary-color);
        }
        
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .form-table td {
            padding: 12px;
            border: 1px solid var(--border-color);
        }
        
        .form-table td:first-child {
            width: 35%;
            background-color: var(--bg-color);
            font-weight: 600;
        }
        
        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-family: inherit;
            font-size: 14px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-secondary {
            background: #64748b;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        @media print {
    body {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .form-container {
        box-shadow: none !important;
        padding: 0 !important;
        max-width: 100% !important;
        margin: 0 !important;
        border-radius: 0 !important;
    }
    
    .btn-group, .btn {
        display: none !important;
    }
    
    .print-header {
        display: block !important;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .print-header h1 {
        font-size: 18px;
        margin: 0 0 5px 0;
    }
    
    .print-header p {
        font-size: 12px;
        margin: 0;
    }
    
    .form-table {
        width: 100% !important;
        page-break-inside: avoid;
    }
    
    @page {
        size: A4;
        margin: 1.5cm;
    }
    
    .no-print {
        display: none !important;
    }
}

/* CSS untuk Print Preview Box */
.print-preview-box {
    background: white;
    border: 1px dashed #ccc;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.print-preview-info {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.print-preview-icon {
    font-size: 24px;
    color: var(--primary-color);
}

.print-preview-text {
    flex: 1;
}

.print-preview-text h3 {
    margin: 0 0 5px 0;
    color: var(--primary-color);
    font-size: 16px;
}

.print-preview-text p {
    margin: 0;
    font-size: 14px;
    color: #64748b;
}

.print-header {
    display: none; /* Hanya tampil saat print */
}

.btn-print {
    background: #10b981;
    color: white;
}
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-container">
    <!-- Header khusus untuk print -->
    <div class="print-header">
        <h1>UNIVERSITAS NEGERI YOGYAKARTA</h1>
        <p>KARTU PERAWATAN MESIN</p>
        <p>Tanggal: <?= date('d/m/Y') ?></p>
    </div>
        <div class="form-header">
            <i class="fas fa-tools form-icon"></i>
            <h2>Kartu Perawatan Mesin</h2>
        </div>
        
        <form action="maintenance_update.php" method="POST">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id" value="<?= htmlspecialchars($data['id']) ?>">
            
            <table class="form-table">
                <tr>
                    <td>Mesin</td>
                    <td>
                        <input type="text" class="form-input" name="nama_item" value="<?= htmlspecialchars($data['nama_item'] ?? '') ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td>Spesifikasi</td>
                    <td>
                        <input type="text" class="form-input" name="spesifikasi" value="<?= htmlspecialchars($data['spesifikasi'] ?? '') ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td>Terakhir di rawat</td>
                    <td>
                        <input type="date" class="form-input" name="terakhir_dirawat" value="<?= htmlspecialchars($data['terakhir_dirawat'] ?? '') ?>" required>
                    </td>
                </tr>
                <tr>
                    <td>Perkiraan berikutnya</td>
                    <td>
                        <input type="date" class="form-input" name="jadwal_berikutnya" value="<?= htmlspecialchars($data['jadwal_berikutnya'] ?? '') ?>" required>
                    </td>
                </tr>
                <tr>
                    <td>Jenis perawatan</td>
                    <td>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="jenis_perawatan" value="Schedule Maintenance" <?= ($jenis_perawatan == 'Schedule Maintenance') ? 'checked' : '' ?>> 
                                Schedule Maintenance
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="jenis_perawatan" value="Corrective Maintenance" <?= ($jenis_perawatan == 'Corrective Maintenance') ? 'checked' : '' ?>>
                                Corrective Maintenance
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="jenis_perawatan" value="Breakdown Maintenance" <?= ($jenis_perawatan == 'Breakdown Maintenance') ? 'checked' : '' ?>>
                                Breakdown Maintenance
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="jenis_perawatan" value="Preventive Maintenance" <?= ($jenis_perawatan == 'Preventive Maintenance') ? 'checked' : '' ?>> 
                                Preventive Maintenance
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>Kerusakan</td>
                    <td>
                        <textarea class="form-input form-textarea" name="kerusakan"><?= htmlspecialchars($kerusakan) ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td>Komponen dipakai</td>
                    <td>
                        <textarea class="form-input form-textarea" name="komponen_digunakan"><?= htmlspecialchars($komponen) ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td>Status Perawatan</td>
                    <td>
                        <select name="status_perawatan" class="form-input">
                            <option value="0" <?= $data['status_perawatan']==0?'selected':'' ?>>Pending</option>
                            <option value="1" <?= $data['status_perawatan']==1?'selected':'' ?>>Selesai</option>
                        </select>
                    </td>
                </tr>
            </table>
            <div class="btn-group">
                <a href="maintenance.php" class="btn btn-secondary no-print">
                    <i class="fas fa-times"></i> Batal
                </a>
                <button type="button" class="btn btn-print no-print" onclick="printForm()">
                    <i class="fas fa-print"></i> Cetak Formulir
                </button>
                <button type="submit" class="btn btn-primary no-print">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>

            <script>
            function printForm() {
                // Optionally add timestamp to form before printing
                const timestampElem = document.createElement('p');
                timestampElem.textContent = 'Dicetak pada: ' + new Date().toLocaleString('id-ID');
                timestampElem.classList.add('print-timestamp');
                timestampElem.style.textAlign = 'right';
                timestampElem.style.fontSize = '11px';
                timestampElem.style.color = '#666';
                timestampElem.style.marginTop = '20px';
                document.querySelector('.form-container').appendChild(timestampElem);
                
                // Print the form
                window.print();
                
                // Remove timestamp after printing
                setTimeout(() => {
                    document.querySelector('.print-timestamp').remove();
                }, 100);
            }
            </script>
        </form>
    </div>
</body>
</html>