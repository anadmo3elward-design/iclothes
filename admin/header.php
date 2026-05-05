<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Auth Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - آي كلوز</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
</head>

<body class="d-flex flex-column min-vh-100 bg-light">
    <style>
        /* Admin Sidebar Layout Styles */
        .admin-top-navbar {
            height: 60px;
            z-index: 1030;
        }
        .admin-sidebar {
            position: fixed;
            top: 60px;
            right: 0;
            bottom: 0;
            width: 260px;
            z-index: 1020;
            background-color: #212529; /* Dark Sidebar */
            overflow-y: auto;
            transition: transform 0.3s ease-in-out;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
        }
        body > main, body > footer {
            margin-right: 260px;
            transition: margin-right 0.3s ease-in-out;
        }
        .admin-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 0.8rem 1rem;
            margin: 0.2rem 1rem;
            border-radius: 0.4rem;
            transition: all 0.2s;
            font-weight: 600;
        }
        .admin-sidebar .nav-link:hover, .admin-sidebar .nav-link.active {
            background-color: var(--bs-primary);
            color: #fff;
            transform: translateX(-5px);
        }
        .admin-sidebar .nav-link i {
            width: 25px;
            text-align: center;
        }
        
        /* Mobile View */
        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            body > main, body > footer {
                margin-right: 0;
            }
        }
    </style>

    <!-- Top Navbar -->
    <header class="admin-top-navbar navbar navbar-dark bg-dark sticky-top shadow-sm px-3">
        <div class="d-flex align-items-center">
            <button class="btn btn-dark d-lg-none me-2" type="button" onclick="document.getElementById('adminSidebar').classList.toggle('show');">
                <i class="fas fa-bars fs-5"></i>
            </button>
            <a class="navbar-brand text-warning fw-bold m-0" href="dashboard.php">
                <i class="fas fa-boxes me-2"></i> آي كلوز | الإدارة
            </a>
        </div>
        <div class="d-flex align-items-center">
            <a class="btn btn-outline-light btn-sm ms-3 d-none d-md-inline-block" href="../index.php" target="_blank">
                <i class="fas fa-external-link-alt"></i> زيارة الموقع
            </a>
            <div class="dropdown">
                <button class="btn btn-dark dropdown-toggle border-0" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle fs-5"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><a class="dropdown-item" href="edit_password.php"><i class="fas fa-key ms-2 text-muted"></i>تغيير المرور</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger fw-bold" href="../logout.php"><i class="fas fa-sign-out-alt ms-2"></i>تسجيل خروج</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Sidebar Menu -->
    <nav class="admin-sidebar" id="adminSidebar">
        <ul class="nav flex-column pt-4 pb-5">
            <li class="nav-item text-white small fw-bold px-4 mb-2">القائمة الرئيسية</li>
            <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> الرئيسية</a></li>
            <li class="nav-item"><a class="nav-link" href="products.php"><i class="fas fa-box"></i> المنتجات</a></li>
            <li class="nav-item"><a class="nav-link" href="inventory.php"><i class="fas fa-warehouse"></i> المخزون</a></li>
            
            <li class="nav-item mt-4 text-white small fw-bold px-4 mb-2">إدارة الأقسام</li>
            <li class="nav-item"><a class="nav-link" href="categories.php"><i class="fas fa-tags"></i> الأقسام الرئيسية</a></li>
            <li class="nav-item"><a class="nav-link" href="sub_categories.php"><i class="fas fa-tag"></i> الأقسام الفرعية</a></li>
            
            <li class="nav-item mt-4 text-white small fw-bold px-4 mb-2">المبيعات والتقارير</li>
            <li class="nav-item"><a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i> الطلبات</a></li>
            <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-line"></i> التقارير التفصيلية</a></li>
            <li class="nav-item"><a class="nav-link" href="coupons.php"><i class="fas fa-ticket-alt"></i> الكوبونات</a></li>
            
            <li class="nav-item mt-4 text-white small fw-bold px-4 mb-2">المستخدمين والصلاحيات</li>
            <li class="nav-item"><a class="nav-link" href="users.php"><i class="fas fa-users"></i> العملاء</a></li>
            <li class="nav-item"><a class="nav-link" href="admins.php"><i class="fas fa-user-shield"></i> المشرفين</a></li>
        </ul>
    </nav>
    <main class="flex-grow-1 p-3 p-md-4">