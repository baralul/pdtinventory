<?php
include '../includes/koneksi.php';
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_barang = $_POST['nama_barang'];
    $peminjam = $_POST['peminjam'];
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $tanggal_kembali = $_POST['tanggal_kembali'];

    $sql = "INSERT INTO peminjaman (nama_barang, peminjam, tanggal_pinjam, tanggal_kembali) 
            VALUES ('$nama_barang', '$peminjam', '$tanggal_pinjam', '$tanggal_kembali')";
    
    if ($conn->query($sql)) {
        header("Location: peminjaman.php");
        exit;
    } else {
        echo "Gagal menambahkan data: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Tambah Peminjaman</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">Tambah Peminjaman</h2>
    <form method="POST">
        <div class="mb-3">
            <label>Nama Barang</label>
            <input type="text" name="nama_barang" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Nama Peminjam</label>
            <input type="text" name="peminjam" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Tanggal Pinjam</label>
            <input type="date" name="tanggal_pinjam" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Tanggal Kembali</label>
            <input type="date" name="tanggal_kembali" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="peminjaman.php" class="btn btn-secondary">Batal</a>
        <a href="../index.php" class="btn btn-secondary">Kembali ke Dashboard</a>
    </form>
</div>
</body>
</html>
