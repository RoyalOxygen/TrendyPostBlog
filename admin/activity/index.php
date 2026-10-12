<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
if (!Auth::isSuperAdmin() && !Auth::can('audit.view')) {
    flash_set('error', 'Only superadmins can view activity logs.');
    header('Location: ' . admin_url('dashboard'));
    exit;
}

$pageTitle = 'Activity Logs | Admin';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 40;
$typeFilter = sanitize($_GET['type'] ?? '');
$userFilter = intval($_GET['user'] ?? 0);
$search = trim($_GET['q'] ?? '');

$where = '1=1';
$params = [];
if ($typeFilter !== '') {
    $where .= ' AND a.type = ?';
    $params[] = $typeFilter;
}
if ($userFilter > 0) {
    $where .= ' AND a.user_id = ?';
    $params[] = $userFilter;
}
if ($search !== '') {
    $where .= ' AND (a.message LIKE ? OR a.ip_address LIKE ? OR u.display_name LIKE ? OR u.username LIKE ?)';
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$total = (int) Database::fetchColumn(
    "SELECT COUNT(*) FROM activity_logs a LEFT JOIN users u ON a.user_id = u.id WHERE {$where}",
    $params
);
$offset = ($page - 1) * $perPage;
$logs = Database::fetchAll(
    "SELECT a.*, u.display_name, u.username, u.email, r.display_name as role_display
     FROM activity_logs a
     LEFT JOIN users u ON a.user_id = u.id
     LEFT JOIN roles r ON u.role_id = r.id
     WHERE {$where}
     ORDER BY a.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);
$types = Database::fetchAll("SELECT DISTINCT type FROM activity_logs ORDER BY type");
$users = Database::fetchAll(
    "SELECT DISTINCT u.id, u.display_name, u.username
     FROM activity_logs a JOIN users u ON a.user_id = u.id
     ORDER BY u.display_name"
);

$badge = static function (string $type): string {
    return match ($type) {
        'login' => 'success',
        'logout' => 'secondary',
        'register' => 'info',
        'publish', 'post_publish' => 'primary',
        'delete', 'user_delete', 'ad_delete' => 'danger',
        'comment', 'comment_approve' => 'warning',
        'settings' => 'dark',
        default => 'light'
    };
};

include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-clock-history me-2"></i>Activity Logs</h4>
    <span class="text-muted small"><?php echo number_format($total); ?> events</span>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="activity">
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All types</option>
                    <?php foreach ($types as $t): ?>
                    <option value="<?php echo esc_attr($t['type']); ?>" <?php echo $typeFilter === $t['type'] ? 'selected' : ''; ?>>
                        <?php echo esc($t['type']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">User</label>
                <select name="user" class="form-select form-select-sm">
                    <option value="">All users</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo $userFilter === (int)$u['id'] ? 'selected' : ''; ?>>
                        <?php echo esc($u['display_name'] ?: $u['username']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control form-control-sm" value="<?php echo esc_attr($search); ?>" placeholder="Message, IP, name...">
            </div>
            <div class="col-md-2">
                <button class="btn btn-success btn-sm w-100" type="submit">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>When</th>
                        <th>Who</th>
                        <th>Type</th>
                        <th>What</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No activity recorded yet.</td></tr>
                    <?php else: foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <div><?php echo format_datetime($log['created_at']); ?></div>
                            <small class="text-muted"><?php echo time_ago($log['created_at']); ?></small>
                        </td>
                        <td>
                            <?php if (!empty($log['display_name']) || !empty($log['username'])): ?>
                                <strong><?php echo esc($log['display_name'] ?: $log['username']); ?></strong>
                                <br><small class="text-muted"><?php echo esc($log['role_display'] ?? ''); ?></small>
                            <?php else: ?>
                                <span class="text-muted">Guest / system</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $badge($log['type']); ?> text-<?php echo $badge($log['type']) === 'light' ? 'dark' : 'white'; ?>">
                                <?php echo esc($log['type']); ?>
                            </span>
                        </td>
                        <td><?php echo esc($log['message']); ?></td>
                        <td><code><?php echo esc($log['ip_address'] ?? ''); ?></code></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        $base = admin_url('activity');
        $qs = [];
        if ($typeFilter !== '') $qs[] = 'type=' . urlencode($typeFilter);
        if ($userFilter) $qs[] = 'user=' . $userFilter;
        if ($search !== '') $qs[] = 'q=' . urlencode($search);
        $pageBase = $base . (str_contains($base, '?') ? '&' : '?') . implode('&', $qs);
        echo pagination($total, $perPage, $page, rtrim($pageBase, '?&'));
        ?>
    </div>
</div>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>
