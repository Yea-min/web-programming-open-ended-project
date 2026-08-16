<?php
/**
 * Expects (optionally) $page_title to be set before include.
 * Requires auth.php to already be loaded (for is_logged_in/current_user).
 */
$page_title = $page_title ?? 'ReTool Campus';
$current_script = basename($_SERVER['SCRIPT_NAME']);
$user = is_logged_in() ? current_user() : null;
$css_version = file_exists(__DIR__ . '/../css/style.css') ? filemtime(__DIR__ . '/../css/style.css') : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?> · ReTool Campus</title>
<link rel="stylesheet" href="css/style.css?v=<?= e($css_version) ?>">
</head>
<body>
<nav class="nav">
    <div class="nav-inner">
        <a href="index.php" class="brand">
            <span class="brand-mark">🔧</span> ReTool Campus
        </a>
        <div class="nav-links">
            <a href="index.php" class="<?= $current_script === 'index.php' ? 'active' : '' ?>">Browse</a>
            <?php if ($user): ?>
                <a href="add_item.php" class="<?= $current_script === 'add_item.php' ? 'active' : '' ?>">List an item</a>
                <a href="dashboard.php" class="<?= $current_script === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="profile.php" class="<?= $current_script === 'profile.php' ? 'active' : '' ?>">Profile</a>
                <span class="nav-user">Hi, <?= e(explode(' ', $user['full_name'])[0]) ?></span>
                <a href="logout.php" class="nav-cta">Log out</a>
            <?php else: ?>
                <a href="login.php" class="<?= $current_script === 'login.php' ? 'active' : '' ?>">Log in</a>
                <a href="register.php" class="nav-cta">Sign up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<?php $flash = flash_get(); if ($flash): ?>
<div class="container" style="padding-top:20px;">
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
</div>
<?php endif; ?>
