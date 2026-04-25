<?php
require_once '../config.php';
require_once 'header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='container'><p>طلب غير صالح.</p></div>";
    require_once '../footer.php';
    exit;
}

$orderId = $_GET['id'];

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE `order` SET OrderStatus = ?, ProcessedBy = ? WHERE OrderID = ?");
    $stmt->execute([$status, $_SESSION['admin_id'], $orderId]);
    echo "<script>window.location='order_details.php?id=$orderId';</script>";
    exit;
}

// Get order info along with user details
$orderStmt = $pdo->prepare("
    SELECT o.*, u.Name as UserName, u.Mobile, u.Email, a.UserName as ProcessedByName, cp.CouponCode, cp.DiscountAmount
    FROM `order` o 
    JOIN users u ON o.UserID = u.UserID 
    LEFT JOIN admins a ON o.ProcessedBy = a.AdminID
    LEFT JOIN copouns cp ON o.CouponID = cp.CouponID
    WHERE o.OrderID = ?
");
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch();

if (!$order) {
    echo "<div class='container'><p>الطلب غير موجود.</p></div>";
    require_once '../footer.php';
    exit;
}

// Get order details (items)
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
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-primary fw-bold m-0"><i class="fas fa-file-invoice ms-2"></i>تفاصيل الطلب #<?= htmlspecialchars($order['OrderID']) ?></h2>
        <a href="orders.php" class="btn btn-outline-secondary shadow-sm"><i class="fas fa-arrow-right ms-1"></i> العودة للطلبات</a>
    </div>

    <div class="row g-4 mb-4">
        <!-- Order Info -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 p-4 h-100 bg-light">
                <h3 class="mb-3 text-primary border-bottom pb-2 fw-bold"><i class="fas fa-info-circle ms-2"></i>معلومات الطلب</h3>
                <p><strong>تاريخ الطلب:</strong> <?= date('Y-m-d H:i', strtotime($order['OrderDate'])) ?></p>
                <p><strong>المبلغ الإجمالي:</strong> <span class="fw-bold fs-5 text-success"><?= number_format($order['TotalPrice'], 2) ?> شيكل</span></p>
                <?php if($order['CouponID']): ?>
                    <p><strong>كوبون خصم مُطبق:</strong> <span class="badge bg-secondary fs-6 rounded-pill px-3 shadow-sm"><?= htmlspecialchars($order['CouponCode']) ?> (-<?= floatval($order['DiscountAmount']) ?>%)</span></p>
                <?php endif; ?>
                <p><strong>آخر تحديث بواسطة:</strong> <?= $order['ProcessedByName'] ? "<span class='badge bg-secondary'>" . htmlspecialchars($order['ProcessedByName']) . "</span>" : "<span class='text-muted'>غير محدد</span>" ?></p>
                
                <div class="mt-4 border-top pt-3">
                    <?php
                        $badgeClass = match ($order['OrderStatus']) {
                            'Pending' => 'bg-warning text-dark',
                            'Shipped' => 'bg-info text-dark',
                            'Delivered' => 'bg-success',
                            'Cancelled' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                        $statusText = match ($order['OrderStatus']) {
                            'Pending' => 'قيد الانتظار',
                            'Shipped' => 'تم الشحن',
                            'Delivered' => 'تم التسليم',
                            'Cancelled' => 'ملغي',
                            default => $order['OrderStatus']
                        };
                    ?>
                    <p class="mb-2"><strong>حالة الطلب:</strong> 
                        <span class="badge <?= $badgeClass ?> fs-6 px-3 py-2 rounded-pill shadow-sm ms-1">
                            <?= $statusText ?>
                        </span>
                    </p>
                    <?php if ($order['OrderStatus'] !== 'Delivered'): ?>
                        <form method="POST" class="d-flex gap-2 align-items-center mt-3">
                            <select name="status" class="form-select form-select-sm w-auto">
                                <option value="Pending" <?= $order['OrderStatus'] == 'Pending' ? 'selected' : '' ?>>قيد الانتظار</option>
                                <option value="Shipped" <?= $order['OrderStatus'] == 'Shipped' ? 'selected' : '' ?>>تم الشحن</option>
                                <option value="Delivered" <?= $order['OrderStatus'] == 'Delivered' ? 'selected' : '' ?>>تم التسليم</option>
                                <option value="Cancelled" <?= $order['OrderStatus'] == 'Cancelled' ? 'selected' : '' ?>>ملغي</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-sm btn-success shadow-sm">تحديث الحالة</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 p-4 h-100 bg-light">
                <h3 class="mb-3 text-primary border-bottom pb-2 fw-bold"><i class="fas fa-user ms-2"></i>معلومات العميل</h3>
                <p><strong>الاسم:</strong> <?= htmlspecialchars($order['UserName']) ?></p>
                <p><strong>رقم الجوال:</strong> <?= htmlspecialchars($order['Mobile'] ?: 'غير متوفر') ?></p>
                <p><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($order['Email'] ?: 'غير متوفر') ?></p>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 p-4 bg-light mb-4 text-center">
        <h3 class="mb-4 text-primary fw-bold border-bottom pb-2 text-start"><i class="fas fa-box-open ms-2"></i>المنتجات المطلوبة</h3>
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0 text-start">
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
                        $imgDisplay = $img ? "../thumbs/" . htmlspecialchars($img) : "../assets/logo.png";
                    ?>
                        <tr>
                            <td class="d-flex align-items-center gap-3">
                                <img src="<?= $imgDisplay ?>" width="60" height="60" class="rounded shadow-sm border" style="object-fit:cover;">
                                <a href="../product.php?id=<?= $row['ItemID'] ?>" target="_blank" class="text-primary text-decoration-none fw-bold">
                                    <?= htmlspecialchars($row['ItemName']) ?>
                                </a>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['SizeName'] ?: '-') ?></span></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['ColorName'] ?: '-') ?></span></td>
                            <td><?= number_format($row['Price'], 2) ?> شيكل</td>
                            <td class="fw-bold"><?= htmlspecialchars($row['Amount']) ?></td>
                            <td class="fw-bold text-success"><?= number_format($row['ItemTotalPrice'], 2) ?> شيكل</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>
