<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('faculty');

$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

$faculty_name = $_SESSION['name'] ?? 'Faculty';

$faculty_data = null;
$stmt = $conn->prepare("SELECT * FROM faculty WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$faculty_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT s.*, b.branch_name, b.branch_code
                        FROM subjects s
                        LEFT JOIN branches b ON s.branch_id = b.id
                        WHERE s.id = ? AND s.faculty_id = ?");
$stmt->bind_param("ii", $subject_id, $_SESSION['user_id']);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$subject) {
    header('Location: all_subjects.php');
    exit();
}

$students = [];

$stmt = $conn->prepare("SELECT * FROM students
                        WHERE branch_id = ? AND year = ? AND semester = ? AND status = 'active'
                        ORDER BY name");
$stmt->bind_param("iss", $subject['branch_id'], $subject['year'], $subject['semester']);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Students - GP Mau</title>
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
        .table th{background:#F8FAFC}
        .photo{width:42px;height:42px;border-radius:50%;object-fit:cover}
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
            <a href="all_subjects.php" class="active"><i class="fas fa-book"></i> My Subjects</a>
            <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
            <a href="marks.php"><i class="fas fa-pen"></i> Marks</a>
            <?php if (!empty($faculty_data) && $faculty_data['is_hod'] == 'yes'): ?>
                <a href="hod_panel.php"><i class="fas fa-star"></i> HOD Panel</a>
            <?php endif; ?>
            <a href="profile.php"><i class="fas fa-user-cog"></i> Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <h5 class="mb-0">Students</h5>
            <div><strong><?php echo htmlspecialchars($faculty_name); ?></strong></div>
        </div>

        <div class="content">

            <div class="card mb-3">
                <div class="card-body">
                    <strong>Subject:</strong> <?php echo htmlspecialchars($subject['subject_name']); ?> |
                    <strong>Branch:</strong> <?php echo htmlspecialchars($subject['branch_name']); ?> |
                    <strong>Year:</strong> <?php echo htmlspecialchars($subject['year']); ?> |
                    <strong>Semester:</strong> <?php echo htmlspecialchars($subject['semester']); ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-users"></i> Students List</h5>
                </div>

                <div class="card-body">
                    <?php if (count($students) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Enrollment</th>
                                        <th>Name</th>
                                        <th>Father Name</th>
                                        <th>Mobile</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($student['photo']) && file_exists('../uploads/students/' . $student['photo'])): ?>
                                                    <img src="../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" class="photo">
                                                <?php else: ?>
                                                    <div class="photo" style="background:#E2E8F0;display:flex;align-items:center;justify-content:center;font-weight:700;color:#64748B;">
                                                        <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($student['enrollment_no']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['father_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['mobile'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($student['email'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No students found.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>