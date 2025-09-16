<?php
// cars_api.php - Διαχείριση αυτοκινήτων
require 'db_connect.php';
session_start();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_cars') {
    try {
        $stmt = $pdo->query("SELECT * FROM cars ORDER BY brand, model");
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $cars]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά τη φόρτωση αυτοκινήτων']);
    }
    exit;
}

if ($action === 'add_car' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $brand = $_POST['brand'] ?? '';
    $model = $_POST['model'] ?? '';
    $category = $_POST['category'] ?? '';
    $pricePerDay = $_POST['priceperday'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($brand) || empty($model) || empty($category) || empty($pricePerDay)) {
        echo json_encode(['success' => false, 'message' => 'Συμπληρώστε όλα τα απαραίτητα πεδία!']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO cars (brand, model, category, priceperday, description, available) VALUES (?, ?, ?, ?, ?, TRUE)");
        $success = $stmt->execute([$brand, $model, $category, $pricePerDay, $description]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Το αυτοκίνητο προστέθηκε επιτυχώς!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Αποτυχία προσθήκης αυτοκινήτου!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων']);
    }
    exit;
}

if ($action === 'delete_car' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $carId = $_POST['id'] ?? '';
    
    if (empty($carId)) {
        echo json_encode(['success' => false, 'message' => 'Μη έγκυρο ID αυτοκινήτου!']);
        exit;
    }
    
    try {
        // Έλεγχος αν υπάρχουν ενεργές κρατήσεις
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE carid = ? AND status IN ('pending', 'confirmed')");
        $stmt->execute([$carId]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Δεν μπορείτε να διαγράψετε αυτοκίνητο με ενεργές κρατήσεις!']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
        $success = $stmt->execute([$carId]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Το αυτοκίνητο διαγράφηκε επιτυχώς!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Αποτυχία διαγραφής αυτοκινήτου!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων']);
    }
    exit;
}

if ($action === 'toggle_availability' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $carId = $_POST['id'] ?? '';
    
    if (empty($carId)) {
        echo json_encode(['success' => false, 'message' => 'Μη έγκυρο ID αυτοκινήτου!']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE cars SET available = NOT available WHERE id = ?");
        $success = $stmt->execute([$carId]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Η διαθεσιμότητα ενημερώθηκε!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Αποτυχία ενημέρωσης!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Μη έγκυρη ενέργεια ή δεν έχετε δικαιώματα']);
?>
