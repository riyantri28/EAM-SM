<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once '../src/db.php';

// Ambil ID aset dari parameter URL
$id = intval($_GET['id'] ?? 0);

if ($id === 0) {
    header('Location: aset_list.php?error=invalid_id');
    exit;
}

// Ambil data aset berdasarkan ID
$stmt = $pdo->prepare("SELECT * FROM aset WHERE id = ?");
$stmt->execute([$id]);
$aset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aset) {
    header('Location: aset_list.php?error=not_found');
    exit;
}

// Proses form jika ada data POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nama_item = $_POST['nama_item'] ?? '';
        $spesifikasi = $_POST['spesifikasi'] ?? '';
        $asal_usul = $_POST['asal_usul'] ?? '';
        $tahun_pengadaan = $_POST['tahun_pengadaan'] ?? '';
        $harga = $_POST['harga'] ?? 0;
        $jumlah = $_POST['jumlah'] ?? 1;
        $status = $_POST['status'] ?? 'Aktif';
        $keterangan = $_POST['keterangan'] ?? '';

        // Validasi input
        if (empty($nama_item)) {
            throw new Exception('Nama item harus diisi');
        }

        // Update data aset
        $updateStmt = $pdo->prepare("
            UPDATE aset SET 
                nama_item = ?, 
                spesifikasi = ?, 
                asal_usul = ?, 
                tahun_pengadaan = ?, 
                harga = ?, 
                jumlah = ?, 
                status = ?, 
                keterangan = ?
            WHERE id = ?
        ");
        
        $updateStmt->execute([
            $nama_item,
            $spesifikasi,
            $asal_usul,
            $tahun_pengadaan,
            $harga,
            $jumlah,
            $status,
            $keterangan,
            $id
        ]);

        header('Location: aset_list.php?edit=success');
        exit;

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Aset - EAM UNY</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, rgb(1, 12, 34) 0%, rgb(14, 42, 90) 100%);
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
        
        .form-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #fff;
            font-weight: 600;
            font-size: 0.95em;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: #fff;
            font-size: 1em;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #4fc3f7;
            box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .form-input::placeholder,
        .form-textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-select option {
            background: #1e3c72;
            color: #fff;
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
        
        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #e57373;
        }
        
        .btn-group {
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            margin-top: 32px;
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
            box-shadow: 0 4px 15px rgba(79<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once '../src/db.php';

// Ambil ID aset dari parameter URL
$id = intval($_GET['id'] ?? 0);

if ($id === 0) {
    header('Location: aset_list.php?error=invalid_id');
    exit;
}

// Ambil data aset berdasarkan ID
$stmt = $pdo->prepare("SELECT * FROM aset WHERE id = ?");
$stmt->execute([$id]);
$aset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aset) {
    header('Location: aset_list.php?error=not_found');
    exit;
}

// Proses form jika ada data POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nama_item = $_POST['nama_item'] ?? '';
        $spesifikasi = $_POST['spesifikasi'] ?? '';
        $asal_usul = $_POST['asal_usul'] ?? '';
        $tahun_pengadaan = $_POST['tahun_pengadaan'] ?? '';
        $harga = $_POST['harga'] ?? 0;
        $jumlah = $_POST['jumlah'] ?? 1;
        $status = $_POST['status'] ?? 'Aktif';
        $keterangan = $_POST['keterangan'] ?? '';

        // Validasi input
        if (empty($nama_item)) {
            throw new Exception('Nama item harus diisi');
        }

        // Update data aset
        $updateStmt = $pdo->prepare("
            UPDATE aset SET 
                nama_item = ?, 
                spesifikasi = ?, 
                asal_usul = ?, 
                tahun_pengadaan = ?, 
                harga = ?, 
                jumlah = ?, 
                status = ?, 
                keterangan = ?
            WHERE id = ?
        ");
        
        $updateStmt->execute([
            $nama_item,
            $spesifikasi,
            $asal_usul,
            $tahun_pengadaan,
            $harga,
            $jumlah,
            $status,
            $keterangan,
            $id
        ]);

        header('Location: aset_list.php?edit=success');
        exit;

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Aset - EAM UNY</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, rgb(1, 12, 34) 0%, rgb(14, 42, 90) 100%);
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
        
        .form-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #fff;
            font-weight: 600;
            font-size: 0.95em;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: #fff;
            font-size: 1em;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #4fc3f7;
            box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .form-input::placeholder,
        .form-textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-select option {
            background: #1e3c72;
            color: #fff;
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
        
        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #e57373;
        }
        
        .btn-group {
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            margin-top: 32px;
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
            background: linear-gradient(45deg, #29b6f6, #1e88e5);
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
        
        @media (max-width: 768px) {
            .navbar {
                padding: 16px 20px;
            }
            
            .container {
                padding: 0 16px;
            }
            
            .form-container {
                padding: 24px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
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
            <i class="fas fa-tools"></i>
            EAM-DPTM UNY
        </div>
        <div class="user">
            <i class="fas fa-user"></i>
            <?= htmlspecialchars($_SESSION['user']['username']) ?>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> Edit Aset</h1>
            <p>Formulir Edit Data Aset Bengkel</p>
        </div>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="nama_item">
                            <i class="fas fa-tag"></i> Nama Item
                        </label>
                        <input type="text" 
                               id="nama_item" 
                               name="nama_item" 
                               class="form-input" 
                               value="<?= htmlspecialchars($aset['nama_item'] ?? '') ?>"
                               placeholder="Masukkan nama item" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="status">
                            <i class="fas fa-info-circle"></i> Status
                        </label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="Aktif" <?= ($aset['status'] ?? '') === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Nonaktif" <?= ($aset['status'] ?? '') === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            <option value="Perbaikan" <?= ($aset['status'] ?? '') === 'Perbaikan' ? 'selected' : '' ?>>Perbaikan</option>
                            <option value="Rusak" <?= ($aset['status'] ?? '') === 'Rusak' ? 'selected' : '' ?>>Rusak</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="spesifikasi">
                        <i class="fas fa-cogs"></i> Spesifikasi
                    </label>
                    <textarea id="spesifikasi" 
                              name="spesifikasi" 
                              class="form-textarea" 
                              placeholder="Masukkan spesifikasi detail aset"><?= htmlspecialchars($aset['spesifikasi'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="asal_usul">
                            <i class="fas fa-home"></i> Asal Usul
                        </label>
                        <input type="text" 
                               id="asal_usul" 
                               name="asal_usul" 
                               class="form-input" 
                               value="<?= htmlspecialchars($aset['asal_usul'] ?? '') ?>"
                               placeholder="Sumber pengadaan">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="tahun_pengadaan">
                            <i class="fas fa-calendar"></i> Tahun Pengadaan
                        </label>
                        <input type="number" 
                               id="tahun_pengadaan" 
                               name="tahun_pengadaan" 
                               class="form-input" 
                               value="<?= htmlspecialchars($aset['tahun_pengadaan'] ?? '') ?>"
                               placeholder="YYYY"
                               min="1900"
                               max="<?= date('Y') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="harga">
                            <i class="fas fa-money-bill-wave"></i> Harga (Rp)
                        </label>
                        <input type="number" 
                               id="harga" 
                               name="harga" 
                               class="form-input" 
                               value="<?= htmlspecialchars($aset['harga'] ?? '') ?>"
                               placeholder="0"
                               min="0"
                               step="1000">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="jumlah">
                            <i class="fas fa-sort-numeric-up"></i> Jumlah
                        </label>
                        <input type="number" 
                               id="jumlah" 
                               name="jumlah" 
                               class="form-input" 
                               value="<?= htmlspecialchars($aset['jumlah'] ?? 1) ?>"
                               placeholder="1"
                               min="1">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="keterangan">
                        <i class="fas fa-sticky-note"></i> Keterangan
                    </label>
                    <textarea id="keterangan" 
                              name="keterangan" 
                              class="form-textarea" 
                              placeholder="Keterangan tambahan..."><?= htmlspecialchars($aset['keterangan'] ?? '') ?></textarea>
                </div>

                <div class="btn-group">
                    <a href="aset_list.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Format input harga dengan pemisah ribuan
        document.getElementById('harga').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value) {
                e.target.value = parseInt(value).toLocaleString('id-ID');
            }
        });

        // Hapus format saat form disubmit
        document.querySelector('form').addEventListener('submit', function(e) {
            let hargaInput = document.getElementById('harga');
            hargaInput.value = hargaInput.value.replace(/\D/g, '');
        });

        // Auto-resize textarea
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }

        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', () => autoResize(textarea));
            autoResize(textarea); // Initial resize
        });

        // Konfirmasi sebelum meninggalkan halaman jika ada perubahan
        let formChanged = false;
        document.querySelectorAll('input, select, textarea').forEach(element => {
            element.addEventListener('change', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Reset flag saat form disubmit
        document.querySelector('form').addEventListener('submit', () => {
            formChanged = false;
        });
    </script>
</body>
</html>