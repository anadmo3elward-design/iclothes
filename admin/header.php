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
    <header class="sticky-top shadow-sm">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand text-warning fw-bold" href="dashboard.php">
                    <i class="fas fa-boxes"></i> آي كلوز | الإدارة
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="adminNavbar">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">الرئيسية</a></li>
                        <li class="nav-item"><a class="nav-link" href="products.php">المنتجات</a></li>
                        <li class="nav-item"><a class="nav-link" href="inventory.php">المخزون</a></li>
                        <li class="nav-item"><a class="nav-link" href="categories.php">الأقسام الرئيسية</a></li>
                        <li class="nav-item"><a class="nav-link" href="sub_categories.php">الأقسام الفرعية</a></li>
                        <li class="nav-item"><a class="nav-link" href="orders.php">الطلبات</a></li>
                        <li class="nav-item"><a class="nav-link" href="coupons.php">الكوبونات</a></li>
                        <li class="nav-item"><a class="nav-link" href="users.php">العملاء</a></li>
                        <li class="nav-item"><a class="nav-link" href="admins.php">المشرفين</a></li>
                    </ul>
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                        <li class="nav-item"><a class="btn btn-outline-light btn-sm me-2" href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> زيارة الموقع</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user-shield"></i> حسابي
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="edit_password.php">تغيير كلمة المرور</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="../logout.php">تسجيل خروج</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="flex-grow-1 p-3 p-md-4">