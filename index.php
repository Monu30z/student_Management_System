<?php
require_once 'config.php';

// Check if user is logged in
if (isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
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
} else {
    header('Location: login.php');
    exit();
}
?>