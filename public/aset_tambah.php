<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once '../src/db.php';
require_once '../src/functions.php';

// Tambahkan logic pemrosesan form di sini
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql = "INSERT INTO aset (nama_item, spesifikasi, asal_usul, tahun_pengadaan, harga, jumlah, status, keterangan) 
                VALUES (:nama_item, :spesifikasi, :asal_usul, :tahun_pengadaan, :harga, :jumlah, :status, :keterangan)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nama_item' => $_POST['nama_item'],
            ':spesifikasi' => $_POST['spesifikasi'],
            ':asal_usul' => $_POST['asal_usul'],
            ':tahun_pengadaan' => $_POST['tahun_pengadaan'],
            ':harga' => $_POST['harga'],
            ':jumlah' => $_POST['jumlah'],
            ':status' => $_POST['status'],
            ':keterangan' => $_POST['keterangan']
        ]);

        // Log aktivitas
        logAktivitas($pdo, "Menambahkan aset baru: " . $_POST['nama_item'], 'success');
        
        header('Location: aset_list.php?msg=success');
        exit;
    } catch (PDOException $e) {
        logAktivitas($pdo, "Gagal menambahkan aset: " . $_POST['nama_item'], 'danger');
        $error = "Gagal menambahkan aset. Silakan coba lagi.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Aset - EAM UNY</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg,rgb(0, 13, 36) 0%,rgb(6, 36, 87) 100%);
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
            max-width: 800px;
            margin: 24px auto;
            padding: 0 24px;
        }
        
        .form-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .form-header h1 {
            font-size: 2.2em;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(45deg, #4fc3f7, #29b6f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .form-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1em;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        
        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #e57373;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            font-size: 0.95em;
        }
        
        .form-group i {
            color: #4fc3f7;
            width: 16px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: #fff;
            font-size: 1em;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4fc3f7;
            box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }
        
        select.form-control {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
            appearance: none;
        }
        
        select.form-control option {
            background: #1e3c72;
            color: #fff;
            padding: 8px;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1em;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            min-width: 160px;
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
        
        .btn-success {
            background: linear-gradient(45deg, #4caf50, #43a047);
            color: #fff;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }
        
        .form-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .divider {
            margin: 32px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }
        
        .divider::after {
            content: 'ATAU';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 0 16px;
            font-size: 0.8em;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 600;
        }
        
        .import-section {
            text-align: center;
            margin-top: 24px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 8px;
        }
        
        .back-link:hover {
            color: #4fc3f7;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(-4px);
        }
        
        .required {
            color: #ff6b6b;
        }
        
        .form-help {
            font-size: 0.85em;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 4px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 16px;
            }
            
            .form-container {
                padding: 24px 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .navbar {
                padding: 12px 16px;
                flex-direction: column;
                gap: 12px;
            }
            
            .form-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
            
            .form-header h1 {
                font-size: 1.8em;
            }
        }
        
        /* Loading animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .loading .btn-primary {
            background: linear-gradient(45deg, #666, #888);
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading .fa-spinner {
            animation: spin 1s linear infinite;
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
        <div class="form-container">
            <div class="form-header">
                <h1><i class="fas fa-plus-circle"></i> Tambah Aset Baru</h1>
                <p>Lengkapi informasi aset yang akan ditambahkan ke sistem</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="assetForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-tag"></i>
                            Nama Item <span class="required">*</span>
                        </label>
                        <input type="text" name="nama_item" class="form-control" 
                               placeholder="Masukkan nama item..." required>
                        <div class="form-help">Nama lengkap dari aset yang akan ditambahkan</div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-info-circle"></i>
                            Spesifikasi
                        </label>
                        <input type="text" name="spesifikasi" class="form-control" 
                               placeholder="Masukkan spesifikasi...">
                        <div class="form-help">Detail spesifikasi teknis (opsional)</div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-building"></i>
                            Asal Usul
                        </label>
                        <input type="text" name="asal_usul" class="form-control" 
                               placeholder="Masukkan asal usul...">
                        <div class="form-help">Sumber atau asal perolehan aset</div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-calendar-alt"></i>
                            Tahun Pengadaan <span class="required">*</span>
                        </label>
                        <input type="number" name="tahun_pengadaan" class="form-control" 
                               min="1900" max="<?php echo date('Y'); ?>" 
                               value="<?php echo date('Y'); ?>" required>
                        <div class="form-help">Tahun pembelian atau pengadaan aset</div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-money-bill-wave"></i>
                            Harga <span class="required">*</span>
                        </label>
                        <input type="number" step="0.01" name="harga" class="form-control" 
                               placeholder="0.00" required>
                        <div class="form-help">Harga dalam Rupiah (Rp)</div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-sort-numeric-up"></i>
                            Jumlah <span class="required">*</span>
                        </label>
                        <input type="number" step="1" name="jumlah" class="form-control" 
                               min="1" value="1" required>
                        <div class="form-help">Jumlah unit aset</div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-signal"></i>
                            Status <span class="required">*</span>
                        </label>
                        <select name="status" class="form-control" required>
                            <option value="">Pilih Status</option>
                            <option value="Aktif">Aktif</option>
                            <option value="Tidak Aktif">Tidak Aktif</option>
                            <option value="Rusak">Rusak</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                        <div class="form-help">Status kondisi aset saat ini</div>
                    </div>

                    <div class="form-group full-width">
                        <label>
                            <i class="fas fa-comment-alt"></i>
                            Keterangan
                        </label>
                        <textarea name="keterangan" class="form-control" rows="4" 
                                  placeholder="Masukkan keterangan tambahan..."></textarea>
                        <div class="form-help">Informasi tambahan tentang aset (opsional)</div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <span class="btn-text">Simpan Aset</span>
                    </button>
                    <a href="aset_list.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Batal
                    </a>
                </div>
            </form>

            <div class="divider"></div>

            <div class="import-section">
                <a href="aset_importexcel.php" class="btn btn-success">
                    <i class="fas fa-file-import"></i>
                    Import dari Excel
                </a>
                <div class="form-help" style="margin-top: 12px;">
                    Impor banyak aset sekaligus menggunakan file Excel
                </div>
            </div>

            <div style="text-align: center;">
                <a href="aset_list.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Daftar Aset
                </a>
            </div>
        </div>
    </div>

    <script>
        // Form validation and enhancement
        document.getElementById('assetForm').addEventListener('submit', function(e) {
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnIcon = submitBtn.querySelector('i');
            
            // Add loading state
            form.classList.add('loading');
            btnText.textContent = 'Menyimpan...';
            btnIcon.className = 'fas fa-spinner';
            
            // Basic validation
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#ff6b6b';
                    field.focus();
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                form.classList.remove('loading');
                btnText.textContent = 'Simpan Aset';
                btnIcon.className = 'fas fa-save';
                
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-error';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Mohon lengkapi semua field yang wajib diisi!';
                
                const existingAlert = document.querySelector('.alert');
                if (existingAlert) {
                    existingAlert.remove();
                }
                
                form.insertBefore(errorDiv, form.firstChild);
                
                // Auto hide error after 5 seconds
                setTimeout(() => {
                    errorDiv.remove();
                }, 5000);
            }
        });
        
        // Format currency input
        document.querySelector('input[name="harga"]').addEventListener('input', function() {
            // Remove non-numeric characters except decimal point
            this.value = this.value.replace(/[^\d.]/g, '');
        });
        
        // Auto-focus first input
        document.querySelector('input[name="nama_item"]').focus();
        
        // Remove border color on input focus
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.borderColor = '';
            });
        });
    </script>
</body>
</html>