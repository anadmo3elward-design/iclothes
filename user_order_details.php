<?php
require_once 'config.php';
require_once 'header.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location = 'login.php';</script>";
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='container'><p>طلب غير صالح.</p></div>";
    require_once 'footer.php';
    exit;
}

$orderId = $_GET['id'];

// Get order
$orderStmt = $pdo->prepare("
    SELECT o.*, a.UserName as ProcessedByName, cp.CouponCode, cp.DiscountAmount
    FROM `order` o 
    LEFT JOIN admins a ON o.ProcessedBy = a.AdminID
    LEFT JOIN copouns cp ON o.CouponID = cp.CouponID
    WHERE o.OrderID = ? AND o.UserID = ?
");
$orderStmt->execute([$orderId, $_SESSION['user_id']]);
$order = $orderStmt->fetch();

if (!$order) {
    echo "<div class='container'><p>الطلب غير موجود أو ليس لديك صلاحية للوصول إليه.</p></div>";
    require_once 'footer.php';
    exit;
}

// Get order details
$detailsStmt = $pdo->prepare("
    SELECT od.Amount, od.ItemTotalPrice, i.Price, item.Name as ItemName, item.ItemID,
           s.Name as SizeName, c.Name as ColorName
    FROM order_detail od
    JOIN inventory i ON od.InventoryID = i.InventoryID
    JOIN item ON i.ItemID = item.ItemID
    LEFT JOIN size s ON i.SizeID = s.SizeID
    LEFT JOIN color c ON i.ColorID = c.ColorID
    WHERE od.OrderID = ?
");
$detailsStmt->execute([$orderId]);
$details = $detailsStmt->fetchAll();

?>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary fw-bold mb-0">تفاصيل الطلب #<?= htmlspecialchars($order['OrderID']) ?></h2>
        <a href="user_orders.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="fas fa-arrow-right ms-2"></i>العودة للطلبات</a>
    </div>

    <div class="card shadow-sm border-0 mb-4 p-4 p-md-5">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4 bg-light p-3 rounded">
            <div class="col">
                <span class="text-muted d-block mb-1">تاريخ الطلب</span>
                <strong class="fs-5"><?= htmlspecialchars($order['OrderDate']) ?></strong>
            </div>
            <div class="col">
                <span class="text-muted d-block mb-1">حالة الطلب</span>
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
                    default => htmlspecialchars($order['OrderStatus'])
                };
                ?>
                <span class="badge <?= $statusClass ?> fs-6 px-3 py-2"><?= $statusText ?></span>
            </div>
            <div class="col">
                <span class="text-muted d-block mb-1">الإجمالي</span>
                <strong class="fs-5 text-primary"><?= number_format($order['TotalPrice'], 2) ?> شيكل</strong>
            </div>
            <?php if ($order['CouponID']): ?>
            <div class="col">
                <span class="text-muted d-block mb-1">كوبون مٌطبق</span>
                <span class="badge bg-danger fs-6 px-3 py-2"><?= htmlspecialchars($order['CouponCode']) ?> (-<?= floatval($order['DiscountAmount']) ?>%)</span>
            </div>
            <?php endif; ?>
            <div class="col">
                <span class="text-muted d-block mb-1">آخر المعالجة بواسطة</span>
                <?= $order['ProcessedByName'] ? "<span class='badge bg-secondary fs-6 px-3 py-2'>" . htmlspecialchars($order['ProcessedByName']) . "</span>" : "<span class='text-muted'>غير محدد</span>" ?>
            </div>
        </div>

        <h3 class="mb-3 text-primary fw-bold">المنتجات</h3>
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>المنتج</th>
                        <th>المقاس</th>
                        <th>اللون</th>
                        <th>السعر الفردي</th>
                        <th>الكمية</th>
                        <th>المجموع</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $row): 
                        // Get one image for the item to display thumbnail
                        $imgStmt = $pdo->prepare("SELECT FileName FROM image WHERE ItemID = ? LIMIT 1");
                        $imgStmt->execute([$row['ItemID']]);
                        $img = $imgStmt->fetchColumn();
                        $imgDisplay = $img ? "thumbs/" . htmlspecialchars($img) : "assets/logo.png";
                    ?>
                        <tr>
                            <td class="d-flex align-items-center gap-3">
                                <img src="<?= $imgDisplay ?>" class="rounded shadow-sm" width="50" height="50" style="object-fit:cover;">
                                <a href="product.php?id=<?= $row['ItemID'] ?>" class="text-primary text-decoration-none fw-bold">
                                    <?= htmlspecialchars($row['ItemName']) ?>
                                </a>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['SizeName'] ?: '-') ?></span></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['ColorName'] ?: '-') ?></span></td>
                            <td><?= number_format($row['Price'], 2) ?> شيكل</td>
                            <td class="fw-bold"><?= htmlspecialchars($row['Amount']) ?></td>
                            <td class="fw-bold text-primary"><?= number_format($row['ItemTotalPrice'], 2) ?> شيكل</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
