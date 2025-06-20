<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once '../src/db.php';
require_once '../src/functions.php';
// ...existing session and require code...
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Aset - EAM UNY</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
        }

        .logo i {
            color: #667eea;
            font-size: 1.8rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #4a5568;
            font-weight: 500;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Main Content */
        .main-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
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
            font-weight: 400;
        }

        /* Import Card */
        .import-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Upload Section */
        .upload-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px dashed #cbd5e0;
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .upload-section:hover {
            border-color: #667eea;
            background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
            transform: translateY(-2px);
        }

        .upload-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            transition: all 0.3s ease;
            opacity: 0;
        }

        .upload-section:hover::before {
            opacity: 1;
        }

        .upload-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .upload-text {
            font-size: 1.2rem;
            color: #4a5568;
            margin-bottom: 1.5rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .upload-subtext {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }

        input[type="file"] {
            display: none;
        }

        .file-label {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .file-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }

        .file-name {
            margin-top: 1rem;
            padding: 0.75rem 1.5rem;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 8px;
            color: #4a5568;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        /* Format Guide */
        .format-guide {
            background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
            border-radius: 16px;
            padding: 2rem;
            margin: 2rem 0;
            border: 1px solid #fed7aa;
        }

        .format-guide h3 {
            color: #c2410c;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .columns-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .column-item {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid #fed7aa;
            font-weight: 500;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .column-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .column-item i {
            color: #ea580c;
            font-size: 0.9rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .submit-btn {
            width: 100%;
            margin-top: 2rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .back-section {
            text-align: center;
            margin-top: 3rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .main-container {
                margin: 1rem auto;
                padding: 0 0.5rem;
            }

            .import-card {
                padding: 2rem 1.5rem;
                border-radius: 16px;
            }

            .page-title {
                font-size: 2rem;
            }

            .upload-section {
                padding: 2rem 1rem;
            }

            .columns-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
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

        .import-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .format-guide {
            animation: fadeInUp 0.8s ease-out;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-database"></i>
                <span>EAM UNY</span>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user']['username'], 0, 2)); ?>
                </div>
                <span><?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">Import Data Aset</h1>
            <p class="page-subtitle">Upload file Excel atau CSV untuk mengimpor data aset secara massal</p>
        </div>

        <div class="import-card">
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="../src/aset_import_excel.php" enctype="multipart/form-data">
                <div class="upload-section">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">Pilih File untuk Diimpor</div>
                    <div class="upload-subtext">Mendukung format Excel (.xlsx, .xls) dan CSV</div>
                    
                    <input type="file" id="file_excel" name="file_excel" 
                           accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" 
                           required>
                    <label for="file_excel" class="file-label">
                        <i class="fas fa-file-excel"></i>
                        Pilih File
                    </label>
                    
                    <div id="file-name" class="file-name">
                        Belum ada file dipilih
                    </div>
                </div>
                
                <div class="format-guide">
                    <h3>
                        <i class="fas fa-info-circle"></i>
                        Format Kolom yang Dibutuhkan
                    </h3>
                    <div class="columns-grid">
                        <div class="column-item">
                            <i class="fas fa-tag"></i>
                            nama_item
                        </div>
                        <div class="column-item">
                            <i class="fas fa-cogs"></i>
                            spesifikasi
                        </div>
                        <div class="column-item">
                            <i class="fas fa-map-marker-alt"></i>
                            asal_usul
                        </div>
                        <div class="column-item">
                            <i class="fas fa-calendar"></i>
                            tahun_pengadaan
                        </div>
                        <div class="column-item">
                            <i class="fas fa-dollar-sign"></i>
                            harga
                        </div>
                        <div class="column-item">
                            <i class="fas fa-sort-numeric-up"></i>
                            jumlah
                        </div>
                        <div class="column-item">
                            <i class="fas fa-check-circle"></i>
                            status
                        </div>
                        <div class="column-item">
                            <i class="fas fa-comment"></i>
                            keterangan
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary submit-btn">
                    <i class="fas fa-upload"></i>
                    Import Data Sekarang
                </button>
            </form>
            
            <div class="action-buttons">
                <a href="template/aset_template.xlsx" class="btn btn-success">
                    <i class="fas fa-download"></i>
                    Download Template Excel
                </a>
            </div>
        </div>
        
        <div class="back-section">
            <a href="aset_list.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Daftar Aset
            </a>
        </div>
    </div>

    <script>
        // Update file name when selected
        document.getElementById('file_excel').addEventListener('change', function(e) {
            const fileNameElement = document.getElementById('file-name');
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Belum ada file dipilih';
            
            if (e.target.files[0]) {
                fileNameElement.innerHTML = `<i class="fas fa-file-check" style="color: #10b981; margin-right: 0.5rem;"></i>${fileName}`;
                fileNameElement.style.background = 'rgba(16, 185, 129, 0.1)';
                fileNameElement.style.color = '#065f46';
                fileNameElement.style.border = '1px solid rgba(16, 185, 129, 0.3)';
            } else {
                fileNameElement.textContent = fileName;
                fileNameElement.style.background = 'rgba(102, 126, 234, 0.1)';
                fileNameElement.style.color = '#4a5568';
                fileNameElement.style.border = 'none';
            }
        });

        // Add loading state to submit button
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengimpor Data...';
            submitBtn.disabled = true;
        });

        // Drag and drop functionality
        const uploadSection = document.querySelector('.upload-section');
        const fileInput = document.getElementById('file_excel');

        uploadSection.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadSection.style.borderColor = '#667eea';
            uploadSection.style.background = 'linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%)';
        });

        uploadSection.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadSection.style.borderColor = '#cbd5e0';
            uploadSection.style.background = 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)';
        });

        uploadSection.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadSection.style.borderColor = '#cbd5e0';
            uploadSection.style.background = 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>