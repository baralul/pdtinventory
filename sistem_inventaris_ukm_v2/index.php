<?php
include 'includes/koneksi.php';
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}
$count = $conn->query("SELECT COUNT(*) as total FROM barang")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Dashboard Inventaris UKM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <h2 class="mb-4">Dashboard Inventaris UKM</h2>
    <div class="mb-3">
      <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Total Barang: <?= $count ?></h5>
        <a href="pages/barang.php" class="btn btn-primary">Kelola Barang</a>
        <a href="pages/user.php" class="btn btn-secondary mt-2">Kelola User</a>
        <a href="pages/peminjaman.php" class="btn btn-success mt-2">Kelola Peminjaman</a>
      </div>
    </div>
  </div>
</body>
</html>
