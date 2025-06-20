<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once '../src/db.php';

$message = '';
$message_type = '';

if ($_POST) {
    $nama_item = trim($_POST['nama_item']);
    $spesifikasi = trim($_POST['spesifikasi']);
    $asal_usul = trim($_POST['asal_usul']);
    $tahun_pengadaan = $_POST['tahun_pengadaan'];
    $harga = str_replace(['Rp', '.', ',', ' '], '', $_POST['harga']);
    $jumlah = $_POST['jumlah'];
    $keterangan = trim($_POST['keterangan']);
    
    // Validasi
    $errors = [];
    if (empty($nama_item)) $errors[] = "Nama item harus diisi";
    if (empty($asal_usul)) $errors[] = "Asal usul harus diisi";
    if (empty($tahun_pengadaan)) $errors[] = "Tahun pengadaan harus diisi";
    if (empty($harga) || !is_numeric($harga)) $errors[] = "Harga harus diisi dengan benar";
    if (empty($jumlah) || $jumlah < 1) $errors[] = "Jumlah harus diisi minimal 1";
    
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO pending_aset (nama_item, spesifikasi, asal_usul, tahun_pengadaan, harga, jumlah, keterangan, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama_item, $spesifikasi, $asal_usul, $tahun_pengadaan, $harga, $jumlah, $keterangan]);
            
            header('Location: administrasi.php?msg=Pengajuan aset berhasil ditambahkan&type=success');
            exit;
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = implode(", ", $errors);
        $message_type = "danger";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Pengajuan Aset</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background: #2c5aa0;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar .logo {
            font-size: 1.2em;
            font-weight: bold;
        }
        .content {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 16px;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .form-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.15s ease-in-out;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        textarea.form-control {
            height: 80px;
            resize: vertical;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            font-weight: 500;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-right: 10px;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .alert {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .required {
            color: #dc3545;
        }
        .form-buttons {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        .input-help {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="navbar">
    <div class="logo">EAM UNY</div>
    <div class="user">👤 <?php echo htmlspecialchars($_SESSION['user']['username']); ?></div>
</div>

<div class="content">
    <div class="form-container">
        <div class="form-header">
            <h2 style="margin: 0; color: #495057;">Tambah Pengajuan Aset</h2>
            <p style="margin: 8px 0 0 0; color: #6c757d;">Ajukan pengadaan aset baru untuk bengkel</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="nama_item">Nama Item <span class="required">*</span></label>
                <input type="text" id="nama_item" name="nama_item" class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['nama_item'] ?? ''); ?>" 
                       placeholder="Contoh: Mesin Las, Bor Listrik, dll">
            </div>

            <div class="form-group">
                <label for="spesifikasi">Spesifikasi</label>
                <textarea id="spesifikasi" name="spesifikasi" class="form-control" 
                          placeholder="Masukkan spesifikasi detail item (opsional)"><?php echo htmlspecialchars($_POST['spesifikasi'] ?? ''); ?></textarea>
                <div class="input-help">Contoh: BIG BLUE 600X, Daya 220V, dll</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="asal_usul">Asal Usul <span class="required">*</span></label>
                    <input type="text" id="asal_usul" name="asal_usul" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['asal_usul'] ?? ''); ?>" 
                           placeholder="Contoh: AMERIKA SERIKAT">
                </div>

                <div class="form-group">
                    <label for="tahun_pengadaan">Tahun Pengadaan <span class="required">*</span></label>
                    <input type="number" id="tahun_pengadaan" name="tahun_pengadaan" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['tahun_pengadaan'] ?? date('Y')); ?>" 
                           min="2000" max="<?php echo date('Y') + 5; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="harga">Harga <span class="required">*</span></label>
                    <input type="text" id="harga" name="harga" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['harga'] ?? ''); ?>" 
                           placeholder="Contoh: 5000000" oninput="formatRupiah(this)">
                    <div class="input-help">Masukkan harga tanpa titik atau koma</div>
                </div>

                <div class="form-group">
                    <label for="jumlah">Jumlah <span class="required">*</span></label>
                    <input type="number" id="jumlah" name="jumlah" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['jumlah'] ?? '1'); ?>" 
                           min="1" max="999">
                </div>
            </div>

            <div class="form-group">
                <label for="keterangan">Keterangan</label>
                <textarea id="keterangan" name="keterangan" class="form-control" 
                          placeholder="Catatan tambahan (opsional)"><?php echo htmlspecialchars($_POST['keterangan'] ?? ''); ?></textarea>
            </div>

            <div class="form-buttons">
                <a href="administrasi.php" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-primary">Simpan Pengajuan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Format input harga menjadi format rupiah
function formatRupiah(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value) {
        input.value = 'Rp ' + parseInt(value).toLocaleString('id-ID');
    }
}

// Auto focus ke nama item
document.getElementById('nama_item').focus();

// Validasi form sebelum submit
document.querySelector('form').addEventListener('submit', function(e) {
    const namaItem = document.getElementById('nama_item').value.trim();
    const asalUsul = document.getElementById('asal_usul').value.trim();
    const tahunPengadaan = document.getElementById('tahun_pengadaan').value;
    const harga = document.getElementById('harga').value.replace(/[^0-9]/g, '');
    const jumlah = document.getElementById('jumlah').value;
    
    if (!namaItem || !asalUsul || !tahunPengadaan || !harga || !jumlah) {
        e.preventDefault();
        alert('Mohon lengkapi semua field yang wajib diisi (*)');
        return false;
    }
    
    if (parseInt(jumlah) < 1) {
        e.preventDefault();
        alert('Jumlah minimal adalah 1');
        return false;
    }
});
</script>
</body>
</html>