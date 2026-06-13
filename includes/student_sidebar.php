<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-user-graduate"></i>
        </div>
        <h4>Student Portal</h4>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="attendance.php" class="menu-item <?php echo ($current_page == 'attendance.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i>
            <span>My Attendance</span>
        </a>
        
        <a href="marks.php" class="menu-item <?php echo ($current_page == 'marks.php') ? 'active' : ''; ?>">
            <i class="fas fa-pen-to-square"></i>
            <span>My Marks</span>
        </a>
        
        <a href="results.php" class="menu-item <?php echo ($current_page == 'results.php') ? 'active' : ''; ?>">
            <i class="fas fa-certificate"></i>
            <span>Results</span>
        </a>
        
        <a href="profile.php" class="menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-cog"></i>
            <span>My Profile</span>
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
                    <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600; font-size: 14px;"><?php echo $_SESSION['name']; ?></div>
                    <div style="font-size: 12px; color: #64748B;">Student</div>
                </div>
            </div>
        </div>
    </div>