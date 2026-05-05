<?php
require_once '../config.php';
require_once 'header.php';

// Handle Add Coupon
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount = floatval($_POST['discount']);
    
    if (empty($code) || $discount <= 0 || $discount > 100) {
        $error = "الرجاء إدخال كود صالح ونسبة خصم بين 1 و 100.";
    } else {
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        
        if ($start_date && $end_date && $end_date < $start_date) {
            $error = "تاريخ الانتهاء لا يمكن أن يكون قبل تاريخ البداية.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO copouns (CouponCode, DiscountAmount, StartDate, EndDate, AddedBy) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$code, $discount, $start_date, $end_date, $_SESSION['admin_id']]);
                $success = "تم إضافة كود الخصم بنجاح.";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // UNIQUE constraint violation
                    $error = "هذا الكود موجود مسبقاً.";
                } else {
                    $error = "حدث خطأ غير متوقع.";
                }
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM copouns WHERE CouponID = ?");
    $stmt->execute([$_GET['delete']]);
    echo "<script>window.location='coupons.php';</script>";
    exit;
}

// Fetch Coupons
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) FROM copouns";
$total_results = $pdo->query($countSql)->fetchColumn();
$total_pages = ceil($total_results / $limit);

$stmt = $pdo->query("SELECT c.*, a.UserName FROM copouns c LEFT JOIN admins a ON c.AddedBy = a.AdminID ORDER BY c.CouponID DESC LIMIT $limit OFFSET $offset");
$coupons = $stmt->fetchAll();
?>

<div class="container my-5">
    <h2 class="mb-4 text-primary fw-bold border-bottom pb-2">إدارة كوبونات الخصم</h2>

    <div class="card shadow-sm border-0 p-4 mb-4 bg-light">
        <h3 class="fs-5 mb-3 text-dark fw-bold"><i class="fas fa-plus-circle text-primary ms-2"></i> إضافة كود خصم جديد</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle ms-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle ms-2"></i><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3 align-items-end">
            <div class="col-md-3 col-sm-12">
                <label class="form-label fw-bold">كود الخصم (أحرف إنجليزية/أرقام)</label>
                <input type="text" name="code" class="form-control" required placeholder="مثال: SUMMER20" style="text-transform: uppercase;" pattern="[A-Za-z0-9]+" title="أحرف وأرقام إنجليزية فقط">
            </div>
            <div class="col-md-2 col-sm-12">
                <label class="form-label fw-bold">نسبة الخصم (%)</label>
                <input type="number" name="discount" class="form-control" min="1" max="100" step="0.01" required placeholder="مثال 20">
            </div>
            <div class="col-md-2 col-sm-12">
                <label class="form-label fw-bold text-muted" style="font-size: 0.9em;">تاريخ البداية (اختياري)</label>
                <input type="date" name="start_date" class="form-control">
            </div>
            <div class="col-md-3 col-sm-12">
                <label class="form-label fw-bold text-muted" style="font-size: 0.9em;">تاريخ الانتهاء (اختياري)</label>
                <input type="date" name="end_date" class="form-control">
            </div>
            <div class="col-md-2 col-sm-12">
                <button type="submit" name="add_coupon" class="btn btn-primary w-100"><i class="fas fa-plus ms-1"></i> إضافة</button>
            </div>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>كود الخصم</th>
                        <th>نسبة الخصم</th>
                        <th>الصلاحية</th>
                        <th>أُضيف بواسطة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr><td colspan="5" class="text-center py-4">لا يوجد كوبونات مضافة حالياً.</td></tr>
                    <?php else: ?>
                        <?php foreach ($coupons as $c): ?>
                            <tr>
                                <td class="fw-bold text-muted"><?= $c['CouponID'] ?></td>
                                <td class="fw-bold text-primary" style="letter-spacing: 1px;"><?= htmlspecialchars($c['CouponCode']) ?></td>
                                <td>
                                    <span class="badge bg-danger fs-6 px-3 py-2">
                                        <?= floatval($c['DiscountAmount']) ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $validFrom = $c['StartDate'] ? date('Y-m-d', strtotime($c['StartDate'])) : 'دائماً';
                                    $validTo = $c['EndDate'] ? date('Y-m-d', strtotime($c['EndDate'])) : 'دائماً';
                                    $today = date('Y-m-d');
                                    $statusClass = 'bg-success';
                                    $statusText = 'فعال';
                                    if ($c['StartDate'] && $today < $c['StartDate']) {
                                        $statusClass = 'bg-warning text-dark';
                                        $statusText = 'لم يبدأ بعد';
                                    } elseif ($c['EndDate'] && $today > $c['EndDate']) {
                                        $statusClass = 'bg-secondary';
                                        $statusText = 'منتهي';
                                    }
                                    ?>
                                    <div class="small">من: <span dir="ltr"><?= $validFrom ?></span> <br>إلى: <span dir="ltr"><?= $validTo ?></span></div>
                                    <span class="badge <?= $statusClass ?> mt-1"><?= $statusText ?></span>
                                </td>
                                <td><?= htmlspecialchars($c['UserName'] ?: 'غير معروف') ?></td>
                                <td>
                                    <a href="coupons.php?delete=<?= $c['CouponID'] ?>" class="btn btn-sm btn-outline-danger shadow-sm" onclick="return confirm('تأكيد مسح الكود؟ لن يعود صالحاً للعملاء.');"><i class="fas fa-trash ms-1"></i> حذف</a>
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
