<?php
require_once 'config/database.php';

// This script will create the initial admin and staff users with proper password hashing
// Run this once to set up your initial users

$database = new Database();
$conn = $database->getConnection();

// Clear existing users (optional - remove this if you want to keep existing users)
$query = "DELETE FROM users";
$stmt = $conn->prepare($query);
$stmt->execute();

// Create admin user
$admin_username = 'admin';
$admin_password = 'admin123';
$admin_role = 'admin';
$admin_hashed = password_hash($admin_password, PASSWORD_DEFAULT);

$query = "INSERT INTO users (username, password, role) VALUES (:username, :password, :role)";
$stmt = $conn->prepare($query);
$stmt->bindParam(':username', $admin_username);
$stmt->bindParam(':password', $admin_hashed);
$stmt->bindParam(':role', $admin_role);

if ($stmt->execute()) {
    echo "Admin user created successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br><br>";
} else {
    echo "Error creating admin user<br>";
}

// Create staff user
$staff_username = 'staff';
$staff_password = 'staff123';
$staff_role = 'staff';
$staff_hashed = password_hash($staff_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare($query);
$stmt->bindParam(':username', $staff_username);
$stmt->bindParam(':password', $staff_hashed);
$stmt->bindParam(':role', $staff_role);

if ($stmt->execute()) {
    echo "Staff user created successfully!<br>";
    echo "Username: staff<br>";
    echo "Password: staff123<br><br>";
} else {
    echo "Error creating staff user<br>";
}

echo "<br><a href='index.php'>Go to Login Page</a>";
?>