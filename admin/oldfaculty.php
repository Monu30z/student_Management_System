<?php
$page_title = "Manage Faculty";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

// Get all faculty
$faculty_list = [];
$result = $conn->query("SELECT * FROM faculty ORDER BY created_at DESC");

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
            <i class="fas fa-plus"></i> Add New Faculty
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
                                    <?php if ($faculty['photo']): ?>
                                        <img src="../uploads/faculty/<?php echo $faculty['photo']; ?>" 
                                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #E2E8F0; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #64748B;">
                                            <?php echo strtoupper(substr($faculty['name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($faculty['faculty_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($faculty['name']); ?></td>
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
                                    <a href="edit_faculty.php?id=<?php echo $faculty['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_faculty.php?id=<?php echo $faculty['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirmDelete('<?php echo htmlspecialchars($faculty['name']); ?>')" 
                                       title="Delete">
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