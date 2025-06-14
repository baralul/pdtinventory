<?php
include '../includes/koneksi.php';
$id = $_GET['id'];
$conn->query("DELETE FROM barang WHERE id=$id");
header("Location: barang.php");
?>
