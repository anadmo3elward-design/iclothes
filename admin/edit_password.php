<?php
require_once '../config.php';
require_once 'header.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $error = "كلمة المرور الجديدة غير متطابقة.";
    } else {
        $stmt = $pdo->prepare("SELECT Password FROM admins WHERE AdminID = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $hash = $stmt->fetchColumn();

        if (password_verify($current, $hash)) {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE admins SET Password = ? WHERE AdminID = ?");
            $update->execute([$newHash, $_SESSION['admin_id']]);
            $success = "تم تغيير كلمة المرور بنجاح.";
        } else {
            $error = "كلمة المرور الحالية غير صحيحة.";
        }
    }
}
?>

<div class="container" style="max-width: 500px;">
    <div class="glass-card" style="padding: 2rem; margin-top: 2rem;">
        <h2 style="text-align: center; color: var(--primary-color);">تغيير كلمة المرور الخاصة بك</h2>
        
        <?php if ($error): ?>
            <p style="color: red; text-align: center;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color: green; text-align: center; font-weight: bold;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>كلمة المرور الحالية</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>كلمة المرور الجديدة</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>
            <div class="form-group">
                <label>تأكيد كلمة المرور الجديدة</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn" style="width: 100%;">تحديث كلمة المرور</button>
        </form>
    </div>
</div>

<?php require_once '../footer.php'; ?>
