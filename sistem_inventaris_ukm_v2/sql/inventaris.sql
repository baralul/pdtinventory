
CREATE DATABASE IF NOT EXISTS inventaris;
USE inventaris;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL
);

CREATE TABLE barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_barang VARCHAR(100),
    jumlah INT
);

CREATE TABLE peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_barang INT,
    jumlah INT,
    tanggal_pinjam DATE,
    FOREIGN KEY (id_barang) REFERENCES barang(id)
);

DELIMITER $$
CREATE TRIGGER after_peminjaman_insert
AFTER INSERT ON peminjaman
FOR EACH ROW
BEGIN
    UPDATE barang SET jumlah = jumlah - NEW.jumlah WHERE id = NEW.id_barang;
END$$
DELIMITER ;
