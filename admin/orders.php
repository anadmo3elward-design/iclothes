<?php
require_once '../config.php';
require_once 'header.php';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE `order` SET OrderStatus = ?, ProcessedBy = ? WHERE OrderID = ?");
    $stmt->execute([$status, $_SESSION['admin_id'], $orderId]);
    echo "<script>window.location='orders.php';</script>";
}

// Fetch Logic with Search
$where = "1=1";
$params = [];

if (isset($_GET['search']) && $_GET['search'] != '') {
    $search = $_GET['search'];
    // Check if numeric for ID search, otherwise Name
    if (is_numeric($search)) {
        $where .= " AND (o.OrderID = ? OR u.Mobile LIKE ?)";
        $params[] = $search;
        $params[] = "%$search%";
    } else {
        $where .= " AND u.Name LIKE ?";
        $params[] = "%$search%";
    }
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countSql = "
    SELECT COUNT(*) 
    FROM `order` o
    JOIN users u ON o.UserID = u.UserID
    WHERE $where
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total_results = $countStmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

$orders = $pdo->prepare("
    SELECT o.*, u.Name as UserName 
    FROM `order` o
    JOIN users u ON o.UserID = u.UserID
    WHERE $where
    ORDER BY o.OrderDate DESC
    LIMIT $limit OFFSET $offset
");
$orders->execute($params);
$orderList = $orders->fetchAll();
?>

<div class="container my-5">
    <h2 class="mb-4 text-primary fw-bold border-bottom pb-2">إدارة الطلبات</h2>

    <!-- Search Form -->
    <div class="card shadow-sm border-0 p-4 mb-4 bg-light">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <input type="text" name="search" class="form-control" placeholder="بحث برقم الطلب أو اسم العميل..."
                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="flex: 1; min-width: 250px;">
            <button type="submit" class="btn btn-primary px-4 shadow-sm text-nowrap"><i class="fas fa-search ms-1"></i> بحث</button>
            <?php if (isset($_GET['search'])): ?>
                <a href="orders.php" class="btn btn-outline-secondary px-4 text-nowrap">إلغاء</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>العميل</th>
                        <th>المبلغ</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th>التفاصيل</th>
                        <th>تحديث الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($orderList)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">لا توجد طلبات مطابقة.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orderList as $order): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= $order['OrderID'] ?></td>
                                <td class="fw-bold text-primary">
                                    <?= htmlspecialchars($order['UserName']) ?>
                                </td>
                                <td class="fw-bold">
                                    <?= number_format($order['TotalPrice'], 2) ?> شيكل
                                </td>
                                <td>
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
                                    <span class="badge <?= $badgeClass ?> fs-6 px-3 py-2 rounded-pill shadow-sm">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td class="text-muted" style="font-size: 0.9rem;">
                                    <?= date('Y-m-d H:i', strtotime($order['OrderDate'])) ?>
                                </td>
                                <td>
                                    <a href="order_details.php?id=<?= $order['OrderID'] ?>" class="btn btn-sm btn-outline-primary shadow-sm"><i class="fas fa-eye ms-1"></i> عرض</a>
                                </td>
                                <td>
                                    <?php if ($order['OrderStatus'] !== 'Delivered'): ?>
                                        <form method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="order_id" value="<?= $order['OrderID'] ?>">
                                            <select name="status" class="form-select form-select-sm" style="min-width: 130px;">
                                                <option value="Pending" <?= $order['OrderStatus'] == 'Pending' ? 'selected' : '' ?>>قيد
                                                    الانتظار</option>
                                                <option value="Shipped" <?= $order['OrderStatus'] == 'Shipped' ? 'selected' : '' ?>>تم
                                                    الشحن</option>
                                                <option value="Delivered" <?= $order['OrderStatus'] == 'Delivered' ? 'selected' : '' ?>>تم
                                                    التسليم</option>
                                                <option value="Cancelled" <?= $order['OrderStatus'] == 'Cancelled' ? 'selected' : '' ?>>
                                                    ملغي</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-success shadow-sm">تحديث</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-success fw-bold"><i class="fas fa-check-circle ms-1"></i> مكتمل</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white border-0 py-3">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mb-0">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php
                        $qParams = $_GET;
                        $qParams['page'] = $i;
                        $queryString = http_build_query($qParams);
                        ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= $queryString ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>