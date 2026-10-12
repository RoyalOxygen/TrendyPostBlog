<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('media.manage');
$action = $_GET['action'] ?? 'index';
$pageTitle = 'Media Library | Admin';
$isSuper = Auth::isSuperAdmin();

if (!class_exists('Media')) {
    require_once INCLUDES_PATH . 'Media.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    $postAction = $_POST['action'] ?? 'upload';

    if ($postAction === 'upload' && !empty($_FILES['files'])) {
        $uploaded = [];
        foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
            if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['files']['name'][$i]);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'mp4', 'mp3'];
            if (!in_array($ext, $allowed, true)) {
                continue;
            }
            $folder = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? 'library' : 'files';
            $dir = Media::ensureDir($folder);
            $newName = $folder . '_' . time() . '_' . uniqid() . '.' . $ext;
            $dest = $dir . '/' . $newName;
            if (move_uploaded_file($tmp, $dest)) {
                $relative = $folder . '/' . $newName;
                Media::register($relative, $name, '', Auth::id());
                $uploaded[] = $relative;
            }
        }
        if ($uploaded) {
            Auth::logActivity('media_upload', 'Uploaded ' . count($uploaded) . ' file(s)');
            flash_set('success', count($uploaded) . ' file(s) uploaded.');
        } else {
            flash_set('error', 'No files uploaded.');
        }
        header('Location: ' . admin_url('media'));
        exit;
    }

    if ($postAction === 'replace' && $isSuper) {
        $id = intval($_POST['id'] ?? 0);
        if ($id && !empty($_FILES['replace_file']) && Media::replace($id, $_FILES['replace_file'], Auth::id())) {
            Auth::logActivity('media_replace', 'Replaced media #' . $id);
            flash_set('success', 'Image replaced. Posts using this file now show the new image.');
        } else {
            flash_set('error', 'Could not replace image.');
        }
        header('Location: ' . admin_url('media'));
        exit;
    }

    if ($postAction === 'localize' && $isSuper) {
        $result = Media::localizeExisting(Auth::id());
        $synced = Media::syncFromDisk();
        Auth::logActivity('media_localize', 'Localized remote images. Updated ' . $result['updated'] . ', synced ' . $synced);
        flash_set('success', 'Imported ' . (int) $result['updated'] . ' remote image(s) and registered ' . $synced . ' file(s) from disk.');
        header('Location: ' . admin_url('media'));
        exit;
    }
}

if ($action === 'delete' && verify_csrf($_GET['_token'] ?? '')) {
    if (!$isSuper && !Auth::can('media.manage')) {
        flash_set('error', 'You cannot delete media.');
        header('Location: ' . admin_url('media'));
        exit;
    }
    $id = intval($_GET['id'] ?? 0);
    $m = Database::fetchOne("SELECT * FROM media WHERE id = ?", [$id]);
    if ($m) {
        $rel = Media::relativePath($m['file_path'] ?: $m['filename']);
        $abs = Media::diskPath($rel);
        if ($rel !== '' && is_file($abs)) {
            $used = Media::usageCount($rel);
            if ($used > 0 && !$isSuper) {
                flash_set('error', 'This file is in use. Only a superadmin can delete it.');
                header('Location: ' . admin_url('media'));
                exit;
            }
        }
        Database::delete('media', 'id = ?', [$id]);
        Auth::logActivity('media_delete', 'Deleted media #' . $id . (!empty($m['original_name']) ? ': ' . $m['original_name'] : ''));
        flash_set('success', 'File removed from the library listing. The file was kept on disk if posts still reference it.');
    }
    header('Location: ' . admin_url('media'));
    exit;
}

