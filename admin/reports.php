<?php
require_once '../config.php';
require_once 'header.php';

// Fetch items for dropdown
$items = $pdo->query("SELECT ItemID, Name FROM item ORDER BY Name ASC")->fetchAll();

// Build query dynamically based on filters
$sql = "
    SELECT 
        o.OrderID, o.OrderDate, o.OrderStatus, u.Name as CustomerName,
        od.Amount as Qty, od.ItemTotalPrice,
        i.Name as ItemName, s.Name as SizeName, c.Name as ColorName
    FROM `order` o
    JOIN users u ON o.UserID = u.UserID
    JOIN order_detail od ON o.OrderID = od.OrderID
    JOIN inventory inv ON od.InventoryID = inv.InventoryID
    JOIN item i ON inv.ItemID = i.ItemID
    LEFT JOIN size s ON inv.SizeID = s.SizeID
    LEFT JOIN color c ON inv.ColorID = c.ColorID
    WHERE 1=1
";
$params = [];

$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$itemId = $_GET['item_id'] ?? '';
$status = $_GET['status'] ?? '';

if (!empty($startDate)) {
    $sql .= " AND DATE(o.OrderDate) >= ?";
    $params[] = $startDate;
}
if (!empty($endDate)) {
    $sql .= " AND DATE(o.OrderDate) <= ?";
    $params[] = $endDate;
}
if (!empty($itemId)) {
    $sql .= " AND i.ItemID = ?";
    $params[] = $itemId;
}
if (!empty($status)) {
    $sql .= " AND o.OrderStatus = ?";
    $params[] = $status;
}

$sql .= " ORDER BY o.OrderDate DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reportData = $stmt->fetchAll();

// KPIs
$totalRevenue = 0;
$totalQty = 0;
$orderIds = [];

foreach ($reportData as $row) {
    // Usually, cancelled orders don't count towards revenue
    if ($row['OrderStatus'] !== 'Cancelled') {
        $totalRevenue += $row['ItemTotalPrice'];
    }
    $totalQty += $row['Qty'];
    $orderIds[$row['OrderID']] = true;
}
$totalOrders = count($orderIds);
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary fw-bold m-0"><i class="fas fa-chart-line ms-2"></i>التقارير المفصلة</h2>
        <button onclick="window.print()" class="btn btn-outline-secondary shadow-sm"><i class="fas fa-print ms-1"></i> طباعة التقرير</button>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">من تاريخ</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">إلى تاريخ</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">المنتج</label>
                    <select name="item_id" class="form-select">
                        <option value="">جميع المنتجات</option>
                        <?php foreach($items as $item): ?>
                            <option value="<?= $item['ItemID'] ?>" <?= $itemId == $item['ItemID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">حالة الطلب</label>
                    <select name="status" class="form-select">
                        <option value="">الكل</option>
                        <option value="Pending" <?= $status == 'Pending' ? 'selected' : '' ?>>قيد الانتظار</option>
                        <option value="Shipped" <?= $status == 'Shipped' ? 'selected' : '' ?>>تم الشحن</option>
                        <option value="Delivered" <?= $status == 'Delivered' ? 'selected' : '' ?>>تم التسليم</option>
                        <option value="Cancelled" <?= $status == 'Cancelled' ? 'selected' : '' ?>>ملغي</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> بحث</button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="fs-1 me-4 opacity-50"><i class="fas fa-money-bill-wave"></i></div>
                    <div>
                        <h5 class="card-title mb-1">إجمالي المبيعات</h5>
                        <h3 class="fw-bold mb-0"><?= number_format($totalRevenue, 2) ?> شيكل</h3>
                        <small class="opacity-75">لا يشمل الطلبات الملغية</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white shadow border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="fs-1 me-4 opacity-50"><i class="fas fa-tshirt"></i></div>
                    <div>
                        <h5 class="card-title mb-1">إجمالي القطع المباعة</h5>
                        <h3 class="fw-bold mb-0"><?= $totalQty ?> قطعة</h3>
                        <small class="opacity-75">بناءً على الفلترة</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white shadow border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="fs-1 me-4 opacity-50"><i class="fas fa-shopping-bag"></i></div>
                    <div>
                        <h5 class="card-title mb-1">عدد الطلبات</h5>
                        <h3 class="fw-bold mb-0"><?= $totalOrders ?> طلب</h3>
                        <small class="opacity-75">تحتوي على هذه المنتجات</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>رقم الطلب</th>
                        <th>التاريخ</th>
                        <th>العميل</th>
                        <th>المنتج</th>
                        <th>المقاس/اللون</th>
                        <th>الكمية</th>
                        <th>الإجمالي</th>
                        <th>حالة الطلب</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-folder-open fs-1 mb-3 d-block"></i> لا توجد بيانات مطابقة للبحث.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td class="fw-bold">
                                    <a href="order_details.php?id=<?= $row['OrderID'] ?>" class="text-decoration-none">#<?= $row['OrderID'] ?></a>
                                </td>
                                <td style="white-space: nowrap;"><?= date('Y-m-d H:i', strtotime($row['OrderDate'])) ?></td>
                                <td><?= htmlspecialchars($row['CustomerName']) ?></td>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($row['ItemName']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['SizeName'] ?? '-') ?></span> <span class="badge bg-secondary"><?= htmlspecialchars($row['ColorName'] ?? '-') ?></span></td>
                                <td class="fw-bold fs-5 text-center"><?= $row['Qty'] ?></td>
                                <td class="fw-bold text-success"><?= number_format($row['ItemTotalPrice'], 2) ?> شيكل</td>
                                <td>
                                    <?php
                                    $statusClass = match ($row['OrderStatus']) {
                                        'Delivered' => 'bg-success',
                                        'Cancelled' => 'bg-danger',
                                        'Shipped' => 'bg-primary',
                                        default => 'bg-warning text-dark'
                                    };
                                    $statusText = match ($row['OrderStatus']) {
                                        'Pending' => 'قيد الانتظار',
                                        'Shipped' => 'تم الشحن',
                                        'Delivered' => 'تم التسليم',
                                        'Cancelled' => 'ملغي',
                                        default => $row['OrderStatus']
                                    };
                                    ?>
                                    <span class="badge <?= $statusClass ?> px-2 py-1"><?= $statusText ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .admin-sidebar, .admin-top-navbar, form, button, .btn-outline-secondary {
        display: none !important;
    }
    body > main {
        margin: 0 !important;
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .badge {
        color: #000 !important;
        background: none !important;
        border: 1px solid #000;
    }
}
</style>

<?php require_once '../footer.php'; ?>
