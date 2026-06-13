<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('faculty');

$faculty_id = $_SESSION['user_id'];
$success = '';
$error = '';

$stmt = $conn->prepare("SELECT f.*, b.branch_name, b.branch_code
                        FROM faculty f
                        LEFT JOIN branches b ON f.department_id = b.id
                        WHERE f.id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$faculty) {
    header('Location: ../logout.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mobile = sanitize_input($_POST['mobile']);
    $email = sanitize_input($_POST['email']);

    $photo = $faculty['photo'];

    if (!is_dir('../uploads/faculty')) {
        mkdir('../uploads/faculty', 0755, true);
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            if (!empty($photo) && file_exists('../uploads/faculty/' . $photo)) {
                @unlink('../uploads/faculty/' . $photo);
            }

            $photo = uniqid('fac_', true) . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/faculty/' . $photo);
        } else {
            $error = 'Invalid photo type';
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("UPDATE faculty SET mobile = ?, email = ?, photo = ? WHERE id = ?");
        $stmt->bind_param("sssi", $mobile, $email, $photo, $faculty_id);

        if ($stmt->execute()) {
            // log_activity('faculty', $faculty_id, 'Updated profile');
            $success = 'Profile updated successfully';
            $stmt->close();

            $stmt = $conn->prepare("SELECT f.*, b.branch_name, b.branch_code
                                    FROM faculty f
                                    LEFT JOIN branches b ON f.department_id = b.id
                                    WHERE f.id = ?");
            $stmt->bind_param("i", $faculty_id);
            $stmt->execute();
            $faculty = $stmt->get_result()->fetch_assoc();
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
    <title>Faculty Profile - GP Mau</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:Segoe UI,Tahoma,sans-serif;background:#F1F5F9}
        .wrapper{display:flex;min-height:100vh}
        .sidebar{width:260px;background:linear-gradient(180deg,#0F172A,#1E293B);color:#fff;position:fixed;height:100vh}
        .side-head{padding:25px;text-align:center;border-bottom:1px solid rgba(255,255,255,.1)}
        .side-head .logo{width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:28px}
        .menu{padding:20px 0}
        .menu a{display:flex;gap:12px;align-items:center;padding:12px 20px;color:rgba(255,255,255,.75);text-decoration:none;border-left:3px solid transparent}
        .menu a:hover,.menu a.active{background:rgba(255,255,255,.1);color:#fff;border-left-color:#06B6D4}
        .main{margin-left:260px;flex:1}
        .topbar{background:#fff;padding:15px 30px;box-shadow:0 2px 10px rgba(0,0,0,.05);display:flex;justify-content:space-between;align-items:center}
        .content{padding:30px}
        .card{border:none;border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
        .card-header{background:#fff;padding:20px 25px;border-bottom:1px solid #E2E8F0}
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
            <div class="logo"><i class="fas fa-chalkboard-teacher"></i></div>
            <h4>Faculty Panel</h4>
        </div>

        <div class="menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="all_subjects.php"><i class="fas fa-book"></i> My Subjects</a>
            <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
            <a href="marks.php"><i class="fas fa-pen"></i> Marks</a>
            <?php if ($faculty['is_hod'] == 'yes'): ?>
                <a href="hod_panel.php"><i class="fas fa-star"></i> HOD Panel</a>
            <?php endif; ?>
            <a href="profile.php" class="active"><i class="fas fa-user-cog"></i> Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <h5 class="mb-0">My Profile</h5>
            <div>
                <strong><?php echo htmlspecialchars($faculty['name']); ?></strong><br>
                <small>
                    <?php echo ($faculty['is_hod'] == 'yes') ? 'HOD' : 'Faculty'; ?>
                </small>
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
                            <?php if (!empty($faculty['photo']) && file_exists('../uploads/faculty/' . $faculty['photo'])): ?>
                                <img src="../uploads/faculty/<?php echo htmlspecialchars($faculty['photo']); ?>" class="profile-photo">
                            <?php else: ?>
                                <div class="avatar"><?php echo strtoupper(substr($faculty['name'], 0, 1)); ?></div>
                            <?php endif; ?>

                            <h5><?php echo htmlspecialchars($faculty['name']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($faculty['faculty_id']); ?></p>

                            <?php if ($faculty['is_hod'] == 'yes'): ?>
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-star"></i> HOD
                                </span>
                            <?php else: ?>
                                <span class="badge bg-primary">Faculty</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-id-card"></i> Faculty Details</h5>
                        </div>

                        <div class="card-body">
                            <div class="info-row"><strong>Faculty ID:</strong> <?php echo htmlspecialchars($faculty['faculty_id']); ?></div>
                            <div class="info-row"><strong>Name:</strong> <?php echo htmlspecialchars($faculty['name']); ?></div>
                            <div class="info-row"><strong>Email:</strong> <?php echo htmlspecialchars($faculty['email']); ?></div>
                            <div class="info-row"><strong>Mobile:</strong> <?php echo htmlspecialchars($faculty['mobile'] ?? '-'); ?></div>
                            <div class="info-row"><strong>Department:</strong> <?php echo htmlspecialchars($faculty['branch_name'] ?? 'Not Assigned'); ?></div>
                            <div class="info-row"><strong>Role:</strong> <?php echo ($faculty['is_hod'] == 'yes') ? 'Head of Department' : 'Regular Faculty'; ?></div>
                            <div class="info-row"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($faculty['status'])); ?></div>
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
                                        <input type="text" name="mobile" maxlength="10" class="form-control" value="<?php echo htmlspecialchars($faculty['mobile']); ?>">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($faculty['email']); ?>" required>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Profile Photo</label>
                                        <input type="file" name="photo" class="form-control" accept="image/*">
                                        <small class="text-muted">Password, Faculty ID, Role admin manage karega.</small>
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