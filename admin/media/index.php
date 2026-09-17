<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('media.manage');
$action = $_GET['action'] ?? 'index';
$pageTitle = 'Media Library | Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['files']) && verify_csrf($_POST['_token'] ?? '')) {
    $uploaded = [];
    foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
            $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['files']['name'][$i]);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','mp4','mp3'];
            if (!in_array($ext, $allowed)) continue;
            $newName = uniqid() . '_' . $name;
            $dest = UPLOADS_PATH . $newName;
            if (move_uploaded_file($tmp, $dest)) {
                Database::insert('media', ['filename'=>$newName,'original_name'=>$name,'file_path'=>$dest,'file_url'=>upload_url($newName),'file_type'=>$ext,'mime_type'=>$_FILES['files']['type'][$i],'file_size'=>$_FILES['files']['size'][$i],'uploaded_by'=>Auth::id()]);
                $uploaded[] = $newName;
            }
        }
    }
    if ($uploaded) { flash_set('success', count($uploaded) . ' file(s) uploaded.'); } else { flash_set('error', 'No files uploaded.'); }
    header('Location: ' . admin_url('media')); exit;
}

if ($action === 'delete' && verify_csrf($_GET['_token'] ?? '')) {
    $id = intval($_GET['id'] ?? 0);
    $m = Database::fetchOne("SELECT * FROM media WHERE id = ?", [$id]);
    if ($m && file_exists($m['file_path'])) unlink($m['file_path']);
    Database::delete('media', 'id = ?', [$id]);
    flash_set('success', 'File deleted.'); header('Location: ' . admin_url('media')); exit;
}

$media = Database::fetchAll("SELECT m.*, u.display_name as uploaded_by_name FROM media m LEFT JOIN users u ON m.uploaded_by = u.id ORDER BY m.created_at DESC LIMIT 100");
include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><h4 class="mb-0"><i class="bi bi-images me-2"></i>Media Library</h4></div>
<div class="card border-0 mb-4"><div class="card-body">
    <form method="POST" enctype="multipart/form-data"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
    <div class="mb-3"><label class="form-label">Upload Files</label><input type="file" name="files[]" class="form-control" multiple accept="image/*,.pdf,.doc,.docx,.mp4,.mp3"></div>
    <button type="submit" class="btn btn-success"><i class="bi bi-cloud-upload me-1"></i>Upload</button>
    </form>
</div></div>
<div class="card border-0"><div class="card-body"><div class="row g-3">
    <?php foreach($media as $m): $isImage = in_array($m['file_type'], ['jpg','jpeg','png','gif','webp']); ?>
    <div class="col-lg-2 col-md-3 col-4">
        <div class="card border-0 shadow-sm">
            <?php if($isImage): ?><img src="<?php echo upload_url($m['filename']); ?>" class="card-img-top" style="height:120px;object-fit:cover" loading="lazy">
            <?php else: ?><div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height:120px"><i class="bi bi-file-earmark fs-1 text-muted"></i></div><?php endif; ?>
            <div class="card-body p-2"><p class="mb-0 small text-truncate" title="<?php echo $m['original_name']; ?>"><?php echo $m['original_name']; ?></p>
            <small class="text-muted"><?php echo format_filesize($m['file_size']??0); ?></small>
            <div class="d-flex gap-1 mt-1"><button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="navigator.clipboard.writeText('<?php echo upload_url($m['filename']); ?>')"><i class="bi bi-link"></i></button><a href="<?php echo admin_url('media&action=delete&id='.$m['id'].'&_token='.csrf_token()); ?>" class="btn btn-sm btn-outline-danger py-0 px-1 delete-confirm"><i class="bi bi-trash"></i></a></div></div>
        </div>
    </div>
    <?php endforeach; ?></div>
</div></div>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>