<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
if (!Auth::isAdmin() && !Auth::can('contact.view')) {
    flash_set('error', 'You do not have permission to view messages.');
    header('Location: ' . admin_url('dashboard'));
    exit;
}

$action = $_GET['action'] ?? 'index';
$pageTitle = 'Messages | Admin';
$id = intval($_GET['id'] ?? 0);

if ($action === 'read' && $id) {
    Database::update('contact_messages', ['status' => 'read'], 'id = ?', [$id]);
    Auth::logActivity('message_read', 'Marked contact message #' . $id . ' as read');
    header('Location: ' . admin_url('messages&action=view&id=' . $id));
    exit;
}

if ($action === 'archive' && $id) {
    Database::update('contact_messages', ['status' => 'archived'], 'id = ?', [$id]);
    Auth::logActivity('message_archive', 'Archived contact message #' . $id);
    flash_set('success', 'Message archived.');
    header('Location: ' . admin_url('messages'));
    exit;
}

if ($action === 'delete' && $id && verify_csrf($_GET['_token'] ?? '')) {
    Database::delete('contact_messages', 'id = ?', [$id]);
    Auth::logActivity('message_delete', 'Deleted contact message #' . $id);
    flash_set('success', 'Message deleted.');
    header('Location: ' . admin_url('messages'));
    exit;
}

if ($action === 'reply' && $_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    $msg = Database::fetchOne("SELECT * FROM contact_messages WHERE id = ?", [intval($_POST['id'] ?? 0)]);
    if ($msg) {
        $body = $_POST['reply'] ?? '';
        $ok = class_exists('Mailer') && Mailer::send($msg['email'], 'Re: ' . ($msg['subject'] ?: 'Your message'), nl2br(esc($body)), $msg['name']);
        Database::update('contact_messages', [
            'status' => 'replied',
            'replied_at' => date('Y-m-d H:i:s'),
            'replied_by' => Auth::id()
        ], 'id = ?', [$msg['id']]);
        Auth::logActivity('message_reply', 'Replied to contact message from ' . $msg['email']);
        flash_set($ok ? 'success' : 'warning', $ok ? 'Reply sent.' : 'Marked as replied, but email could not be sent.');
    }
    header('Location: ' . admin_url('messages'));
    exit;
}

$status = $_GET['status'] ?? '';
$where = '1=1';
$params = [];
if ($status !== '') {
    $where .= ' AND status = ?';
    $params[] = $status;
}
$messages = Database::fetchAll("SELECT * FROM contact_messages WHERE {$where} ORDER BY created_at DESC LIMIT 80", $params);
$view = ($action === 'view' && $id) ? Database::fetchOne("SELECT * FROM contact_messages WHERE id = ?", [$id]) : null;
if ($view && $view['status'] === 'new') {
    Database::update('contact_messages', ['status' => 'read'], 'id = ?', [$id]);
    $view['status'] = 'read';
}
$counts = [
    'all' => Database::count('contact_messages'),
    'new' => Database::count('contact_messages', "status = 'new'"),
    'read' => Database::count('contact_messages', "status = 'read'"),
    'replied' => Database::count('contact_messages', "status = 'replied'"),
];

include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-inbox me-2"></i>Contact Messages</h4>
</div>
<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?php echo admin_url('messages'); ?>" class="btn btn-sm <?php echo $status===''?'btn-success':'btn-outline-secondary'; ?>">All (<?php echo $counts['all']; ?>)</a>
    <a href="<?php echo admin_url('messages&status=new'); ?>" class="btn btn-sm <?php echo $status==='new'?'btn-warning':'btn-outline-warning'; ?>">New (<?php echo $counts['new']; ?>)</a>
    <a href="<?php echo admin_url('messages&status=read'); ?>" class="btn btn-sm <?php echo $status==='read'?'btn-info':'btn-outline-info'; ?>">Read</a>
    <a href="<?php echo admin_url('messages&status=replied'); ?>" class="btn btn-sm <?php echo $status==='replied'?'btn-success':'btn-outline-success'; ?>">Replied</a>
</div>

<?php if ($view): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h5><?php echo esc($view['subject'] ?: '(No subject)'); ?></h5>
        <p class="text-muted mb-2">From <strong><?php echo esc($view['name']); ?></strong> &lt;<?php echo esc($view['email']); ?>&gt; &middot; <?php echo format_datetime($view['created_at']); ?></p>
        <div class="border rounded p-3 bg-light mb-3"><?php echo nl2br(esc($view['message'])); ?></div>
        <form method="POST" action="<?php echo admin_url('messages&action=reply'); ?>">
            <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="id" value="<?php echo $view['id']; ?>">
            <div class="mb-3"><label class="form-label">Reply</label><textarea name="reply" class="form-control" rows="5" required></textarea></div>
            <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-success" type="submit">Send reply</button>
            <a class="btn btn-outline-secondary" href="<?php echo admin_url('messages'); ?>">Back</a>
            <a class="btn btn-outline-warning" href="<?php echo admin_url('messages&action=archive&id='.$view['id']); ?>">Archive</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>From</th><th>Subject</th><th>Date</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($messages)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No messages.</td></tr>
                <?php else: foreach ($messages as $m): ?>
                <tr class="<?php echo $m['status']==='new'?'fw-semibold':''; ?>">
                    <td><?php echo esc($m['name']); ?><br><small class="text-muted"><?php echo esc($m['email']); ?></small></td>
                    <td><?php echo esc($m['subject'] ?: '(No subject)'); ?></td>
                    <td><small><?php echo time_ago($m['created_at']); ?></small></td>
                    <td><span class="badge bg-<?php echo $m['status']==='new'?'warning':($m['status']==='replied'?'success':'secondary'); ?>"><?php echo esc($m['status']); ?></span></td>
                    <td>
                        <a class="btn btn-sm btn-outline-primary" href="<?php echo admin_url('messages&action=view&id='.$m['id']); ?>">Open</a>
                        <a class="btn btn-sm btn-outline-danger delete-confirm" href="<?php echo admin_url('messages&action=delete&id='.$m['id'].'&_token='.csrf_token()); ?>"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>
