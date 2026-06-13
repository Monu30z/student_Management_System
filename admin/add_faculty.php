<?php
$page_title = "Add Faculty / HOD";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$error = '';

$branches = [];
$branch_result = $conn->query("SELECT * FROM branches WHERE status = 'active' ORDER BY branch_name");
while ($row = $branch_result->fetch_assoc()) {
    $branches[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $faculty_id = sanitize_input($_POST['faculty_id']);
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $mobile = sanitize_input($_POST['mobile']);
    $password = $_POST['password'];
    $is_hod = ($_POST['is_hod'] == 'yes') ? 'yes' : 'no';

    if (empty($faculty_id) || empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill all required fields';
    } else {
        $stmt = $conn->prepare("SELECT id FROM faculty WHERE faculty_id = ? OR email = ?");
        $stmt->bind_param("ss", $faculty_id, $email);
        $stmt->execute();
        $check = $stmt->get_result();
        $stmt->close();

        if ($check->num_rows > 0) {
            $error = 'Faculty ID or Email already exists';
        } else {
            $photo = '';

            if (!is_dir('../uploads/faculty')) {
                mkdir('../uploads/faculty', 0755, true);
            }

            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, $allowed)) {
                    $photo = uniqid('fac_', true) . '.' . $ext;
                    move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/faculty/' . $photo);
                }
            }

            $stmt = $conn->prepare("INSERT INTO faculty 
                (faculty_id, name, email, department_id, mobile, password, photo, status, is_hod) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)");

            $stmt->bind_param(
                "sssissss",
                $faculty_id,
                $name,
                $email,
                $department_id,
                $mobile,
                $password,
                $photo,
                $is_hod
            );

            if ($stmt->execute()) {
                log_activity('admin', $_SESSION['user_id'], 'Added faculty/HOD: ' . $name);
                header('Location: faculty.php');
                exit();
            } else {
                $error = 'Failed to add faculty';
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
        <h4><i class="fas fa-plus"></i> Add Faculty / HOD</h4>
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
                        <input type="text" name="faculty_id" class="form-control" placeholder="Example: FAC001" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Department / Branch</label>
                        <select name="department_id" class="form-control">
                            <option value="">Select Department</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>">
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mobile</label>
                        <input type="text" name="mobile" class="form-control" maxlength="10">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                        <small class="text-muted">Faculty password admin create/manage karega.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Role</label>
                        <select name="is_hod" class="form-control">
                            <option value="no">Regular Faculty</option>
                            <option value="yes">Head of Department (HOD)</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>

                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Add Faculty / HOD
                </button>

            </form>
        </div>
    </div>

</div>

<?php include '../includes/admin_footer.php'; ?>