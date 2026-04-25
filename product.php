<?php
require_once 'config.php';
require_once 'header.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM item WHERE ItemID = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    echo "<div class='container'><h2>المنتج غير موجود.</h2></div>";
    require_once 'footer.php';
    exit;
}

// Fetch available sizes and colors from inventory
$invStmt = $pdo->prepare("
    SELECT i.InventoryID, s.Name as SizeName, c.Name as ColorName, i.Price, i.Amount 
    FROM inventory i 
    JOIN size s ON i.SizeID = s.SizeID 
    JOIN color c ON i.ColorID = c.ColorID 
    WHERE i.ItemID = ? AND i.Amount > 0
");
$invStmt->execute([$id]);
$inventory = $invStmt->fetchAll();

// Image
// Images
$imgStmt = $pdo->prepare("SELECT FileName FROM image WHERE ItemID = ?");
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

// Define Main Image (First one or placeholder)
$mainImg = (!empty($images)) ? "images/" . htmlspecialchars($images[0]) : "assets/logo.png";


$price = $inventory[0]['Price'] ?? 0;
?>

<div class="container my-5">
    <div class="card shadow-sm border-0 p-lg-5 p-3">
        <div class="row g-5">
            <!-- Product Images -->
            <div class="col-md-6">
                <img id="mainImage" src="<?= $mainImg ?>"
                    class="img-fluid rounded shadow-sm w-100" style="object-fit: cover; max-height: 500px;"
                    alt="<?= htmlspecialchars($item['Name']) ?>">

                <!-- Thumbnails Gallery -->
                <?php if (count($images) > 1): ?>
                    <div class="d-flex gap-2 mt-3 overflow-auto pb-2">
                        <?php foreach ($images as $imgFile): ?>
                            <img src="thumbs/<?= htmlspecialchars($imgFile) ?>"
                                onclick="document.getElementById('mainImage').src='images/<?= htmlspecialchars($imgFile) ?>'"
                                class="rounded border"
                                style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; transition: 0.2s;"
                                onmouseover="this.classList.add('border-primary', 'border-2')"
                                onmouseout="this.classList.remove('border-primary', 'border-2')" alt="Thumbnail">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Details -->
            <div class="col-md-6">
                <h1 class="text-primary fw-bold mb-3"><?= htmlspecialchars($item['Name']) ?></h1>
                <h3 class="text-dark mb-4"><?= number_format($price, 2) ?> شيكل</h3>
                <p class="lead text-muted" style="line-height: 1.8;"><?= nl2br(htmlspecialchars($item['Description'])) ?></p>

                <?php if (!empty($item['Tags'])):
                    $tags = explode(',', $item['Tags']);
                    ?>
                    <div class="mt-4 mb-3">
                        <strong class="me-2">الوسوم:</strong>
                        <?php foreach ($tags as $tag):
                            $tag = trim($tag);
                            if (empty($tag))
                                continue;
                            ?>
                            <a href="shop.php?tag=<?= urlencode($tag) ?>" class="badge bg-secondary text-decoration-none p-2 fs-6 mx-1"><?= htmlspecialchars($tag) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <hr class="my-4">

                <form action="cart_action.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="item_id" value="<?= $id ?>">

                    <div class="mb-4">
                        <label class="form-label fw-bold">اختر الخيارات (الحجم - اللون):</label>
                        <select name="inventory_id" class="form-select form-select-lg" required>
                            <option value="">-- اختر --</option>
                            <?php foreach ($inventory as $inv): ?>
                                <option value="<?= $inv['InventoryID'] ?>">
                                    <?= $inv['SizeName'] ?> - <?= $inv['ColorName'] ?>
                                    (متوفر: <?= $inv['Amount'] ?>) - <?= $inv['Price'] ?> شيكل
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">الكمية:</label>
                        <input type="number" name="amount" value="1" min="1" max="10" class="form-control form-control-lg"
                            style="width: 120px;">
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold">
                        <i class="fas fa-shopping-cart ms-2"></i> أضف إلى السلة
                    </button>
                </form>

                <?php if (empty($inventory)): ?>
                    <div class="alert alert-danger mt-3 fw-bold"><i class="fas fa-exclamation-circle ms-1"></i> نفذت الكمية!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>