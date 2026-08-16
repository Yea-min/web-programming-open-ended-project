<?php
/**
 * General-purpose helper functions used across pages.
 */

/** Generate a short, human-friendly asset code like TL-4F82. */
function generate_asset_code(): string {
    return 'TL-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
}

/** All categories, cached per-request. */
function get_categories(PDO $pdo): array {
    static $cats = null;
    if ($cats === null) {
        $cats = $pdo->query(
            "SELECT categories.*, COUNT(items.id) AS item_count
             FROM categories
             LEFT JOIN items ON items.category_id = categories.id AND items.status = 'available'
             GROUP BY categories.id
             ORDER BY categories.name"
        )->fetchAll();
    }
    return $cats;
}

/** Days between two Y-m-d dates (inclusive-friendly for loan length). */
function days_between(string $start, string $end): int {
    $a = new DateTime($start);
    $b = new DateTime($end);
    return max(1, $a->diff($b)->days);
}

/** Human relative time, e.g. "3 days ago". */
function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    $units = [
        31536000 => 'year', 2592000 => 'month', 86400 => 'day',
        3600 => 'hour', 60 => 'minute'
    ];
    foreach ($units as $secs => $label) {
        $val = floor($diff / $secs);
        if ($val >= 1) return $val . ' ' . $label . ($val > 1 ? 's' : '') . ' ago';
    }
    return 'just now';
}

/** Status -> badge CSS class map, used in several templates. */
function status_badge_class(string $status): string {
    return match ($status) {
        'available' => 'badge badge-available',
        'borrowed'  => 'badge badge-borrowed',
        'unavailable' => 'badge badge-unavailable',
        'pending'   => 'badge badge-pending',
        'approved'  => 'badge badge-approved',
        'rejected'  => 'badge badge-rejected',
        'returned'  => 'badge badge-returned',
        'cancelled' => 'badge badge-cancelled',
        default     => 'badge',
    };
}

/** Handle an uploaded item photo; returns stored relative path or null. */
function handle_item_photo_upload(array $file): ?string {
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Photo upload failed. Please try again.');
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, or WEBP photos are allowed.');
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        throw new RuntimeException('Photo must be smaller than 4MB.');
    }
    $ext = $allowed[$mime];
    $name = 'item_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = __DIR__ . '/../uploads/items/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save photo on the server.');
    }
    return 'uploads/items/' . $name;
}
