<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $errors[] = 'Incorrect email or password.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
        unset($_SESSION['redirect_after_login']);
        flash_set('success', 'Welcome back, ' . explode(' ', $user['full_name'])[0] . '!');
        header('Location: ' . $redirect);
        exit;
    }
}

$page_title = 'Log in';
include __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="card form-narrow">
        <h1>Welcome back</h1>
        <p style="color:var(--muted);margin-top:0;">Log in to borrow, lend, and track your requests.</p>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Log in</button>
        </form>
        <p style="text-align:center;margin-top:16px;font-size:.88rem;">
            New here? <a href="register.php" style="font-weight:600;">Create an account</a>
        </p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
