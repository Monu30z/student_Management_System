<?php
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Dashboard'; ?> - GP Mau</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
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
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h4 {
            font-size: 18px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .sidebar-header .logo {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 30px;
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
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .content-area {
            padding: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            border-left: 4px solid;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card.blue { border-left-color: #2563EB; }
        .stat-card.green { border-left-color: #10B981; }
        .stat-card.orange { border-left-color: #F59E0B; }
        .stat-card.purple { border-left-color: #8B5CF6; }
        
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
        .stat-card.purple .stat-icon { background: rgba(139, 92, 246, 0.1); color: #8B5CF6; }
        
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
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #E2E8F0;
            padding: 20px;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .card-header h5 {
            margin: 0;
            color: #0F172A;
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563EB 0%, #06B6D4 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }
        
        .table thead th {
            background: #F8FAFC;
            color: #0F172A;
            font-weight: 600;
            border: none;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -260px;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">