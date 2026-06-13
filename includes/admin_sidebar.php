<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <h4>GP Mau Admin</h4>
    </div>

    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>

        <a href="students.php" class="menu-item <?php echo in_array($current_page, ['students.php','add_student.php','edit_student.php']) ? 'active' : ''; ?>">
            <i class="fas fa-user-graduate"></i>
            <span>Students</span>
        </a>

        <a href="faculty.php" class="menu-item <?php echo in_array($current_page, ['faculty.php','add_faculty.php','edit_faculty.php']) ? 'active' : ''; ?>">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Faculty / HOD</span>
        </a>

        <a href="subjects.php" class="menu-item <?php echo in_array($current_page, ['subjects.php','add_subject.php','edit_subject.php']) ? 'active' : ''; ?>">
            <i class="fas fa-book"></i>
            <span>Subjects</span>
        </a>

        <a href="results.php" class="menu-item <?php echo in_array($current_page, ['results.php','add_result.php','edit_result.php']) ? 'active' : ''; ?>">
            <i class="fas fa-certificate"></i>
            <span>Results</span>
        </a>

        <a href="profile.php" class="menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-cog"></i>
            <span>Profile</span>
        </a>

        <a href="../logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <h5><?php echo $page_title ?? 'Dashboard'; ?></h5>
        </div>

        <div class="topbar-right">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight:600;font-size:14px;">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </div>
                    <div style="font-size:12px;color:#64748B;">
                        Administrator
                    </div>
                </div>
            </div>
        </div>
    </div>