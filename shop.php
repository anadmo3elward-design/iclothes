<?php
require_once 'config.php';
require_once 'header.php';
?>

<div class="container my-5">
    <h2 class="mb-4 text-primary fw-bold border-bottom pb-2">جميع المنتجات</h2>

    <div class="row g-4">
        <?php
        $where = "1=1";
        $params = [];

        if (isset($_GET['tag']) && !empty($_GET['tag'])) {
            $where .= " AND Tags LIKE ?";
            $params[] = "%" . $_GET['tag'] . "%";
            echo "<div class='col-12'><div class='alert alert-info d-flex justify-content-between align-items-center'><span>عرض المنتجات بالوسم: <strong>" . htmlspecialchars($_GET['tag']) . "</strong></span> <a href='shop.php' class='btn btn-sm btn-outline-danger'>(إلغاء)</a></div></div>";
        }

        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $where .= " AND CategoryID = ?";
            $params[] = $_GET['category'];
            
            // Get category name for display
            $catStmt = $pdo->prepare("SELECT Name FROM category WHERE CategoryID = ?");
            $catStmt->execute([$_GET['category']]);
            $catName = $catStmt->fetchColumn();
            
            if ($catName) {
                echo "<div class='col-12'><div class='alert alert-info d-flex justify-content-between align-items-center'><span>عرض المنتجات للقسم الرئيسي: <strong>" . htmlspecialchars($catName) . "</strong></span> <a href='shop.php' class='btn btn-sm btn-outline-danger'>(إلغاء)</a></div></div>";
            }
        }

        if (isset($_GET['sub_category']) && !empty($_GET['sub_category'])) {
            $where .= " AND SubCategoryID = ?";
            $params[] = $_GET['sub_category'];
            
            // Get subcategory name for display
            $subCatStmt = $pdo->prepare("SELECT Name FROM sub_category WHERE SubCategoryID = ?");
            $subCatStmt->execute([$_GET['sub_category']]);
            $subCatName = $subCatStmt->fetchColumn();
            
            if ($subCatName) {
                echo "<div class='col-12'><div class='alert alert-info d-flex justify-content-between align-items-center'><span>عرض المنتجات للقسم الفرعي: <strong>" . htmlspecialchars($subCatName) . "</strong></span> <a href='shop.php' class='btn btn-sm btn-outline-danger'>(إلغاء)</a></div></div>";
            }
        }

        $stmt = $pdo->prepare("SELECT * FROM item WHERE $where ORDER BY ItemID DESC");
        $stmt->execute($params);
        while ($item = $stmt->fetch()):
            // Get one image
            $imgStmt = $pdo->prepare("SELECT FileName FROM image WHERE ItemID = ? LIMIT 1");
            $imgStmt->execute([$item['ItemID']]);
            $img = $imgStmt->fetchColumn();
            $imgDisplay = $img ? "thumbs/" . htmlspecialchars($img) : "assets/logo.png";

            // Get Min Price
            $priceStmt = $pdo->prepare("SELECT MIN(Price) FROM inventory WHERE ItemID = ?");
            $priceStmt->execute([$item['ItemID']]);
            $minPrice = $priceStmt->fetchColumn();
            $displayPrice = $minPrice ? number_format($minPrice, 2) : "غير متوفر";

            // Get Total Quantity
            $qtyStmt = $pdo->prepare("SELECT SUM(Amount) FROM inventory WHERE ItemID = ?");
            $qtyStmt->execute([$item['ItemID']]);
            $totalQty = $qtyStmt->fetchColumn() ?: 0;
            ?>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 shadow-sm border-0 product-card position-relative">
                    <?php if ($totalQty <= 0): ?>
                        <span class="badge bg-danger position-absolute top-0 start-0 m-2 px-3 py-2 fs-6 shadow-sm z-1">نفدت الكمية</span>
                    <?php endif; ?>
                    <img src="<?= $imgDisplay ?>" alt="<?= htmlspecialchars($item['Name']) ?>" class="card-img-top" style="height: 280px; object-fit: cover;">
                    <div class="card-body d-flex flex-column text-center">
                        <h5 class="card-title"><?= htmlspecialchars($item['Name']) ?></h5>
                        <p class="card-text fw-bold text-primary mb-2"><?= $displayPrice ?> شيكل</p>
                        <p class="card-text text-muted small mb-3 flex-grow-1"><?= htmlspecialchars(substr($item['Description'], 0, 50)) ?>...</p>
                        <a href="product.php?id=<?= $item['ItemID'] ?>" class="btn btn-outline-primary mt-auto rounded-pill w-100">التفاصيل</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if ($stmt->rowCount() == 0): ?>
        <div class="col-12 mt-4">
            <div class="alert alert-warning text-center">لم يتم العثور على منتجات. قم بتشغيل <a href="db_seed.php" class="alert-link">db_seed.php</a> لإضافة بيانات تجريبية.</div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>