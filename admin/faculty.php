<?php
$page_title = "Manage Faculty";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$faculty_list = [];

$query = "SELECT f.*, b.branch_name, b.branch_code 
          FROM faculty f
          LEFT JOIN branches b ON f.department_id = b.id
          ORDER BY f.name DESC";

$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $faculty_list[] = $row;
}

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="content-area">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-chalkboard-teacher"></i> All Faculty</h4>
        <a href="add_faculty.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Add New Faculty / HOD
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Faculty ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($faculty_list as $faculty): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($faculty['photo']) && file_exists('../uploads/faculty/' . $faculty['photo'])): ?>
                                        <img src="../uploads/faculty/<?php echo htmlspecialchars($faculty['photo']); ?>" 
                                             style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                    <?php else: ?>
                                        <div style="width:40px;height:40px;border-radius:50%;background:#E2E8F0;display:flex;align-items:center;justify-content:center;font-weight:700;color:#64748B;">
                                            <?php echo strtoupper(substr($faculty['name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td><strong><?php echo htmlspecialchars($faculty['faculty_id']); ?></strong></td>

                                <td><?php echo htmlspecialchars($faculty['name']); ?></td>

                                <td>
                                    <?php if (!empty($faculty['branch_code'])): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($faculty['branch_code']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Not Assigned</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($faculty['is_hod'] == 'yes'): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-star"></i> HOD
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Faculty</span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo htmlspecialchars($faculty['email']); ?></td>

                                <td><?php echo htmlspecialchars($faculty['mobile'] ?? '-'); ?></td>

                                <td>
                                    <?php if ($faculty['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="edit_faculty.php?id=<?php echo $faculty['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <a href="delete_faculty.php?id=<?php echo $faculty['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete('<?php echo htmlspecialchars($faculty['name']); ?>')">
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