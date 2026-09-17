<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('newsletters.manage');
$action = $_GET['action'] ?? 'index';
$pageTitle = 'Newsletters | Admin';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    Database::insert('newsletters', ['subject'=>sanitize($_POST['subject']??''),'content'=>$_POST['content']??'','status'=>'draft','created_by'=>Auth::id()]);
    flash_set('success', 'Newsletter created.'); header('Location: ' . admin_url('newsletters')); exit;
}
if ($action === 'delete' && verify_csrf($_GET['_token'] ?? '')) { Database::delete('newsletters', 'id = ?', [intval($_GET['id'] ?? 0)]); flash_set('success', 'Deleted.'); header('Location: ' . admin_url('newsletters')); exit; }

$newsletters = Database::fetchAll("SELECT n.*, u.display_name as creator FROM newsletters n LEFT JOIN users u ON n.created_by = u.id ORDER BY n.created_at DESC");
$subscribers = Database::fetchAll("SELECT * FROM subscribers WHERE status = 'active' ORDER BY created_at DESC LIMIT 20");
$subCount = Database::count('subscribers', "status = 'active'");

include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><h4 class="mb-0"><i class="bi bi-envelope me-2"></i>Newsletters</h4></div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="d-flex gap-2 mb-3"><a href="<?php echo admin_url('newsletters&action=create'); ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i>New Newsletter</a></div>
        <?php if($action==='create'): ?>
        <div class="card border-0"><div class="card-body">
            <h6 class="mb-3">Create Newsletter</h6>
            <form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
            <div class="mb-3"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Content (HTML)</label><textarea name="content" class="form-control" rows="12" placeholder="<h2>Hello!</h2><p>Your newsletter content here...</p>"></textarea></div>
            <button type="submit" class="btn btn-success">Save Draft</button><a href="<?php echo admin_url('newsletters'); ?>" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div></div>
        <?php else: ?>
        <div class="card border-0"><div class="card-body"><div class="table-responsive">
            <table class="table table-hover"><thead><tr><th>Subject</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody><?php foreach($newsletters as $n): ?><tr>
                <td class="fw-medium"><?php echo $n['subject']; ?></td>
                <td><span class="badge bg-<?php echo $n['status']==='sent'?'success':($n['status']==='draft'?'secondary':'warning'); ?>"><?php echo $n['status']; ?></span></td>
                <td><small><?php echo time_ago($n['created_at']); ?> by <?php echo $n['creator']; ?></small></td>
                <td><a href="<?php echo admin_url('newsletters&action=delete&id='.$n['id'].'&_token='.csrf_token()); ?>" class="btn btn-sm btn-outline-danger delete-confirm"><i class="bi bi-trash"></i></a></td>
            </tr><?php endforeach; ?></tbody>
        </table></div></div></div>
        <?php endif; ?>
    </div>
    <div class="col-lg-4">
        <div class="card border-0"><div class="card-body text-center p-4">
            <h2 class="text-success mb-1"><?php echo number_format($subCount); ?></h2>
            <p class="text-muted mb-0">Active Subscribers</p>
        </div></div>
        <div class="card border-0 mt-4"><div class="card-header bg-white pt-4 px-4"><h6 class="mb-0">Recent Subscribers</h6></div><div class="card-body">
            <?php foreach($subscribers as $s): ?><div class="d-flex justify-content-between align-items-center py-2 border-bottom"><small><?php echo $s['email']; ?></small><small class="text-muted"><?php echo time_ago($s['created_at']); ?></small></div><?php endforeach; ?>
        </div></div>
    </div>
</div>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>