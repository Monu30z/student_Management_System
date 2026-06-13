<?php
$page_title = "Manage Results";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$success = isset($_GET['success']) ? 'Result saved successfully.' : '';
$deleted = isset($_GET['deleted']) ? 'Result deleted successfully.' : '';

$branch_filter = $_GET['branch'] ?? 'all';
$semester_filter = $_GET['semester'] ?? 'all';

$branches = [];
$branch_result = $conn->query("SELECT * FROM branches WHERE status = 'active' ORDER BY branch_name");
while ($row = $branch_result->fetch_assoc()) {
    $branches[] = $row;
}

$where = "1=1";

if ($branch_filter !== 'all') {
    $branch_id = (int)$branch_filter;
    $where .= " AND st.branch_id = $branch_id";
}

if ($semester_filter !== 'all') {
    $sem = (int)$semester_filter;
    if ($sem >= 1 && $sem <= 6) {
        $where .= " AND r.semester = '$sem'";
    }
}

$results = [];

$query = "SELECT r.*, 
          st.enrollment_no, st.name AS student_name, st.year, st.semester AS current_semester,
          b.branch_name, b.branch_code,
          sub.subject_name, sub.subject_code
          FROM results r
          LEFT JOIN students st ON r.student_id = st.id
          LEFT JOIN branches b ON st.branch_id = b.id
          LEFT JOIN subjects sub ON r.subject_id = sub.id
          WHERE $where
          ORDER BY r.grade ASC";

$res = $conn->query($query);

while ($row = $res->fetch_assoc()) {
    $results[] = $row;
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

    <?php if ($deleted): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-trash"></i> <?php echo $deleted; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-certificate"></i> Published Results</h4>
        <a href="add_result.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Publish / Update Result
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> Filter Results</h5>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-3">

                <div class="col-md-5">
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

                <div class="col-md-5">
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

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>

            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>
                <i class="fas fa-list"></i> Results Found:
                <span class="badge bg-primary"><?php echo count($results); ?></span>
            </h5>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Branch</th>
                            <th>Semester</th>
                            <th>Subject</th>
                            <th>Internal</th>
                            <th>External</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($results as $r): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($r['student_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($r['enrollment_no']); ?></small>
                                </td>

                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($r['branch_code']); ?>
                                    </span>
                                </td>

                                <td>Sem <?php echo htmlspecialchars($r['semester']); ?></td>

                                <td>
                                    <strong><?php echo htmlspecialchars($r['subject_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($r['subject_code']); ?></small>
                                </td>

                                <td><?php echo htmlspecialchars($r['internal_marks']); ?></td>
                                <td><?php echo htmlspecialchars($r['external_marks']); ?></td>
                                <td><strong><?php echo htmlspecialchars($r['total_marks']); ?></strong></td>

                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($r['grade']); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($r['status'] == 'Pass'): ?>
                                        <span class="badge bg-success">Pass</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Fail</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="edit_result.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <a href="delete_result.php?id=<?php echo $r['id']; ?>" 
                                       onclick="return confirmDelete('this result')"
                                       class="btn btn-sm btn-danger">
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