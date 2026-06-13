<?php
$page_title = "Edit Faculty / HOD";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$error = '';
$faculty_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM faculty WHERE id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$faculty = $result->fetch_assoc();
$stmt->close();

if (!$faculty) {
    header('Location: faculty.php');
    exit();
}

$branches = [];
$branch_result = $conn->query("SELECT * FROM branches WHERE status = 'active' ORDER BY branch_name");
while ($row = $branch_result->fetch_assoc()) {
    $branches[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $faculty_code = sanitize_input($_POST['faculty_id']);
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $mobile = sanitize_input($_POST['mobile']);
    $status = $_POST['status'];
    $is_hod = ($_POST['is_hod'] == 'yes') ? 'yes' : 'no';
    $password = $_POST['password'];

    if (empty($faculty_code) || empty($name) || empty($email)) {
        $error = 'Please fill all required fields';
    } else {
        $stmt = $conn->prepare("SELECT id FROM faculty WHERE (faculty_id = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $faculty_code, $email, $faculty_id);
        $stmt->execute();
        $check = $stmt->get_result();
        $stmt->close();

        if ($check->num_rows > 0) {
            $error = 'Faculty ID or Email already exists';
        } else {
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
                }
            }

            if (!empty($password)) {
                $stmt = $conn->prepare("UPDATE faculty 
                    SET faculty_id = ?, name = ?, email = ?, department_id = ?, mobile = ?, password = ?, photo = ?, status = ?, is_hod = ? 
                    WHERE id = ?");

                $stmt->bind_param(
                    "sssisssssi",
                    $faculty_code,
                    $name,
                    $email,
                    $department_id,
                    $mobile,
                    $password,
                    $photo,
                    $status,
                    $is_hod,
                    $faculty_id
                );
            } else {
                $stmt = $conn->prepare("UPDATE faculty 
                    SET faculty_id = ?, name = ?, email = ?, department_id = ?, mobile = ?, photo = ?, status = ?, is_hod = ? 
                    WHERE id = ?");

                $stmt->bind_param(
                    "sssissssi",
                    $faculty_code,
                    $name,
                    $email,
                    $department_id,
                    $mobile,
                    $photo,
                    $status,
                    $is_hod,
                    $faculty_id
                );
            }

            if ($stmt->execute()) {
                // log_activity('admin', $_SESSION['user_id'], 'Updated faculty/HOD: ' . $name);
                header('Location: faculty.php');
                exit();
            } else {
                $error = 'Failed to update faculty';
            }

            $stmt->close();
        }
    }
}

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="content-area">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-edit"></i> Edit Faculty / HOD</h4>
        <a href="faculty.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Faculty ID <span class="text-danger">*</span></label>
                        <input type="text" name="faculty_id" class="form-control" value="<?php echo htmlspecialchars($faculty['faculty_id']); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($faculty['name']); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($faculty['email']); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Department / Branch</label>
                        <select name="department_id" class="form-control">
                            <option value="">Select Department</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo ($faculty['department_id'] == $branch['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mobile</label>
                        <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($faculty['mobile']); ?>" maxlength="10">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" minlength="6">
                        <small class="text-muted">Blank chhodne par old password same rahega.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Role</label>
                        <select name="is_hod" class="form-control">
                            <option value="no" <?php echo ($faculty['is_hod'] == 'no') ? 'selected' : ''; ?>>Regular Faculty</option>
                            <option value="yes" <?php echo ($faculty['is_hod'] == 'yes') ? 'selected' : ''; ?>>Head of Department (HOD)</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?php echo ($faculty['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($faculty['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">

                        <?php if (!empty($faculty['photo']) && file_exists('../uploads/faculty/' . $faculty['photo'])): ?>
                            <div class="mt-2">
                                <img src="../uploads/faculty/<?php echo htmlspecialchars($faculty['photo']); ?>" 
                                     style="width:80px;height:80px;border-radius:50%;object-fit:cover;">
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update Faculty / HOD
                </button>

            </form>
        </div>
    </div>

</div>

<?php include '../includes/admin_footer.php'; ?>