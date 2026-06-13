<?php
$page_title = "Admin Profile";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$success = '';
$error = '';

// Get admin info
$stmt = $conn->prepare("SELECT username, email FROM admins WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Change Username
    if ($action == 'change_username') {
        $new_username = sanitize_input($_POST['new_username']);
        $current_password = $_POST['current_password'];
        
        if (empty($new_username) || empty($current_password)) {
            $error = 'Please fill all fields';
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin_check = $result->fetch_assoc();
            $stmt->close();
            
            if ($current_password === $admin_check['password']) {
                // Check if username already exists
                $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
                $stmt->bind_param("si", $new_username, $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = 'Username already exists';
                } else {
                    // Update username
                    $stmt = $conn->prepare("UPDATE admins SET username = ? WHERE id = ?");
                    $stmt->bind_param("si", $new_username, $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $_SESSION['username'] = $new_username;
                        // log_activity('admin', $_SESSION['user_id'], 'Changed username');
                        $success = 'Username changed successfully';
                        $admin['username'] = $new_username;
                    } else {
                        $error = 'Failed to change username';
                    }
                }
                $stmt->close();
            } else {
                $error = 'Current password is incorrect';
            }
        }
    }
    
    // Change Password
    if ($action == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Please fill all fields';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin_check = $result->fetch_assoc();
            $stmt->close();
            
            if ($current_password === $admin_check['password']) {
                // Update password (plain text for now)
                $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $new_password, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    // log_activity('admin', $_SESSION['user_id'], 'Changed password');
                    $success = 'Password changed successfully';
                } else {
                    $error = 'Failed to change password';
                }
                $stmt->close();
            } else {
                $error = 'Current password is incorrect';
            }
        }
    }
    
    // Update Email
    if ($action == 'update_email') {
        $new_email = sanitize_input($_POST['email']);
        
        if (empty($new_email)) {
            $error = 'Please enter email';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else {
            $stmt = $conn->prepare("UPDATE admins SET email = ? WHERE id = ?");
            $stmt->bind_param("si", $new_email, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                // log_activity('admin', $_SESSION['user_id'], 'Updated email');
                $success = 'Email updated successfully';
                $admin['email'] = $new_email;
            } else {
                $error = 'Failed to update email';
            }
            $stmt->close();
        }
    }
}

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="content-area">
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row g-4">
        
        <!-- Profile Info -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="user-avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                    </div>
                    <h5><?php echo htmlspecialchars($admin['username']); ?></h5>
                    <p class="text-muted">Administrator</p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($admin['email'] ?? 'Not set'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Change Username -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-user-edit"></i> Change Username</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_username">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">New Username</label>
                                <input type="text" name="new_username" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Change Username
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-key"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" minlength="6" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Update Email -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-envelope"></i> Update Email</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_email">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Email
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
    </div>
    
</div>

<?php include '../includes/admin_footer.php'; ?>