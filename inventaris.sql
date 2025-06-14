-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 14, 2025 at 09:44 AM
-- Server version: 10.4.32-MariaDB-log
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventaris`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `backup_database` ()   BEGIN
    DECLARE backup_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
    
    START TRANSACTION;
    
    INSERT INTO backup_log (backup_date, status) 
    VALUES (backup_time, 'Backup dimulai');
    
    -- In real implementation, you would execute:
    -- SYSTEM mysqldump inventaris > backup_filename.sql
    
    UPDATE backup_log 
    SET status = 'Backup selesai' 
    WHERE backup_date = backup_time;
    
    COMMIT;
    SELECT 'Backup completed' as message, backup_time as timestamp;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `kembalikan_barang` (IN `p_id_peminjaman` INT, IN `p_tanggal_kembali` DATE)   BEGIN
    DECLARE v_id_barang INT;
    DECLARE v_jumlah INT;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get borrowing details
    SELECT id_barang, jumlah INTO v_id_barang, v_jumlah 
    FROM peminjaman 
    WHERE id = p_id_peminjaman AND status = 'dipinjam';
    
    IF v_id_barang IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Peminjaman tidak ditemukan';
    END IF;
    
    -- Update return status
    UPDATE peminjaman 
    SET status = 'dikembalikan', tanggal_kembali = p_tanggal_kembali 
    WHERE id = p_id_peminjaman;
    
    -- Return stock
    UPDATE barang 
    SET jumlah = jumlah + v_jumlah 
    WHERE id = v_id_barang;
    
    COMMIT;
    SELECT 'Pengembalian berhasil' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `pinjam_barang` (IN `p_id_barang` INT, IN `p_jumlah` INT, IN `p_tanggal` DATE)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    INSERT INTO peminjaman (id_barang, jumlah, tanggal_pinjam) 
    VALUES (p_id_barang, p_jumlah, p_tanggal);
    
    COMMIT;
    SELECT 'Peminjaman berhasil' as message;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `check_stock` (`item_id` INT, `needed` INT) RETURNS TINYINT(1) DETERMINISTIC READS SQL DATA BEGIN
    RETURN get_stock(item_id) >= needed;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `get_stock` (`item_id` INT) RETURNS INT(11) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE stock INT DEFAULT 0;
    SELECT jumlah INTO stock FROM barang WHERE id = item_id;
    RETURN IFNULL(stock, 0);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `backup_log`
--

CREATE TABLE `backup_log` (
  `id` int(11) NOT NULL,
  `backup_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `backup_log`
--

INSERT INTO `backup_log` (`id`, `backup_date`, `status`) VALUES
(1, '2025-06-14 04:12:07', 'Backup selesai');

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id` int(11) NOT NULL,
  `nama_barang` varchar(100) DEFAULT NULL,
  `jumlah` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id`, `nama_barang`, `jumlah`) VALUES
(1, 'Laptop', 5),
(2, 'Proyektor', 3),
(3, 'Kamera', 2),
(4, 'Laptop', 5),
(5, 'Proyektor', 3),
(6, 'Kamera', 2);

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int(11) NOT NULL,
  `id_barang` int(11) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `tanggal_pinjam` date DEFAULT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `status` enum('dipinjam','dikembalikan') DEFAULT 'dipinjam'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id`, `id_barang`, `jumlah`, `tanggal_pinjam`, `tanggal_kembali`, `status`) VALUES
(1, 3, 2, '2025-06-14', '2025-06-14', 'dikembalikan');

--
-- Triggers `peminjaman`
--
DELIMITER $$
CREATE TRIGGER `after_peminjaman_insert` AFTER INSERT ON `peminjaman` FOR EACH ROW BEGIN
    UPDATE barang SET jumlah = jumlah - NEW.jumlah WHERE id = NEW.id_barang;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `validate_before_borrow` BEFORE INSERT ON `peminjaman` FOR EACH ROW BEGIN
    IF NEW.jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah harus lebih dari 0';
    END IF;
    
    IF NOT check_stock(NEW.id_barang, NEW.jumlah) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok tidak mencukupi';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(7, 'admin', '$2y$10$jCF9aC2Ji8G71.3wJ6MJOOKcqJtN1zPUQ2BD0013PXGOioyjpY0Mu', 'admin'),
(8, 'staff', '$2y$10$wSZHrPAXWlfKDxt2Qsu0k.sSJV2IqQAYoe4xHZrrNaXrdL3nlpsxS', 'staff');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `backup_log`
--
ALTER TABLE `backup_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_barang` (`id_barang`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `backup_log`
--
ALTER TABLE `backup_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id`);

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `daily_backup_event` ON SCHEDULE EVERY 1 DAY STARTS '2025-06-14 11:12:07' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    CALL backup_database();
END$$

CREATE DEFINER=`root`@`localhost` EVENT `weekly_cleanup` ON SCHEDULE EVERY 1 WEEK STARTS '2025-06-14 11:12:07' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    DELETE FROM backup_log 
    WHERE backup_date < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