Media::syncFromDisk();
$filter = sanitize($_GET['type'] ?? 'images');
$q = trim($_GET['q'] ?? '');
$where = '1=1';
$params = [];
if ($filter === 'images') {
    $where .= " AND m.file_type IN ('jpg','jpeg','png','gif','webp','svg','image/jpeg','image/png','image/gif','image/webp','image/svg+xml')";
} elseif ($filter === 'other') {
    $where .= " AND m.file_type NOT IN ('jpg','jpeg','png','gif','webp','svg','image/jpeg','image/png','image/gif','image/webp','image/svg+xml')";
}
if ($q !== '') {
    $where .= ' AND (m.filename LIKE ? OR m.original_name LIKE ? OR m.alt_text LIKE ?)';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
$media = Database::fetchAll(
    "SELECT m.*, u.display_name as uploaded_by_name
     FROM media m LEFT JOIN users u ON m.uploaded_by = u.id
     WHERE {$where}
     ORDER BY m.created_at DESC LIMIT 400",
    $params
);
$totalAll = Database::count('media');
include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-images me-2"></i>Media Library</h4>
    <span class="text-muted small"><?php echo number_format($totalAll); ?> files</span>
</div>

<div class="card border-0 mb-4"><div class="card-body">
    <form method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="upload">
        <div class="col-md-8"><label class="form-label">Upload files</label><input type="file" name="files[]" class="form-control" multiple accept="image/*,.pdf,.doc,.docx,.mp4,.mp3"></div>
        <div class="col-md-4"><button type="submit" class="btn btn-success"><i class="bi bi-cloud-upload me-1"></i>Upload</button></div>
    </form>
    <?php if ($isSuper): ?>
    <form method="POST" class="mt-3">
        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="localize">
        <button type="submit" class="btn btn-outline-secondary btn-sm">Import remote / missing images into the library</button>
    </form>
    <?php endif; ?>
</div></div>

<form method="GET" class="row g-2 mb-3">
    <input type="hidden" name="route" value="media">
    <div class="col-md-3">
        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="images" <?php echo $filter==='images'?'selected':''; ?>>Images</option>
            <option value="other" <?php echo $filter==='other'?'selected':''; ?>>Other files</option>
            <option value="all" <?php echo $filter==='all'?'selected':''; ?>>All</option>
        </select>
    </div>
    <div class="col-md-5"><input type="search" name="q" class="form-control form-control-sm" placeholder="Search filename" value="<?php echo esc_attr($q); ?>"></div>
    <div class="col-md-2"><button class="btn btn-sm btn-outline-secondary" type="submit">Search</button></div>
</form>

<div class="card border-0"><div class="card-body"><div class="row g-3">
    <?php if (empty($media)): ?>
    <div class="col-12 text-muted py-4 text-center">No media files yet.</div>
    <?php endif; ?>
    <?php foreach ($media as $m):
        $rel = Media::relativePath($m['file_path'] ?: $m['filename']);
        $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION) ?: $m['file_type']);
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
        $used = $rel !== '' ? Media::usageCount($rel) : 0;
    ?>
    <div class="col-6 col-sm-4 col-md-3 col-xl-2">
        <div class="card border-0 shadow-sm h-100 admin-media-card">
            <?php if ($isImage): ?>
            <img src="<?php echo media_url($rel); ?>" class="card-img-top" style="height:120px;object-fit:cover" loading="lazy" alt="<?php echo esc_attr($m['original_name'] ?? $rel); ?>">
            <?php else: ?>
            <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height:120px"><i class="bi bi-file-earmark fs-1 text-muted"></i></div>
            <?php endif; ?>
            <div class="card-body p-2">
                <p class="mb-0 small text-truncate" title="<?php echo esc_attr($m['original_name'] ?? $rel); ?>"><?php echo esc($m['original_name'] ?? $rel); ?></p>
                <small class="text-muted"><?php echo format_filesize($m['file_size'] ?? 0); ?><?php if ($used): ?> · used <?php echo $used; ?>x<?php endif; ?></small>
                <div class="d-flex gap-1 mt-1 flex-wrap">
                    <button class="btn btn-sm btn-outline-primary py-0 px-1" type="button" onclick="navigator.clipboard.writeText('<?php echo esc_attr(upload_url($rel)); ?>')"><i class="bi bi-link"></i></button>
                    <?php if ($isSuper && $isImage): ?>
                    <button class="btn btn-sm btn-outline-success py-0 px-1" type="button" onclick="document.getElementById('replace-<?php echo (int)$m['id']; ?>').click()" title="Replace"><i class="bi bi-arrow-repeat"></i></button>
                    <form method="POST" enctype="multipart/form-data" class="d-none">
                        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="replace">
                        <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                        <input type="file" name="replace_file" id="replace-<?php echo (int)$m['id']; ?>" accept="image/*" onchange="this.form.submit()">
                    </form>
                    <?php endif; ?>
                    <a href="<?php echo admin_url('media&action=delete&id='.$m['id'].'&_token='.csrf_token()); ?>" class="btn btn-sm btn-outline-danger py-0 px-1 delete-confirm"><i class="bi bi-trash"></i></a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div></div></div>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>
