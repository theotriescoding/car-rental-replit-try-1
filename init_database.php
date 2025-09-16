<?php
require 'db_connect.php';

// Create tables
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'customer'
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS cars (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    category VARCHAR(50) NOT NULL,
    priceperday DECIMAL(8,2) NOT NULL,
    description TEXT,
    available BOOLEAN DEFAULT 1
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    carid INTEGER NOT NULL,
    userid INTEGER NOT NULL,
    startdate DATE NOT NULL,
    enddate DATE NOT NULL,
    totalprice DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    FOREIGN KEY (carid) REFERENCES cars(id),
    FOREIGN KEY (userid) REFERENCES users(id)
);
");

// Insert sample data only if tables are empty
$stmt = $pdo->query("SELECT COUNT(*) FROM cars");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("
    INSERT INTO cars (brand, model, category, priceperday, description) VALUES
    ('Toyota', 'Corolla', 'Compact', 25.00, 'Οικονομικό αυτοκίνητο για καθημερινή χρήση'),
    ('BMW', 'X3', 'SUV', 65.00, 'Πολυτελές SUV για άνετα ταξίδια'),
    ('Mercedes', 'C-Class', 'Luxury', 85.00, 'Κομψό sedan για επαγγελματικές συναντήσεις');
    ");
}

// Insert admin user only if users table is empty
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
if ($stmt->fetchColumn() == 0) {
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Administrator', 'admin@carrental.com', $hashedPassword, 'admin']);
}

echo "Database initialized successfully!\n";
?>
