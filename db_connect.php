<?php
$database_path = __DIR__ . '/car_rental.db';

$dsn = "sqlite:$database_path";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
     $pdo = new PDO($dsn, null, null, $options);
     $pdo->exec("PRAGMA foreign_keys = ON"); // Enable foreign key constraints in SQLite
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
