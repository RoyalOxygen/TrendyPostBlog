<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('comments.moderate');
$action = $_GET['action'] ?? 'index';

if ($action === 'approve') {
    $id = intval($_GET['id'] ?? 0);
    if ($id) { Database::update('comments', ['status' => 'approved'], 'id = ?', [$id]); flash_set('success', 'Comment approved.'); }
    header('Location: ' . admin_url('comments')); exit;
}
if ($action === 'reject') {
    $id = intval($_GET['id'] ?? 0);
    if ($id) { Database::update('comments', ['status' => 'trash'], 'id = ?', [$id]); flash_set('success', 'Comment rejected.'); }
    header('Location: ' . admin_url('comments')); exit;
}
if ($action === 'spam') {
    $id = intval($_GET['id'] ?? 0);
    if ($id) { Database::update('comments', ['status' => 'spam'], 'id = ?', [$id]); flash_set('success', 'Marked as spam.'); }
    header('Location: ' . admin_url('comments')); exit;
}
if ($action === 'delete' && verify_csrf($_GET['_token'] ?? '')) {
    $id = intval($_GET['id'] ?? 0);
    if ($id) { Database::delete('comments', 'id = ?', [$id]); flash_set('success', 'Comment deleted.'); }
    header('Location: ' . admin_url('comments')); exit;
}

$status = $_GET['status'] ?? '';
$where = '1=1';
$params = [];
if ($status) { $where .= ' AND c.status = ?'; $params[] = $status; }
$comments = Database::fetchAll("SELECT c.*, p.title as post_title, p.slug as post_slug FROM comments c JOIN posts p ON c.post_id = p.id WHERE {$where} ORDER BY c.created_at DESC LIMIT 50", $params);
$counts = ['all'=>Database::count('comments'),'approved'=>Database::count('comments',"status='approved'"),'pending'=>Database::count('comments',"status='pending'"),'spam'=>Database::count('comments',"status='spam'"),'trash'=>Database::count('comments',"status='trash'")];
$pageTitle = 'Comments | Admin';
include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><h4 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Comments</h4></div>
<div class="d-flex gap-2 mb-3">
    <a href="<?php echo admin_url('comments'); ?>" class="btn btn-sm <?php echo !$status?'btn-success':'btn-outline-secondary'; ?>">All (<?php echo $counts['all']; ?>)</a>
    <a href="<?php echo admin_url('comments&status=pending'); ?>" class="btn btn-sm <?php echo $status==='pending'?'btn-warning':'btn-outline-warning'; ?>">Pending (<?php echo $counts['pending']; ?>)</a>
    <a href="<?php echo admin_url('comments&status=approved'); ?>" class="btn btn-sm <?php echo $status==='approved'?'btn-success':'btn-outline-success'; ?>">Approved (<?php echo $counts['approved']; ?>)</a>
    <a href="<?php echo admin_url('comments&status=spam'); ?>" class="btn btn-sm <?php echo $status==='spam'?'btn-danger':'btn-outline-danger'; ?>">Spam (<?php echo $counts['spam']; ?>)</a>
</div>
<div class="card border-0"><div class="card-body"><div class="table-responsive">
    <table class="table table-hover"><thead><tr><th>Author</th><th>Comment</th><th>Post</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody><?php foreach($comments as $c): ?><tr>
        <td><strong><?php echo $c['author_name']; ?></strong><br><small class="text-muted"><?php echo $c['author_email']; ?></small></td>
        <td><?php echo truncate($c['content'], 100); ?></td>
        <td><a href="<?php echo post_url($c['post_slug']); ?>" target="_blank" class="small"><?php echo truncate($c['post_title'], 30); ?></a></td>
        <td><small><?php echo time_ago($c['created_at']); ?></small></td>
        <td><span class="badge bg-<?php echo $c['status']==='approved'?'success':($c['status']==='pending'?'warning':($c['status']==='spam'?'danger':'secondary')); ?>"><?php echo $c['status']; ?></span></td>
        <td>
            <?php if($c['status']!=='approved'): ?><a href="<?php echo admin_url('comments&action=approve&id='.$c['id']); ?>" class="btn btn-sm btn-outline-success" title="Approve"><i class="bi bi-check-lg"></i></a><?php endif; ?>
            <?php if($c['status']!=='spam'): ?><a href="<?php echo admin_url('comments&action=spam&id='.$c['id']); ?>" class="btn btn-sm btn-outline-warning" title="Spam"><i class="bi bi-shield-exclamation"></i></a><?php endif; ?>
            <a href="<?php echo admin_url('comments&action=delete&id='.$c['id'].'&_token='.csrf_token()); ?>" class="btn btn-sm btn-outline-danger delete-confirm" title="Delete"><i class="bi bi-trash"></i></a>
        </td>
    </tr><?php endforeach; ?></tbody>
</table></div></div></div>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>