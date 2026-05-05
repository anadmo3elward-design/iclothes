<?php
require_once 'config.php';
require_once 'header.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>";
    exit;
}

$userId = $_SESSION['user_id'];

// Handle Coupon Application
$couponError = '';
$couponSuccess = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_coupon'])) {
    $code = strtoupper(htmlspecialchars(trim($_POST['coupon_code'])));
    
    if (empty($code)) {
        $couponError = "يرجى إدخال كود الخصم.";
    } elseif (!preg_match('/^[A-Z0-9]+$/', $code)) {
        $couponError = "صيغة الكود غير صحيحة.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM copouns WHERE CouponCode = ?");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

        if ($coupon) {
            $today = date('Y-m-d');
            if ($coupon['StartDate'] && $today < $coupon['StartDate']) {
                $couponError = "صلاحية هذا الكوبون لم تبدأ بعد.";
            } elseif ($coupon['EndDate'] && $today > $coupon['EndDate']) {
                $couponError = "هذا الكوبون منتهي الصلاحية.";
            } else {
                $_SESSION['checkout_coupon'] = [
                    'id' => $coupon['CouponID'],
                    'code' => $coupon['CouponCode'],
                    'discount' => $coupon['DiscountAmount'],
                    'start' => $coupon['StartDate'],
                    'end' => $coupon['EndDate']
                ];
                $couponSuccess = "تم تطبيق الخصم بنجاح!";
            }
        } else {
            $couponError = "كود الخصم غير صحيح.";
        }
    }
}

// Handle Cancel Coupon
if (isset($_GET['cancel_coupon'])) {
    unset($_SESSION['checkout_coupon']);
    header("Location: checkout.php");
    exit;
}

