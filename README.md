# 📦 pdtinventory (Inventory Management System)

Proyek ini merupakan sistem manajemen inventaris yang dibangun menggunakan PHP dan MySQL. Tujuannya adalah untuk mengelola peminjaman dan pengembalian barang secara aman dan konsisten, dengan memanfaatkan stored procedure, trigger, transaction, dan stored function. Sistem ini juga dilengkapi mekanisme backup otomatis untuk menjaga keamanan data jika terjadi hal yang tidak diinginkan.

![Dashboard](assets/img/dashboard.png)

## 📌 Detail Konsep

### ⚠️ Disclaimer

Peran **stored procedure**, **trigger**, **transaction**, dan **stored function** dalam proyek ini dirancang khusus untuk kebutuhan sistem **manajemen inventaris**. Penerapannya bisa berbeda pada sistem lain, tergantung arsitektur dan kebutuhan masing-masing sistem.

### 🧠 Stored Procedure 
Stored procedure bertindak seperti SOP internal yang menetapkan alur eksekusi berbagai operasi penting di sistem inventaris. Procedure ini disimpan langsung di lapisan database, sehingga dapat menjamin konsistensi, efisiensi, dan keamanan eksekusi, terutama dalam sistem terdistribusi atau multi-user.

![Procedure](assets/img/procedure.png)

Beberapa procedure penting yang digunakan:

`includes/inventory.php`
* `pinjam_barang(p_id_barang, p_jumlah, p_tanggal)`: Memvalidasi stok tersedia, mengurangi jumlah barang, dan mencatat transaksi peminjaman.
  ```php
  // Call the pinjam_barang stored procedure
  $query = "CALL pinjam_barang(:id_barang, :jumlah, :tanggal_pinjam)";
  $stmt = $this->conn->prepare($query);
  $stmt->bindParam(':id_barang', $id_barang);
  $stmt->bindParam(':jumlah', $jumlah);
  $stmt->bindParam(':tanggal_pinjam', $tanggal_pinjam);
  return $stmt->execute();
  ```

* `kembalikan_barang(p_id_peminjaman, p_tanggal_kembali)`: Memvalidasi data peminjaman, mengembalikan stok barang, dan mengupdate status peminjaman.
  ```php
  // Call the kembalikan_barang stored procedure
  $query = "CALL kembalikan_barang(:id_peminjaman, :tanggal_kembali)";
  $stmt = $this->conn->prepare($query);
  $stmt->bindParam(':id_peminjaman', $id_peminjaman);
  $stmt->bindParam(':tanggal_kembali', $tanggal_kembali);
  return $stmt->execute();
  ```

* `backup_database()`: Mencatat aktivitas backup ke dalam log sistem untuk audit trail.
  ```php
  // Call the backup_database stored procedure
  $query = "CALL backup_database()";
  $stmt = $this->conn->prepare($query);
  return $stmt->execute();
  ```

Dengan menyimpan proses-proses ini di sisi database, sistem menjaga integritas data di level paling dasar, terlepas dari cara aplikasi mengaksesnya.

### 🚨 Trigger
Trigger `validate_before_borrow` berfungsi sebagai sistem pengaman otomatis yang aktif sebelum data masuk ke dalam tabel peminjaman. Seperti palang pintu yang hanya terbuka jika syarat tertentu terpenuhi, trigger mencegah input data yang tidak valid atau berisiko merusak integritas sistem.

![Trigger](assets/img/trigger.png)

Trigger `validate_before_borrow` otomatis aktif pada procedure berikut:
* `pinjam_barang`
  ```sql
  INSERT INTO peminjaman (id_barang, jumlah, tanggal_pinjam) 
  VALUES (p_id_barang, p_jumlah, p_tanggal);
  ```

Trigger `after_peminjaman_insert` otomatis mengurangi stok setelah peminjaman berhasil:
* `pinjam_barang`
  ```sql
  UPDATE barang SET jumlah = jumlah - NEW.jumlah 
  WHERE id = NEW.id_barang;
  ```

