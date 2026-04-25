<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Calculate cart count
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT SUM(Amount) FROM cart WHERE UserID = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_count = $stmt->fetchColumn() ?: 0;
    }
}

// Fetch categories for menu
$categories = [];
if (isset($pdo)) {
    try {
        $catStmt = $pdo->query("SELECT CategoryID, Name FROM category ORDER BY Name ASC");
        if ($catStmt) {
            $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($categories as &$cat) {
                 $subStmt = $pdo->prepare("SELECT SubCategoryID, Name FROM sub_category WHERE CategoryID = ? ORDER BY Name ASC");
                 $subStmt->execute([$cat['CategoryID']]);
                 $cat['SubCategories'] = $subStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($cat);
        }
    } catch (PDOException $e) {
        // Ignore if table doesn't exist yet
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>آي كلوز - متجر عصري</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>

<body class="d-flex flex-column min-vh-100 bg-light">
    <header class="sticky-top bg-white shadow-sm">
        <nav class="navbar navbar-expand-lg navbar-light container">
            <a href="index.php" class="navbar-brand d-flex align-items-center gap-2 fw-bold text-primary">
                <img src="assets/logo-sm.png" alt="آي كلوز" style="height: 40px; border-radius: 5px;">
                آي كلوز
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop.php">المتجر</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            التصنيفات
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item fw-bold text-primary" href="shop.php">الكل</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php foreach($categories as $cat): ?>
                                <li><a class="dropdown-item fw-bold" href="shop.php?category=<?= $cat['CategoryID'] ?>"><?= htmlspecialchars($cat['Name']) ?></a></li>
                                <?php if(!empty($cat['SubCategories'])): ?>
                                    <?php foreach($cat['SubCategories'] as $sub): ?>
                                        <li><a class="dropdown-item text-muted ps-4" href="shop.php?sub_category=<?= $sub['SubCategoryID'] ?>" style="font-size: 0.9em;">- <?= htmlspecialchars($sub['Name']) ?></a></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">السلة <span class="badge bg-danger rounded-pill"><?= $cart_count ?></span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user_orders.php">طلباتي</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'مستخدم') ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="edit_password.php"><i class="fas fa-key ms-2"></i>تغيير كلمة المرور</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt ms-2"></i>تسجيل خروج</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-lg-3">
                            <a class="nav-link" href="login.php">دخول</a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-primary rounded-pill px-4" href="register.php">حساب جديد</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>
    <main class="flex-grow-1 py-4">