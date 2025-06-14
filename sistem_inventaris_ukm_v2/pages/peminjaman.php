<?php
include '../includes/koneksi.php';
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

$peminjaman = $conn->query("SELECT * FROM peminjaman");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Kelola Peminjaman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">Kelola Peminjaman</h2>

    <!-- Tombol tambah -->
    <a href="tambah_peminjaman.php" class="btn btn-success mb-3">+ Tambah Peminjaman</a>
    <a href="../index.php" class="btn btn-secondary">Kembali ke Dashboard</a>


    <table class="table table-bordered">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th>Peminjam</th>
                <th>Tanggal Pinjam</th>
                <th>Tanggal Kembali</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($peminjaman->num_rows > 0): ?>
                <?php $no = 1; ?>
                <?php while($row = $peminjaman->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td><?= htmlspecialchars($row['peminjam']) ?></td>
                        <td><?= $row['tanggal_pinjam'] ?></td>
                        <td><?= $row['tanggal_kembali'] ?></td>
                        <td>
                            <!-- Placeholder untuk aksi nanti -->
                            <a href="#" class="btn btn-sm btn-warning disabled">Edit</a>
                            <a href="#" class="btn btn-sm btn-danger disabled">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">Belum ada data peminjaman.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
