<?php
$page_title = "Manage Students";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$branch_filter = $_GET['branch'] ?? 'all';
$year_filter = $_GET['year'] ?? 'all';
$semester_filter = $_GET['semester'] ?? 'all';

$where = [];
$where[] = "1=1";

if ($branch_filter !== 'all') {
    $branch_id = (int)$branch_filter;
    $where[] = "s.branch_id = $branch_id";
}

$allowed_years = ['First Year', 'Second Year', 'Third Year'];
if ($year_filter !== 'all' && in_array($year_filter, $allowed_years)) {
    $safe_year = $conn->real_escape_string($year_filter);
    $where[] = "s.year = '$safe_year'";
}

if ($semester_filter !== 'all') {
    $sem = (int)$semester_filter;
    if ($sem >= 1 && $sem <= 6) {
        $where[] = "s.semester = '$sem'";
    }
}

$where_sql = implode(" AND ", $where);

$query = "SELECT s.*, b.branch_name, b.branch_code 
          FROM students s 
          LEFT JOIN branches b ON s.branch_id = b.id 
          WHERE $where_sql
          ORDER BY s.name DESC";

$result = $conn->query($query);

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

$branches = [];
$branch_result = $conn->query("SELECT * FROM branches WHERE status = 'active' ORDER BY branch_name");
while ($row = $branch_result->fetch_assoc()) {
    $branches[] = $row;
}

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="content-area">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-user-graduate"></i> All Students</h4>
        <a href="add_student.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Student
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> Branch / Year / Semester Filter</h5>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Branch</label>
                    <select name="branch" class="form-control">
                        <option value="all">All Branches</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>" <?php echo ($branch_filter == $branch['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-control">
                        <option value="all">All Years</option>
                        <?php foreach ($allowed_years as $yr): ?>
                            <option value="<?php echo $yr; ?>" <?php echo ($year_filter == $yr) ? 'selected' : ''; ?>>
                                <?php echo $yr; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-control">
                        <option value="all">All Semesters</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($semester_filter == $i) ? 'selected' : ''; ?>>
                                Semester <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filter
                    </button>

                    <a href="students.php" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> Reset
                    </a>
                </div>

            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>
                <i class="fas fa-list"></i> Students Found: 
                <span class="badge bg-primary"><?php echo count($students); ?></span>
            </h5>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Enrollment</th>
                            <th>Name</th>
                            <th>Branch</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>Mobile</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($student['photo']) && file_exists('../uploads/students/' . $student['photo'])): ?>
                                        <img src="../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" 
                                             style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                    <?php else: ?>
                                        <div style="width:40px;height:40px;border-radius:50%;background:#E2E8F0;display:flex;align-items:center;justify-content:center;font-weight:700;color:#64748B;">
                                            <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td><strong><?php echo htmlspecialchars($student['enrollment_no']); ?></strong></td>

                                <td><?php echo htmlspecialchars($student['name']); ?></td>

                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($student['branch_code']); ?>
                                    </span>
                                </td>

                                <td><?php echo htmlspecialchars($student['year']); ?></td>

                                <td>Sem <?php echo htmlspecialchars($student['semester']); ?></td>

                                <td><?php echo htmlspecialchars($student['mobile'] ?? '-'); ?></td>

                                <td>
                                    <?php if ($student['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($student['status']); ?></span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <a href="delete_student.php?id=<?php echo $student['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete('<?php echo htmlspecialchars($student['name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/admin_footer.php'; ?>