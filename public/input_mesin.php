<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once '../src/db.php';

$message = '';
$error = '';

// Fungsi untuk mengambil daftar users untuk dropdown operator
function getUsers($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, username FROM users ORDER BY username");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_mesin = trim($_POST['nama_mesin'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $operator_id = !empty($_POST['operator_id']) ? (int)$_POST['operator_id'] : null;
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    $rpm_current = (int)($_POST['rpm_current'] ?? 0);
    $suhu_current = (float)($_POST['suhu_current'] ?? 0);
    
    // Validasi input
    if (empty($nama_mesin)) {
        $error = 'Nama mesin harus diisi!';
    } elseif (strlen($nama_mesin) < 3) {
        $error = 'Nama mesin minimal 3 karakter!';
    } else {
        try {
            // Cek apakah nama mesin sudah ada
            $stmt = $pdo->prepare("SELECT id FROM machines WHERE nama_mesin = ?");
            $stmt->execute([$nama_mesin]);
            
            if ($stmt->fetch()) {
                $error = 'Nama mesin sudah ada! Gunakan nama lain.';
            } else {
                // Insert data mesin baru
                $stmt = $pdo->prepare("
                    INSERT INTO machines (nama_mesin, lokasi, operator_id, status_aktif, rpm_current, suhu_current, last_update) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                if ($stmt->execute([$nama_mesin, $lokasi, $operator_id, $status_aktif, $rpm_current, $suhu_current])) {
                    $message = 'Mesin berhasil ditambahkan!';
                    // Reset form
                    $_POST = array();
                } else {
                    $error = 'Gagal menambahkan mesin!';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$users = getUsers($pdo);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Mesin Baru</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            background: #f4f7fa;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background: #3b5998;
            color: #fff;
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .navbar .logo {
            font-size: 1.3em;
            font-weight: bold;
        }
        .navbar a {
            color: #fff;
            text-decoration: none;
            margin-left: 20px;
        }
        .navbar a:hover {
            text-decoration: underline;
        }
        .content {
            max-width: 800px;
            margin: 32px auto;
            padding: 16px;
        }
        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b5998;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        .btn {
            background: #3b5998;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2d4373;
        }
        .btn-secondary {
            background: #6c757d;
            margin-right: 10px;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .input-addon {
            background: #e9ecef;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .content {
                padding: 16px 8px;
            }
            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="navbar">
    <div class="logo">EAM UNY</div>
    <div>
        <a href="machine_activity.php">← Kembali ke Aktivitas Mesin</a>
        <span style="margin-left: 20px;">👤 <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
    </div>
</div>

<div class="content">
    <div class="form-container">
        <h1>Tambah Mesin Baru</h1>
        <p>Isi form di bawah ini untuk menambahkan mesin baru ke dalam sistem.</p>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nama_mesin">Nama Mesin *</label>
                    <input type="text" id="nama_mesin" name="nama_mesin" 
                           value="<?php echo htmlspecialchars($_POST['nama_mesin'] ?? ''); ?>" 
                           required placeholder="Contoh: Mesin Bubut A1">
                </div>
                
                <div class="form-group">
                    <label for="lokasi">Lokasi</label>
                    <input type="text" id="lokasi" name="lokasi" 
                           value="<?php echo htmlspecialchars($_POST['lokasi'] ?? ''); ?>" 
                           placeholder="Contoh: Lantai 2 Ruang Produksi">
                </div>
                
                <div class="form-group">
                    <label for="operator_id">Operator</label>
                    <select id="operator_id" name="operator_id">
                        <option value="">-- Pilih Operator --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" 
                                    <?php echo (isset($_POST['operator_id']) && $_POST['operator_id'] == $user['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status Mesin</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="status_aktif" name="status_aktif" 
                               <?php echo (isset($_POST['status_aktif']) && $_POST['status_aktif']) ? 'checked' : ''; ?>>
                        <label for="status_aktif">Mesin sedang hidup</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="rpm_current">RPM Saat Ini</label>
                    <div class="input-group">
                        <input type="number" id="rpm_current" name="rpm_current" 
                               value="<?php echo htmlspecialchars($_POST['rpm_current'] ?? '0'); ?>" 
                               min="0" max="10000" placeholder="0">
                        <span class="input-addon">RPM</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="suhu_current">Suhu Saat Ini</label>
                    <div class="input-group">
                        <input type="number" id="suhu_current" name="suhu_current" 
                               value="<?php echo htmlspecialchars($_POST['suhu_current'] ?? '0'); ?>" 
                               min="0" max="200" step="0.1" placeholder="0">
                        <span class="input-addon">°C</span>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 30px;">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='machine_activity.php'">
                    Batal
                </button>
                <button type="submit" class="btn">
                    Tambah Mesin
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-focus pada input pertama
document.getElementById('nama_mesin').focus();

// Validasi form sebelum submit
document.querySelector('form').addEventListener('submit', function(e) {
    const namaMesin = document.getElementById('nama_mesin').value.trim();
    
    if (namaMesin.length < 3) {
        alert('Nama mesin minimal 3 karakter!');
        e.preventDefault();
        document.getElementById('nama_mesin').focus();
        return false;
    }
});

// Auto-format input RPM dan Suhu
document.getElementById('rpm_current').addEventListener('input', function(e) {
    let value = parseInt(e.target.value);
    if (value > 10000) e.target.value = 10000;
    if (value < 0) e.target.value = 0;
});

document.getElementById('suhu_current').addEventListener('input', function(e) {
    let value = parseFloat(e.target.value);
    if (value > 200) e.target.value = 200;
    if (value < 0) e.target.value = 0;
});
</script>

</body>
</html>