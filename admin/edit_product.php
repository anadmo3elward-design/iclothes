<?php
require_once '../config.php';
require_once 'header.php';
require_once 'functions.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<script>window.location='products.php';</script>";
    exit;
}

// Fetch Item
$stmt = $pdo->prepare("SELECT * FROM item WHERE ItemID = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    echo "Product not found!";
    exit;
}

// Fetch Images
$imgStmt = $pdo->prepare("SELECT * FROM image WHERE ItemID = ?");
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll();

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $name = trim($_POST['name']);
    $catId = $_POST['category_id'];
    $subCatId = !empty($_POST['sub_category_id']) ? $_POST['sub_category_id'] : null;
    $desc = trim($_POST['description']);
    $tags = trim($_POST['tags']);

    if(empty($name)){
        echo "<script>alert('اسم المنتج مطلوب'); window.location='edit_product.php?id=$id';</script>";
        exit;
    }

    $stmt = $pdo->prepare("UPDATE item SET Name = ?, CategoryID = ?, SubCategoryID = ?, Description = ?, Tags = ? WHERE ItemID = ?");
    $stmt->execute([$name, $catId, $subCatId, $desc, $tags, $id]);

    // Handle New Images
    if (!empty($_FILES['images']['name'][0])) {
        $targetDir = "../images/";
        $thumbDir = "../thumbs/";

        if (!is_dir($targetDir))
            mkdir($targetDir, 0777, true);
        if (!is_dir($thumbDir))
            mkdir($thumbDir, 0777, true);
        if (!function_exists('gd_info'))
            die("GD Library missing");

        $count = count($_FILES['images']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['images']['error'][$i] == 0) {
                $fileExtension = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                $fileName = generateUniqueFilename($fileExtension);

                $targetFilePath = $targetDir . $fileName;
                $thumbFilePath = $thumbDir . $fileName;
                $tmpName = $_FILES["images"]["tmp_name"][$i];

                if (move_uploaded_file($tmpName, $targetFilePath)) {
                    createThumbnail($targetFilePath, $thumbFilePath, 300, 300);
                    $stmt = $pdo->prepare("INSERT INTO image (FileName, ItemID) VALUES (?, ?)");
                    $stmt->execute([$fileName, $id]);
                }
            }
        }
    }

    echo "<script>window.location='edit_product.php?id=$id';</script>";
}

// Handle Delete Image
if (isset($_GET['delete_img'])) {
    $imgId = $_GET['delete_img'];
    // Get filename
    $stmt = $pdo->prepare("SELECT FileName FROM image WHERE ImageID = ?");
    $stmt->execute([$imgId]);
    $imgName = $stmt->fetchColumn();

    if ($imgName) {
        $pdo->prepare("DELETE FROM image WHERE ImageID = ?")->execute([$imgId]);
        if (file_exists("../images/$imgName"))
            unlink("../images/$imgName");
        if (file_exists("../thumbs/$imgName"))
            unlink("../thumbs/$imgName");
    }
    echo "<script>window.location='edit_product.php?id=$id';</script>";
}

// Handle Add Inventory
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_inventory'])) {
    $sizeId = $_POST['size_id'];
    $colorId = $_POST['color_id'];
    $amount = (int) $_POST['amount'];
    $price = (float) $_POST['price'];

    if ($amount >= 0 && $price >= 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO inventory (ItemID, SizeID, ColorID, Amount, Price, AddedBy) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $sizeId, $colorId, $amount, $price, $_SESSION['admin_id']]);
            echo "<script>window.location='edit_product.php?id=$id';</script>";
        } catch (PDOException $e) {
            echo "<script>alert('خطأ: ربما هذا المزيج من الحجم واللون موجود مسبقاً.');</script>";
        }
    } else {
        echo "<script>alert('الكمية والسعر يجب أن تكون صفر أو أكثر');</script>";
    }
}

