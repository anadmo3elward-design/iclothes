<?php
require_once '../config.php';
require_once 'header.php';

// Delete user action removed per request

// Fetch Users
$search = $_GET['search'] ?? '';
$where = "1=1";
$params = [];
if (!empty($search)) {
    $where .= " AND (u.Name LIKE ? OR u.UserName LIKE ? OR u.Mobile LIKE ? OR u.Email LIKE ?)";
    $params = array_fill(0, 4, "%$search%");
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) FROM users u WHERE $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total_results = $countStmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

$stmt = $pdo->prepare("
    SELECT u.*, COUNT(o.OrderID) as OrdersCount 
    FROM users u 
    LEFT JOIN `order` o ON u.UserID = o.UserID 
    WHERE $where
    GROUP BY u.UserID 
    ORDER BY u.UserID DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="container my-5">
    <h2 class="mb-4 text-primary fw-bold border-bottom pb-2">إدارة العملاء (المستخدمين)</h2>

    <!-- Search Form -->
    <div class="card shadow-sm border-0 p-4 mb-4 bg-light">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <input type="text" name="search" class="form-control" placeholder="بحث بالاسم، الإيميل أو رقم الجوال..." value="<?= htmlspecialchars($search) ?>" style="flex: 1; min-width: 250px;">
            <button type="submit" class="btn btn-primary px-4 shadow-sm text-nowrap"><i class="fas fa-search ms-1"></i> بحث</button>
            <?php if (!empty($search)): ?>
                <a href="users.php" class="btn btn-outline-secondary px-4 text-nowrap">إلغاء</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>اليوزر (UserName)</th>
                        <th>الاسم الكامل</th>
                        <th>رقم الجوال</th>
                        <th>البريد الإلكتروني</th>
                        <th>عدد الطلبات</th>
                        <th>آخر دخول</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">لا يوجد مستخدمين مطابقين للبحث.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="fw-bold text-muted"><?= $u['UserID'] ?></td>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($u['UserName']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($u['Name']) ?></td>
                                <td><?= htmlspecialchars($u['Mobile'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($u['Email'] ?: '-') ?></td>
                                <td>
                                    <span class="badge bg-primary fs-6 px-3 py-2 shadow-sm rounded-pill">
                                        <?= $u['OrdersCount'] ?>
                                    </span>
                                </td>
                                <td class="text-muted" style="font-size: 0.9rem;"><?= $u['LastLogin'] ?: '<span class="text-muted">لم يسجل دخول</span>' ?></td>
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
