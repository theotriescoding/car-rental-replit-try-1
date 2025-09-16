<?php
require 'db_connect.php';

// Create tables
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer'
)");

$pdo->exec("
CREATE TABLE IF NOT EXISTS cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    category VARCHAR(50) NOT NULL,
    priceperday DECIMAL(8,2) NOT NULL,
    description TEXT,
    available BOOLEAN DEFAULT TRUE
)");

$pdo->exec("
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carid INT NOT NULL,
    userid INT NOT NULL,
    startdate DATE NOT NULL,
    enddate DATE NOT NULL,
    totalprice DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    FOREIGN KEY (carid) REFERENCES cars(id),
    FOREIGN KEY (userid) REFERENCES users(id)
)");

// Insert sample data only if tables are empty
$stmt = $pdo->query("SELECT COUNT(*) FROM cars");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("
    INSERT INTO cars (brand, model, category, priceperday, description) VALUES
    ('Toyota', 'Corolla', 'Compact', 25.00, 'Οικονομικό αυτοκίνητο για καθημερινή χρήση'),
    ('BMW', 'X3', 'SUV', 65.00, 'Πολυτελές SUV για άνετα ταξίδια'),
    ('Mercedes', 'C-Class', 'Luxury', 85.00, 'Κομψό sedan για επαγγελματικές συναντήσεις')
    ");
}

// Insert admin user only if users table is empty
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
if ($stmt->fetchColumn() == 0) {
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Administrator', 'admin@carrental.com', $hashedPassword, 'admin']);
}

echo "MySQL database initialized successfully!\n";
?>
