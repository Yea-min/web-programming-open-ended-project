<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$categories = get_categories($pdo);
$errors = [];
$old = ['title' => '', 'description' => '', 'category_id' => '', 'item_condition' => 'Good',
        'max_loan_days' => 7, 'deposit_note' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $old['title']          = trim($_POST['title'] ?? '');
    $old['description']    = trim($_POST['description'] ?? '');
    $old['category_id']    = $_POST['category_id'] ?? '';
    $old['item_condition'] = $_POST['item_condition'] ?? 'Good';
    $old['max_loan_days']  = (int)($_POST['max_loan_days'] ?? 7);
    $old['deposit_note']   = trim($_POST['deposit_note'] ?? '');

    if (mb_strlen($old['title']) < 3)        $errors[] = 'Give the item a descriptive title (min 3 characters).';
    if (mb_strlen($old['description']) < 10) $errors[] = 'Please describe the item in a bit more detail.';
    if (!ctype_digit((string)$old['category_id'])) $errors[] = 'Please choose a category.';
    if ($old['max_loan_days'] < 1 || $old['max_loan_days'] > 60) $errors[] = 'Max loan length should be between 1 and 60 days.';

    $photo_path = null;
    if (!$errors) {
        try {
            $photo_path = handle_item_photo_upload($_FILES['photo'] ?? []);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        do {
            $asset_code = generate_asset_code();
            $check = $pdo->prepare('SELECT id FROM items WHERE asset_code = ?');
            $check->execute([$asset_code]);
        } while ($check->fetch());

        $stmt = $pdo->prepare(
            'INSERT INTO items (owner_id, category_id, title, description, item_condition, photo_path,
                                 max_loan_days, deposit_note, asset_code)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $_SESSION['user_id'], $old['category_id'], $old['title'], $old['description'],
            $old['item_condition'], $photo_path, $old['max_loan_days'],
            $old['deposit_note'] ?: null, $asset_code,
        ]);
        flash_set('success', 'Your item "' . $old['title'] . '" is now listed (' . $asset_code . ').');
        header('Location: item.php?id=' . $pdo->lastInsertId());
        exit;
    }
}

$page_title = 'List an item';
include __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="card form-narrow" style="max-width:600px;">
        <h1>List an item</h1>
        <p style="color:var(--muted);margin-top:0;">A clear photo and honest description help borrowers trust the listing.</p>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="field">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?= e($old['title']) ?>" placeholder="e.g. Canon EOS 200D DSLR Camera" required>
            </div>
            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" required placeholder="Condition details, accessories included, pickup notes…"><?= e($old['description']) ?></textarea>
            </div>
            <div class="form-row">
                <div class="field">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select…</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (string)$old['category_id'] === (string)$c['id'] ? 'selected' : '' ?>>
                                <?= e($c['icon'] . ' ' . $c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="item_condition">Condition</label>
                    <select id="item_condition" name="item_condition">
                        <?php foreach (['New','Like New','Good','Fair','Worn'] as $c): ?>
                            <option value="<?= $c ?>" <?= $old['item_condition'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="field">
                    <label for="max_loan_days">Max loan length (days)</label>
                    <input type="number" id="max_loan_days" name="max_loan_days" min="1" max="60" value="<?= (int)$old['max_loan_days'] ?>">
                </div>
                <div class="field">
                    <label for="deposit_note">Deposit / handover note (optional)</label>
                    <input type="text" id="deposit_note" name="deposit_note" value="<?= e($old['deposit_note']) ?>" placeholder="e.g. Meet at library desk">
                </div>
            </div>
            <div class="field">
                <label for="photo">Photo (optional, JPG/PNG/WEBP, max 4MB)</label>
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Publish listing</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
