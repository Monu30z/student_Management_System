<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('faculty');

$stmt = $conn->prepare("SELECT f.*, b.branch_name, b.branch_code
                        FROM faculty f
                        LEFT JOIN branches b ON f.department_id = b.id
                        WHERE f.id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$hod = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$hod || $hod['is_hod'] != 'yes') {
    die("Access denied. This page is only for HOD.");
}

if (empty($hod['department_id'])) {
    die("Your department is not assigned. Contact admin.");
}

$year_filter = $_GET['year'] ?? 'all';
$semester_filter = $_GET['semester'] ?? 'all';

$where = "branch_id = " . (int)$hod['department_id'] . " AND status = 'active'";

if ($year_filter != 'all') {
    $safe_year = $conn->real_escape_string($year_filter);
    $where .= " AND year = '$safe_year'";
}

if ($semester_filter != 'all') {
    $sem = (int)$semester_filter;
    if ($sem >= 1 && $sem <= 6) {
        $where .= " AND semester = '$sem'";
    }
}

$students = [];
$res = $conn->query("SELECT * FROM students WHERE $where ORDER BY year, semester, name");
while ($row = $res->fetch_assoc()) {
    $students[] = $row;
}

$subjects = [];
$stmt = $conn->prepare("SELECT s.*, f.name AS faculty_name
                        FROM subjects s
                        LEFT JOIN faculty f ON s.faculty_id = f.id
                        WHERE s.branch_id = ? AND s.status = 'active'
                        ORDER BY s.year, s.semester, s.subject_name");
$stmt->bind_param("i", $hod['department_id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>HOD Panel - GP Mau</title>
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
        .table th{background:#F8FAFC}
    </style>
</head>

<body>
<div class="wrapper">

    <div class="sidebar">
        <div class="side-head">
            <div class="logo"><i class="fas fa-star"></i></div>
            <h4>HOD Panel</h4>
        </div>

        <div class="menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="all_subjects.php"><i class="fas fa-book"></i> My Subjects</a>
            <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
            <a href="marks.php"><i class="fas fa-pen"></i> Marks</a>
            <a href="hod_panel.php" class="active"><i class="fas fa-star"></i> HOD Panel</a>
            <a href="profile.php"><i class="fas fa-user-cog"></i> Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <h5 class="mb-0">HOD Panel</h5>
            <div>
                <strong><?php echo htmlspecialchars($hod['name']); ?></strong><br>
                <small><?php echo htmlspecialchars($hod['branch_name']); ?></small>
            </div>
        </div>

        <div class="content">

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h3><?php echo count($students); ?></h3>
                            <p class="mb-0">Students in Department</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h3><?php echo count($subjects); ?></h3>
                            <p class="mb-0">Subjects in Department</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Filter Students</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <select name="year" class="form-control">
                                <option value="all">All Years</option>
                                <option value="First Year" <?php echo ($year_filter == 'First Year') ? 'selected' : ''; ?>>First Year</option>
                                <option value="Second Year" <?php echo ($year_filter == 'Second Year') ? 'selected' : ''; ?>>Second Year</option>
                                <option value="Third Year" <?php echo ($year_filter == 'Third Year') ? 'selected' : ''; ?>>Third Year</option>
                            </select>
                        </div>

                        <div class="col-md-5">
                            <select name="semester" class="form-control">
                                <option value="all">All Semesters</option>
                                <?php for ($i=1;$i<=6;$i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($semester_filter == $i) ? 'selected' : ''; ?>>
                                        Semester <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-users"></i> Department Students</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Enrollment</th>
                                    <th>Name</th>
                                    <th>Year</th>
                                    <th>Semester</th>
                                    <th>Mobile</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($student['enrollment_no']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['year']); ?></td>
                                        <td>Sem <?php echo htmlspecialchars($student['semester']); ?></td>
                                        <td><?php echo htmlspecialchars($student['mobile'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (count($students) == 0): ?>
                                    <tr><td colspan="5" class="text-center">No students found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-book"></i> Department Subjects</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Subject</th>
                                    <th>Year</th>
                                    <th>Semester</th>
                                    <th>Faculty</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($subjects as $sub): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($sub['subject_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($sub['year']); ?></td>
                                        <td>Sem <?php echo htmlspecialchars($sub['semester']); ?></td>
                                        <td><?php echo htmlspecialchars($sub['faculty_name'] ?? 'Not Assigned'); ?></td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (count($subjects) == 0): ?>
                                    <tr><td colspan="5" class="text-center">No subjects found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>