<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once '../src/db.php';
require_once '../src/functions.php'; // Tambahkan ini

// Handle approval/rejection
if ($_POST['action'] ?? false) {
    $id = $_POST['id'];
    $action = $_POST['action'];
    
    // Ambil detail aset sebelum diproses
    $sql_get = "SELECT * FROM pending_aset WHERE id = ?";
    $stmt = $pdo->prepare($sql_get);
    $stmt->execute([$id]);
    $pending_aset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($action === 'approve') {
        try {
            // Insert ke tabel aset
            $sql_insert = "INSERT INTO aset (nama_item, spesifikasi, asal_usul, tahun_pengadaan, harga, jumlah, status, keterangan) 
                          VALUES (?, ?, ?, ?, ?, ?, 'Aktif', ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                $pending_aset['nama_item'],
                $pending_aset['spesifikasi'],
                $pending_aset['asal_usul'],
                $pending_aset['tahun_pengadaan'],
                $pending_aset['harga'],
                $pending_aset['jumlah'],
                $pending_aset['keterangan']
            ]);
            
            // Hapus dari tabel pending_aset
            $sql_delete = "DELETE FROM pending_aset WHERE id = ?";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->execute([$id]);
            
            // Log aktivitas persetujuan
            logAktivitas($pdo, "Menyetujui pengadaan aset: " . $pending_aset['nama_item'], 'success');
            
            $message = "Aset berhasil disetujui dan dipindahkan ke inventaris resmi.";
            $message_type = "success";
        } catch (PDOException $e) {
            logAktivitas($pdo, "Gagal menyetujui pengadaan aset: " . $pending_aset['nama_item'], 'danger');
            $message = "Gagal menyetujui aset. Silakan coba lagi.";
            $message_type = "danger";
        }
    } elseif ($action === 'reject') {
        try {
            // Update status menjadi rejected
            $sql_reject = "UPDATE pending_aset SET status = 'rejected' WHERE id = ?";
            $stmt = $pdo->prepare($sql_reject);
            $stmt->execute([$id]);
            
            // Log aktivitas penolakan
            logAktivitas($pdo, "Menolak pengadaan aset: " . $pending_aset['nama_item'], 'warning');
            
            $message = "Aset telah ditolak.";
            $message_type = "danger";
        } catch (PDOException $e) {
            logAktivitas($pdo, "Gagal menolak pengadaan aset: " . $pending_aset['nama_item'], 'danger');
            $message = "Gagal menolak aset. Silakan coba lagi.";
            $message_type = "danger";
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode($message) . "&type=" . $message_type);
    exit;
}

// Query untuk mengambil data pending aset
$sql = "SELECT * FROM pending_aset WHERE status = 'pending' ORDER BY id ASC";
$result = $pdo->query($sql);
$pending_asets = $result->fetchAll(PDO::FETCH_ASSOC);

// Statistik
$sql_stats = "SELECT 
    COUNT(*) as total_pending,
    (SELECT COUNT(*) FROM pending_aset WHERE status = 'rejected') as total_rejected,
    (SELECT COUNT(*) FROM aset) as total_approved
    FROM pending_aset WHERE status = 'pending'";
