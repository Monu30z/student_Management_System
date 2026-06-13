<?php
$page_title = "Dashboard";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('faculty');

$faculty_info = get_user_info('faculty', $_SESSION['user_id']);

// Get assigned subjects
$assigned_subjects = [];
$total_students = 0;

$stmt = $conn->prepare("SELECT s.*, b.branch_name, b.branch_code 
                        FROM subjects s 
                        LEFT JOIN branches b ON s.branch_id = b.id 
                        WHERE s.faculty_id = ? AND s.status = 'active'
                        ORDER BY s.semester");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM students WHERE branch_id = ? AND year = ? AND semester = ? AND status = 'active'");
    $stmt2->bind_param("iss", $row['branch_id'], $row['year'], $row['semester']);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $count = $res2->fetch_assoc();
    $row['student_count'] = $count['total'];
    $total_students += $count['total'];
    $stmt2->close();
    
    $assigned_subjects[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - GP Mau</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #F1F5F9;
        }
        
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #0F172A 0%, #1E293B 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header .logo {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 30px;
        }
        
        .sidebar-header h4 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 12px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .menu-item:hover,
        .menu-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #06B6D4;
        }
        
        .menu-item i {
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 260px;
            background: #F1F5F9;
        }
        
        .topbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .topbar-left h5 {
            margin: 0;
            color: #0F172A;
            font-weight: 600;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }
        
        .content-area {
            padding: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid;
        }
        
        .stat-card.blue { border-left-color: #2563EB; }
        .stat-card.green { border-left-color: #10B981; }
        .stat-card.orange { border-left-color: #F59E0B; }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .stat-card.blue .stat-icon { background: rgba(37, 99, 235, 0.1); color: #2563EB; }
        .stat-card.green .stat-icon { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .stat-card.orange .stat-icon { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #0F172A;
            margin: 10px 0 5px 0;
        }
        
        .stat-label {
            color: #64748B;
            font-size: 14px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            background: white;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #E2E8F0;
            padding: 20px 25px;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .card-header h5 {
            margin: 0;
            color: #0F172A;
            font-weight: 600;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .table thead th {
            background: #F8FAFC;
            color: #0F172A;
            font-weight: 600;
            border: none;
            padding: 12px;
        }
        
        .table tbody td {
            padding: 12px;
            border-bottom: 1px solid #E2E8F0;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background: #F8FAFC;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h4>Faculty Panel</h4>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="attendance.php" class="menu-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Mark Attendance</span>
                </a>
                
                <a href="marks.php" class="menu-item">
                    <i class="fas fa-pen"></i>
                    <span>Upload Marks</span>
                </a>
                
                <a href="profile.php" class="menu-item">
                    <i class="fas fa-user-cog"></i>
                    <span>My Profile</span>
                </a>
                
                <a href="../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <h5>Dashboard</h5>
                </div>
                <div class="topbar-right">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($faculty_info['name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($faculty_info['name']); ?></div>
                            <div style="font-size: 12px; color: #64748B;">Faculty</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-area">
                
                <!-- Stats Row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card blue">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-number"><?php echo count($assigned_subjects); ?></div>
                            <div class="stat-label">My Subjects</div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="stat-card green">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number"><?php echo $total_students; ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="stat-card orange">
                            <div class="stat-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="stat-number"><?php echo date('d M'); ?></div>
                            <div class="stat-label"><?php echo date('Y'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Subjects Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-book-open"></i> My Assigned Subjects</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($assigned_subjects) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Subject Name</th>
                                            <th>Code</th>
                                            <th>Branch</th>
                                            <th>Year</th>
                                            <th>Semester</th>
                                            <th>Students</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assigned_subjects as $subject): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong></td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($subject['subject_code']); ?></span></td>
                                                <td><span class="badge bg-info"><?php echo htmlspecialchars($subject['branch_code']); ?></span></td>
                                                <td><?php echo htmlspecialchars($subject['year']); ?></td>
                                                <td>Sem <?php echo $subject['semester']; ?></td>
                                                <td><span class="badge bg-primary"><?php echo $subject['student_count']; ?></span></td>
                                                <td>
                                                    <a href="attendance.php?subject_id=<?php echo $subject['id']; ?>" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="marks.php?subject_id=<?php echo $subject['id']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-pen"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No Subjects Assigned</h5>
                                <p class="text-muted">Contact administrator to assign subjects</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </div>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>