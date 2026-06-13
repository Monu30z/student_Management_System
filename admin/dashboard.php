<?php
$page_title = "Dashboard";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

// Get statistics
$stats = [];

// Total Students
$result = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
$stats['students'] = $result->fetch_assoc()['total'];

// Total Faculty
$result = $conn->query("SELECT COUNT(*) as total FROM faculty WHERE status = 'active'");
$stats['faculty'] = $result->fetch_assoc()['total'];

// Total Subjects
$result = $conn->query("SELECT COUNT(*) as total FROM subjects WHERE status = 'active'");
$stats['subjects'] = $result->fetch_assoc()['total'];

// Total Branches
$result = $conn->query("SELECT COUNT(*) as total FROM branches WHERE status = 'active'");
$stats['branches'] = $result->fetch_assoc()['total'];

// Branch-wise student count
$branch_stats = [];
$result = $conn->query("SELECT b.branch_name, COUNT(s.id) as student_count 
                        FROM branches b 
                        LEFT JOIN students s ON b.id = s.branch_id AND s.status = 'active'
                        GROUP BY b.id, b.branch_name
                        ORDER BY b.branch_name");
while ($row = $result->fetch_assoc()) {
    $branch_stats[] = $row;
}

// Recent students
$recent_students = [];
$result = $conn->query("SELECT s.*, b.branch_name 
                        FROM students s 
                        JOIN branches b ON s.branch_id = b.id 
                        WHERE s.status = 'active'
                        ORDER BY s.name DESC 
                        LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recent_students[] = $row;
}

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="content-area">
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card blue">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-number"><?php echo $stats['students']; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card green">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-number"><?php echo $stats['faculty']; ?></div>
                <div class="stat-label">Total Faculty</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card orange">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-number"><?php echo $stats['subjects']; ?></div>
                <div class="stat-label">Total Subjects</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card purple">
                <div class="stat-icon">
                    <i class="fas fa-sitemap"></i>
                </div>
                <div class="stat-number"><?php echo $stats['branches']; ?></div>
                <div class="stat-label">Total Branches</div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Branch-wise Students -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Branch-wise Students</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th class="text-end">Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($branch_stats) > 0): ?>
                                    <?php foreach ($branch_stats as $branch): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($branch['branch_name']); ?></strong></td>
                                            <td class="text-end">
                                                <span class="badge bg-primary"><?php echo $branch['student_count']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center">No data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Students -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-clock"></i> Recent Students</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Enrollment</th>
                                    <th>Name</th>
                                    <th>Branch</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_students) > 0): ?>
                                    <?php foreach ($recent_students as $student): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($student['enrollment_no']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($student['branch_name']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No students added yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row g-4 mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="add_student.php" class="btn btn-primary w-100">
                                <i class="fas fa-plus"></i> Add Student
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="add_faculty.php" class="btn btn-success w-100">
                                <i class="fas fa-plus"></i> Add Faculty
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="add_subject.php" class="btn btn-warning w-100">
                                <i class="fas fa-plus"></i> Add Subject
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="students.php" class="btn btn-info w-100">
                                <i class="fas fa-list"></i> View All Students
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<?php include '../includes/admin_footer.php'; ?>