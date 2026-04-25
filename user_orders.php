<?php
require_once 'config.php';
require_once 'header.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location = 'login.php';</script>";
    exit;
}

$userId = $_SESSION['user_id'];
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) FROM `order` WHERE UserID = ?";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute([$userId]);
$total_results = $countStmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

$orders = $pdo->prepare("SELECT * FROM `order` WHERE UserID = ? ORDER BY OrderDate DESC LIMIT $limit OFFSET $offset");
$orders->execute([$userId]);
$myOrders = $orders->fetchAll();
?>

<div class="container my-5">
    <h2 class="mb-4 text-primary fw-bold border-bottom pb-2">طلباتي</h2>

    <?php if (empty($myOrders)): ?>
        <div class="alert alert-info py-4 text-center fs-5">
            لا يوجد لديك طلبات سابقة. <br><a href="shop.php" class="btn btn-primary mt-3 rounded-pill px-4">تصفح المنتجات</a>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>رقم الطلب</th>
                            <th>التاريخ</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th>التفاصيل</th>
                        </tr>
                    </thead>
                <tbody>
                    <?php foreach ($myOrders as $order): ?>
                        <tr>
                            <td class="fw-bold">#<?= $order['OrderID'] ?></td>
                            <td><?= $order['OrderDate'] ?></td>
                            <td class="text-primary fw-bold"><?= $order['TotalPrice'] ?> شيكل</td>
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
                                <span class="badge <?= $statusClass ?> fs-6"><?= $statusText ?></span>
                            </td>
                            <td>
                                <a href="user_order_details.php?id=<?= $order['OrderID'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">عرض التفاصيل</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white border-0 py-3 mt-2 rounded shadow-sm">
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
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>