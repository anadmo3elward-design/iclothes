<?php
require_once 'config.php';

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "البريد الإلكتروني غير صالح.";
    } elseif (empty($name) || empty($password)) {
        $error = "جميع الحقول المطلوبة يجب تعبئتها.";
    } elseif (strlen($password) < 6) {
        $error = "يجب أن تتكون كلمة المرور من 6 أحرف على الأقل.";
    } elseif ($password !== $confirm_password) {
        $error = "كلمات المرور غير متطابقة.";
    } elseif (!empty($mobile) && !preg_match("/^[0-9]+$/", $mobile)) {
        $error = "رقم الهاتف يجب أن يحتوي على أرقام فقط.";
    } else {
        // Check exist
        $stmt = $pdo->prepare("SELECT UserID FROM users WHERE Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "البريد الإلكتروني مسجل مسبقاً.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (Name, UserName, Email, Mobile, Password) VALUES (?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$name, $email, $email, $mobile, $hashed]);
                header("Location: login.php?registered=1");
                exit;
            } catch (PDOException $e) {
                $error = "فشل التسجيل: " . $e->getMessage();
            }
        }
    }
}
require_once 'header.php';
?>

<div class="container my-5" style="max-width: 500px;">
    <div class="card shadow-sm p-4 p-md-5 border-0">
        <h2 class="text-center text-primary mb-4 fw-bold">إنشاء حساب جديد</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">الاسم الكامل</label>
                <input type="text" name="name" class="form-control" required minlength="3">
            </div>

            <div class="mb-3">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">رقم الهاتف</label>
                <input type="tel" name="mobile" class="form-control" pattern="[0-9]{8,15}" title="أدخل رقم هاتف صحيح (أرقام فقط)">
            </div>

            <div class="mb-3">
                <label class="form-label">كلمة المرور</label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>

            <div class="mb-4">
                <label class="form-label">تأكيد كلمة المرور</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="6">
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">تسجيل</button>
            <p class="text-center mt-3 text-muted">
                لديك حساب بالفعل؟ <a href="login.php" class="text-primary text-decoration-none fw-bold">دخول</a>
            </p>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>