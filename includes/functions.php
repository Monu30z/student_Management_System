<?php
// Security Functions

function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    $data = $conn->real_escape_string($data);
    return $data;
}

function redirect_user($user_type) {
    switch ($user_type) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'faculty':
            header('Location: faculty/dashboard.php');
            break;
        case 'student':
            header('Location: student/dashboard.php');
            break;
        default:
            header('Location: login.php');
    }
    exit();
}

function check_login($required_type = null) {
    if (!isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
    
    if ($required_type && $_SESSION['user_type'] != $required_type) {
        header('Location: ../login.php');
        exit();
    }
    
    return true;
}

// function log_activity($user_type, $user_id, $action) {
//     global $conn;
    
//     $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
//     $stmt = $conn->prepare("INSERT INTO activity_logs (user_type, user_id, action) VALUES (?, ?, ?)");
//     $stmt->bind_param("sis", $user_type, $user_id, $action);
//     $stmt->execute();
//     $stmt->close();
// }

function get_user_info($user_type, $user_id) {
    global $conn;
    
    switch ($user_type) {
        case 'admin':
            $stmt = $conn->prepare("SELECT username, email FROM admins WHERE id = ?");
            break;
        case 'faculty':
            $stmt = $conn->prepare("SELECT faculty_id, name, email, photo FROM faculty WHERE id = ?");
            break;
        case 'student':
            $stmt = $conn->prepare("SELECT enrollment_no, name, email, photo, branch_id, year, semester FROM students WHERE id = ?");
            break;
        default:
            return null;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

function generate_random_password($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

function format_date($date) {
    return date('d-m-Y', strtotime($date));
}

function format_datetime($datetime) {
    return date('d-m-Y h:i A', strtotime($datetime));
}

function get_branch_name($branch_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT branch_name FROM branches WHERE id = ?");
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $branch = $result->fetch_assoc();
    $stmt->close();
    
    return $branch ? $branch['branch_name'] : 'Unknown';
}

function get_semester_name($semester) {
    return "Semester " . $semester;
}

function show_toast($message, $type = 'success') {
    $_SESSION['toast_message'] = $message;
    $_SESSION['toast_type'] = $type;
}

function display_toast() {
    if (isset($_SESSION['toast_message'])) {
        $message = $_SESSION['toast_message'];
        $type = $_SESSION['toast_type'] ?? 'success';
        
        echo "<div class='toast-notification {$type}'>{$message}</div>";
        
        unset($_SESSION['toast_message']);
        unset($_SESSION['toast_type']);
    }
}
?>