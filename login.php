<?php
require_once 'config.php';
require_once 'includes/functions.php';

// If already logged in, redirect
if (isset($_SESSION['user_type'])) {
    redirect_user($_SESSION['user_type']);
}

$error = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_type = $_POST['login_type'] ?? '';
    
    if ($login_type == 'admin') {
        $username = sanitize_input($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter username and password';
        } else {
            $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $admin = $result->fetch_assoc();
                
                if ($password === $admin['password']) {
                    $_SESSION['user_id'] = $admin['id'];
                    $_SESSION['user_type'] = 'admin';
                    $_SESSION['username'] = $admin['username'];
                    
                    // log_activity('admin', $admin['id'], 'Admin logged in');
                    header('Location: admin/dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid username or password';
                }
            } else {
                $error = 'Invalid username or password';
            }
            $stmt->close();
        }
    } 
    elseif ($login_type == 'faculty') {
        $faculty_id = sanitize_input($_POST['faculty_id']);
        $password = $_POST['password'];
        
        if (empty($faculty_id) || empty($password)) {
            $error = 'Please enter faculty ID and password';
        } else {
            $stmt = $conn->prepare("SELECT id, faculty_id, name, password, status FROM faculty WHERE faculty_id = ?");
            $stmt->bind_param("s", $faculty_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $faculty = $result->fetch_assoc();
                
                if ($faculty['status'] == 'inactive') {
                    $error = 'Your account has been deactivated. Contact admin.';
                } elseif ($password === $faculty['password']) {
                    $_SESSION['user_id'] = $faculty['id'];
                    $_SESSION['user_type'] = 'faculty';
                    $_SESSION['faculty_id'] = $faculty['faculty_id'];
                    $_SESSION['name'] = $faculty['name'];
                    
                    // log_activity('faculty', $faculty['id'], 'Faculty logged in');
                    header('Location: faculty/dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid faculty ID or password';
                }
            } else {
                $error = 'Invalid faculty ID or password';
            }
            $stmt->close();
        }
    } 
    elseif ($login_type == 'student') {
        $enrollment_no = sanitize_input($_POST['enrollment_no']);
        $dob = $_POST['dob'];
        
        if (empty($enrollment_no) || empty($dob)) {
            $error = 'Please enter enrollment number and date of birth';
        } else {
            $stmt = $conn->prepare("SELECT id, enrollment_no, name, dob, status, branch_id, year, semester FROM students WHERE enrollment_no = ?");
            $stmt->bind_param("s", $enrollment_no);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $student = $result->fetch_assoc();
                
                if ($student['status'] == 'inactive') {
                    $error = 'Your account has been deactivated. Contact admin.';
                } elseif ($student['dob'] == $dob) {
                    $_SESSION['user_id'] = $student['id'];
                    $_SESSION['user_type'] = 'student';
                    $_SESSION['enrollment_no'] = $student['enrollment_no'];
                    $_SESSION['name'] = $student['name'];
                    $_SESSION['branch_id'] = $student['branch_id'];
                    $_SESSION['year'] = $student['year'];
                    $_SESSION['semester'] = $student['semester'];
                    
                    // log_activity('student', $student['id'], 'Student logged in');
                    header('Location: student/dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid enrollment number or date of birth';
                }
            } else {
                $error = 'Invalid enrollment number or date of birth';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Government Polytechnic Mau</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-header .logo {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 35px;
        }

        .login-header h4 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .login-header p {
            font-size: 13px;
            opacity: 0.8;
            margin: 5px 0 0 0;
        }

        .login-body {
            padding: 30px;
        }

        .login-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .login-tabs button {
            flex: 1;
            padding: 10px;
            border: 2px solid #E2E8F0;
            background: white;
            border-radius: 10px;
            font-weight: 500;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            color: #64748B;
        }

        .login-tabs button:hover {
            border-color: #2563EB;
            color: #2563EB;
        }

        .login-tabs button.active {
            background: linear-gradient(135deg, #2563EB 0%, #06B6D4 100%);
            color: white;
            border-color: transparent;
        }

        .login-form {
            display: none;
        }

        .login-form.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: #0F172A;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group label i {
            margin-right: 5px;
            color: #2563EB;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #2563EB 0%, #06B6D4 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            border: none;
        }

        .alert-danger {
            background: #FEE2E2;
            color: #991B1B;
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #E2E8F0;
        }

        .login-footer p {
            font-size: 13px;
            color: #64748B;
            margin: 0;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
            }

            .login-tabs button {
                font-size: 11px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h4>Government Polytechnic Mau</h4>
            <p>Student Management System</p>
        </div>

        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="login-tabs">
                <button type="button" class="active" onclick="switchTab('admin')">
                    <i class="fas fa-user-shield"></i> Admin
                </button>
                <button type="button" onclick="switchTab('faculty')">
                    <i class="fas fa-chalkboard-teacher"></i> Faculty
                </button>
                <button type="button" onclick="switchTab('student')">
                    <i class="fas fa-user-graduate"></i> Student
                </button>
            </div>

            <!-- Admin Login -->
            <form method="POST" class="login-form active" id="admin-form">
                <input type="hidden" name="login_type" value="admin">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <!-- Faculty Login -->
            <form method="POST" class="login-form" id="faculty-form">
                <input type="hidden" name="login_type" value="faculty">
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> Faculty ID</label>
                    <input type="text" name="faculty_id" class="form-control" placeholder="Enter faculty ID" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <!-- Student Login -->
            <form method="POST" class="login-form" id="student-form">
                <input type="hidden" name="login_type" value="student">
                <div class="form-group">
                    <label><i class="fas fa-id-badge"></i> Enrollment Number</label>
                    <input type="text" name="enrollment_no" class="form-control" placeholder="Enter enrollment number" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Date of Birth</label>
                    <input type="date" name="dob" class="form-control" required>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="login-footer">
                <p><i class="fas fa-info-circle"></i> Need help? Contact Administrator</p>
            </div>
        </div>
    </div>

    <script>
        function switchTab(type) {
            // Remove active class from all buttons
            document.querySelectorAll('.login-tabs button').forEach(btn => {
                btn.classList.remove('active');
            });

            // Remove active class from all forms
            document.querySelectorAll('.login-form').forEach(form => {
                form.classList.remove('active');
            });

            // Add active class to clicked button
            event.target.classList.add('active');

            // Show corresponding form
            document.getElementById(type + '-form').classList.add('active');

            // Focus first input
            setTimeout(() => {
                document.getElementById(type + '-form').querySelector('input').focus();
            }, 100);
        }

        // Auto focus first input on page load
        document.querySelector('.login-form.active input').focus();
    </script>
</body>
</html>