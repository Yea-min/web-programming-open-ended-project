<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'student_id' => '', 'department' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $old['full_name']  = trim($_POST['full_name'] ?? '');
    $old['email']      = trim($_POST['email'] ?? '');
    $old['student_id'] = trim($_POST['student_id'] ?? '');
    $old['department'] = trim($_POST['department'] ?? '');
    $old['phone']      = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password_confirm'] ?? '';

    if ($old['full_name'] === '' || mb_strlen($old['full_name']) < 2) {
        $errors[] = 'Please enter your full name.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['student_id'] === '') {
        $errors[] = 'Please enter your student/staff ID.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $password2) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists. Try logging in instead.';
        }
    }

    if (!$errors) {
        $initials = strtoupper(mb_substr($old['full_name'], 0, 1) .
                    mb_substr(strrchr(' ' . $old['full_name'], ' '), 1, 1));
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, student_id, password_hash, department, phone, avatar_initials)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $old['full_name'],
            $old['email'],
            $old['student_id'],
            password_hash($password, PASSWORD_DEFAULT),
            $old['department'] ?: null,
            $old['phone'] ?: null,
            $initials,
        ]);
        $_SESSION['user_id'] = (int)$pdo->lastInsertId();
        flash_set('success', 'Welcome to ReTool Campus! Your account has been created.');
        header('Location: index.php');
        exit;
    }
}

$page_title = 'Sign up';
include __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="card form-narrow">
        <h1>Create your account</h1>
        <p style="color:var(--muted);margin-top:0;">Join the campus circular economy — list items or borrow what you need.</p>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="field">
                <label for="full_name">Full name</label>
                <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required>
            </div>
            <div class="field">
                <label for="email">Campus email</label>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
            </div>
            <div class="form-row">
                <div class="field">
                    <label for="student_id">Student / Staff ID</label>
                    <input type="text" id="student_id" name="student_id" value="<?= e($old['student_id']) ?>" required>
                </div>
                <div class="field">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" value="<?= e($old['department']) ?>" placeholder="e.g. CSE">
                </div>
            </div>
            <div class="field">
                <label for="phone">Phone (optional)</label>
                <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>">
            </div>
            <div class="form-row">
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="8" required>
                    <div class="hint">At least 8 characters.</div>
                </div>
                <div class="field">
                    <label for="password_confirm">Confirm password</label>
                    <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create account</button>
        </form>
        <p style="text-align:center;margin-top:16px;font-size:.88rem;">
            Already have an account? <a href="login.php" style="font-weight:600;">Log in</a>
        </p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
