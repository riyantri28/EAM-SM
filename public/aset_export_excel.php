<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once '../src/db.php';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Data_Aset_Bengkel_' . date('Y-m-d_H-i-s') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Ambil data aset dari database
$stmt = $pdo->query("SELECT * FROM aset ORDER BY id ASC");
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output HTML yang akan diinterpretasi sebagai Excel
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Data Aset Bengkel</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .number {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>DATA ASET BENGKEL PEMESINAN</h2>
        <h3>DEPARTEMEN PENDIDIKAN TEKNIK MESIN</h3>
        <h3>UNIVERSITAS NEGERI YOGYAKARTA</h3>
        <p>Tanggal Export: <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID Aset</th>
                <th>Nama Item</th>
                <th>Spesifikasi</th>
                <th>Asal Usul</th>
                <th>Tahun Pengadaan</th>
                <th>Harga (Rp)</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($assets as $asset): 
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($asset['id']) ?></td>
                <td><?= htmlspecialchars($asset['nama_item']) ?></td>
                <td><?= htmlspecialchars($asset['spesifikasi']) ?></td>
                <td><?= htmlspecialchars($asset['asal_usul']) ?></td>
                <td><?= htmlspecialchars($asset['tahun_pengadaan']) ?></td>
                <td class="number"><?= number_format($asset['harga'], 0, ',', '.') ?></td>
                <td class="number"><?= htmlspecialchars($asset['jumlah']) ?></td>
                <td><?= htmlspecialchars($asset['status']) ?></td>
                <td><?= htmlspecialchars($asset['keterangan']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6">Total Aset</th>
                <th class="number"><?= number_format(array_sum(array_column($assets, 'harga')), 0, ',', '.') ?></th>
                <th class="number"><?= array_sum(array_column($assets, 'jumlah')) ?></th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 30px;">
        <p><strong>Keterangan Status:</strong></p>
        <ul>
            <li><strong>Aktif:</strong> Aset dalam kondisi baik dan siap digunakan</li>
            <li><strong>Rusak:</strong> Aset mengalami kerusakan dan perlu perbaikan</li>
            <li><strong>Maintenance:</strong> Aset sedang dalam tahap perawatan</li>
            <li><strong>Nonaktif:</strong> Aset tidak digunakan sementara</li>
        </ul>
    </div>

    <div style="margin-top: 20px;">
        <p>Data ini diekspor oleh: <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong></p>
        <p>Jumlah total record: <strong><?= count($assets) ?></strong></p>
    </div>
</body>
</html>