<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('tags.manage');
$action = $_GET['action'] ?? 'index';
$pageTitle = 'Tags | Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    $data = ['name'=>sanitize($_POST['name']??''),'slug'=>create_slug($_POST['slug']??$_POST['name']??''),'seo_title'=>sanitize($_POST['seo_title']??''),'seo_description'=>sanitize($_POST['seo_description']??'')];
    if (!empty($_POST['id'])) {
        Database::update('tags', $data, 'id = ?', [intval($_POST['id'])]);
        Auth::logActivity('tag_update', 'Updated tag: ' . $data['name']);
        flash_set('success', 'Tag updated.');
    } else {
        $data['slug'] = unique_slug('tags', $data['slug']);
        Database::insert('tags', $data);
        Auth::logActivity('tag_create', 'Created tag: ' . $data['name']);
        flash_set('success', 'Tag created.');
    }
    header('Location: ' . admin_url('tags')); exit;
}
if ($action === 'delete' && verify_csrf($_GET['_token'] ?? '')) {
    $tagId = intval($_GET['id'] ?? 0);
    Database::delete('tags', 'id = ?', [$tagId]);
    Auth::logActivity('tag_delete', 'Deleted tag #' . $tagId);
    flash_set('success', 'Tag deleted.');
    header('Location: ' . admin_url('tags'));
    exit;
}

$tags = Tag::getAll(false);
$editId = ($action === 'edit') ? intval($_GET['id'] ?? 0) : 0;
$editTag = $editId ? Tag::getById($editId) : null;

include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2"><h4 class="mb-0"><i class="bi bi-tags me-2"></i>Tags</h4></div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0"><div class="card-body"><div class="table-responsive">
            <table class="table table-hover"><thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th>Actions</th></tr></thead>
            <tbody><?php foreach($tags as $t): $tc = Database::fetchColumn("SELECT COUNT(*) FROM post_tags WHERE tag_id = ?", [$t['id']]); ?><tr>
                <td class="fw-medium"><?php echo $t['name']; ?></td><td><code class="small"><?php echo $t['slug']; ?></code></td><td><span class="badge bg-light text-dark"><?php echo $tc; ?></span></td>
                <td><a href="<?php echo admin_url('tags&action=edit&id='.$t['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a> <a href="<?php echo admin_url('tags&action=delete&id='.$t['id'].'&_token='.csrf_token()); ?>" class="btn btn-sm btn-outline-danger delete-confirm"><i class="bi bi-trash"></i></a></td>
            </tr><?php endforeach; ?></tbody>
        </table></div></div></div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0"><div class="card-header bg-white"><h6 class="mb-0"><?php echo $editTag ? 'Edit' : 'Add'; ?> Tag</h6></div><div class="card-body">
            <form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
            <?php if($editTag): ?><input type="hidden" name="id" value="<?php echo $editTag['id']; ?>"><?php endif; ?>
            <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?php echo esc_attr($editTag['name'] ?? ''); ?>" required></div>
            <div class="mb-3"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="<?php echo esc_attr($editTag['slug'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">SEO Title</label><input type="text" name="seo_title" class="form-control" value="<?php echo esc_attr($editTag['seo_title'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">SEO Description</label><textarea name="seo_description" class="form-control" rows="2"><?php echo esc_attr($editTag['seo_description'] ?? ''); ?></textarea></div>
            <button type="submit" class="btn btn-success w-100"><?php echo $editTag ? 'Update' : 'Create'; ?></button>
            <?php if($editTag): ?><a href="<?php echo admin_url('tags'); ?>" class="btn btn-outline-secondary w-100 mt-2">Cancel</a><?php endif; ?>
            </form>
        </div></div>
    </div>
</div>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>