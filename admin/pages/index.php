<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('pages.manage');
$action = $_GET['action'] ?? 'index';
$pageTitle = 'Pages | Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    $data = ['title'=>sanitize($_POST['title']??''),'slug'=>create_slug($_POST['slug']??$_POST['title']??''),'content'=>$_POST['content']??'','excerpt'=>sanitize($_POST['excerpt']??''),'seo_title'=>sanitize($_POST['seo_title']??''),'seo_description'=>sanitize($_POST['seo_description']??''),'status'=>$_POST['status']??'draft','show_in_menu'=>isset($_POST['show_in_menu'])?1:0,'author_id'=>Auth::id()];
    if (!empty($_POST['id'])) {
        Database::update('pages', $data, 'id = ?', [intval($_POST['id'])]);
        Auth::logActivity('page_update', 'Updated page: ' . $data['title']);
        flash_set('success', 'Page updated.');
    } else {
        $data['slug'] = unique_slug('pages', $data['slug']);
        Database::insert('pages', $data);
        Auth::logActivity('page_create', 'Created page: ' . $data['title']);
        flash_set('success', 'Page created.');
    }
    if (($data['status'] ?? '') === 'published' && class_exists('SearchIndex')) {
        SearchIndex::notifyPublishedPage($data);
    }
    header('Location: ' . admin_url('pages')); exit;
}
if ($action === 'delete' && verify_csrf($_GET['_token'] ?? '')) {
    $pageId = intval($_GET['id'] ?? 0);
    Database::delete('pages', 'id = ?', [$pageId]);
    Auth::logActivity('page_delete', 'Deleted page #' . $pageId);
    flash_set('success', 'Page deleted.');
    header('Location: ' . admin_url('pages'));
    exit;
}

$pages = Database::fetchAll("SELECT * FROM pages ORDER BY created_at DESC");
$editId = ($action === 'edit') ? intval($_GET['id'] ?? 0) : 0;
$editPage = $editId ? Page::getById($editId) : null;

include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2"><h4 class="mb-0"><i class="bi bi-file-earmark me-2"></i>Pages</h4><a href="<?php echo admin_url('pages&action=create'); ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i>New Page</a></div>
<?php if($action==='create' || $editPage): ?>
<div class="card border-0"><div class="card-body">
    <form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>"><?php if($editPage): ?><input type="hidden" name="id" value="<?php echo $editPage['id']; ?>"><?php endif; ?>
    <div class="row g-3"><div class="col-md-8"><div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" value="<?php echo esc_attr($editPage['title']??''); ?>" required></div></div><div class="col-md-4"><div class="mb-3"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="<?php echo esc_attr($editPage['slug']??''); ?>"></div></div></div>
    <div class="mb-3"><label class="form-label">Content</label><textarea name="content" class="form-control" rows="10"><?php echo esc_attr($editPage['content']??''); ?></textarea></div>
    <div class="mb-3"><label class="form-label">Excerpt</label><textarea name="excerpt" class="form-control" rows="2"><?php echo esc_attr($editPage['excerpt']??''); ?></textarea></div>
    <div class="row g-3"><div class="col-md-4"><div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="draft" <?php echo ($editPage['status']??'')==='draft'?'selected':''; ?>>Draft</option><option value="published" <?php echo ($editPage['status']??'')==='published'?'selected':''; ?>>Published</option></select></div></div><div class="col-md-4"><div class="mb-3"><label class="form-label">SEO Title</label><input type="text" name="seo_title" class="form-control" value="<?php echo esc_attr($editPage['seo_title']??''); ?>"></div></div><div class="col-md-4 pt-4"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="show_in_menu" id="menu" <?php echo ($editPage['show_in_menu']??0)?'checked':''; ?>><label class="form-check-label" for="menu">Show in Menu</label></div></div></div>
    <button type="submit" class="btn btn-success"><?php echo $editPage ? 'Update' : 'Create'; ?> Page</button>
    <?php if($editPage): ?><a href="<?php echo admin_url('pages'); ?>" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
    </form>
</div></div>
<?php else: ?>
<div class="card border-0"><div class="card-body"><div class="table-responsive">
    <table class="table table-hover"><thead><tr><th>Title</th><th>Slug</th><th>Status</th><th>Menu</th><th>Actions</th></tr></thead>
    <tbody><?php foreach($pages as $p): ?><tr>
        <td class="fw-medium"><a href="<?php echo page_url($p['slug']); ?>" target="_blank"><?php echo $p['title']; ?></a></td>
        <td><code class="small"><?php echo $p['slug']; ?></code></td>
        <td><span class="badge bg-<?php echo $p['status']==='published'?'success':'secondary'; ?>"><?php echo $p['status']; ?></span></td>
        <td><?php echo $p['show_in_menu'] ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>'; ?></td>
        <td><a href="<?php echo admin_url('pages&action=edit&id='.$p['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a> <a href="<?php echo admin_url('pages&action=delete&id='.$p['id'].'&_token='.csrf_token()); ?>" class="btn btn-sm btn-outline-danger delete-confirm"><i class="bi bi-trash"></i></a></td>
    </tr><?php endforeach; ?></tbody>
</table></div></div></div>
<?php endif; include ADMIN_PATH . 'layout/footer.php'; ?>