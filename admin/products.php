<?php
require_once '../config.php';
require_once 'header.php';
require_once 'functions.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $catId = $_POST['category_id'];
    $subCatId = !empty($_POST['sub_category_id']) ? $_POST['sub_category_id'] : null;
    $desc = trim($_POST['description']);

    if(empty($name)) {
        echo "<script>alert('اسم المنتج مطلوب'); window.location='products.php';</script>";
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO item (Name, CategoryID, SubCategoryID, Description, CreatedBy) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $catId, $subCatId, $desc, $_SESSION['admin_id']]);
    $itemId = $pdo->lastInsertId();

    // Handle Images
    if (!empty($_FILES['images']['name'][0])) {
        $targetDir = "../images/";
        $thumbDir = "../thumbs/";

        // Ensure dirs exist (just in case)
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true))
                die("Failed to create images dir: " . print_r(error_get_last(), true));
        }
        if (!is_dir($thumbDir)) {
            if (!mkdir($thumbDir, 0777, true))
                die("Failed to create thumbs dir: " . print_r(error_get_last(), true));
        }

        if (!function_exists('gd_info'))
            die("GD Library is missing! Please enable php_gd in php.ini");

        // Loop through files
        $count = count($_FILES['images']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['images']['error'][$i] == 0) {
                $fileExtension = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                $fileName = generateUniqueFilename($fileExtension);

                $targetFilePath = $targetDir . $fileName;
                $thumbFilePath = $thumbDir . $fileName;
                $tmpName = $_FILES["images"]["tmp_name"][$i];

                if (move_uploaded_file($tmpName, $targetFilePath)) {
                    // Create Thumbnail
                    if (!createThumbnail($targetFilePath, $thumbFilePath, 300, 300)) {
                        // Attempt to copy original if thumbnail fails, or log error
                        // For now just continue, maybe the original is small enough
                    }

                    // Save to DB
                    $stmt = $pdo->prepare("INSERT INTO image (FileName, ItemID) VALUES (?, ?)");
                    $stmt->execute([$fileName, $itemId]);
                } else {
                    // Log error but don't die entire process?
                    error_log("Failed to move uploaded file: $tmpName to $targetFilePath");
                }
            }
        }
    }

    echo "<script>window.location='products.php';</script>";
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM item WHERE ItemID = ?")->execute([$id]);
    echo "<script>window.location='products.php';</script>";
}

$where = "1=1";
$params = [];

if (isset($_GET['category']) && $_GET['category'] != '') {
    $where .= " AND item.CategoryID = ?";
    $params[] = $_GET['category'];
}

if (isset($_GET['search']) && $_GET['search'] != '') {
    $where .= " AND item.Name LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
}

$sql = "SELECT item.*, category.Name as CatName, sub_category.Name as SubCatName, 
        (SELECT FileName FROM image WHERE image.ItemID = item.ItemID LIMIT 1) as MainImage 
        FROM item 
        LEFT JOIN category ON item.CategoryID = category.CategoryID 
        LEFT JOIN sub_category ON item.SubCategoryID = sub_category.SubCategoryID
        WHERE $where 
        ORDER BY ItemID DESC";

// Count total results for pagination
$countSql = "SELECT COUNT(*) FROM item 
             LEFT JOIN category ON item.CategoryID = category.CategoryID 
             LEFT JOIN sub_category ON item.SubCategoryID = sub_category.SubCategoryID 
             WHERE $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total_results = $countStmt->fetchColumn();

// Pagination variables
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_results / $limit);

// Append LIMIT & OFFSET
$sql .= " LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM category")->fetchAll();
$subCategories = $pdo->query("SELECT * FROM sub_category")->fetchAll();
?>

<div class="container my-5">
    <h2 class="mb-4 text-primary fw-bold border-bottom pb-2">إدارة المنتجات</h2>

    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-4">
        <button onclick="document.getElementById('addProductForm').style.display='block'" class="btn btn-primary shadow-sm px-4">
            <i class="fas fa-plus"></i> منتج جديد
        </button>

        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <select name="category" class="form-select" style="min-width: 150px;">
                <option value="">كل الأقسام</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['CategoryID'] ?>" <?= (isset($_GET['category']) && $_GET['category'] == $cat['CategoryID']) ? 'selected' : '' ?>>
                        <?= $cat['Name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="search" class="form-control" placeholder="بحث عن منتج..."
                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="min-width: 200px;">
            <button type="submit" class="btn btn-primary text-nowrap"><i class="fas fa-search me-1"></i> بحث</button>
        </form>
    </div>

    <div id="addProductForm" class="card shadow-sm border-0 p-4 mb-4 bg-light" style="display: none; border-top: 4px solid var(--bs-primary) !important;">
        <h3 class="mb-4 fw-bold text-primary">إضافة منتج جديد</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label fw-bold">اسم المنتج</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">القسم</label>
                <select name="category_id" class="form-select" id="categorySelect" required>
                    <option value="" disabled selected>-- اختر القسم --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['CategoryID'] ?>">
                            <?= htmlspecialchars($cat['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">القسم الفرعي <small class="text-muted">(اختياري)</small></label>
                <select name="sub_category_id" class="form-select" id="subCategorySelect">
                    <option value="">-- بدون قسم فرعي --</option>
                    <?php foreach ($subCategories as $sub): ?>
                        <option value="<?= $sub['SubCategoryID'] ?>" data-parent="<?= $sub['CategoryID'] ?>" style="display: none;">
                            <?= htmlspecialchars($sub['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">الوصف</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">الصور (يمكنك اختيار أكثر من صورة)</label>
                <input type="file" name="images[]" class="form-control" multiple accept="image/*">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="add_product" class="btn btn-primary px-4">حفظ</button>
                <button type="button" onclick="document.getElementById('addProductForm').style.display='none'"
                    class="btn btn-outline-secondary px-4">إلغاء</button>
            </div>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>الصورة</th>
                        <th>الاسم</th>
                        <th>القسم</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="fw-bold text-muted">
                                #<?= $item['ItemID'] ?>
                            </td>
                            <td>
                                <?php if ($item['MainImage']): ?>
                                    <img src="../thumbs/<?= $item['MainImage'] ?>"
                                        class="rounded shadow-sm" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-image fs-4"></i></span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-primary">
                                <?= htmlspecialchars($item['Name']) ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= htmlspecialchars($item['CatName']) ?></span>
                                <?php if($item['SubCatName']): ?>
                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($item['SubCatName']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_product.php?id=<?= $item['ItemID'] ?>"
                                    class="btn btn-sm btn-outline-primary" title="تعديل">
                                    <i class="fas fa-edit"></i> تعديل
                                </a>
                                <a href="?delete=<?= $item['ItemID'] ?>" onclick="return confirm('حذف هذا المنتج؟')"
                                    class="btn btn-sm btn-outline-danger ms-1" title="حذف">
                                    <i class="fas fa-trash"></i> حذف
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($items)): ?>
                        <tr><td colspan="5" class="text-center py-4">لا توجد منتجات مطابقة.</td></tr>
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

<script>
document.getElementById('categorySelect').addEventListener('change', function() {
    let parentId = this.value;
    let subOptions = document.querySelectorAll('#subCategorySelect option[data-parent]');
    subOptions.forEach(opt => {
        if (opt.getAttribute('data-parent') == parentId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
    document.getElementById('subCategorySelect').value = '';
});
</script>

<?php require_once '../footer.php'; ?>