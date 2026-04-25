<?php
require_once 'config.php';
require_once 'header.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>";
    exit;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (strlen($new) < 6) {
        $error = "كلمة المرور الجديدة يجب أن تتكون من 6 أحرف على الأقل.";
    } elseif ($new !== $confirm) {
        $error = "كلمة المرور الجديدة غير متطابقة.";
    } else {
        $stmt = $pdo->prepare("SELECT Password FROM users WHERE UserID = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $hash = $stmt->fetchColumn();

        if (password_verify($current, $hash)) {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET Password = ? WHERE UserID = ?");
            $update->execute([$newHash, $_SESSION['user_id']]);
            $success = "تم تغيير كلمة المرور بنجاح.";
        } else {
            $error = "كلمة المرور الحالية غير صحيحة.";
        }
    }
}
?>

<div class="container my-5" style="max-width: 500px;">
    <div class="card shadow-sm p-4 p-md-5 mt-4 border-0">
        <h2 class="text-center text-primary mb-4 fw-bold">تغيير كلمة المرور</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success text-center fw-bold"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">كلمة المرور الحالية</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">كلمة المرور الجديدة</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>
            <div class="mb-4">
                <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">تحديث كلمة المرور</button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>
