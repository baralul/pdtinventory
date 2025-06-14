<?php
include '../includes/koneksi.php';
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}
$result = $conn->query("SELECT * FROM barang");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Barang</title>
</head>
<body>
    <h2>Daftar Barang</h2>
    <a href="../index.php">Kembali</a> | <a href="tambah_barang.php">Tambah Barang</a>
    <table border="1" cellpadding="10">
        <tr><th>ID</th><th>Nama Barang</th><th>Jumlah</th><th>Aksi</th></tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['nama_barang'] ?></td>
            <td><?= $row['jumlah'] ?></td>
            <td><a href="hapus_barang.php?id=<?= $row['id'] ?>">Hapus</a></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
