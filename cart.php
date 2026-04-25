<?php
require_once 'config.php';
require_once 'header.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>";
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT c.CartID, c.Amount, i.Price, item.Name as ItemName, size.Name as SizeName, color.Name as ColorName 
    FROM cart c
    JOIN inventory i ON c.InventoryID = i.InventoryID
    JOIN item ON i.ItemID = item.ItemID
    JOIN size ON i.SizeID = size.SizeID
    JOIN color ON i.ColorID = color.ColorID
    WHERE c.UserID = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$total = 0;
?>

<div class="container my-5">
    <h2 class="text-primary fw-bold mb-4 border-bottom pb-2">سلة المشتريات</h2>

    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info py-4 text-center fs-5">
            <i class="fas fa-shopping-cart mx-2"></i> السلة فارغة. <a href="shop.php" class="alert-link">ابدأ التسوق</a>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th>المنتج</th>
                            <th>الحجم</th>
                            <th>اللون</th>
                            <th>السعر</th>
                            <th>الكمية</th>
                            <th>المجموع</th>
                            <th class="text-center">إجراء</th>
                        </tr>
                    </thead>
                <tbody>
                    <?php foreach ($cartItems as $item):
                        $subtotal = $item['Price'] * $item['Amount'];
                        $total += $subtotal;
                        ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= htmlspecialchars($item['ItemName']) ?></td>
                            <td><span class="badge bg-secondary"><?= $item['SizeName'] ?></span></td>
                            <td><span class="badge bg-secondary"><?= $item['ColorName'] ?></span></td>
                            <td><?= $item['Price'] ?> شيكل</td>
                            <td><?= $item['Amount'] ?></td>
                            <td class="fw-bold"><?= number_format($subtotal, 2) ?> شيكل</td>
                            <td class="text-center">
                                <form action="cart_action.php" method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_id" value="<?= $item['CartID'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            
            <div class="card-body bg-light border-top d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                <h3 class="mb-0 text-dark fw-bold">المجموع الكلي: <span class="text-primary"><?= number_format($total, 2) ?> شيكل</span></h3>
                <a href="checkout.php" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">
                    إتمام الطلب <i class="fas fa-arrow-left ms-2"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>