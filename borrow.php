<?php
require_once 'includes/auth.php';
require_once 'includes/inventory.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$inventory = new Inventory();
$items = $inventory->getAllItems();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_barang = $_POST['id_barang'];
    $jumlah = $_POST['jumlah'];
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    
    try {
        if ($inventory->borrowItem($id_barang, $jumlah, $tanggal_pinjam)) {
            $success = "Item borrowed successfully!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Item - Inventory Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Borrow Item</h1>
            <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-700 px-3 py-1 rounded">Back to Dashboard</a>
        </div>
    </nav>

    <div class="container mx-auto mt-6 px-4 max-w-md">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Borrow Item</h2>
            
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="id_barang" class="block text-gray-700 text-sm font-bold mb-2">Select Item</label>
                    <select id="id_barang" name="id_barang" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Choose an item...</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?php echo $item['id']; ?>">
                                <?php echo htmlspecialchars($item['nama_barang']); ?> (Stock: <?php echo $item['jumlah']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="jumlah" class="block text-gray-700 text-sm font-bold mb-2">Quantity</label>
                    <input type="number" id="jumlah" name="jumlah" min="1" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-6">
                    <label for="tanggal_pinjam" class="block text-gray-700 text-sm font-bold mb-2">Borrow Date</label>
                    <input type="date" id="tanggal_pinjam" name="tanggal_pinjam" value="<?php echo date('Y-m-d'); ?>" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <button type="submit" 
                        class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Borrow Item
                </button>
            </form>
        </div>
    </div>
</body>
</html>