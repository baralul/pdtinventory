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
$borrowedItems = $inventory->getBorrowedItems();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Inventory Management</h1>
            <div class="flex items-center space-x-4">
                <span>Welcome, <?php echo $_SESSION['username']; ?> (<?php echo $_SESSION['role']; ?>)</span>
                <a href="logout.php" class="bg-red-500 hover:bg-red-700 px-3 py-1 rounded">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-6 px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Current Inventory -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-bold mb-4">Current Inventory</h2>
                <div class="space-y-2">
                    <?php foreach ($items as $item): ?>
                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                            <span><?php echo htmlspecialchars($item['nama_barang']); ?></span>
                            <span class="font-bold <?php echo $item['jumlah'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $item['jumlah']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($auth->isAdmin()): ?>
                    <a href="add_item.php" class="mt-4 inline-block bg-green-500 hover:bg-green-700 text-white px-4 py-2 rounded">
                        Add New Item
                    </a>
                <?php endif; ?>
            </div>

            <!-- Borrow Item -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-bold mb-4">Borrow Item</h2>
                <a href="borrow.php" class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded inline-block">
                    Borrow Items
                </a>
            </div>

            <!-- Return Item -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-bold mb-4">Return Item</h2>
                <a href="return.php" class="bg-orange-500 hover:bg-orange-700 text-white px-4 py-2 rounded inline-block">
                    Return Items
                </a>
            </div>
        </div>

        <!-- Currently Borrowed Items -->
        <div class="mt-6 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold mb-4">Currently Borrowed Items</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left">Item</th>
                            <th class="px-4 py-2 text-left">Quantity</th>
                            <th class="px-4 py-2 text-left">Borrow Date</th>
                            <th class="px-4 py-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowedItems as $item): ?>
                            <tr class="border-b">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                                <td class="px-4 py-2"><?php echo $item['jumlah']; ?></td>
                                <td class="px-4 py-2"><?php echo $item['tanggal_pinjam']; ?></td>
                                <td class="px-4 py-2">
                                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($auth->isAdmin()): ?>
            <!-- Backup Logs -->
            <div class="mt-6 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-bold mb-4">Recent Backup Logs</h2>
                <a href="logs.php" class="bg-gray-500 hover:bg-gray-700 text-white px-4 py-2 rounded inline-block">
                    View All Logs
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>