<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$uid = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'listings';
if (!in_array($tab, ['listings', 'incoming', 'outgoing'], true)) $tab = 'listings';

// My listings
$myItems = $pdo->prepare(
    'SELECT items.*, categories.name AS category_name, categories.icon AS category_icon
     FROM items JOIN categories ON categories.id = items.category_id
     WHERE owner_id = ? ORDER BY created_at DESC'
);
$myItems->execute([$uid]);
$myItems = $myItems->fetchAll();

// Requests other people made on MY items
$incoming = $pdo->prepare(
    "SELECT borrow_requests.*, items.title AS item_title, items.asset_code, users.full_name AS borrower_name
     FROM borrow_requests
     JOIN items ON items.id = borrow_requests.item_id
     JOIN users ON users.id = borrow_requests.borrower_id
     WHERE items.owner_id = ?
     ORDER BY borrow_requests.created_at DESC"
);
$incoming->execute([$uid]);
$incoming = $incoming->fetchAll();

// Requests I made on OTHER people's items
$outgoing = $pdo->prepare(
    "SELECT borrow_requests.*, items.title AS item_title, items.asset_code, users.full_name AS owner_name
     FROM borrow_requests
     JOIN items ON items.id = borrow_requests.item_id
     JOIN users ON users.id = items.owner_id
     WHERE borrow_requests.borrower_id = ?
     ORDER BY borrow_requests.created_at DESC"
);
$outgoing->execute([$uid]);
$outgoing = $outgoing->fetchAll();

$page_title = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
<div class="container section">
    <div class="section-head">
        <div>
            <h2>Your dashboard</h2>
            <p>Manage listings and track borrow requests in one place.</p>
        </div>
        <a href="add_item.php" class="btn btn-primary">+ List an item</a>
    </div>

    <div class="tabs">
        <a class="tab-btn <?= $tab === 'listings' ? 'active' : '' ?>" href="dashboard.php?tab=listings">My listings (<?= count($myItems) ?>)</a>
        <a class="tab-btn <?= $tab === 'incoming' ? 'active' : '' ?>" href="dashboard.php?tab=incoming">Incoming requests (<?= count($incoming) ?>)</a>
        <a class="tab-btn <?= $tab === 'outgoing' ? 'active' : '' ?>" href="dashboard.php?tab=outgoing">My requests (<?= count($outgoing) ?>)</a>
    </div>

    <?php if ($tab === 'listings'): ?>
        <?php if (!$myItems): ?>
            <div class="empty-state"><div class="emoji">📦</div><h3>No listings yet</h3>
                <p>List your first underused item and start saving campus resources.</p>
                <a href="add_item.php" class="btn btn-dark" style="margin-top:10px;">List an item</a>
            </div>
        <?php else: ?>
            <div class="item-grid">
                <?php foreach ($myItems as $item): ?>
                    <a class="item-card" href="item.php?id=<?= (int)$item['id'] ?>">
                        <div class="item-photo" style="<?= $item['photo_path'] ? 'background-image:url(' . e($item['photo_path']) . ')' : '' ?>">
                            <?= $item['photo_path'] ? '' : e($item['category_icon']) ?>
                        </div>
                        <div class="item-body">
                            <div class="item-code"><?= e($item['asset_code']) ?></div>
                            <div class="item-title"><?= e($item['title']) ?></div>
                            <div class="item-footer">
                                <span class="<?= status_badge_class($item['status']) ?>"><?= e($item['status']) ?></span>
                                <span style="font-size:.78rem;color:var(--muted);"><?= e(time_ago($item['created_at'])) ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'incoming'): ?>
        <?php if (!$incoming): ?>
            <div class="empty-state"><div class="emoji">📭</div><h3>No requests yet</h3><p>When someone wants to borrow your items, requests will show up here.</p></div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Item</th><th>Borrower</th><th>Dates</th><th>Message</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($incoming as $r): ?>
                    <tr>
                        <td><a href="item.php?id=<?= (int)$r['item_id'] ?>"><?= e($r['item_title']) ?></a><br><span class="item-code"><?= e($r['asset_code']) ?></span></td>
                        <td><?= e($r['borrower_name']) ?></td>
                        <td><?= e($r['start_date']) ?> &rarr; <?= e($r['end_date']) ?></td>
                        <td style="white-space:normal;max-width:220px;"><?= e($r['message'] ?: '—') ?></td>
                        <td><span class="<?= status_badge_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
                        <td>
                            <?php if ($r['status'] === 'pending'): ?>
                                <form method="post" action="request_action.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button class="btn btn-sm btn-primary" type="submit">Approve</button>
                                </form>
                                <form method="post" action="request_action.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button class="btn btn-sm btn-outline" type="submit">Decline</button>
                                </form>
                            <?php elseif ($r['status'] === 'approved'): ?>
                                <form method="post" action="request_action.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="action" value="returned">
                                    <button class="btn btn-sm btn-dark" type="submit">Mark returned</button>
                                </form>
                            <?php else: ?>
                                <span style="color:var(--muted);font-size:.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <?php if (!$outgoing): ?>
            <div class="empty-state"><div class="emoji">🔎</div><h3>You haven't requested anything yet</h3><p>Browse the pegboard to find something to borrow.</p></div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Item</th><th>Owner</th><th>Dates</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($outgoing as $r): ?>
                    <tr>
                        <td><a href="item.php?id=<?= (int)$r['item_id'] ?>"><?= e($r['item_title']) ?></a><br><span class="item-code"><?= e($r['asset_code']) ?></span></td>
                        <td><?= e($r['owner_name']) ?></td>
                        <td><?= e($r['start_date']) ?> &rarr; <?= e($r['end_date']) ?></td>
                        <td><span class="<?= status_badge_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
                        <td>
                            <?php if ($r['status'] === 'pending'): ?>
                                <form method="post" action="request_action.php" onsubmit="return confirm('Cancel this request?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button class="btn btn-sm btn-outline" type="submit">Cancel</button>
                                </form>
                            <?php else: ?>
                                <span style="color:var(--muted);font-size:.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
