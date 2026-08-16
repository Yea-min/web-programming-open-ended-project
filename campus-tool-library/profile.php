<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $form = $_POST['form'] ?? '';

    if ($form === 'details') {
        $full_name  = trim($_POST['full_name'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');

        if (mb_strlen($full_name) < 2) $errors[] = 'Please enter your full name.';

        if (!$errors) {
            $pdo->prepare('UPDATE users SET full_name=?, department=?, phone=? WHERE id=?')
                ->execute([$full_name, $department ?: null, $phone ?: null, $user['id']]);
            flash_set('success', 'Profile updated.');
            header('Location: profile.php');
            exit;
        }
    }

    if ($form === 'password') {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $new2     = $_POST['new_password_confirm'] ?? '';

        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }
        if (strlen($new) < 8) $errors[] = 'New password must be at least 8 characters.';
        if ($new !== $new2)   $errors[] = 'New passwords do not match.';

        if (!$errors) {
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash_set('success', 'Password changed successfully.');
            header('Location: profile.php');
            exit;
        }
    }
    $user = current_user();
}

// Simple stats
$listedCount = $pdo->prepare('SELECT COUNT(*) FROM items WHERE owner_id=?');
$listedCount->execute([$user['id']]);
$listedCount = $listedCount->fetchColumn();

$borrowedCount = $pdo->prepare("SELECT COUNT(*) FROM borrow_requests WHERE borrower_id=? AND status='returned'");
$borrowedCount->execute([$user['id']]);
$borrowedCount = $borrowedCount->fetchColumn();

$page_title = 'Profile';
include __DIR__ . '/includes/header.php';
?>
<div class="container section" style="max-width:640px;">
    <h2>Your profile</h2>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <div class="stat-strip" style="grid-template-columns:repeat(3,1fr);margin-bottom:26px;">
        <div class="stat-card"><div class="num"><?= (int)$listedCount ?></div><div class="label">Items listed</div></div>
        <div class="stat-card"><div class="num"><?= (int)$borrowedCount ?></div><div class="label">Loans completed</div></div>
        <div class="stat-card"><div class="num"><?= (int)$user['reputation_pts'] ?></div><div class="label">Reputation points</div></div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <h3>Account details</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form" value="details">
            <div class="field">
                <label>Email</label>
                <input type="text" value="<?= e($user['email']) ?>" disabled>
                <div class="hint">Email cannot be changed. Contact an admin if needed.</div>
            </div>
            <div class="field">
                <label for="full_name">Full name</label>
                <input type="text" id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" required>
            </div>
            <div class="form-row">
                <div class="field">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" value="<?= e($user['department']) ?>">
                </div>
                <div class="field">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($user['phone']) ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save changes</button>
        </form>
    </div>

    <div class="card">
        <h3>Change password</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form" value="password">
            <div class="field">
                <label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-row">
                <div class="field">
                    <label for="new_password">New password</label>
                    <input type="password" id="new_password" name="new_password" minlength="8" required>
                </div>
                <div class="field">
                    <label for="new_password_confirm">Confirm new password</label>
                    <input type="password" id="new_password_confirm" name="new_password_confirm" minlength="8" required>
                </div>
            </div>
            <button type="submit" class="btn btn-dark">Update password</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
