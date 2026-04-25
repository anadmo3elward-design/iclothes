<?php
require_once 'config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mobile_or_email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($mobile_or_email) || empty($password)) {
        $error = "يرجى تعبئة جميع الحقول.";
    } else {

    // Check Users table
    $stmt = $pdo->prepare("SELECT * FROM users WHERE Email = ? OR Mobile = ?");
    $stmt->execute([$mobile_or_email, $mobile_or_email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['Password'])) {
        // Update Last Login
        $update = $pdo->prepare("UPDATE users SET LastLogin = NOW() WHERE UserID = ?");
        $update->execute([$user['UserID']]);

        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['Name'];
        $_SESSION['role'] = 'user';
        header("Location: index.php");
        exit;
    } else {
        // Check Admins table
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE UserName = ?");
        $stmt->execute([$mobile_or_email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['Password'])) {
            // Update Last Login
            $update = $pdo->prepare("UPDATE admins SET LastLogin = NOW() WHERE AdminID = ?");
            $update->execute([$admin['AdminID']]);

            $_SESSION['admin_id'] = $admin['AdminID'];
            $_SESSION['admin_username'] = $admin['UserName'];
            $_SESSION['admin_logged_in'] = true;
            // Redirect to new Admin Dashboard
            header("Location: admin/dashboard.php");
            exit;
        } else {
            $error = "بيانات الدخول غير صحيحة.";
        }
    }
    }
}
require_once 'header.php';
?>

<div class="container my-5" style="max-width: 500px;">
    <div class="card shadow-sm p-4 p-md-5 border-0">
        <h2 class="text-center text-primary mb-4 fw-bold">تسجيل الدخول</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">البريد الإلكتروني أو الهاتف</label>
                <input type="text" name="email" class="form-control form-control-lg" required placeholder="أدخل البريد أو الهاتف">
            </div>

            <div class="mb-4">
                <label class="form-label">كلمة المرور</label>
                <input type="password" name="password" class="form-control form-control-lg" required placeholder="أدخل كلمة المرور">
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">دخول</button>
            <p class="text-center mt-3 text-muted">
                ليس لديك حساب؟ <a href="register.php" class="text-primary text-decoration-none fw-bold">سجل الآن</a>
            </p>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>