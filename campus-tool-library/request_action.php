<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}
verify_csrf($_POST['csrf_token'] ?? null);

$uid        = $_SESSION['user_id'];
$request_id = (int)($_POST['request_id'] ?? 0);
$action     = $_POST['action'] ?? '';

$stmt = $pdo->prepare(
    'SELECT borrow_requests.*, items.owner_id, items.id AS item_id
     FROM borrow_requests JOIN items ON items.id = borrow_requests.item_id
     WHERE borrow_requests.id = ?'
);
$stmt->execute([$request_id]);
$req = $stmt->fetch();

if (!$req) {
    flash_set('error', 'Request not found.');
    header('Location: dashboard.php');
    exit;
}

$is_owner    = $req['owner_id'] == $uid;
$is_borrower = $req['borrower_id'] == $uid;

try {
    $pdo->beginTransaction();

    if ($action === 'approve' && $is_owner && $req['status'] === 'pending') {
        $pdo->prepare("UPDATE borrow_requests SET status='approved' WHERE id=?")->execute([$request_id]);
        $pdo->prepare("UPDATE items SET status='borrowed' WHERE id=?")->execute([$req['item_id']]);
        // Auto-decline any other pending requests for the same item/dates conflict window
        $pdo->prepare("UPDATE borrow_requests SET status='rejected'
                        WHERE item_id = ? AND id != ? AND status = 'pending'")
            ->execute([$req['item_id'], $request_id]);
        flash_set('success', 'Request approved. The item is now marked as borrowed.');

    } elseif ($action === 'reject' && $is_owner && $req['status'] === 'pending') {
        $pdo->prepare("UPDATE borrow_requests SET status='rejected' WHERE id=?")->execute([$request_id]);
        flash_set('info', 'Request declined.');

    } elseif ($action === 'returned' && $is_owner && $req['status'] === 'approved') {
        $pdo->prepare("UPDATE borrow_requests SET status='returned' WHERE id=?")->execute([$request_id]);
        $pdo->prepare("UPDATE items SET status='available' WHERE id=?")->execute([$req['item_id']]);
        $pdo->prepare("INSERT INTO impact_log (request_id, est_money_saved) VALUES (?, ?)")
            ->execute([$request_id, 15.00]);
        $pdo->prepare("UPDATE users SET reputation_pts = reputation_pts + 5 WHERE id IN (?, ?)")
            ->execute([$req['owner_id'], $req['borrower_id']]);
        flash_set('success', 'Marked as returned. Thanks for keeping the pegboard honest!');

    } elseif ($action === 'cancel' && $is_borrower && $req['status'] === 'pending') {
        $pdo->prepare("UPDATE borrow_requests SET status='cancelled' WHERE id=?")->execute([$request_id]);
        flash_set('info', 'Request cancelled.');

    } else {
        flash_set('error', 'That action is not allowed for this request.');
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    flash_set('error', 'Something went wrong. Please try again.');
}

header('Location: dashboard.php?tab=' . ($is_owner ? 'incoming' : 'outgoing'));
exit;
