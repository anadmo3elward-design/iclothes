<?php
require_once '../config.php';
require_once 'header.php';

// Handle Add Inventory
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_inventory'])) {
    $itemId = $_POST['item_id'];
    $sizeId = $_POST['size_id'];
    $colorId = $_POST['color_id'];
    $amount = (int) $_POST['amount'];
    $price = (float) $_POST['price'];

    if ($amount >= 0 && $price >= 0) {
        $stmt = $pdo->prepare("INSERT INTO inventory (ItemID, SizeID, ColorID, Amount, Price, AddedBy) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$itemId, $sizeId, $colorId, $amount, $price, $_SESSION['admin_id']]);
        echo "<script>window.location='inventory.php';</script>";
    } else {
        echo "<script>alert('الكمية والسعر يجب أن تكون صفر أو أكثر');</script>";
    }
}

// Fetch Logic with Filters
$where = "1=1";
$params = [];

if (isset($_GET['search']) && $_GET['search'] != '') {
    $where .= " AND i.Name LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
}
if (isset($_GET['size_id']) && $_GET['size_id'] != '') {
    $where .= " AND inv.SizeID = ?";
    $params[] = $_GET['size_id'];
}
if (isset($_GET['color_id']) && $_GET['color_id'] != '') {
    $where .= " AND inv.ColorID = ?";
    $params[] = $_GET['color_id'];
}

// Pagination variables
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count query
$countSql = "
    SELECT COUNT(*) 
    FROM inventory inv
    JOIN item i ON inv.ItemID = i.ItemID
    JOIN size s ON inv.SizeID = s.SizeID
    JOIN color c ON inv.ColorID = c.ColorID
    WHERE $where
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total_results = $countStmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

$inventory = $pdo->prepare("
    SELECT inv.*, i.Name as ItemName, s.Name as Size, c.Name as Color, i.ItemID
    FROM inventory inv
    JOIN item i ON inv.ItemID = i.ItemID
    JOIN size s ON inv.SizeID = s.SizeID
    JOIN color c ON inv.ColorID = c.ColorID
    WHERE $where
    ORDER BY inv.InventoryID DESC
    LIMIT $limit OFFSET $offset
");
$inventory->execute($params);
$inventoryItems = $inventory->fetchAll();

$items = $pdo->query("SELECT ItemID, Name FROM item")->fetchAll();
$sizes = $pdo->query("SELECT * FROM size")->fetchAll();
$colors = $pdo->query("SELECT * FROM color")->fetchAll();
?>

<div class="container my-5">
    <h2 class="mb-4 text-primary fw-bold border-bottom pb-2">إدارة المخزون</h2>

    <!-- Search & Filter Form -->
    <div class="card shadow-sm border-0 p-4 mb-4 bg-light">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5 col-sm-12">
                <label class="form-label fw-bold">بحث عن منتج</label>
                <input type="text" name="search" class="form-control" placeholder="اسم المنتج..."
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label fw-bold">الحجم</label>
                <select name="size_id" class="form-select">
                    <option value="">كل الأحجام</option>
                    <?php foreach ($sizes as $s): ?>
                        <option value="<?= $s['SizeID'] ?>" <?= (isset($_GET['size_id']) && $_GET['size_id'] == $s['SizeID']) ? 'selected' : '' ?>>
                            <?= $s['Name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label fw-bold">اللون</label>
                <select name="color_id" class="form-select">
                    <option value="">كل الألوان</option>
                    <?php foreach ($colors as $c): ?>
                        <option value="<?= $c['ColorID'] ?>" <?= (isset($_GET['color_id']) && $_GET['color_id'] == $c['ColorID']) ? 'selected' : '' ?>>
                            <?= $c['Name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-sm-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> بحث</button>
                <a href="inventory.php" class="btn btn-outline-secondary w-100">إعادة تعيين</a>
            </div>
        </form>
    </div>

    <!-- Add Inventory Form (Collapsed) -->
    <button
        onclick="document.getElementById('addInvForm').style.display = document.getElementById('addInvForm').style.display == 'none' ? 'block' : 'none'"
        class="btn btn-success shadow-sm mb-4"><i class="fas fa-plus-circle ms-1"></i> إضافة مخزون جديد</button>

    <div id="addInvForm" class="card shadow-sm border-0 p-4 mb-4" style="display: none; border-top: 4px solid var(--bs-success) !important;">
        <h3 class="mb-4 text-success fw-bold">إضافة مخزون جديد</h3>
        <form method="POST" class="row g-3 align-items-end">
            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-bold">المنتج</label>
                <select name="item_id" class="form-select" required>
                    <?php foreach ($items as $i): ?>
                        <option value="<?= $i['ItemID'] ?>">
                            <?= $i['Name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label fw-bold">الحجم</label>
                <select name="size_id" class="form-select" required>
                    <?php foreach ($sizes as $s): ?>
                        <option value="<?= $s['SizeID'] ?>">
                            <?= $s['Name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label fw-bold">اللون</label>
                <select name="color_id" class="form-select" required>
                    <?php foreach ($colors as $c): ?>
                        <option value="<?= $c['ColorID'] ?>">
                            <?= $c['Name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label fw-bold">الكمية</label>
                <input type="number" name="amount" class="form-control" required min="0">
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label fw-bold">السعر</label>
                <input type="number" step="0.01" name="price" class="form-control" required min="0">
            </div>
            <div class="col-md-1 col-sm-12">
                <button type="submit" name="add_inventory" class="btn btn-success w-100">حفظ</button>
            </div>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>المنتج</th>
                        <th>الحجم - اللون</th>
                        <th>الكمية</th>
                        <th>السعر</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventoryItems as $inv): ?>
                        <tr>
                            <td class="fw-bold text-primary">
                                <?= htmlspecialchars($inv['ItemName']) ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= $inv['Size'] ?></span> -
                                <span class="badge bg-secondary"><?= $inv['Color'] ?></span>
                            </td>
                            <td class="fw-bold">
                                <?= $inv['Amount'] ?>
                            </td>
                            <td class="text-success fw-bold">
                                <?= number_format($inv['Price'], 2) ?> شيكل
                            </td>
                            <td>
                                <a href="edit_product.php?id=<?= $inv['ItemID'] ?>"
                                    class="btn btn-sm btn-outline-primary" title="تعديل المنتج والمخزون">
                                    <i class="fas fa-edit"></i> تعديل
                                </a>
                                <!-- Inventory delete is safer from product page, maybe just remove delete here or warn -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($inventoryItems)): ?>
                        <tr><td colspan="5" class="text-center py-3">لا يوجد مخزون مطابق للبحث.</td></tr>
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