<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once '../src/db.php';
$stmt = $pdo->query("SELECT * FROM aset");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Aset - EAM UNY</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg,rgb(1, 12, 34) 0%,rgb(14, 42, 90) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            color: #fff;
        }
        
        .navbar {
            background: rgba(30, 60, 114, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.4em;
            font-weight: 700;
            color: #fff;
        }
        
        .navbar .logo i {
            color: #4fc3f7;
        }
        
        .navbar .user {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9em;
        }
        
        .container {
            max-width: 1400px;
            margin: 24px auto;
            padding: 0 24px;
        }
        
        .page-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 2.2em;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(45deg, #4fc3f7, #29b6f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1em;
        }
        
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 48px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            color: #fff;
            font-size: 1em;
            backdrop-filter: blur(10px);
        }
        
        .search-box input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95em;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #4fc3f7, #29b6f6);
            color: #fff;
            box-shadow: 0 4px 15px rgba(79, 195, 247, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 195, 247, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.3);
            color: #81c784;
        }
        
        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #e57373;
        }
        
        .table-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        .aset-table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
        }
        
        .aset-table th {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 20px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95em;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .aset-table td {
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9em;
        }
        
        .aset-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
            transition: background 0.2s ease;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-aktif {
            background: rgba(76, 175, 80, 0.2);
            color: #81c784;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .status-rusak {
            background: rgba(244, 67, 54, 0.2);
            color: #e57373;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
        
        .status-maintenance {
            background: rgba(255, 193, 7, 0.2);
            color: #ffb74d;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .action-buttons-table {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8em;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-edit {
            background: rgba(33, 150, 243, 0.2);
            color: #64b5f6;
            border: 1px solid rgba(33, 150, 243, 0.3);
        }
        
        .btn-edit:hover {
            background: rgba(33, 150, 243, 0.3);
            transform: scale(1.05);
        }
        
        .btn-delete {
            background: rgba(244, 67, 54, 0.2);
            color: #e57373;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
        
        .btn-delete:hover {
            background: rgba(244, 67, 54, 0.3);
            transform: scale(1.05);
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 2em;
            font-weight: 700;
            color: #4fc3f7;
            margin-bottom: 8px;
        }
        
        .stat-card .label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 16px;
            }
            
            .navbar {
                padding: 12px 16px;
                flex-direction: column;
                gap: 12px;
            }
            
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                max-width: none;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .aset-table th,
            .aset-table td {
                padding: 12px 8px;
                font-size: 0.85em;
            }
            
            .page-header {
                padding: 24px 20px;
            }
            
            .page-header h1 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-cogs"></i>
            EAM UNY
        </div>
        <div class="user">
            <i class="fas fa-user-circle"></i>
            <?= htmlspecialchars($_SESSION['user']['username'] ?? '') // Modified this line ?>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-boxes"></i> Manajemen Aset Bengkel</h1>
            <p>Kelola dan pantau semua aset bengkel dengan mudah</p>
        </div>

        <?php
        // Count assets for stats
        $totalAssets = $pdo->query("SELECT COUNT(*) FROM aset")->fetchColumn();
        $activeAssets = $pdo->query("SELECT COUNT(*) FROM aset WHERE status = 'Aktif'")->fetchColumn();
        $damagedAssets = $pdo->query("SELECT COUNT(*) FROM aset WHERE status = 'Rusak'")->fetchColumn();
        $maintenanceAssets = $pdo->query("SELECT COUNT(*) FROM aset WHERE status = 'Maintenance'")->fetchColumn();
        ?>

        <div class="stats-row">
            <div class="stat-card">
                <div class="number"><?= $totalAssets ?></div>
                <div class="label">Total Aset</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $activeAssets ?></div>
                <div class="label">Aset Aktif</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $damagedAssets ?></div>
                <div class="label">Aset Rusak</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $maintenanceAssets ?></div>
                <div class="label">Maintenance</div>
            </div>
        </div>

        <div class="action-bar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Cari aset..." id="searchInput">
            </div>
            <div class="action-buttons">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Kembali
                </a>
                <a href="aset_export_excel.php" class="btn btn-success">
                    <i class="fas fa-file-excel"></i>
                    Download Excel
                </a>
                <a href="aset_tambah.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Tambah Aset
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_GET['import'])): ?>
            <?php if ($_GET['import'] === 'success'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Import aset dari CSV berhasil!
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    Import aset gagal. Pastikan format file benar.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($_GET['edit']) && $_GET['edit'] === 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Data aset berhasil diubah!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['hapus']) && $_GET['hapus'] === 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Data aset berhasil dihapus!
            </div>
        <?php endif; ?>

        <div class="table-container">
            <div class="table-wrapper">
                <table class="aset-table" id="assetTable">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-tag"></i> Nama Item</th>
                            <th><i class="fas fa-info-circle"></i> Spesifikasi</th>
                            <th><i class="fas fa-building"></i> Asal Usul</th>
                            <th><i class="fas fa-calendar"></i> Tahun</th>
                            <th><i class="fas fa-money-bill"></i> Harga</th>
                            <th><i class="fas fa-sort-numeric-up"></i> Jumlah</th>
                            <th><i class="fas fa-signal"></i> Status</th>
                            <th><i class="fas fa-comment"></i> Keterangan</th>
                            <th><i class="fas fa-cog"></i> Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stmt = $pdo->query("SELECT * FROM aset ORDER BY id DESC");
                        while ($row = $stmt->fetch()) { 
                        ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><strong><?= htmlspecialchars($row['nama_item']) ?></strong></td>
                            <td><?= htmlspecialchars($row['spesifikasi']) ?></td>
                            <td><?= htmlspecialchars($row['asal_usul']) ?></td>
                            <td><?= htmlspecialchars($row['tahun_pengadaan']) ?></td>
                            <td><strong>Rp <?= number_format($row['harga'], 0, ',', '.') ?></strong></td>
                            <td><?= htmlspecialchars($row['jumlah']) ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['keterangan']) ?></td>
                            <td>
                                <div class="action-buttons-table">
                                    <a href="aset_edit.php?id=<?= $row['id'] ?>" class="btn-sm btn-edit">
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </a>
                                    <a href="aset_hapus.php?id=<?= $row['id'] ?>" class="btn-sm btn-delete" 
                                       onclick="return confirm('Yakin hapus aset ini?')">
                                        <i class="fas fa-trash"></i>
                                        Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#assetTable tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>