Beberapa peran trigger di sistem ini:
* Menolak peminjaman jika stok tidak mencukupi.
* Menolak peminjaman dengan jumlah tidak logis (≤ 0).
* Otomatis mengurangi stok barang setelah peminjaman berhasil.
* Mencegah inkonsistensi data antara tabel barang dan peminjaman.

Dengan adanya trigger di lapisan database, validasi tetap dijalankan secara otomatis, bahkan jika ada celah atau kelalaian dari sisi aplikasi. Ini selaras dengan prinsip reliabilitas pada sistem terdistribusi.

### 🔄 Transaction (Transaksi)
Dalam sistem inventaris, sebuah transaksi seperti peminjaman atau pengembalian barang tidak dianggap berhasil jika hanya sebagian prosesnya yang selesai. Semua langkah harus dijalankan hingga tuntas — jika salah satu gagal, seluruh proses dibatalkan. Prinsip ini diwujudkan melalui penggunaan `START TRANSACTION` dan `COMMIT` di stored procedure.

Contohnya, pada proses peminjaman, sistem akan memulai transaksi, memvalidasi stok, mencatat peminjaman, mengurangi stok, lalu meng-commit perubahan jika berhasil. Namun, jika ditemukan masalah — seperti stok tidak mencukupi atau barang tidak ditemukan — maka seluruh proses dibatalkan menggunakan `ROLLBACK`. Hal ini mencegah perubahan data yang parsial, seperti stok yang berkurang padahal peminjaman tidak sah.

`database.sql`
* Implementasi transaction untuk procedure `pinjam_barang`
  ```sql
  CREATE PROCEDURE pinjam_barang(IN p_id_barang INT, IN p_jumlah INT, IN p_tanggal DATE)
  BEGIN
      START TRANSACTION;
      INSERT INTO peminjaman (id_barang, jumlah, tanggal_pinjam) 
      VALUES (p_id_barang, p_jumlah, p_tanggal);
      COMMIT;
  END
  ```

* Implementasi transaction untuk procedure `kembalikan_barang`
  ```sql
  CREATE PROCEDURE kembalikan_barang(IN p_id_peminjaman INT, IN p_tanggal_kembali DATE)
  BEGIN
      DECLARE v_id_barang INT; 
      DECLARE v_jumlah INT;
      START TRANSACTION;
      
      SELECT id_barang, jumlah INTO v_id_barang, v_jumlah 
      FROM peminjaman WHERE id = p_id_peminjaman AND status = 'dipinjam';
      
      IF v_id_barang IS NULL THEN 
          SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data tidak ditemukan'; 
      END IF;
      
      UPDATE peminjaman SET status = 'dikembalikan', tanggal_kembali = p_tanggal_kembali 
      WHERE id = p_id_peminjaman;
      
      UPDATE barang SET jumlah = jumlah + v_jumlah WHERE id = v_id_barang;
      COMMIT;
  END
  ```

Demikian pula saat admin menambahkan barang baru, sistem tidak hanya menyimpan data barang, tetapi juga mencatat log aktivitas sekaligus. Proses ini dijalankan dalam satu transaksi agar semua langkah saling bergantung dan terjamin konsistensinya.

### 📺 Stored Function 
Stored function digunakan untuk mengambil informasi tanpa mengubah data. Seperti layar monitor: hanya menampilkan data, tidak mengubah apapun.

Contohnya, function `get_stock(item_id)` mengembalikan jumlah stok terkini dari sebuah barang, dan function `check_stock(item_id, needed)` memvalidasi apakah stok mencukupi untuk peminjaman.

Function ini dipanggil baik dari aplikasi maupun dari procedure yang ada di database. Dengan begitu, logika pembacaan stok tetap terpusat dan konsisten, tanpa perlu duplikasi kode atau risiko ketidaksesuaian antara sistem aplikasi dan database.

