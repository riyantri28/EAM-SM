<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once '../src/db.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'start_operation') {
        $machine_id = $_POST['machine_id'];
        $user_id = $_SESSION['user']['id'];
        $operator_name = $_POST['operator_name'] ?? '';
        $operator_nim = $_POST['operator_nim'] ?? '';
        
        // Validasi input
        if (empty($operator_name) || empty($operator_nim)) {
            echo json_encode(['success' => false, 'message' => 'Nama operator dan NIM harus diisi']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Update mesin menjadi aktif dan set operator
            $stmt = $pdo->prepare("UPDATE machines SET status_aktif = 1, operator_id = ?, operator_name = ?, operator_nim = ?, last_update = NOW() WHERE id = ?");
            $stmt->execute([$user_id, $operator_name, $operator_nim, $machine_id]);
            
            // Buat log operasi baru
            $stmt = $pdo->prepare("INSERT INTO operation_logs (machine_id, operator_id, operator_name, operator_nim, start_time, status) VALUES (?, ?, ?, ?, NOW(), 'active')");
            $stmt->execute([$machine_id, $user_id, $operator_name, $operator_nim]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Operasi mesin dimulai']);
        } catch (Exception $e) {
            $pdo->rollback();
            echo json_encode(['success' => false, 'message' => 'Gagal memulai operasi: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'stop_operation') {
        $machine_id = $_POST['machine_id'];
        $user_id = $_SESSION['user']['id'];
        
        try {
            $pdo->beginTransaction();
            
            // Update log operasi yang aktif
            $stmt = $pdo->prepare("UPDATE operation_logs SET end_time = NOW(), status = 'completed' WHERE machine_id = ? AND status = 'active'");
            $stmt->execute([$machine_id]);
            
            // Update mesin menjadi tidak aktif dan hapus operator
            $stmt = $pdo->prepare("UPDATE machines SET status_aktif = 0, operator_id = NULL, operator_name = NULL, operator_nim = NULL, last_update = NOW() WHERE id = ?");
            $stmt->execute([$machine_id]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Operasi mesin dihentikan']);
        } catch (Exception $e) {
            $pdo->rollback();
            echo json_encode(['success' => false, 'message' => 'Gagal menghentikan operasi: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Fungsi untuk mengambil data mesin dengan informasi operasi
function getMachines($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                m.id,
                m.nama_mesin,
                m.status_aktif,
                m.rpm_current,
                m.suhu_current,
                m.lokasi,
                m.last_update,
                m.operator_name,
                m.operator_nim,
                u.username as user_login,
                u.id as operator_id,
                ol.start_time as operation_start,
                ol.id as operation_log_id,
                TIMESTAMPDIFF(MINUTE, ol.start_time, NOW()) as operation_duration
            FROM machines m 
            LEFT JOIN users u ON m.operator_id = u.id 
            LEFT JOIN operation_logs ol ON m.id = ol.machine_id AND ol.status = 'active'
            ORDER BY m.status_aktif DESC, m.nama_mesin
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Mengambil data mesin
$machines = getMachines($pdo);
$current_user_id = $_SESSION['user']['id'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivitas Mesin - EAM UNY</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 25px;
            font-weight: 500;
            color: #667eea;
        }

        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.6);
        }

        .btn-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.4);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(244, 67, 54, 0.6);
        }

        .btn-disabled {
            background: #95a5a6;
            color: white;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .machines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .machine-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .machine-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #ddd, #ddd);
            transition: all 0.3s ease;
        }

        .machine-card.active::before {
            background: linear-gradient(90deg, #4CAF50, #45a049);
        }

        .machine-card.my-operation::before {
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .machine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .machine-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .machine-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            line-height: 1.3;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .status-active {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-inactive {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-active .status-indicator {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .machine-info {
            display: grid;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .info-icon {
            width: 16px;
            text-align: center;
            color: #667eea;
        }

        .operator-info {
            background: rgba(76, 175, 80, 0.1);
            border-left: 3px solid #4CAF50;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .operator-name {
            font-weight: 600;
            color: #2e7d32;
            margin-bottom: 0.25rem;
        }

        .operator-details {
            font-size: 0.85rem;
            color: #388e3c;
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .action-buttons .btn {
            flex: 1;
            justify-content: center;
            font-size: 0.9rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 2rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 1.1rem;
            color: #212529;
            font-weight: 500;
        }

        .rpm-high { color: #dc3545; }
        .rpm-normal { color: #28a745; }
        .temp-high { color: #dc3545; }
        .temp-normal { color: #28a745; }

        .operator-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #495057;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .no-data {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .no-data-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .no-data h3 {
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .no-data p {
            color: #adb5bd;
        }

        .back-button {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 999;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 0 0.5rem;
            }

            .machines-grid {
                grid-template-columns: 1fr;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
            }

            .modal-body {
                padding: 1rem;
            }

            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .page-title {
                font-size: 2rem;
            }

            .back-button {
                position: static;
                margin: 2rem auto;
                display: block;
                width: fit-content;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-content">
            <div class="logo">
                <i class="fas fa-cogs"></i>
                <span>EAM UNY</span>
            </div>
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span><?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-industry"></i>
                Aktivitas Mesin
            </h1>
            <p class="page-subtitle">
                Monitor dan kelola operasi mesin secara real-time
            </p>
        </div>

        <!-- Statistics Cards -->
        <?php
        $total_machines = count($machines);
        $active_machines = count(array_filter($machines, fn($m) => $m['status_aktif']));
        $my_operations = count(array_filter($machines, fn($m) => $m['status_aktif'] && $m['operator_id'] == $current_user_id));
        ?>
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon">🏭</div>
                <div class="stat-number"><?php echo $total_machines; ?></div>
                <div class="stat-label">Total Mesin</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚡</div>
                <div class="stat-number"><?php echo $active_machines; ?></div>
                <div class="stat-label">Mesin Aktif</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-number"><?php echo $my_operations; ?></div>
                <div class="stat-label">Operasi Saya</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💤</div>
                <div class="stat-number"><?php echo $total_machines - $active_machines; ?></div>
                <div class="stat-label">Mesin Idle</div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <a href="input_mesin.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Tambah Mesin Baru
            </a>
            <div>
                <span style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">
                    <i class="fas fa-sync-alt"></i>
                    Auto refresh setiap 30 detik
                </span>
            </div>
        </div>

        <!-- Machines Grid -->
        <?php if (empty($machines)): ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <i class="fas fa-industry"></i>
                </div>
                <h3>Tidak ada data mesin tersedia</h3>
                <p>Belum ada mesin yang terdaftar dalam sistem atau terjadi masalah koneksi database.</p>
                <div style="margin-top: 2rem;">
                    <a href="input_mesin.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Tambah Mesin Pertama
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="machines-grid">
                <?php foreach ($machines as $machine): ?>
                    <?php
                    $isMyOperation = $machine['operator_id'] == $current_user_id;
                    $cardClass = '';
                    if ($machine['status_aktif']) {
                        $cardClass = $isMyOperation ? 'active my-operation' : 'active';
                    }
                    ?>
                    <div class="machine-card <?php echo $cardClass; ?>" onclick="showMachineDetail(<?php echo htmlspecialchars(json_encode($machine)); ?>)">
                        <div class="machine-header">
                            <div class="machine-name">
                                <i class="fas fa-cog"></i>
                                <?php echo htmlspecialchars($machine['nama_mesin']); ?>
                            </div>
                            <div class="status-badge <?php echo $machine['status_aktif'] ? 'status-active' : 'status-inactive'; ?>">
                                <div class="status-indicator"></div>
                                <?php echo $machine['status_aktif'] ? 'Beroperasi' : 'Idle'; ?>
                            </div>
                        </div>

                        <?php if ($machine['status_aktif'] && $machine['operator_name']): ?>
                            <div class="operator-info">
                                <div class="operator-name">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($machine['operator_name']); ?>
                                    <?php if ($isMyOperation): ?>
                                        <span style="color: #667eea; font-weight: 600;">(Anda)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="operator-details">
                                    <i class="fas fa-id-card"></i>
                                    NIM: <?php echo htmlspecialchars($machine['operator_nim']); ?>
                                    <?php if ($machine['operation_duration']): ?>
                                        | <i class="fas fa-clock"></i>
                                        <?php 
                                            $duration = $machine['operation_duration'];
                                            $hours = floor($duration / 60);
                                            $minutes = $duration % 60;
                                            echo $hours > 0 ? "{$hours}j {$minutes}m" : "{$minutes} menit";
                                        ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="machine-info">
                            <div class="info-row">
                                <i class="fas fa-map-marker-alt info-icon"></i>
                                <span><?php echo htmlspecialchars($machine['lokasi'] ?? 'Tidak diketahui'); ?></span>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-tachometer-alt info-icon"></i>
                                <span><?php echo $machine['rpm_current'] ?? 0; ?> RPM</span>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-thermometer-half info-icon"></i>
                                <span><?php echo $machine['suhu_current'] ?? 0; ?>°C</span>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-clock info-icon"></i>
                                <span><?php echo $machine['last_update'] ? date('d/m/Y H:i', strtotime($machine['last_update'])) : 'Tidak ada'; ?></span>
                            </div>
                        </div>

                        <div class="action-buttons" onclick="event.stopPropagation();">
                            <?php if (!$machine['status_aktif']): ?>
                                <button class="btn btn-success" onclick="showOperatorForm(<?php echo $machine['id']; ?>)">
                                    <i class="fas fa-play"></i>
                                    Mulai Operasi
                                </button>
                            <?php elseif ($isMyOperation): ?>
                                <button class="btn btn-danger" onclick="stopOperation(<?php echo $machine['id']; ?>)">
                                    <i class="fas fa-stop"></i>
                                    Selesai Operasi
                                </button>
                            <?php else: ?>
                                <button class="btn btn-disabled" disabled>
                                    <i class="fas fa-lock"></i>
                                    Sedang Digunakan
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Back Button -->
    <div class="back-button">
        <a href="dashboard.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i>
            Kembali ke Dashboard
        </a>
    </div>

    <!-- Machine Detail Modal -->
    <div id="machineModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Detail Mesin</h2>
                <button class="close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="detail-grid">
                    <div class="detail-card">
                        <div class="detail-label">Status</div>
                        <div class="detail-value" id="modalStatus"></div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Operator</div>
                        <div class="detail-value" id="modalOperator"></div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">RPM</div>
                        <div class="detail-value" id="modalRPM"></div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Suhu</div>
                        <div class="detail-value" id="modalSuhu"></div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Lokasi</div>
                        <div class="detail-value" id="modalLokasi"></div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Update Terakhir</div>
                        <div class="detail-value" id="modalLastUpdate"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Operator Form Modal -->
    <div id="operatorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Input Data Operator</h2>
                <button class="close" onclick="closeOperatorModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="operatorForm" class="operator-form">
                    <input type="hidden" id="machineId" name="machine_id">
                    <div class="form-group">
                        <label class="form-label">Nama Operator</label>
                        <input type="text" class="form-control" id="operatorName" name="operator_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">NIM</label>
                        <input type="text" class="form-control" id="operatorNim" name="operator_nim" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play"></i>
                        Mulai Operasi
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Show machine detail modal
        function showMachineDetail(machine) {
            document.getElementById('modalTitle').textContent = machine.nama_mesin;
            document.getElementById('modalStatus').textContent = machine.status_aktif ? 'Beroperasi' : 'Idle';
            document.getElementById('modalOperator').textContent = machine.operator_name || 'Tidak ada operator';
            document.getElementById('modalRPM').textContent = `${machine.rpm_current || 0} RPM`;
            document.getElementById('modalSuhu').textContent = `${machine.suhu_current || 0}°C`;
            document.getElementById('modalLokasi').textContent = machine.lokasi || 'Tidak diketahui';
            document.getElementById('modalLastUpdate').textContent = machine.last_update ? 
                new Date(machine.last_update).toLocaleString('id-ID') : 'Tidak ada data';
            
            document.getElementById('machineModal').style.display = 'block';
        }

        // Close machine detail modal
        function closeModal() {
            document.getElementById('machineModal').style.display = 'none';
        }

        // Show operator form modal
        function showOperatorForm(machineId) {
            document.getElementById('machineId').value = machineId;
            document.getElementById('operatorModal').style.display = 'block';
        }

        // Close operator form modal
        function closeOperatorModal() {
            document.getElementById('operatorModal').style.display = 'none';
            document.getElementById('operatorForm').reset();
        }

        // Handle operator form submission
        document.getElementById('operatorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('aktivitas_mesin.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'start_operation',
                    machine_id: formData.get('machine_id'),
                    operator_name: formData.get('operator_name'),
                    operator_nim: formData.get('operator_nim')
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Terjadi kesalahan');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan sistem');
            });
        });

        // Stop machine operation
        function stopOperation(machineId) {
            if (!confirm('Anda yakin ingin menghentikan operasi mesin ini?')) return;
            
            fetch('aktivitas_mesin.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'stop_operation',
                    machine_id: machineId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Terjadi kesalahan');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan sistem');
            });
        }

        // Auto refresh setiap 30 detik
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>