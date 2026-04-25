<?php
require_once 'config.php';
require_once 'header.php';
?>

<!-- Hero Section -->
<section class="hero text-white text-center py-5"
    style="background: url('assets/bg.jpg') no-repeat center center/cover; padding: 100px 0;">
    <div class="card bg-white bg-opacity-75 p-4 p-md-5 mx-auto shadow-sm" style="max-width: 800px; backdrop-filter: blur(8px);">
        <h1 class="display-4 fw-bold text-primary mb-3 text-shadow">مرحباً بكم في آي كلوز</h1>
        <p class="lead text-primary mb-4 fw-semibold">اكتشف أحدث صيحات الموضة. الجودة والأناقة معاً.</p>
        <a href="shop.php" class="btn btn-primary btn-lg rounded-pill px-4">تسوّق الآن</a>
    </div>
</section>

<div class="container my-5">
    <h2 class="text-center mb-5 text-primary fw-bold border-bottom pb-2">وصل حديثاً</h2>

    <div class="row g-4">
        <?php
        // Fetch 4 latest items
        $stmt = $pdo->query("SELECT * FROM item ORDER BY ItemID DESC LIMIT 6");
        while ($item = $stmt->fetch()):
            // Get one image for the item, or a placeholder
            $imgStmt = $pdo->prepare("SELECT FileName FROM image WHERE ItemID = ? LIMIT 1");
            $imgStmt->execute([$item['ItemID']]);
            $img = $imgStmt->fetchColumn();
            $imgDisplay = $img ? "thumbs/" . htmlspecialchars($img) : "assets/logo.png";

            // Get Min Price for this item from inventory
            $priceStmt = $pdo->prepare("SELECT MIN(Price) FROM inventory WHERE ItemID = ?");
            $priceStmt->execute([$item['ItemID']]);
            $minPrice = $priceStmt->fetchColumn();
            ?>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 shadow-sm border-0 product-card">
                    <img src="<?= $imgDisplay ?>" alt="<?= htmlspecialchars($item['Name']) ?>" class="card-img-top" style="height: 250px; object-fit: cover;">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= htmlspecialchars($item['Name']) ?></h5>
                        <p class="card-text fw-bold text-primary"><?= number_format($minPrice, 2) ?> شيكل</p>
                        <a href="product.php?id=<?= $item['ItemID'] ?>" class="btn btn-outline-primary rounded-pill w-100 mt-2">التفاصيل</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>