$stats_result = $pdo->query($sql_stats);
$stats = $stats_result->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Administrasi - Pending Inventaris | EAM UNY</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --accent-color: #0ea5e9;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --border-radius: 8px;
            --border-radius-lg: 12px;
        }

        body {
            background: linear-gradient(135deg, var(--light-bg) 0%, #e2e8f0 100%);
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--gray-700);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Enhanced Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);            color: var(--white);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .navbar .logo {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .navbar .logo::before {
            content: '🏛️';
            font-size: 1.8rem;
        }

        .navbar .user {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .navbar .user i {
            font-size: 1.1rem;
        }

        /* Main Content */
        .content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        /* Enhanced Header Section */
        .header-section {
            background: var(--white);
            padding: 32px;
            border-radius: var(--border-radius-lg);
            margin-bottom: 24px;
            box-shadow: var(--shadow-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light), var(--accent-color));
        }

        .header-section h2 {
            color: var(--gray-800);
            font-size: 1.875rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .header-section p {
            color: var(--gray-500);
            font-size: 1rem;
        }

        /* Enhanced Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: var(--white);
            padding: 8px 16px;
            font-size: 0.75rem;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: var(--white);
            padding: 8px 16px;
            font-size: 0.75rem;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
        }

        /* Enhanced Stats Summary */
        .stats-summary {
            background: var(--white);
            padding: 24px;
            border-radius: var(--border-radius-lg);
            margin-bottom: 24px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
        }

        .stat-item {
            text-align: center;
            padding: 16px;
            border-radius: var(--border-radius);
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            font-weight: 500;
            margin-top: 4px;
        }

        /* Enhanced Table Container */
        .table-container {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
        }

        .table-header {
            background: linear-gradient(135deg, var(--gray-50), var(--gray-100));
            padding: 20px 24px;
            border-bottom: 2px solid var(--gray-200);
            font-weight: 700;
            color: var(--gray-800);
            font-size: 1.125rem;
        }

        /* Enhanced Table */
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            font-size: 0.875rem;
        }

        th {
            background: linear-gradient(135deg, var(--gray-50), var(--gray-100));
            padding: 16px 12px;
            text-align: center;
            border-bottom: 2px solid var(--gray-200);
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 16px 12px;
            text-align: center;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
            transition: all 0.2s ease;
        }

        .row-number {
            background: var(--gray-50);
            font-weight: 700;
            width: 60px;
            color: var(--primary-color);
        }

        tr:hover {
            background: var(--gray-50);
            transform: scale(1.01);
        }

        .nama-item {
            text-align: left;
            font-weight: 600;
            color: var(--gray-800);
        }

        .spesifikasi {
            text-align: left;
            max-width: 200px;
            word-wrap: break-word;
            color: var(--gray-600);
        }

        .harga {
            font-weight: 700;
            color: var(--success-color);
            font-family: 'Courier New', monospace;
        }

        /* Enhanced Status Badge */
        .status-pending {
            background: linear-gradient(135deg, #fef3c7, #fbbf24);
            color: #92400e;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: var(--shadow-sm);
        }

        /* Enhanced Action Buttons */
        .action-cell {
            width: 140px;
        }

        .action-buttons {
            display: flex;
            gap: 6px;
            justify-content: center;
            align-items: center;
        }

        .icon-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            color: var(--white);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .icon-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .edit-btn {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
        }

        .delete-btn {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
        }

        .approve-btn {
            background: linear-gradient(135deg, var(--success-color), #059669);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .reject-btn {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Enhanced Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 24px;
        }

        .empty-state h3 {
            color: var(--gray-700);
            font-size: 1.5rem;
            margin-bottom: 12px;
        }

        .empty-state p {
            margin-bottom: 24px;
            font-size: 1rem;
        }

        /* Enhanced Alerts */
        .alert {
            padding: 16px 24px;
            border-radius: var(--border-radius-lg);
            margin-bottom: 24px;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-sm);
        }

        .alert i {
            font-size: 1.25rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-color: #10b981;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border-color: #ef4444;
        }

        /* Back to Dashboard Button */
        .back-to-dashboard {
            text-align: center;
            margin: 40px 0;
        }

        .back-to-dashboard .btn {
            background: linear-gradient(135deg, var(--info-color), #0891b2);
            color: var(--white);
            padding: 16px 32px;
            font-size: 1rem;
            border-radius: 50px;
            box-shadow: var(--shadow-lg);
        }

        .back-to-dashboard .btn:hover {
            background: linear-gradient(135deg, #0891b2, #0e7490);
            transform: translateY(-3px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-section {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .stats-summary {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 800px;
            }

            .navbar {
                padding: 12px 16px;
            }

            .content {
                padding: 16px 12px;
            }
        }

        /* Loading Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .content > * {
            animation: fadeIn 0.6s ease-out;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-100);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gray-400);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-500);
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">EAM UNY</div>
        <div class="user">
            <i class="fas fa-user-circle"></i>
            <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
        </div>
    </div>

    <div class="content">
        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_GET['type']); ?>">
            <i class="fas fa-<?php echo $_GET['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
        <?php endif; ?>

        <div class="header-section">
            <div>
                <h2>Administrasi - Pending Inventaris</h2>
                <p>Kelola aset yang menunggu persetujuan dengan mudah dan efisien</p>
            </div>
            <div>
                <a href="inventaris_resmi.php" class="btn btn-primary">
                    <i class="fas fa-list"></i>
                    Lihat Inventaris Resmi
                </a>
                <a href="tambah_pending_aset.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Tambah Pengadaan
                </a>
            </div>
        </div>

        <div class="stats-summary">
            <div class="stat-item">
                <span class="stat-number"><?php echo $stats['total_pending']; ?></span>
                <span class="stat-label">Menunggu Persetujuan</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $stats['total_approved']; ?></span>
                <span class="stat-label">Telah Disetujui</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $stats['total_rejected']; ?></span>
                <span class="stat-label">Ditolak</span>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <i class="fas fa-clipboard-list"></i>
                Daftar Aset Menunggu Persetujuan
            </div>
            
            <?php if (count($pending_asets) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Item</th>
                        <th>Spesifikasi</th>
                        <th>Asal Usul</th>
                        <th>Tahun Pengadaan</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_asets as $index => $aset): ?>
                    <tr>
                        <td class="row-number"><?php echo $index + 1; ?></td>
                        <td class="nama-item"><?php echo htmlspecialchars($aset['nama_item']); ?></td>
                        <td class="spesifikasi"><?php echo htmlspecialchars($aset['spesifikasi'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($aset['asal_usul']); ?></td>
                        <td><?php echo htmlspecialchars($aset['tahun_pengadaan']); ?></td>
                        <td class="harga">Rp<?php echo number_format($aset['harga'], 0, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($aset['jumlah']); ?></td>
                        <td>
                            <span class="status-pending">Proses Pengadaan</span>
                        </td>
                        <td><?php echo htmlspecialchars($aset['keterangan'] ?? '-'); ?></td>
                        <td class="action-cell">
                            <div class="action-buttons">
                                <!-- Tombol Approve -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $aset['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn approve-btn" 
                                            onclick="return confirm('Setujui aset ini?')" 
                                            title="Setujui">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                
                                <!-- Tombol Reject -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $aset['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn reject-btn" 
                                            onclick="return confirm('Tolak pengajuan ini?')" 
                                            title="Tolak">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                
                                <!-- Icon Buttons -->
                                <button class="icon-btn edit-btn" onclick="editItem(<?php echo $aset['id']; ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="icon-btn delete-btn" onclick="deleteItem(<?php echo $aset['id']; ?>)" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-check"></i>
                <h3>Tidak ada aset yang menunggu persetujuan</h3>
                <p>Semua pengajuan telah diproses atau belum ada pengajuan baru</p>
                <a href="tambah_pending_aset.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Tambah Pengadaan Aset
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="back-to-dashboard">
        <a href="dashboard.php" class="btn">
            <i class="fas fa-arrow-left"></i>
            Kembali ke Dashboard
        </a>
    </div>

    <script>
        // Fungsi untuk edit item
        function editItem(id) {
            if (confirm('Apakah Anda yakin ingin mengedit item ini?')) {
                // Log aktivitas akan ditangani di halaman edit
                window.location.href = 'edit_pending_aset.php?id=' + id;
            }
        }

        // Fungsi untuk hapus item
        function deleteItem(id) {
            if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
                // Log aktivitas akan ditangani di halaman hapus
                window.location.href = 'hapus_pending_aset.php?id=' + id;
            }
        }

        // Auto hide alerts setelah 5 detik
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Smooth scroll untuk link internal
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Loading animation untuk tabel
        document.addEventListener('DOMContentLoaded', function() {
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
                row.style.animation = 'fadeIn 0.6s ease-out both';
            });
        });
    </script>
</body>
</html>