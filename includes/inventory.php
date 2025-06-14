<?php
require_once 'config/database.php';

class Inventory {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getAllItems() {
        $query = "SELECT * FROM barang ORDER BY nama_barang";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function addItem($nama_barang, $jumlah) {
        $query = "INSERT INTO barang (nama_barang, jumlah) VALUES (:nama_barang, :jumlah)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nama_barang', $nama_barang);
        $stmt->bindParam(':jumlah', $jumlah);
        return $stmt->execute();
    }
    
    public function borrowItem($id_barang, $jumlah, $tanggal_pinjam) {
        $query = "CALL pinjam_barang(:id_barang, :jumlah, :tanggal_pinjam)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_barang', $id_barang);
        $stmt->bindParam(':jumlah', $jumlah);
        $stmt->bindParam(':tanggal_pinjam', $tanggal_pinjam);
        return $stmt->execute();
    }
    
    public function returnItem($id_peminjaman, $tanggal_kembali) {
        $query = "CALL kembalikan_barang(:id_peminjaman, :tanggal_kembali)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_peminjaman', $id_peminjaman);
        $stmt->bindParam(':tanggal_kembali', $tanggal_kembali);
        return $stmt->execute();
    }
    
    public function getBorrowedItems() {
        $query = "SELECT p.id, b.nama_barang, p.jumlah, p.tanggal_pinjam, p.status 
                  FROM peminjaman p 
                  JOIN barang b ON p.id_barang = b.id 
                  WHERE p.status = 'dipinjam' 
                  ORDER BY p.tanggal_pinjam DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getBackupLogs() {
        $query = "SELECT * FROM backup_log ORDER BY backup_date DESC LIMIT 20";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>