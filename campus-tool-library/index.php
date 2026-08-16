<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$categories = get_categories($pdo);

$q          = trim($_GET['q'] ?? '');
$cat_id     = $_GET['category'] ?? '';
$status     = $_GET['status'] ?? 'available';

$sql = "SELECT items.*, categories.name AS category_name, categories.icon AS category_icon,
               users.full_name AS owner_name
        FROM items
        JOIN categories ON categories.id = items.category_id
        JOIN users ON users.id = items.owner_id
        WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= ' AND (items.title LIKE ? OR items.description LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($cat_id !== '' && ctype_digit((string)$cat_id)) {
    $sql .= ' AND items.category_id = ?';
    $params[] = $cat_id;
}
if (in_array($status, ['available', 'borrowed', 'unavailable'], true)) {
    $sql .= ' AND items.status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY items.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Quick impact counters for the hero strip
$totalItems     = $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
$totalLoans     = $pdo->query("SELECT COUNT(*) FROM borrow_requests WHERE status IN ('approved','returned')")->fetchColumn();
$totalMembers   = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

$page_title = 'Browse items';
include __DIR__ . '/includes/header.php';
?>
<header class="hero">
    <div class="container hero-grid">
        <div>
            <!-- <span class="eyebrow">Campus circular economy</span> -->
            <h1>Borrow what you need. Lend what you don't use.</h1>
            <p class="lead">ReTool Campus connects students and staff so underused electronics, cameras,
               and lab tools get shared instead of shelved — cutting e-waste and saving everyone money.</p>
            <div class="hero-actions">
                <a href="#browse" class="btn btn-primary">Browse the pegboard</a>
                <a href="<?= is_logged_in() ? 'add_item.php' : 'register.php' ?>" class="btn btn-outline">List an item</a>
            </div>
            <div class="stat-strip">
                <div class="stat-card"><div class="num"><?= (int)$totalItems ?></div><div class="label">Items listed</div></div>
                <div class="stat-card"><div class="num"><?= (int)$totalLoans ?></div><div class="label">Loans completed</div></div>
                <div class="stat-card"><div class="num"><?= (int)$totalMembers ?></div><div class="label">Members</div></div>
            </div>
        </div>
        <div class="pegboard">
            <?php
            $sample = array_slice($categories, 0, 4);
            foreach ($sample as $c): ?>
                <div class="peg-tag">
                    <div class="tag-visual"><span><?= e($c['icon']) ?></span></div>
                    <div class="tag-name"><?= e($c['name']) ?></div>
                    <div class="tag-count"><?= (int)$c['item_count'] ?> item<?= $c['item_count'] === '1' ? '' : 's' ?> available</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</header>

<section class="section container" id="categories">
    <div class="section-head">
        <div>
            <h2>Explore categories</h2>
            <p>Jump into the most popular gear types and see what’s available right now.</p>
        </div>
    </div>
    <div class="category-grid">
        <?php foreach ($categories as $c): ?>
            <a href="index.php?category=<?= (int)$c['id'] ?>#browse" class="category-card">
                <div class="category-media">
                    <span><?= e($c['icon']) ?></span>
                </div>
                <div>
                    <div class="category-name"><?= e($c['name']) ?></div>
                    <div class="category-availability"><?= (int)$c['item_count'] ?> item<?= (int)$c['item_count'] === 1 ? '' : 's' ?> available</div>
                </div>
                <div class="category-pill">Browse</div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section container" id="browse">
    <div class="section-head">
        <div>
            <h2>The pegboard</h2>
            <p>Everything currently listed by the community.</p>
        </div>
    </div>

    <form class="filter-bar" method="get" action="index.php#browse">
        <input type="text" name="q" placeholder="Search tools, cameras, kits…" value="<?= e($q) ?>">
        <select name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (string)$cat_id === (string)$c['id'] ? 'selected' : '' ?>>
                    <?= e($c['icon'] . ' ' . $c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available now</option>
            <option value=""          <?= $status === '' ? 'selected' : '' ?>>Any status</option>
            <option value="borrowed"  <?= $status === 'borrowed' ? 'selected' : '' ?>>Currently borrowed</option>
        </select>
        <button type="submit" class="btn btn-dark">Filter</button>
    </form>

    <?php if (!$items): ?>
        <div class="empty-state">
            <div class="emoji">🗄️</div>
            <h3>Nothing matches yet</h3>
            <p>Try a different search, or be the first to list this kind of item.</p>
        </div>
    <?php else: ?>
        <div class="item-grid">
            <?php foreach ($items as $item): ?>
                <a class="item-card" href="item.php?id=<?= (int)$item['id'] ?>">
                    <div class="item-photo" style="<?= $item['photo_path'] ? 'background-image:url(' . e($item['photo_path']) . ')' : '' ?>">
                        <?= $item['photo_path'] ? '' : e($item['category_icon']) ?>
                    </div>
                    <div class="item-body">
                        <div class="item-code"><?= e($item['asset_code']) ?></div>
                        <div class="item-title"><?= e($item['title']) ?></div>
                        <div class="item-meta"><?= e($item['category_name']) ?> &middot; <?= e($item['item_condition']) ?></div>
                        <div class="item-meta">Owner: <?= e($item['owner_name']) ?></div>
                        <div class="item-footer">
                            <span class="<?= status_badge_class($item['status']) ?>"><?= e($item['status']) ?></span>
                            <span style="font-size:.78rem;color:var(--muted);"><?= e(time_ago($item['created_at'])) ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
