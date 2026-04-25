<?php
require_once '../config.php';
require_once 'header.php';

// Handle Add Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "يرجى تعبئة جميع الحقول المطلوبة.";
    } elseif (strlen($username) < 3) {
        $error = "اسم المستخدم يجب أن يكون 3 أحرف على الأقل.";
    } elseif (strlen($password) < 6) {
        $error = "يجب أن تتكون كلمة المرور من 6 أحرف على الأقل.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO admins (UserName, Password) VALUES (?, ?)");
            $stmt->execute([$username, $hashed]);
            echo "<script>window.location='admins.php';</script>";
            exit;
        } catch(PDOException $e) {
            $error = "اسم المستخدم موجود مسبقاً.";
        }
    }
}

// Handle Delete Admin
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // Prevent deleting self
    if ($_GET['delete'] != $_SESSION['admin_id']) {
        $stmt = $pdo->prepare("DELETE FROM admins WHERE AdminID = ?");
        $stmt->execute([$_GET['delete']]);
    }
    echo "<script>window.location='admins.php';</script>";
    exit;
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) FROM admins";
$total_results = $pdo->query($countSql)->fetchColumn();
$total_pages = ceil($total_results / $limit);

$stmt = $pdo->query("SELECT * FROM admins ORDER BY AdminID ASC LIMIT $limit OFFSET $offset");
$admins = $stmt->fetchAll();
?>

<div class="container my-5">
    <h2 class="mb-4 text-primary fw-bold border-bottom pb-2">إدارة المشرفين (الادارة)</h2>

    <div class="card shadow-sm border-0 p-4 mb-4 bg-light">
        <h3 class="fs-5 mb-3 text-dark fw-bold"><i class="fas fa-user-shield text-primary ms-2"></i> إضافة مشرف جديد</h3>
        <?php if(isset($error)): ?><div class="alert alert-danger"><i class="fas fa-exclamation-triangle ms-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" class="row g-3 align-items-end">
            <div class="col-md-5 col-sm-12">
                <label class="form-label fw-bold">اسم المستخدم (UserName)</label>
                <input type="text" name="username" class="form-control" required minlength="3" pattern="[A-Za-z0-9_]+" title="أحرف إنجليزية وأرقام فقط">
            </div>
            <div class="col-md-5 col-sm-12">
                <label class="form-label fw-bold">كلمة المرور</label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <div class="col-md-2 col-sm-12">
                <button type="submit" name="add_admin" class="btn btn-primary w-100 shadow-sm">إضافة <i class="fas fa-plus ms-1"></i></button>
            </div>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>اسم المستخدم</th>
                        <th>آخر دخول</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $a): ?>
                        <tr>
                            <td class="fw-bold text-muted"><?= $a['AdminID'] ?></td>
                            <td class="fw-bold text-primary">
                                <?= htmlspecialchars($a['UserName']) ?>
                                <?php if(isset($_SESSION['admin_id']) && $a['AdminID'] == $_SESSION['admin_id']) echo "<span class='badge bg-info text-dark ms-2'>أنت</span>"; ?>
                            </td>
                            <td><?= $a['LastLogin'] ?: '<span class="text-muted">لم يسجل دخول</span>' ?></td>
                            <td>
                                <?php if (isset($_SESSION['admin_id']) && $a['AdminID'] != $_SESSION['admin_id']): ?>
                                    <a href="admins.php?delete=<?= $a['AdminID'] ?>" class="btn btn-sm btn-outline-danger shadow-sm" onclick="return confirm('حذف المشرف؟ لا يمكن التراجع.');"><i class="fas fa-trash ms-1"></i> حذف</a>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-ban ms-1"></i> لا يمكن الحذف</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