// Handle Order Confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_order'])) {
    try {
        $pdo->beginTransaction();

        // Get Cart Total
        $stmt = $pdo->prepare("
            SELECT c.*, i.Price 
            FROM cart c JOIN inventory i ON c.InventoryID = i.InventoryID 
            WHERE c.UserID = ?
        ");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll();

        if (empty($cartItems))
            throw new Exception("السلة فارغة");

        $totalPrice = 0;
        foreach ($cartItems as $c) {
            $totalPrice += $c['Amount'] * $c['Price'];
        }

        // Apply discount if exists
        $couponId = null;
        if (isset($_SESSION['checkout_coupon'])) {
            $today = date('Y-m-d');
            $start = $_SESSION['checkout_coupon']['start'] ?? null;
            $end = $_SESSION['checkout_coupon']['end'] ?? null;
            
            if (($start && $today < $start) || ($end && $today > $end)) {
                unset($_SESSION['checkout_coupon']);
                throw new Exception("الكوبون المطبق منتهي الصلاحية أو لم يعد صالحاً. يرجى المحاولة مرة أخرى.");
            }
            
            $couponId = $_SESSION['checkout_coupon']['id'];
            $discount = $_SESSION['checkout_coupon']['discount'];
            $totalPrice = $totalPrice - ($totalPrice * ($discount / 100));
        }

        // Create Order
        $stmt = $pdo->prepare("INSERT INTO `order` (UserID, TotalPrice, CouponID, OrderStatus) VALUES (?, ?, ?, 'Pending')");
        $stmt->execute([$userId, $totalPrice, $couponId]);
        $orderId = $pdo->lastInsertId();

        // Create Order Details
        foreach ($cartItems as $c) {
            $itemTotal = $c['Amount'] * $c['Price'];
            
            // Should itemTotal be discounted in details table? Usually yes, or keep original and Order Total reflects discount. Let's keep original for details.
            
            $stmt = $pdo->prepare("INSERT INTO order_detail (OrderID, InventoryID, Amount, ItemTotalPrice) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $c['InventoryID'], $c['Amount'], $itemTotal]);

            $stmt = $pdo->prepare("UPDATE inventory SET Amount = Amount - ? WHERE InventoryID = ?");
            $stmt->execute([$c['Amount'], $c['InventoryID']]);
        }

        // Clear Cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE UserID = ?");
        $stmt->execute([$userId]);

        $pdo->commit();
        $success = true;
        
        // Remove coupon after order placement
        unset($_SESSION['checkout_coupon']);

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Calculate preview totals for UI
if (!isset($success)) {
    $stmt = $pdo->prepare("SELECT c.*, i.Price FROM cart c JOIN inventory i ON c.InventoryID = i.InventoryID WHERE c.UserID = ?");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll();
    
    $baseTotal = 0;
    foreach ($cartItems as $c) {
        $baseTotal += $c['Amount'] * $c['Price'];
    }
    
    $finalTotal = $baseTotal;
    $discountVal = 0;

    if (isset($_SESSION['checkout_coupon'])) {
        $discountVal = $baseTotal * ($_SESSION['checkout_coupon']['discount'] / 100);
        $finalTotal = $baseTotal - $discountVal;
    }
}
?>

<div class="container my-5">
    <div class="card shadow-sm border-0 mx-auto" style="max-width: 650px;">
        <div class="card-body p-4 p-md-5">
        <?php if (isset($success)): ?>
            <div class="text-center py-4">
                <i class="fas fa-check-circle text-success mb-3" style="font-size: 4rem;"></i>
                <h2 class="text-success fw-bold mb-3">تم استلام طلبك بنجاح!</h2>
                <p class="fs-5 mb-4 mb-3">رقم طلبك هو <span class="badge bg-primary fs-5 px-3 py-2">#<?= $orderId ?></span></p>
                <a href="shop.php" class="btn btn-outline-primary btn-lg rounded-pill px-4">متابعة التسوق</a>
            </div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger text-center shadow-sm">
                <i class="fas fa-exclamation-triangle mx-2"></i> خطأ: <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif (empty($cartItems)): ?>
            <div class="alert alert-info text-center fs-5">
                السلة فارغة. <a href="shop.php" class="alert-link">ابدأ التسوق</a>
            </div>
        <?php else: ?>
            <h2 class="mb-4 text-center text-primary fw-bold border-bottom pb-3">تأكيد وإتمام الطلب</h2>
            
            <div class="card mb-4 bg-light border-0">
                <div class="card-body">
                    <p class="mb-3 fs-5"><i class="fas fa-user mx-2 text-muted"></i><strong>العميل (يشحن إلى):</strong> <span class="text-primary"><?= htmlspecialchars($_SESSION['username']) ?></span></p>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">المجموع الفرعي للإصناف:</span>
                        <span class="fw-semibold"><?= number_format($baseTotal, 2) ?> شيكل</span>
                    </div>

                <?php if (isset($_SESSION['checkout_coupon'])): ?>
                    <div class="d-flex justify-content-between mb-2 text-success fw-bold">
                        <span>تأثير الكوبون (="<?= htmlspecialchars($_SESSION['checkout_coupon']['code']) ?>", <?= floatval($_SESSION['checkout_coupon']['discount']) ?>%):</span>
                        <span>- <?= number_format($discountVal, 2) ?> شيكل</span>
                    </div>
                <?php endif; ?>

                    <hr>
                    <div class="d-flex justify-content-between fs-4 fw-bold text-primary">
                        <span>الإجمالي النهائي للدفع:</span>
                        <span><?= number_format($finalTotal, 2) ?> شيكل</span>
                    </div>
                </div>
            </div>

            <!-- Coupon Box -->
            <div class="mb-4">
                <?php if (!isset($_SESSION['checkout_coupon'])): ?>
                    <form method="POST" class="d-flex gap-2">
                        <input type="text" name="coupon_code" class="form-control" placeholder="أدخل كود الخصم (إن وجد)..." style="text-transform: uppercase;" pattern="[A-Za-z0-9]+" title="أحرف وأرقام إنجليزية فقط">
                        <button type="submit" name="apply_coupon" class="btn btn-outline-secondary px-4 text-nowrap">تطبيق كود الخصم</button>
                    </form>
                    <?php if ($couponError): ?><div class="text-danger small mt-2"><i class="fas fa-info-circle mx-1"></i><?= htmlspecialchars($couponError) ?></div><?php endif; ?>
                    <?php if ($couponSuccess): ?><div class="text-success small mt-2"><i class="fas fa-check-circle mx-1"></i><?= htmlspecialchars($couponSuccess) ?></div><?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-success d-flex justify-content-between align-items-center mb-0 p-3">
                        <span><i class="fas fa-check-circle mx-1"></i> تم تطبيق الكوبون <strong><?= htmlspecialchars($_SESSION['checkout_coupon']['code']) ?></strong> بنجاح.</span>
                        <a href="checkout.php?cancel_coupon=1" class="text-danger text-decoration-none fw-bold" title="إلغاء الكود">إلغاء <i class="fas fa-times"></i></a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Place Order -->
            <form method="POST">
                <input type="hidden" name="confirm_order" value="1">
                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow-sm">تأكيد الدفع وإتمام الطلب</button>
            </form>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>