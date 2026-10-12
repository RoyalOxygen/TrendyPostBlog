<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('comments.moderate');
$action = $_GET['action'] ?? 'index';

function recount_post_comments(?int $postId): void {
    if (!$postId) {
        return;
    }
    Database::query(
        "UPDATE posts SET comment_count = (SELECT COUNT(*) FROM comments WHERE post_id = ? AND status = 'approved') WHERE id = ?",
        [$postId, $postId]
    );
}

if (in_array($action, ['approve', 'reject', 'spam', 'delete'], true) && verify_csrf($_GET['_token'] ?? '')) {
    $id = intval($_GET['id'] ?? 0);
    $c = $id ? Database::fetchOne("SELECT post_id FROM comments WHERE id = ?", [$id]) : null;
    if ($id && $c) {
        if ($action === 'approve') {
            Database::update('comments', ['status' => 'approved'], 'id = ?', [$id]);
            Auth::logActivity('comment_approve', 'Approved comment #' . $id);
            flash_set('success', 'Comment approved.');
        } elseif ($action === 'reject') {
            Database::update('comments', ['status' => 'trash'], 'id = ?', [$id]);
            Auth::logActivity('comment_reject', 'Rejected comment #' . $id);
            flash_set('success', 'Comment rejected.');
        } elseif ($action === 'spam') {
            Database::update('comments', ['status' => 'spam'], 'id = ?', [$id]);
            Auth::logActivity('comment_spam', 'Marked comment #' . $id . ' as spam');
            flash_set('success', 'Marked as spam.');
        } else {
            Database::delete('comments', 'id = ?', [$id]);
            Auth::logActivity('comment_delete', 'Deleted comment #' . $id);
            flash_set('success', 'Comment deleted.');
        }
        recount_post_comments((int) $c['post_id']);
    }
    header('Location: ' . admin_url('comments' . (!empty($_GET['status']) ? '&status=' . urlencode((string) $_GET['status']) : '')));
    exit;
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
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2"><h4 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Comments</h4></div>
<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?php echo admin_url('comments'); ?>" class="btn btn-sm <?php echo !$status?'btn-success':'btn-outline-secondary'; ?>">All (<?php echo $counts['all']; ?>)</a>
    <a href="<?php echo admin_url('comments&status=pending'); ?>" class="btn btn-sm <?php echo $status==='pending'?'btn-warning':'btn-outline-warning'; ?>">Pending (<?php echo $counts['pending']; ?>)</a>
    <a href="<?php echo admin_url('comments&status=approved'); ?>" class="btn btn-sm <?php echo $status==='approved'?'btn-success':'btn-outline-success'; ?>">Approved (<?php echo $counts['approved']; ?>)</a>
    <a href="<?php echo admin_url('comments&status=spam'); ?>" class="btn btn-sm <?php echo $status==='spam'?'btn-danger':'btn-outline-danger'; ?>">Spam (<?php echo $counts['spam']; ?>)</a>
    <a href="<?php echo admin_url('comments&status=trash'); ?>" class="btn btn-sm <?php echo $status==='trash'?'btn-secondary':'btn-outline-secondary'; ?>">Trash (<?php echo $counts['trash']; ?>)</a>
</div>
<div class="card border-0"><div class="card-body"><div class="table-responsive">
    <table class="table table-hover"><thead><tr><th>Author</th><th>Comment</th><th>Post</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody><?php foreach($comments as $c): ?><tr>
        <td><strong><?php echo esc($c['author_name']); ?></strong><br><small class="text-muted text-break"><?php echo esc($c['author_email']); ?></small></td>
        <td class="text-break"><?php echo esc(truncate($c['content'], 100)); ?></td>
        <td><a href="<?php echo post_url($c['post_slug']); ?>" target="_blank" class="small"><?php echo truncate($c['post_title'], 30); ?></a></td>
        <td><small><?php echo time_ago($c['created_at']); ?></small></td>
        <td><span class="badge bg-<?php echo $c['status']==='approved'?'success':($c['status']==='pending'?'warning':($c['status']==='spam'?'danger':'secondary')); ?>"><?php echo $c['status']; ?></span></td>
        <td>
            <?php $tok = csrf_token(); if($c['status']!=='approved'): ?><a href="<?php echo admin_url('comments&action=approve&id='.$c['id'].'&_token='.$tok); ?>" class="btn btn-sm btn-outline-success" title="Approve"><i class="bi bi-check-lg"></i></a><?php endif; ?>
            <?php if($c['status']!=='trash'): ?><a href="<?php echo admin_url('comments&action=reject&id='.$c['id'].'&_token='.$tok); ?>" class="btn btn-sm btn-outline-secondary" title="Reject"><i class="bi bi-x-lg"></i></a><?php endif; ?>
            <?php if($c['status']!=='spam'): ?><a href="<?php echo admin_url('comments&action=spam&id='.$c['id'].'&_token='.$tok); ?>" class="btn btn-sm btn-outline-warning" title="Spam"><i class="bi bi-shield-exclamation"></i></a><?php endif; ?>
            <a href="<?php echo admin_url('comments&action=delete&id='.$c['id'].'&_token='.$tok); ?>" class="btn btn-sm btn-outline-danger delete-confirm" title="Delete"><i class="bi bi-trash"></i></a>
        </td>
    </tr><?php endforeach; ?></tbody>
</table></div></div></div>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>