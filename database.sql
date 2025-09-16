CREATE DATABASE IF NOT EXISTS car_rental_db;
USE car_rental_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer'
);

CREATE TABLE cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    category VARCHAR(50) NOT NULL,
    priceperday DECIMAL(8,2) NOT NULL,
    description TEXT,
    available BOOLEAN DEFAULT TRUE
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carid INT NOT NULL,
    userid INT NOT NULL,
    startdate DATE NOT NULL,
    enddate DATE NOT NULL,
    totalprice DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    FOREIGN KEY (carid) REFERENCES cars(id),
    FOREIGN KEY (userid) REFERENCES users(id)
);

-- Sample data
INSERT INTO cars (brand, model, category, priceperday, description) VALUES
('Toyota', 'Corolla', 'Compact', 25.00, 'Οικονομικό αυτοκίνητο για καθημερινή χρήση'),
('BMW', 'X3', 'SUV', 65.00, 'Πολυτελές SUV για άνετα ταξίδια'),
('Mercedes', 'C-Class', 'Luxury', 85.00, 'Κομψό sedan για επαγγελματικές συναντήσεις');

-- Admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Administrator', 'admin@carrental.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