![Function](assets/img/function.png)

* Aplikasi

  `dashboard.php`
  ```php
  $items = $inventory->getAllItems();
  foreach ($items as $item) {
      echo $item['jumlah']; // Display current stock
  }
  ```
  ```html
  <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
      <span><?php echo htmlspecialchars($item['nama_barang']); ?></span>
      <span class="font-bold <?php echo $item['jumlah'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
          <?php echo $item['jumlah']; ?>
      </span>
  </div>
  ```

  `includes/inventory.php`
  ```php
  // Using get_stock function indirectly through getAllItems
  public function getAllItems() {
      $query = "SELECT *, get_stock(id) as current_stock FROM barang ORDER BY nama_barang";
      $stmt = $this->conn->prepare($query);
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  ```

* Trigger `validate_before_borrow`
  ```sql
  CREATE TRIGGER validate_before_borrow BEFORE INSERT ON peminjaman
  FOR EACH ROW BEGIN
      IF NEW.jumlah <= 0 OR NOT check_stock(NEW.id_barang, NEW.jumlah)
      THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok tidak mencukupi atau jumlah tidak valid';
      END IF;
  END
  ```

Penggunaan function seperti ini mencerminkan praktik pemisahan logika bisnis di database layer, yang relevan dalam konteks Pemrosesan Data Terdistribusi — di mana konsistensi dan reliabilitas antar node atau proses sangat krusial.

### 🔄 Backup Otomatis
Untuk menjaga ketersediaan dan keamanan data, sistem dilengkapi fitur backup otomatis menggunakan `mysqldump` dan task scheduler. Backup dilakukan secara berkala dan disimpan dengan nama file yang mencakup timestamp, sehingga mudah ditelusuri. Semua file disimpan di direktori `storage/backups`.

`backup.php`
```php
<?php
require_once 'config/database.php';

class BackupManager {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function createBackup() {
        $date = date('Y-m-d_H-i-s');
        $backupDir = __DIR__ . '/storage/backups';
        
        // Create backup directory if it doesn't exist
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . "/inventaris_backup_$date.sql";
        
        // MySQL dump command
        $command = "mysqldump -u root inventaris > \"$backupFile\"";
        exec($command, $output, $returnVar);
        
        if ($returnVar === 0) {
            // Log backup success to database
            $this->logBackup($date, 'Backup berhasil');
            return $backupFile;
        } else {
            // Log backup failure
            $this->logBackup($date, 'Backup gagal');
            throw new Exception('Backup failed');
        }
    }
    
    private function logBackup($date, $status) {
        try {
            $query = "CALL backup_database()";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to log backup: " . $e->getMessage());
        }
    }
    
    public function getBackupLogs() {
        $query = "SELECT * FROM backup_log ORDER BY backup_date DESC LIMIT 20";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Auto-backup execution
$backupManager = new BackupManager();
try {
    $backupFile = $backupManager->createBackup();
    echo "Backup created successfully: " . basename($backupFile);
} catch (Exception $e) {
    echo "Backup failed: " . $e->getMessage();
}
?>
```

### 📅 Scheduled Events
Sistem menggunakan MySQL Event Scheduler untuk menjalankan backup otomatis dan pembersihan log secara berkala:

`database.sql`
```sql
-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- Daily backup event
CREATE EVENT daily_backup_event
ON SCHEDULE EVERY 1 DAY STARTS CURRENT_TIMESTAMP
DO BEGIN 
    CALL backup_database(); 
END;

-- Weekly cleanup event
CREATE EVENT weekly_cleanup
ON SCHEDULE EVERY 1 WEEK STARTS CURRENT_TIMESTAMP
DO BEGIN
    DELETE FROM backup_log WHERE backup_date < DATE_SUB(NOW(), INTERVAL 30 DAY);
END;
```

## 🖥️ Antarmuka Pengguna

