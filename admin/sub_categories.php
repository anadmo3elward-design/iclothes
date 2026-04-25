<?php
require_once '../config.php';
require_once 'header.php';

// Handle Add / Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_subcat'])) {
    $name = trim($_POST['name']);
    $categoryId = $_POST['category_id'];

    if (!empty($name) && !empty($categoryId)) {
        if (isset($_POST['sub_category_id']) && !empty($_POST['sub_category_id'])) {
            // Update
            $id = $_POST['sub_category_id'];
            $stmt = $pdo->prepare("UPDATE sub_category SET Name = ?, CategoryID = ? WHERE SubCategoryID = ?");
            $stmt->execute([$name, $categoryId, $id]);
        } else {
            // Add
            $stmt = $pdo->prepare("INSERT INTO sub_category (Name, CategoryID, CreatedBy) VALUES (?, ?, ?)");
            $stmt->execute([$name, $categoryId, $_SESSION['admin_id']]);
        }
        echo "<script>window.location='sub_categories.php';</script>";
    } else {
        echo "<script>alert('يرجى اختيار القسم الرئيسي وإدخال اسم القسم الفرعي'); window.location='sub_categories.php';</script>";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM sub_category WHERE SubCategoryID = ?")->execute([$id]);
    echo "<script>window.location='sub_categories.php';</script>";
}

// Fetch Sub Categories with Parent Name
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) FROM sub_category";
$total_results = $pdo->query($countSql)->fetchColumn();
$total_pages = ceil($total_results / $limit);

$subCategories = $pdo->query("
    SELECT sc.*, c.Name as CategoryName 
    FROM sub_category sc
    JOIN category c ON sc.CategoryID = c.CategoryID
    ORDER BY c.Name ASC, sc.Name ASC
    LIMIT $limit OFFSET $offset
")->fetchAll();

// Fetch Main Categories for Select Dropdown
$categories = $pdo->query("SELECT * FROM category ORDER BY Name ASC")->fetchAll();

// Fetch Edit Data
$editSubCat = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM sub_category WHERE SubCategoryID = ?");
    $stmt->execute([$id]);
    $editSubCat = $stmt->fetch();
}
?>

<div class="container my-5">
    <h2 class="mb-4 text-primary fw-bold border-bottom pb-2">إدارة الأقسام الفرعية</h2>

    <div class="card shadow-sm border-0 mb-4 p-4 bg-light">
        <form method="POST" class="row g-3 align-items-end">
            <input type="hidden" name="sub_category_id" value="<?= $editSubCat['SubCategoryID'] ?? '' ?>">
            
            <div class="col-md-5 col-sm-12">
                <label class="form-label text-muted fw-bold">القسم الرئيسي</label>
                <select name="category_id" class="form-select shadow-sm" required>
                    <option value="" disabled <?= !isset($editSubCat) ? 'selected' : '' ?>>-- اختر القسم الرئيسي --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['CategoryID'] ?>" 
                            <?= (isset($editSubCat) && $editSubCat['CategoryID'] == $cat['CategoryID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4 col-sm-12">
                <label class="form-label text-muted fw-bold">القسم الفرعي</label>
                <input type="text" name="name" class="form-control shadow-sm" placeholder="اسم القسم الفرعي" required
                    value="<?= isset($editSubCat) ? htmlspecialchars($editSubCat['Name']) : '' ?>">
            </div>
            
            <div class="col-md-3 col-sm-12 d-flex gap-2">
                <button type="submit" name="save_subcat" class="btn btn-primary w-100 shadow-sm">
                    <i class="fas fa-save ms-1"></i> <?= isset($editSubCat) ? 'تحديث' : 'إضافة' ?>
                </button>
                <?php if (isset($editSubCat)): ?>
                    <a href="sub_categories.php" class="btn btn-outline-secondary w-100 shadow-sm">إلغاء</a>
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
                        <th>القسم الرئيسي</th>
                        <th>القسم الفرعي</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subCategories)): ?>
                        <tr><td colspan="4" class="text-center py-3 text-muted">لا توجد أقسام فرعية متاحة.</td></tr>
                    <?php else: ?>
                        <?php foreach ($subCategories as $subCat): ?>
                            <tr>
                                <td class="fw-bold text-muted">
                                    #<?= $subCat['SubCategoryID'] ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary rounded-pill px-3 py-2 shadow-sm">
                                        <?= htmlspecialchars($subCat['CategoryName']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-primary fs-5">
                                    <?= htmlspecialchars($subCat['Name']) ?>
                                </td>
                                <td>
                                    <a href="?edit=<?= $subCat['SubCategoryID'] ?>"
                                        class="btn btn-sm btn-outline-primary shadow-sm" title="تعديل">
                                        <i class="fas fa-edit ms-1"></i> تعديل
                                    </a>
                                    <a href="?delete=<?= $subCat['SubCategoryID'] ?>" onclick="return confirm('هل أنت متأكد من مسح هذا القسم الفرعي؟')"
                                        class="btn btn-sm btn-outline-danger shadow-sm ms-1" title="حذف">
                                        <i class="fas fa-trash ms-1"></i> حذف
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
