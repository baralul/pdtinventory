<?php
include '../includes/koneksi.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama_barang'];
    $jumlah = $_POST['jumlah'];
    $conn->query("INSERT INTO barang (nama_barang, jumlah) VALUES ('$nama', $jumlah)");
    header("Location: barang.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head><title>Tambah Barang</title></head>
<body>
    <h2>Tambah Barang</h2>
    <form method="POST">
        Nama Barang: <input type="text" name="nama_barang" required><br>
        Jumlah: <input type="number" name="jumlah" required><br>
        <button type="submit">Simpan</button>
    </form>
</body>
</html>