### Dashboard Utama
Dashboard menampilkan ringkasan inventaris, item yang sedang dipinjam, dan akses cepat ke fungsi utama sistem.

![Dashboard](assets/img/dashboard.png)

### Form Peminjaman Barang
Interface yang user-friendly untuk meminjam barang dengan validasi real-time stok tersedia.

![Borrow Form](assets/img/borrow_form.png)

`borrow.php`
```html
<form method="POST" action="">
    <div class="mb-4">
        <label for="id_barang" class="block text-gray-700 text-sm font-bold mb-2">Select Item</label>
        <select id="id_barang" name="id_barang" required 
                class="w-full px-3 py-2 border border-gray-300 rounded-md">
            <option value="">Choose an item...</option>
            <?php foreach ($items as $item): ?>
                <option value="<?php echo $item['id']; ?>">
                    <?php echo htmlspecialchars($item['nama_barang']); ?> 
                    (Stock: <?php echo $item['jumlah']; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <!-- Additional form fields -->
</form>
```

### Form Pengembalian Barang
Interface untuk mengembalikan barang yang dipinjam dengan tracking otomatis.

![Return Form](assets/img/return_form.png)

### Manajemen Pengguna (Admin Only)
Panel administrasi untuk mengelola akun pengguna dan hak akses.

![User Management](assets/img/user_management.png)

## 🔐 Sistem Keamanan

### Autentikasi dan Otorisasi
`includes/auth.php`
```php
class Auth {
    public function login($username, $password) {
        $query = "SELECT id, username, password, role FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                return true;
            }
        }
        return false;
    }
    
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}
```

### Password Hashing
Semua password disimpan menggunakan PHP's `password_hash()` dengan algoritma bcrypt:

```php
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
```

## 📊 Monitoring dan Logging

### Backup Logs
Sistem mencatat semua aktivitas backup untuk audit dan monitoring:

`logs.php`
```php
$inventory = new Inventory();
$logs = $inventory->getBackupLogs();

foreach ($logs as $log) {
    echo "<tr>";
    echo "<td>" . $log['backup_date'] . "</td>";
    echo "<td>" . $log['status'] . "</td>";
    echo "</tr>";
}
```

### Activity Tracking
Setiap transaksi peminjaman dan pengembalian tercatat dengan timestamp dan detail lengkap.

![Activity Logs](assets/img/activity_logs.png)

## 🧩 Relevansi Proyek dengan Pemrosesan Data Terdistribusi

Sistem ini dirancang dengan memperhatikan prinsip-prinsip dasar pemrosesan data terdistribusi:

### **Konsistensi (Consistency)**
* Semua transaksi peminjaman dan pengembalian dieksekusi dengan stored procedure yang terpusat di database
* Trigger memastikan validasi data konsisten di semua level akses
* Function `get_stock()` dan `check_stock()` memberikan hasil yang konsisten dari berbagai titik akses

### **Reliabilitas (Reliability)**
* Transaction dengan `START TRANSACTION` dan `COMMIT/ROLLBACK` memastikan atomicity
* Trigger `validate_before_borrow` mencegah data corruption
* Backup otomatis dengan event scheduler menjamin data recovery
* Error handling yang komprehensif di setiap layer aplikasi

### **Integritas (Integrity)**
* Foreign key constraints menjaga referential integrity
* Stored procedure mencegah direct table manipulation
* Trigger validation memastikan business rules selalu diterapkan
* Password hashing dan session management untuk security integrity

### **Availability (Ketersediaan)**
* Backup otomatis harian dengan retention policy
* Event scheduler untuk maintenance otomatis
* Graceful error handling untuk user experience yang konsisten
* Role-based access control untuk operational continuity

### **Partition Tolerance**
* Database-centric logic memungkinkan multiple application instances
* Stateless session management memungkinkan load balancing
* Stored procedures dapat diakses dari berbagai client applications
* Centralized validation rules di database layer
