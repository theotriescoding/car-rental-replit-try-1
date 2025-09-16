<?php
// auth.php - Διαχείριση αυθεντικοποίησης
require 'db_connect.php';
session_start();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Συμπληρώστε όλα τα πεδία!']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['userid'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            echo json_encode([
                'success' => true, 
                'message' => 'Σύνδεση επιτυχής!', 
                'role' => $user['role'],
                'name' => $user['name']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Λάθος email ή κωδικός!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων']);
    }
    exit;
}

if ($action === 'register') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Όλα τα πεδία είναι υποχρεωτικά!']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Ο κωδικός πρέπει να έχει τουλάχιστον 6 χαρακτήρες!']);
        exit;
    }
    
    try {
        // Έλεγχος αν το email υπάρχει ήδη
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Το email χρησιμοποιείται ήδη!']);
            exit;
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");
        $success = $stmt->execute([$name, $email, $hashedPassword]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Η εγγραφή ολοκληρώθηκε επιτυχώς!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Αποτυχία εγγραφής!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων']);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Αποσυνδεθήκατε επιτυχώς']);
    exit;
}

if ($action === 'check_session') {
    if (isset($_SESSION['userid'])) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'role' => $_SESSION['role'],
            'name' => $_SESSION['name']
        ]);
    } else {
        echo json_encode(['success' => true, 'logged_in' => false]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Μη έγκυρη ενέργεια']);
?>
