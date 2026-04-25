<?php
require_once '../config.php';
require_once 'header.php';

// Handle Add / Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_cat'])) {
    $name = trim($_POST['name']);

    if (!empty($name)) {
        if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
            // Update
            $id = $_POST['category_id'];
            $stmt = $pdo->prepare("UPDATE category SET Name = ? WHERE CategoryID = ?");
            $stmt->execute([$name, $id]);
        } else {
            // Add
            $stmt = $pdo->prepare("INSERT INTO category (Name, CreatedBy) VALUES (?, ?)");
            $stmt->execute([$name, $_SESSION['admin_id']]);
        }
        echo "<script>window.location='categories.php';</script>";
    } else {
        echo "<script>alert('يرجى إدخال اسم القسم'); window.location='categories.php';</script>";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM category WHERE CategoryID = ?")->execute([$id]);
    echo "<script>window.location='categories.php';</script>";
}

// Fetch Categories
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) FROM category";
$total_results = $pdo->query($countSql)->fetchColumn();
$total_pages = ceil($total_results / $limit);

$categories = $pdo->query("SELECT * FROM category LIMIT $limit OFFSET $offset")->fetchAll();

// Fetch Edit Data
$editCat = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM category WHERE CategoryID = ?");
    $stmt->execute([$id]);
    $editCat = $stmt->fetch();
}
?>

<div class="container my-5">
    <h2 class="mb-4 text-primary fw-bold border-bottom pb-2">إدارة الأقسام</h2>

    <div class="card shadow-sm border-0 mb-4 p-4 bg-light">
        <form method="POST" class="row g-3 align-items-center">
            <input type="hidden" name="category_id" value="<?= $editCat['CategoryID'] ?? '' ?>">
            <div class="col-md-8 col-sm-12">
                <input type="text" name="name" class="form-control" placeholder="اسم القسم" required
                    value="<?= isset($editCat) ? htmlspecialchars($editCat['Name']) : '' ?>">
            </div>
            <div class="col-md-4 col-sm-12 d-flex gap-2">
                <button type="submit" name="save_cat" class="btn btn-primary w-100">
                    <i class="fas fa-save ms-1"></i> <?= isset($editCat) ? 'تحديث' : 'إضافة' ?>
                </button>
                <?php if (isset($editCat)): ?>
                    <a href="categories.php" class="btn btn-outline-secondary w-100">إلغاء</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الرقم</th>
                        <th>الاسم</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="3" class="text-center py-3">لا توجد أقسام متاحة.</td></tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td class="fw-bold text-muted">
                                    #<?= $cat['CategoryID'] ?>
                                </td>
                                <td class="fw-bold">
                                    <?= htmlspecialchars($cat['Name']) ?>
                                </td>
                                <td>
                                    <a href="?edit=<?= $cat['CategoryID'] ?>"
                                        class="btn btn-sm btn-outline-primary shadow-sm" title="تعديل">
                                        <i class="fas fa-edit"></i> تعديل
                                    </a>
                                    <a href="?delete=<?= $cat['CategoryID'] ?>" onclick="return confirm('هل أنت متأكد من مسح هذا القسم؟')"
                                        class="btn btn-sm btn-outline-danger shadow-sm ms-1" title="حذف">
                                        <i class="fas fa-trash"></i> حذف
                                    </a>
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