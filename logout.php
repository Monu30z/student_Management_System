<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Log activity before logout
// if (isset($_SESSION['user_type']) && isset($_SESSION['user_id'])) {
//     log_activity($_SESSION['user_type'], $_SESSION['user_id'], ucfirst($_SESSION['user_type']) . ' logged out');
// }

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header('Location: login.php');
exit();
?>