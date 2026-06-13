<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('student');

$student_id = $_SESSION['user_id'];
$success = '';
$error = '';

$stmt = $conn->prepare("SELECT s.*, b.branch_name, b.branch_code 
                        FROM students s
                        LEFT JOIN branches b ON s.branch_id = b.id
                        WHERE s.id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header('Location: ../logout.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mobile = sanitize_input($_POST['mobile']);
    $email = sanitize_input($_POST['email']);

    $photo = $student['photo'];

    if (!is_dir('../uploads/students')) {
        mkdir('../uploads/students', 0755, true);
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            if (!empty($photo) && file_exists('../uploads/students/' . $photo)) {
                @unlink('../uploads/students/' . $photo);
            }

            $photo = uniqid('stu_', true) . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/students/' . $photo);
        } else {
            $error = 'Invalid photo type';
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("UPDATE students SET mobile = ?, email = ?, photo = ? WHERE id = ?");
        $stmt->bind_param("sssi", $mobile, $email, $photo, $student_id);

        if ($stmt->execute()) {
            // log_activity('student', $student_id, 'Updated profile');
            $success = 'Profile updated successfully';

            $stmt->close();

            $stmt = $conn->prepare("SELECT s.*, b.branch_name, b.branch_code 
                                    FROM students s
                                    LEFT JOIN branches b ON s.branch_id = b.id
                                    WHERE s.id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $error = 'Profile update failed';
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile - GP Mau</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:Segoe UI,Tahoma,sans-serif;background:#F1F5F9}
        .wrapper{display:flex;min-height:100vh}
        .sidebar{width:260px;background:linear-gradient(180deg,#0F172A,#1E293B);color:white;position:fixed;height:100vh}
        .side-head{padding:25px;text-align:center;border-bottom:1px solid rgba(255,255,255,.1)}
        .side-head .logo{width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:28px}
        .menu{padding:20px 0}
        .menu a{display:flex;gap:12px;align-items:center;padding:12px 20px;color:rgba(255,255,255,.75);text-decoration:none;border-left:3px solid transparent}
        .menu a:hover,.menu a.active{background:rgba(255,255,255,.1);color:white;border-left-color:#06B6D4}
        .main{margin-left:260px;flex:1}
        .topbar{background:white;padding:15px 30px;box-shadow:0 2px 10px rgba(0,0,0,.05);display:flex;justify-content:space-between;align-items:center}
        .content{padding:30px}
        .card{border:none;border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
        .card-header{background:white;padding:20px 25px;border-bottom:1px solid #E2E8F0}
        .profile-photo{width:150px;height:150px;border-radius:50%;object-fit:cover;display:block;margin:0 auto 20px}
        .avatar{width:150px;height:150px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;color:white;font-size:60px;font-weight:800}
        .info-row{padding:7px 0;border-bottom:1px solid #E2E8F0}
        .info-row strong{display:inline-block;width:140px}
    </style>
</head>

<body>
<div class="wrapper">

    <div class="sidebar">
        <div class="side-head">
            <div class="logo"><i class="fas fa-user-graduate"></i></div>
            <h4>Student Portal</h4>
        </div>

        <div class="menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="attendance.php"><i class="fas fa-calendar-check"></i> My Attendance</a>
            <a href="marks.php"><i class="fas fa-pen-to-square"></i> My Marks</a>
            <a href="results.php"><i class="fas fa-certificate"></i> Results</a>
            <a href="report_card.php"><i class="fas fa-file-pdf"></i> Report Card</a>
            <a href="profile.php" class="active"><i class="fas fa-user-cog"></i> My Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <h5 class="mb-0">My Profile</h5>
            <div>
                <strong><?php echo htmlspecialchars($student['name']); ?></strong><br>
                <small><?php echo htmlspecialchars($student['enrollment_no']); ?></small>
            </div>
        </div>

        <div class="content">

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <?php if (!empty($student['photo']) && file_exists('../uploads/students/' . $student['photo'])): ?>
                                <img src="../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" class="profile-photo">
                            <?php else: ?>
                                <div class="avatar"><?php echo strtoupper(substr($student['name'], 0, 1)); ?></div>
                            <?php endif; ?>

                            <h5><?php echo htmlspecialchars($student['name']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($student['enrollment_no']); ?></p>

                            <a href="report_card.php" class="btn btn-danger w-100 mt-2">
                                <i class="fas fa-file-pdf"></i> Report Card
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-id-card"></i> Student Details</h5>
                        </div>

                        <div class="card-body">
                            <div class="info-row"><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></div>
                            <div class="info-row"><strong>Enrollment:</strong> <?php echo htmlspecialchars($student['enrollment_no']); ?></div>
                            <div class="info-row"><strong>Father:</strong> <?php echo htmlspecialchars($student['father_name']); ?></div>
                            <div class="info-row"><strong>Mother:</strong> <?php echo htmlspecialchars($student['mother_name']); ?></div>
                            <div class="info-row"><strong>DOB:</strong> <?php echo date('d-m-Y', strtotime($student['dob'])); ?></div>
                            <div class="info-row"><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></div>
                            <div class="info-row"><strong>Branch:</strong> <?php echo htmlspecialchars($student['branch_name']); ?></div>
                            <div class="info-row"><strong>Year:</strong> <?php echo htmlspecialchars($student['year']); ?></div>
                            <div class="info-row"><strong>Semester:</strong> <?php echo htmlspecialchars($student['semester']); ?></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-edit"></i> Update Allowed Details</h5>
                        </div>

                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Mobile</label>
                                        <input type="text" name="mobile" maxlength="10" class="form-control" value="<?php echo htmlspecialchars($student['mobile']); ?>">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>">
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Profile Photo</label>
                                        <input type="file" name="photo" class="form-control" accept="image/*">
                                        <small class="text-muted">Only mobile, email and photo can be updated.</small>
                                    </div>

                                </div>

                                <button class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
</body>
</html>