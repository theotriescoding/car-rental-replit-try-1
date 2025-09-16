<?php
// bookings_api.php - Διαχείριση κρατήσεων
require 'db_connect.php';
session_start();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create_booking' && isset($_SESSION['userid'])) {
    $carId = $_POST['car_id'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    
    if (empty($carId) || empty($startDate) || empty($endDate)) {
        echo json_encode(['success' => false, 'message' => 'Συμπληρώστε όλα τα πεδία!']);
        exit;
    }
    
    // Έλεγχος ημερομηνιών
    if (strtotime($startDate) >= strtotime($endDate)) {
        echo json_encode(['success' => false, 'message' => 'Οι ημερομηνίες δεν είναι έγκυρες!']);
        exit;
    }
    
    if (strtotime($startDate) < strtotime(date('Y-m-d'))) {
        echo json_encode(['success' => false, 'message' => 'Η ημερομηνία έναρξης δεν μπορεί να είναι στο παρελθόν!']);
        exit;
    }
    
    try {
        // Έλεγχος διαθεσιμότητας αυτοκινήτου
        $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND available = TRUE");
        $stmt->execute([$carId]);
        $car = $stmt->fetch();
        
        if (!$car) {
            echo json_encode(['success' => false, 'message' => 'Το αυτοκίνητο δεν είναι διαθέσιμο!']);
            exit;
        }
        
        // Έλεγχος για επικαλύπτουσες κρατήσεις
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE carid = ? 
            AND status IN ('pending', 'confirmed') 
            AND (
                (startdate <= ? AND enddate > ?) OR
                (startdate < ? AND enddate >= ?) OR
                (startdate >= ? AND startdate < ?)
            )
        ");
        $stmt->execute([$carId, $startDate, $startDate, $endDate, $endDate, $startDate, $endDate]);
        
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Το αυτοκίνητο δεν είναι διαθέσιμο για αυτές τις ημερομηνίες!']);
            exit;
        }
        
        // Υπολογισμός τιμής
        $days = max(1, (strtotime($endDate) - strtotime($startDate)) / (60*60*24));
        $totalPrice = $days * $car['priceperday'];
        
        // Δημιουργία κράτησης
        $stmt = $pdo->prepare("INSERT INTO bookings (carid, userid, startdate, enddate, totalprice, status, paymentstatus) VALUES (?, ?, ?, ?, ?, 'pending', 'pending')");
        $success = $stmt->execute([$carId, $_SESSION['userid'], $startDate, $endDate, $totalPrice]);
        
        if ($success) {
            echo json_encode([
                'success' => true, 
                'message' => 'Η κράτηση δημιουργήθηκε επιτυχώς!',
                'total_price' => $totalPrice,
                'days' => $days
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Αποτυχία δημιουργίας κράτησης!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων']);
    }
    exit;
}

if ($action === 'get_user_bookings' && isset($_SESSION['userid'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, c.brand, c.model, c.category, c.priceperday
            FROM bookings b 
            JOIN cars c ON b.carid = c.id 
            WHERE b.userid = ? 
            ORDER BY b.startdate DESC
        ");
        $stmt->execute([$_SESSION['userid']]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $bookings]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά τη φόρτωση κρατήσεων']);
    }
    exit;
}

if ($action === 'cancel_booking' && isset($_SESSION['userid'])) {
    $bookingId = $_POST['booking_id'] ?? '';
    
    if (empty($bookingId)) {
        echo json_encode(['success' => false, 'message' => 'Μη έγκυρο ID κράτησης!']);
        exit;
    }
    
    try {
        // Έλεγχος αν η κράτηση ανήκει στον χρήστη και είναι ακυρώσιμη
        $stmt = $pdo->prepare("
            SELECT * FROM bookings 
            WHERE id = ? AND userid = ? AND status = 'pending'
        ");
        $stmt->execute([$bookingId, $_SESSION['userid']]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Η κράτηση δεν μπορεί να ακυρωθεί!']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $success = $stmt->execute([$bookingId]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Η κράτηση ακυρώθηκε επιτυχώς!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Αποτυχία ακύρωσης κράτησης!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων']);
    }
    exit;
}

// Admin functions
if ($action === 'get_all_bookings' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    try {
        $stmt = $pdo->query("
            SELECT b.*, u.name as customer_name, u.email as customer_email, 
                   c.brand, c.model, c.category 
            FROM bookings b 
            JOIN users u ON b.userid = u.id 
            JOIN cars c ON b.carid = c.id 
            ORDER BY b.startdate DESC
        ");
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $bookings]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά τη φόρτωση κρατήσεων']);
    }
    exit;
}

if ($action === 'update_booking_status' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $bookingId = $_POST['booking_id'] ?? '';
    $newStatus = $_POST['status'] ?? '';
    
    if (empty($bookingId) || empty($newStatus)) {
        echo json_encode(['success' => false, 'message' => 'Μη έγκυρα δεδομένα!']);
        exit;
    }
    
    $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!in_array($newStatus, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Μη έγκυρη κατάσταση!']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $success = $stmt->execute([$newStatus, $bookingId]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Η κατάσταση ενημερώθηκε επιτυχώς!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Αποτυχία ενημέρωσης κατάστασης!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Μη έγκυρη ενέργεια ή δεν έχετε δικαιώματα']);
?>
