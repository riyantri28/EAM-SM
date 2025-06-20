<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Aset</title>
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
            letter-spacing: 1px;
        }
        .navbar .user {
            font-size: 1em;
        }
        .container {
            background: #fff;
            max-width: 420px;
            margin: 40px auto 0 auto;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.10);
            padding: 36px 32px 28px 32px;
        }
        h2 {
            color: #3b5998;
            margin-top: 0;
            margin-bottom: 24px;
            font-weight: 600;
            text-align: center;
        }
        form label {
            display: block;
            margin-bottom: 6px;
            color: #444;
            font-weight: 500;
        }
        form input[type="text"],
        form input[type="number"],
        form input[type="date"] {
            width: 100%;
            padding: 9px 12px;
            margin-bottom: 18px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 1em;
            background: #f9fafb;
            transition: border 0.2s;
        }
        form input:focus {
            border: 1.5px solid #3b5998;
            outline: none;
            background: #fff;
        }
        button[type="submit"] {
            width: 100%;
            background: #3b5998;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 12px 0;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.2s;
        }
        button[type="submit"]:hover {
            background: #1abc9c;
        }
        .back-link {
            display: block;
            margin: 22px auto 0 auto;
            text-align: center;
            color: #3b5998;
            text-decoration: none;
            font-weight: 500;
            font-size: 1em;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: #1abc9c;
        }
        @media (max-width: 600px) {
            .container {
                max-width: 98vw;
                padding: 18px 6px 14px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">EAM UNY</div>
        <div class="user">👤 <?php echo htmlspecialchars($_SESSION['user']['username']); ?></div>
    </div>
    <div class="container">
        <h2>Form Tambah Aset</h2>
        <form method="post" action="../src/aset_proses_tambah.php">
            <label>Nama:</label>
            <input type="text" name="nama" required>
            <label>Jenis:</label>
            <input type="text" name="jenis">
            <label>Produsen:</label>
            <input type="text" name="produsen">
            <label>Harga:</label>
            <input type="number" step="0.01" name="harga">
            <label>Tanggal Beli:</label>
            <input type="date" name="tanggal_beli">
            <label>Garansi (bulan):</label>
            <input type="number" name="garansi">
            <label>Lokasi:</label>
            <input type="text" name="lokasi">
            <button type="submit">Simpan</button>
        </form>
        <a href="aset_list.php" class="back-link">← Kembali ke Daftar Aset</a>
    </div>
</body>
</html>