<?php
session_start();

// Security: Regenerate session ID untuk mencegah session fixation
if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}

// Redirect jika user belum login
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once '../src/db.php';

// Inisialisasi variabel default untuk error handling
$activities = [];
$total_aset = 0;
$maintenance_stats = ['total' => 0, 'selesai' => 0, 'terlambat' => 0];
$urgent_maintenance = 0;
$error_message = '';

try {
    // Ambil log aktivitas terbaru
    $stmtLog = $pdo->prepare("
        SELECT 
            DATE_FORMAT(tanggal, '%d/%m/%Y %H:%i') as waktu,
            COALESCE(aktivitas, 'Tidak ada keterangan') as aktivitas,
            COALESCE(status, 'info') as status
        FROM log_aktivitas 
        ORDER BY tanggal DESC 
        LIMIT 10
    ");
    $stmtLog->execute();
    $activities = $stmtLog->fetchAll(PDO::FETCH_ASSOC);
    
    // Ambil jumlah aset aktif
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM aset WHERE status = 'Aktif'");
    $stmt->execute();
    $total_aset = $stmt->fetchColumn();

    // Ambil statistik perawatan
    $stmtMaintenance = $pdo->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status_perawatan = 1 THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN jadwal_berikutnya < CURDATE() AND status_perawatan = 0 THEN 1 ELSE 0 END) as terlambat
        FROM perawatan");
    $stmtMaintenance->execute();
    $maintenance_stats = $stmtMaintenance->fetch(PDO::FETCH_ASSOC);

    // Query untuk administrasi
    $stmtAdmin = $pdo->prepare("
        SELECT COUNT(*) as total_pending 
        FROM pending_aset 
        WHERE status = 'pending'
    ");
    $stmtAdmin->execute();
    $admin_result = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
    
    // Pastikan nilai default jika query gagal
    $admin_stats = [
        'total_pending' => $admin_result ? (int)$admin_result['total_pending'] : 0
    ];
    
} catch (PDOException $e) {
    error_log("Admin query error: " . $e->getMessage());
    $admin_stats = ['total_pending' => 0];
}

// Query untuk aktivitas mesin dengan error handling
try {
    $stmtMesin = $pdo->prepare("
        SELECT COUNT(*) as total_aktivitas 
        FROM machines 
        WHERE DATE(last_update) = CURRENT_DATE
        AND status_aktif = 'aktif'  -- Status perlu disesuaikan dengan nilai yang benar di database
    ");
    $stmtMesin->execute();
    $mesin_result = $stmtMesin->fetch(PDO::FETCH_ASSOC);
    
    // Debug untuk melihat nilai status
    $debugStmt = $pdo->query("SELECT id, nama_mesin, status_aktif FROM machines");
    $debugMesin = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Status mesin: " . print_r($debugMesin, true));
    
    $mesin_stats = [
        'total_aktivitas' => $mesin_result ? (int)$mesin_result['total_aktivitas'] : 0
    ];
    
} catch (PDOException $e) {
    error_log("Mesin query error: " . $e->getMessage());
    $mesin_stats = ['total_aktivitas' => 0];
}

// Format waktu untuk greeting
date_default_timezone_set('Asia/Jakarta');
$hour = date('H');
if ($hour < 10) {
    $greeting = "Selamat Pagi";
    $greeting_icon = "🌅";
} elseif ($hour < 15) {
    $greeting = "Selamat Siang";
    $greeting_icon = "☀️";
} elseif ($hour < 18) {
    $greeting = "Selamat Sore";
    $greeting_icon = "🌆";
} else {
    $greeting = "Selamat Malam";
    $greeting_icon = "🌙";
}

$username = htmlspecialchars($_SESSION['user']['username'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard Enterprise Asset Management Universitas Negeri Yogyakarta">
    <title>Dashboard - EAM UNY</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4338ca;
            --secondary-color: #8b5cf6;
            --accent-color: #06b6d4;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --info-color: #3b82f6;
            --secondary-color: #8b5cf6;
            
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #64748b;
            
            --sidebar-width: 280px;
            --sidebar-mini-width: 70px; /* Ukuran saat sidebar diminimalkan */
            --header-height: 70px;
            --border-radius: 12px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            border-right: 1px solid var(--bg-tertiary);
            z-index: 1000;
            transition: var(--transition);
            overflow-y: auto;
            scrollbar-width: none;
        }

        .sidebar::-webkit-scrollbar {
            display: none;
        }

        /* Stlye untuk sidebar diminimalkan */
        .sidebar.minimized {
            width: var(--sidebar-mini-width);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--bg-tertiary);
            background: rgba(99, 102, 241, 0.1);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .logo i {
            font-size: 1.5rem;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar.minimized .logo span {
            display: none;
        }

        /* Button untuk minimize sidebar */
        .minimize-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.1rem;
            border-radius: 4px;
            padding: 4px 8px;
            transition: var(--transition);
        }

        .minimize-btn:hover {
            background: rgba(99, 102, 241, 0.2);
            color: var(--primary-color);
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            padding: 0 1.5rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            white-space: nowrap;
            transition: var(--transition);
        }

        .sidebar.minimized .nav-section-title {
            opacity: 0;
            visibility: hidden;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
            border-radius: 0;
            white-space: nowrap;
        }

        .nav-link:hover {
            background: rgba(99, 102, 241, 0.1);
            color: var(--text-primary);
            transform: translateX(4px);
        }

        .sidebar.minimized .nav-link:hover {
            transform: translateX(0);
        }

        .nav-link.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.2) 0%, transparent 100%);
            color: var(--primary-color);
            border-right: 3px solid var(--primary-color);
        }

        .nav-link i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .nav-link span {
            transition: var(--transition);
        }

        .sidebar.minimized .nav-link span {
            opacity: 0;
            visibility: hidden;
            width: 0;
            margin-left: -20px;
        }

        .nav-badge {
            margin-left: auto;
            background: var(--danger-color);
            color: white;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: var(--transition);
        }

        .sidebar.minimized .nav-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            padding: 0.1rem 0.35rem;
            font-size: 0.6rem;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: var(--transition);
        }

        .main-content.expanded {
            margin-left: var(--sidebar-mini-width);
        }

        /* Header */
        .header {
            height: var(--header-height);
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--bg-tertiary);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .sidebar-toggle:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-search {
            position: relative;
            display: none;
        }

        .search-input {
            background: var(--bg-tertiary);
            border: 1px solid transparent;
            color: var(--text-primary);
            padding: 0.5rem 0.75rem 0.5rem 2.5rem;
            border-radius: var(--border-radius);
            width: 300px;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--bg-tertiary);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .user-menu:hover {
            background: var(--bg-secondary);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 2rem;
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--bg-tertiary);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .welcome-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .welcome-text h1 {
            font-size: 1.875rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .greeting-text {
            background: linear-gradient(45deg, var(--text-primary), var(--primary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline;
        }

        /* Styling khusus untuk emoji */
        .greeting-icon {
            margin-right: 5px;
            font-size: 1.5rem;
        }

        .welcome-text p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .date-time {
            text-align: right;
            color: var(--text-muted);
        }

        .date-time .date {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-secondary);
        }

        .date-time .time {
            font-size: 0.875rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--bg-tertiary);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-color);
        }

        .stat-card.primary::before { background: var(--primary-color); }
        .stat-card.success::before { background: var(--success-color); }
        .stat-card.warning::before { background: var(--warning-color); }
        .stat-card.danger::before { background: var(--danger-color); }
        .stat-card.info::before { background: var(--info-color); }
        .stat-card.secondary::before { background: var(--secondary-color); }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            background: var(--primary-color);
        }

        .stat-card.success .stat-icon { background: var(--success-color); }
        .stat-card.warning .stat-icon { background: var(--warning-color); }
        .stat-card.danger .stat-icon { background: var(--danger-color); }
        .stat-card.info .stat-icon { background: var(--info-color); }
        .stat-card.secondary .stat-icon { background: var(--secondary-color); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .stat-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .stat-link:hover {
            color: var(--accent-color);
            transform: translateX(4px);
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .content-card {
            background: var(--bg-secondary);
            border: 1px solid var(--bg-tertiary);
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary-color);
        }

        /* Activity Table */
        .activity-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .activity-table th {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            padding: 1rem 0.75rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .activity-table td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid var(--bg-tertiary);
            color: var(--text-secondary);
        }

        .activity-table tbody tr:hover {
            background: rgba(99, 102, 241, 0.05);
        }

        .activity-table tbody tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge.success {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success-color);
        }

        .badge.warning {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning-color);
        }

        .badge.danger {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger-color);
        }

        /* Quick Actions */
        .action-btn {
            display: block;
            width: 100%;
            padding: 0.875rem 1rem;
            margin-bottom: 0.75rem;
            background: var(--bg-tertiary);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-align: center;
            transition: var(--transition);
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .action-btn:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .action-btn.danger {
            background: var(--danger-color);
        }

        .action-btn.danger:hover {
            background: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Tooltip untuk sidebar diminimalkan */
        .nav-link .tooltip {
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translate(10px, -50%);
            background: var(--bg-tertiary);
            color: var(--text-primary);
            padding: 0.5rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            pointer-events: none;
            z-index: 1010;
            box-shadow: var(--shadow-md);
        }

        .sidebar.minimized .nav-link:hover .tooltip {
            opacity: 1;
            visibility: visible;
            transform: translate(5px, -50%);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .header-search {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
                width: var(--sidebar-width) !important; /* Force full width on mobile when open */
            }

            .main-content {
                margin-left: 0 !important; /* Always full width on mobile */
            }

            .sidebar-toggle {
                display: block;
            }

            .dashboard-content {
                padding: 1rem;
            }

            .welcome-content {
                flex-direction: column;
                text-align: center;
            }

            .welcome-text h1 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .activity-table {
                font-size: 0.875rem;
            }

            .activity-table th,
            .activity-table td {
                padding: 0.75rem 0.5rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        /* Loading State */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: var(--success-color);
            color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            transform: translateX(400px);
            transition: var(--transition);
            z-index: 1001;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.warning {
            background: var(--warning-color);
        }

        .notification.danger {
            background: var(--danger-color);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-cogs"></i>
                <span>EAM UNY</span>
            </div>
            <!-- Tombol minimize sidebar -->
            <button class="minimize-btn" id="minimizeBtn" title="Minimize Sidebar">
                <i class="fas fa-chevron-left" id="minimizeIcon"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Menu Utama</div>
                <div class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                        <div class="tooltip">Dashboard</div>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="aset_list.php" class="nav-link">
                        <i class="fas fa-boxes"></i>
                        <span>Data Aset</span>
                        <div class="tooltip">Data Aset</div>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="aset_tambah.php" class="nav-link">
                        <i class="fas fa-plus-circle"></i>
                        <span>Tambah Aset</span>
                        <div class="tooltip">Tambah Aset</div>
                    </a>
                </div>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Manajemen</div>
                <div class="nav-item">
                    <a href="maintenance.php" class="nav-link">
                        <i class="fas fa-wrench"></i>
                        <span>Perawatan</span>
                        <?php if ($urgent_maintenance > 0): ?>
                        <span class="nav-badge"><?php echo $urgent_maintenance; ?></span>
                        <?php endif; ?>
                        <div class="tooltip">Perawatan</div>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="aktivitas_mesin.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Aktivitas Mesin</span>
                        <div class="tooltip">Aktivitas Mesin</div>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="administrasi.php" class="nav-link">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Administrasi</span>
                        <div class="tooltip">Administrasi</div>
                    </a>
                </div>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Laporan</div>
                <div class="nav-item">
                    <a href="laporan.php" class="nav-link">
                        <i class="fas fa-file-alt"></i>
                        <span>Generate Laporan</span>
                        <div class="tooltip">Generate Laporan</div>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="statistik.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistik</span>
                        <div class="tooltip">Statistik</div>
                    </a>
                </div>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Sistem</div>
                <div class="nav-item">
                    <a href="pengaturan.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Pengaturan</span>
                        <div class="tooltip">Pengaturan</div>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="logout.php" class="nav-link" onclick="return confirm('Yakin ingin logout?')">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                        <div class="tooltip">Logout</div>
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="breadcrumb">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </div>
            </div>

            <div class="header-right">
                <div class="header-search">
                    <div class="search-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" class="search-input" placeholder="Cari aset...">
                </div>

                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <span><?php echo $username; ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="dashboard-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <div class="welcome-text">
                        <h1>
                            <span class="greeting-icon"><?php echo $greeting_icon; ?></span>
                            <span class="greeting-text"><?php echo $greeting; ?>, <?php echo $username; ?>!</span>
                        </h1>
                        <p>Selamat datang di sistem Enterprise Asset Management Universitas Negeri Yogyakarta</p>
                    </div>
                    <div class="date-time">
                        <div class="date" id="currentDate"></div>
                        <div class="time" id="currentTime"></div>
                    </div>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card primary" onclick="location.href='aset_list.php'">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_aset); ?></div>
                    <div class="stat-label">Total Aset Terdaftar</div>
                    <a href="aset_list.php" class="stat-link">
                        Lihat Detail <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="stat-card success" onclick="location.href='maintenance.php'">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $maintenance_stats['total']; ?></div>
                    <div class="stat-label">Jadwal Perawatan</div>
                    <a href="maintenance.php" class="stat-link">
                        Kelola Perawatan <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="stat-card info" onclick="location.href='administrasi.php'">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php 
                            echo "<!-- Debug Admin: " . print_r($admin_stats, true) . " -->";
                            echo isset($admin_stats['total_pending']) ? number_format($admin_stats['total_pending']) : '0'; 
                        ?> 
                    </div>
                    <div class="stat-label">Pengajuan Menunggu</div>
                    <a href="administrasi.php" class="stat-link">
                        Lihat Administrasi <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="stat-card secondary" onclick="location.href='aktivitas_mesin.php'">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php 
                            echo "<!-- Debug Mesin: " . print_r($mesin_stats, true) . " -->";
                            echo isset($mesin_stats['total_aktivitas']) ? number_format($mesin_stats['total_aktivitas']) : '0'; 
                        ?>
                    </div>
                    <div class="stat-label">Aktivitas Hari Ini</div>
                    <a href="aktivitas_mesin.php" class="stat-link">
                        Lihat Aktivitas <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
                
            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Activity Log -->
                <div class="content-card">
                    <h3 class="section-title">
                        <i class="fas fa-history"></i>
                        Log Aktivitas Terbaru
                    </h3>
                    <?php if (empty($activities)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Belum ada aktivitas tercatat</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="activity-table">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Aktivitas</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td><?php echo $activity['waktu']; ?></td>
                                        <td><?php echo htmlspecialchars($activity['aktivitas']); ?></td>
                                        <td>
                                            <span class="badge <?php echo htmlspecialchars($activity['status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($activity['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Quick Actions -->
                <div class="content-card">
                    <h3 class="section-title">
                        <i class="fas fa-bolt"></i>
                        Aksi Cepat
                    </h3>
                    <a href="aset_tambah.php" class="action-btn">
                        <i class="fas fa-plus-circle"></i>
                        Tambah Aset Baru
                    </a>
                    <a href="maintenance.php?action=new" class="action-btn">
                        <i class="fas fa-calendar-plus"></i>
                        Jadwalkan Perawatan
                    </a>
                    <a href="laporan.php" class="action-btn">
                        <i class="fas fa-file-export"></i>
                        Generate Laporan
                    </a>
                    <a href="logout.php" class="action-btn danger" onclick="return confirm('Yakin ingin logout?')">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Update current date and time
        function updateDateTime() {
            const now = new Date();
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            
            document.getElementById('currentDate').textContent = now.toLocaleDateString('id-ID', dateOptions);
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('id-ID', timeOptions);
        }

        // Update time every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Sidebar toggle untuk mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });

        // Fungsi untuk minimize sidebar yang sudah diperbaiki
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const minimizeBtn = document.getElementById('minimizeBtn');
        const minimizeIcon = document.getElementById('minimizeIcon');
        
        // Toggle minimize sidebar saat tombol diklik
        minimizeBtn.addEventListener('click', function() {
            sidebar.classList.toggle('minimized');
            mainContent.classList.toggle('expanded');
            
            // Toggle icon dan simpan status
            if (sidebar.classList.contains('minimized')) {
                minimizeIcon.classList.remove('fa-chevron-left');
                minimizeIcon.classList.add('fa-chevron-right');
                localStorage.setItem('sidebarMinimized', 'true');
            } else {
                minimizeIcon.classList.remove('fa-chevron-right');
                minimizeIcon.classList.add('fa-chevron-left');
                localStorage.setItem('sidebarMinimized', 'false');
            }
        });
        
        // Muat status sidebar dari localStorage saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const isSidebarMinimized = localStorage.getItem('sidebarMinimized') === 'true';
            
            if (isSidebarMinimized) {
                sidebar.classList.add('minimized');
                mainContent.classList.add('expanded');
                minimizeIcon.classList.remove('fa-chevron-left');
                minimizeIcon.classList.add('fa-chevron-right');
            } else {
                sidebar.classList.remove('minimized');
                mainContent.classList.remove('expanded');
                minimizeIcon.classList.remove('fa-chevron-right');
                minimizeIcon.classList.add('fa-chevron-left');
            }
        });
    </script>
</body>
</html>