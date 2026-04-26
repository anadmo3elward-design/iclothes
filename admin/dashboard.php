<?php
require_once '../config.php';
require_once 'header.php';

// Stats
$totalOrders = $pdo->query("SELECT COUNT(*) FROM `order`")->fetchColumn();
$totalSales = $pdo->query("SELECT SUM(TotalPrice) FROM `order` WHERE OrderStatus != 'Cancelled'")->fetchColumn() ?: 0;
$totalProducts = $pdo->query("SELECT COUNT(*) FROM item")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Recent Orders
$recentOrders = $pdo->query("SELECT * FROM `order` ORDER BY OrderDate DESC LIMIT 5")->fetchAll();

// Out of stock products
$outOfStockItems = $pdo->query("
    SELECT i.Name as ItemName, s.Name as Size, c.Name as Color 
    FROM inventory inv
    JOIN item i ON inv.ItemID = i.ItemID
    JOIN size s ON inv.SizeID = s.SizeID
    JOIN color c ON inv.ColorID = c.ColorID
    WHERE inv.Amount <= 0
")->fetchAll();
?>

<div class="container my-5">
    <h1 class="text-primary fw-bold mb-4 border-bottom pb-2">لوحة التحكم</h1>

    <?php if (!empty($outOfStockItems)): ?>
        <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
            <h5 class="alert-heading fw-bold mb-2"><i class="fas fa-exclamation-triangle ms-2"></i>تنبيه: منتجات نفدت كميتها!</h5>
            <ul class="mb-0">
                <?php foreach ($outOfStockItems as $outItem): ?>
                    <li>المنتج: <strong><?= htmlspecialchars($outItem['ItemName']) ?></strong> (<?= htmlspecialchars($outItem['Size']) ?> - <?= htmlspecialchars($outItem['Color']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mt-2">
        <div class="col">
            <div class="card shadow-sm border-0 h-100 bg-light text-center p-4">
                <h3 class="fs-5 text-muted">اجمالي المبيعات</h3>
                <p class="fs-2 fw-bold text-success mb-0">
                    <?= number_format($totalSales, 2) ?> شيكل
                </p>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 h-100 bg-light text-center p-4">
                <h3 class="fs-5 text-muted">الطلبات</h3>
                <p class="fs-2 fw-bold text-info mb-0">
                    <?= $totalOrders ?>
                </p>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 h-100 bg-light text-center p-4">
                <h3 class="fs-5 text-muted">المنتجات</h3>
                <p class="fs-2 fw-bold text-primary mb-0">
                    <?= $totalProducts ?>
                </p>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 h-100 bg-light text-center p-4">
                <h3 class="fs-5 text-muted">المستخدمين</h3>
                <p class="fs-2 fw-bold text-secondary mb-0">
                    <?= $totalUsers ?>
                </p>
            </div>
        </div>
    </div>

    <h2 class="mt-5 mb-4 text-primary fw-bold border-bottom pb-2">آخر الطلبات</h2>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>رقم الطلب</th>
                        <th>المبلغ</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td class="fw-bold">#<?= $order['OrderID'] ?></td>
                            <td class="text-primary fw-bold">
                                <?= number_format($order['TotalPrice'], 2) ?> شيكل
                            </td>
                            <td>
                                <?php
                                $statusClass = match ($order['OrderStatus']) {
                                    'Delivered' => 'bg-success',
                                    'Cancelled' => 'bg-danger',
                                    'Shipped' => 'bg-primary',
                                    default => 'bg-warning text-dark'
                                };
                                $statusText = match ($order['OrderStatus']) {
                                    'Pending' => 'قيد الانتظار',
                                    'Shipped' => 'تم الشحن',
                                    'Delivered' => 'تم التسليم',
                                    'Cancelled' => 'ملغي',
                                    default => $order['OrderStatus']
                                };
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                            </td>
                            <td>
                                <?= $order['OrderDate'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>