// Handle Update Inventory
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_inventory'])) {
    $invId = $_POST['inventory_id'];
    $amount = (int) $_POST['amount'];
    $price = (float) $_POST['price'];

    if ($amount >= 0 && $price >= 0) {
        $stmt = $pdo->prepare("UPDATE inventory SET Amount = ?, Price = ? WHERE InventoryID = ?");
        $stmt->execute([$amount, $price, $invId]);
        echo "<script>window.location='edit_product.php?id=$id';</script>";
    } else {
        echo "<script>alert('الكمية والسعر يجب أن تكون صفر أو أكثر');</script>";
    }
}

// Handle Delete Inventory
if (isset($_GET['delete_inventory'])) {
    $invId = $_GET['delete_inventory'];
    $pdo->prepare("DELETE FROM inventory WHERE InventoryID = ?")->execute([$invId]);
    echo "<script>window.location='edit_product.php?id=$id';</script>";
}

$categories = $pdo->query("SELECT * FROM category")->fetchAll();
$subCategories = $pdo->query("SELECT * FROM sub_category")->fetchAll();
?>

<div class="container my-5">
    <h2 class="mb-4 text-primary fw-bold border-bottom pb-2">تعديل المنتج:
        <?= htmlspecialchars($item['Name']) ?>
    </h2>

    <div class="card shadow-sm border-0 p-4 p-md-5 mb-4 bg-light">
        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-6 col-sm-12">
                <label class="form-label fw-bold">اسم المنتج</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['Name']) ?>"
                    required>
            </div>
            <div class="col-md-6 col-sm-12">
                <label class="form-label fw-bold">القسم الرئيسي</label>
                <select name="category_id" class="form-select" id="categorySelect" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['CategoryID'] ?>" <?= $cat['CategoryID'] == $item['CategoryID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 col-sm-12">
                <label class="form-label fw-bold">القسم الفرعي <small class="text-muted">(اختياري)</small></label>
                <select name="sub_category_id" class="form-select" id="subCategorySelect">
                    <option value="">-- بدون قسم فرعي --</option>
                    <?php foreach ($subCategories as $sub): ?>
                        <option value="<?= $sub['SubCategoryID'] ?>" data-parent="<?= $sub['CategoryID'] ?>" 
                            <?= ($sub['CategoryID'] != $item['CategoryID']) ? 'style="display:none;"' : '' ?>
                            <?= ($sub['SubCategoryID'] == $item['SubCategoryID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sub['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold">الوصف</label>
                <textarea name="description" class="form-control"
                    rows="3"><?= htmlspecialchars($item['Description']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold">الوسوم / التاجات (افصل بينها بفاصلة)</label>
                <input type="text" name="tags" class="form-control" value="<?= htmlspecialchars($item['Tags'] ?? '') ?>"
                    placeholder="مثال: صيفي, قطن, جديد">
            </div>

            <div class="col-12 mb-3">
                <label class="form-label fw-bold">إضافة صور جديدة</label>
                <input type="file" name="images[]" class="form-control" multiple accept="image/*">
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" name="update_product" class="btn btn-primary px-4">حفظ التعديلات</button>
                <a href="products.php" class="btn btn-outline-secondary px-4">عودة</a>
            </div>
        </form>
    </div>

    <!-- Existing Images -->
    <div class="card shadow-sm border-0 p-4 mb-4">
        <h3 class="mb-4 text-primary fw-bold border-bottom pb-2">الصور الحالية</h3>
        <div class="row g-3">
            <?php foreach ($images as $img): ?>
                <div class="col-auto">
                    <div class="position-relative border rounded p-2 text-center bg-light">
                        <img src="../thumbs/<?= $img['FileName'] ?>" class="rounded shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                        <a href="?id=<?= $id ?>&delete_img=<?= $img['ImageID'] ?>" onclick="return confirm('حذف هذه الصورة؟')"
                            class="d-block mt-2 text-danger text-decoration-none fw-bold" style="font-size: 0.9rem;">
                            <i class="fas fa-trash"></i> حذف
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($images))
                echo "<p class='text-muted mt-2'>لا توجد صور.</p>"; ?>
        </div>
    </div>
    <!-- Stock Management -->
    <div class="card shadow-sm border-0 p-4 mb-5">
        <h3 class="mb-4 text-primary fw-bold border-bottom pb-2">إدارة المخزون</h3>

        <!-- Add New Stock -->
        <form method="POST" class="row g-3 align-items-end mb-4 pb-4 border-bottom">
            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-bold">الحجم</label>
                <select name="size_id" class="form-select" required>
                    <?php
                    $sizes = $pdo->query("SELECT * FROM size")->fetchAll();
                    foreach ($sizes as $s): ?>
                        <option value="<?= $s['SizeID'] ?>"><?= $s['Name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-bold">اللون</label>
                <select name="color_id" class="form-select" required>
                    <?php
                    $colors = $pdo->query("SELECT * FROM color")->fetchAll();
                    foreach ($colors as $c): ?>
                        <option value="<?= $c['ColorID'] ?>"><?= $c['Name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label fw-bold">الكمية</label>
                <input type="number" name="amount" class="form-control" required min="1">
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label fw-bold">السعر</label>
                <input type="number" step="0.01" name="price" class="form-control" required min="0">
            </div>
            <div class="col-md-2 col-sm-12">
                <button type="submit" name="add_inventory" class="btn btn-success w-100 shadow-sm"><i class="fas fa-plus me-1"></i>
                    إضافة</button>
            </div>
        </form>

        <!-- List/Edit Existing Stock -->
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الحجم - اللون</th>
                        <th>الكمية</th>
                        <th>السعر</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $inventory = $pdo->prepare("
                        SELECT inv.*, s.Name as Size, c.Name as Color
                        FROM inventory inv
                        JOIN size s ON inv.SizeID = s.SizeID
                        JOIN color c ON inv.ColorID = c.ColorID
                        WHERE inv.ItemID = ?
                        ORDER BY inv.InventoryID DESC
                    ");
                    $inventory->execute([$id]);
                    $stockItems = $inventory->fetchAll();

                    foreach ($stockItems as $stock): ?>
                        <tr>
                            <form method="POST">
                                <input type="hidden" name="inventory_id" value="<?= $stock['InventoryID'] ?>">
                                <td>
                                    <span class="badge bg-secondary fs-6"><?= $stock['Size'] ?></span> - 
                                    <span class="badge bg-secondary fs-6"><?= $stock['Color'] ?></span>
                                </td>
                                <td>
                                    <input type="number" name="amount" value="<?= $stock['Amount'] ?>" class="form-control form-control-sm border-primary"
                                        style="max-width: 100px;" min="0">
                                </td>
                                <td>
                                    <div class="input-group input-group-sm" style="max-width: 150px;">
                                        <input type="number" step="0.01" name="price" value="<?= $stock['Price'] ?>"
                                            class="form-control border-success" required min="0">
                                        <span class="input-group-text">شيكل</span>
                                    </div>
                                </td>
                                <td>
                                    <button type="submit" name="update_inventory"
                                        class="btn btn-sm btn-outline-primary"
                                        title="تحديث">
                                        <i class="fas fa-save"></i>
                                    </button>
                                    <a href="?id=<?= $id ?>&delete_inventory=<?= $stock['InventoryID'] ?>"
                                        onclick="return confirm('حذف هذا المخزون؟')" class="btn btn-sm btn-outline-danger ms-1"
                                        title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($stockItems))
                        echo "<tr><td colspan='4' class='text-center py-4 text-muted'>لا يوجد مخزون مضاف لهذا المنتج.</td></tr>"; ?>
                </tbody>
            </table>
        </div>
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
    
    // Only reset if current selected sub_category doesn't belong to new parent
    let currentSub = document.getElementById('subCategorySelect');
    let selectedOpt = currentSub.options[currentSub.selectedIndex];
    if (selectedOpt && selectedOpt.getAttribute('data-parent') != parentId) {
        currentSub.value = '';
    }
});
</script>

<?php require_once '../footer.php'; ?>