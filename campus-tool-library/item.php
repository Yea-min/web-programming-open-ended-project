<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT items.*, categories.name AS category_name, categories.icon AS category_icon,
            users.full_name AS owner_name, users.avatar_initials, users.department, users.id AS owner_id
     FROM items
     JOIN categories ON categories.id = items.category_id
     JOIN users ON users.id = items.owner_id
     WHERE items.id = ?'
);
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    $page_title = 'Item not found';
    include __DIR__ . '/includes/header.php';
    echo '<div class="container section"><div class="empty-state"><div class="emoji">🔍</div><h3>Item not found</h3>
          <p>It may have been removed by its owner.</p><a class="btn btn-dark" href="index.php">Back to browse</a></div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$is_owner = is_logged_in() && $_SESSION['user_id'] == $item['owner_id'];
$errors = [];

// Handle a new borrow request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_borrow') {
    require_login();
    verify_csrf($_POST['csrf_token'] ?? null);

    if ($is_owner) {
        $errors[] = 'You cannot borrow your own item.';
    }
    $start = $_POST['start_date'] ?? '';
    $end   = $_POST['end_date'] ?? '';
    $message = trim($_POST['message'] ?? '');

    $today = date('Y-m-d');
    if (!$start || $start < $today) $errors[] = 'Please choose a valid start date (today or later).';
    if (!$end || $end < $start)     $errors[] = 'End date must be on or after the start date.';
    if (!$errors && days_between($start, $end) > $item['max_loan_days']) {
        $errors[] = 'This item can be borrowed for at most ' . $item['max_loan_days'] . ' days.';
    }
    if ($item['status'] !== 'available') {
        $errors[] = 'This item is not currently available.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO borrow_requests (item_id, borrower_id, start_date, end_date, message)
             VALUES (?,?,?,?,?)'
        );
        $stmt->execute([$item['id'], $_SESSION['user_id'], $start, $end, $message ?: null]);
        flash_set('success', 'Request sent! The owner will review it soon.');
        header('Location: item.php?id=' . $item['id']);
        exit;
    }
}

// Owner deletes their own listing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_item') {
    require_login();
    verify_csrf($_POST['csrf_token'] ?? null);
    if ($is_owner) {
        $pdo->prepare('DELETE FROM items WHERE id = ? AND owner_id = ?')->execute([$item['id'], $_SESSION['user_id']]);
        flash_set('info', 'Listing removed.');
        header('Location: dashboard.php');
        exit;
    }
}

$page_title = $item['title'];
include __DIR__ . '/includes/header.php';
?>
<div class="container section">
    <div class="detail-grid">
        <div>
            <div class="detail-photo" style="<?= $item['photo_path'] ? 'background-image:url(' . e($item['photo_path']) . ')' : '' ?>">
                <?= $item['photo_path'] ? '' : e($item['category_icon']) ?>
            </div>
        </div>
        <div>
            <div class="item-code"><?= e($item['asset_code']) ?></div>
            <h1><?= e($item['title']) ?></h1>
            <span class="<?= status_badge_class($item['status']) ?>"><?= e($item['status']) ?></span>
            <span class="badge" style="margin-left:6px;"><?= e($item['category_icon'] . ' ' . $item['category_name']) ?></span>
            <span class="badge" style="margin-left:6px;">Condition: <?= e($item['item_condition']) ?></span>

            <p style="margin-top:16px;line-height:1.6;"><?= nl2br(e($item['description'])) ?></p>

            <div class="owner-chip">
                <div class="avatar"><?= e($item['avatar_initials'] ?: '?') ?></div>
                <div>
                    <div style="font-weight:600;font-size:.9rem;"><?= e($item['owner_name']) ?></div>
                    <div style="font-size:.78rem;color:var(--muted);"><?= e($item['department'] ?: 'ULAB Community') ?></div>
                </div>
            </div>

            <p style="font-size:.86rem;color:var(--muted);">
                Max loan length: <strong><?= (int)$item['max_loan_days'] ?> days</strong>
                <?php if ($item['deposit_note']): ?> &middot; <?= e($item['deposit_note']) ?><?php endif; ?>
            </p>

            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error"><?= e($err) ?></div>
            <?php endforeach; ?>

            <?php if ($is_owner): ?>
                <div style="display:flex;gap:10px;margin-top:14px;">
                    <a href="dashboard.php" class="btn btn-outline">Manage in dashboard</a>
                    <form method="post" onsubmit="return confirm('Remove this listing permanently?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete_item">
                        <button type="submit" class="btn btn-danger">Delete listing</button>
                    </form>
                </div>
            <?php elseif ($item['status'] !== 'available'): ?>
                <div class="alert alert-info" style="margin-top:14px;">This item isn't available to borrow right now.</div>
            <?php elseif (!is_logged_in()): ?>
                <a href="login.php" class="btn btn-primary" style="margin-top:14px;">Log in to request this item</a>
            <?php else: ?>
                <form method="post" class="card" style="margin-top:16px;padding:18px;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="request_borrow">
                    <h3 style="font-size:1rem;">Request to borrow</h3>
                    <div class="form-row">
                        <div class="field">
                            <label for="start_date">Start date</label>
                            <input type="date" id="start_date" name="start_date" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="field">
                            <label for="end_date">End date</label>
                            <input type="date" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="message">Message to owner (optional)</label>
                        <textarea id="message" name="message" placeholder="What will you use it for? When can you pick it up?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Send borrow request</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
