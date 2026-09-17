<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('ads.manage');
$action = $_GET['action'] ?? 'index';
$pageTitle = 'Advertisements | Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    // Handle image upload for display ads
    $image = '';
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = UPLOADS_PATH . 'ads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename = 'ad_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $filename)) {
                $image = 'ads/' . $filename;
            }
        }
    }
    if (!empty($_POST['image_url'])) {
        $image = sanitize($_POST['image_url']);
    }

    $data = [
        'title' => sanitize($_POST['title'] ?? ''),
        'placement_id' => intval($_POST['placement_id'] ?? 0),
        'network_id' => intval($_POST['network_id'] ?? 0) ?: null,
        'ad_type' => $_POST['ad_type'] ?? 'display',
        'ad_code' => $_POST['ad_code'] ?? '',
        'custom_html' => $_POST['custom_html'] ?? '',
        'image' => $image ?: ($editAd['image'] ?? ''),
        'link_url' => sanitize($_POST['link_url'] ?? ''),
        'status' => $_POST['status'] ?? 'active',
        'start_date' => $_POST['start_date'] ?: null,
        'end_date' => $_POST['end_date'] ?: null,
        'target_blank' => isset($_POST['target_blank']) ? 1 : 0,
        'nofollow' => isset($_POST['nofollow']) ? 1 : 0
    ];

    if (!empty($_POST['id'])) {
        Database::update('advertisements', $data, 'id = ?', [intval($_POST['id'])]);
        flash_set('success', 'Ad updated successfully.');
    } else {
        Database::insert('advertisements', $data);
        flash_set('success', 'Ad created successfully.');
    }
    header('Location: ' . admin_url('ads'));
    exit;
}

if ($action === 'delete' && verify_csrf($_GET['_token'] ?? '')) {
    $id = intval($_GET['id'] ?? 0);
    $ad = Database::fetchOne("SELECT image FROM advertisements WHERE id = ?", [$id]);
    if ($ad && !empty($ad['image'])) {
        $imagePath = UPLOADS_PATH . $ad['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    Database::delete('advertisements', 'id = ?', [$id]);
    flash_set('success', 'Ad deleted successfully.');
    header('Location: ' . admin_url('ads'));
    exit;
}

$placements = Database::fetchAll("SELECT * FROM ad_placements ORDER BY location, name");
$networks = Database::fetchAll("SELECT * FROM ad_networks ORDER BY name");
$ads = Database::fetchAll("SELECT a.*, ap.name as placement_name, ap.location, an.name as network_name FROM advertisements a LEFT JOIN ad_placements ap ON a.placement_id = ap.id LEFT JOIN ad_networks an ON a.network_id = an.id ORDER BY a.created_at DESC LIMIT 50");
$editAd = ($action === 'edit' && !empty($_GET['id'])) ? Database::fetchOne("SELECT * FROM advertisements WHERE id = ?", [intval($_GET['id'])]) : null;

include ADMIN_PATH . 'layout/header.php';
?>
<style>
    .ad-preview { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-top: 10px; }
    .ad-preview .ad-label { font-size: 10px; color: #adb5bd; text-transform: uppercase; letter-spacing: 0.5px; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-badge-ad me-2"></i>Advertisements</h4>
</div>

<?php if($action === 'create' || $editAd): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="mb-3"><?php echo $editAd ? 'Edit' : 'Create'; ?> Advertisement</h6>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
            <?php if($editAd): ?><input type="hidden" name="id" value="<?php echo $editAd['id']; ?>"><?php endif; ?>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Ad Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?php echo esc_attr($editAd['title'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Ad Type</label>
                        <select name="ad_type" class="form-select" id="adType">
                            <option value="display" <?php echo ($editAd['ad_type'] ?? '') === 'display' ? 'selected' : ''; ?>>Display (Image/Banner)</option>
                            <option value="native" <?php echo ($editAd['ad_type'] ?? '') === 'native' ? 'selected' : ''; ?>>Native</option>
                            <option value="custom_html" <?php echo ($editAd['ad_type'] ?? '') === 'custom_html' ? 'selected' : ''; ?>>Custom HTML</option>
                            <option value="affiliate" <?php echo ($editAd['ad_type'] ?? '') === 'affiliate' ? 'selected' : ''; ?>>Affiliate</option>
                            <option value="google_adsense" <?php echo ($editAd['ad_type'] ?? '') === 'google_adsense' ? 'selected' : ''; ?>>Google AdSense</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Placement <span class="text-danger">*</span></label>
                        <select name="placement_id" class="form-select" required>
                            <option value="">Select Placement...</option>
                            <?php foreach($placements as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($editAd['placement_id'] ?? 0) == $p['id'] ? 'selected' : ''; ?>>
                                <?php echo $p['name']; ?> (<?php echo $p['location']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Network</label>
                        <select name="network_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach($networks as $n): ?>
                            <option value="<?php echo $n['id']; ?>" <?php echo ($editAd['network_id'] ?? '') == $n['id'] ? 'selected' : ''; ?>>
                                <?php echo $n['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Display Ad Image Upload -->
            <div id="displayAdFields" style="display: <?php echo ($editAd['ad_type'] ?? 'display') === 'display' ? 'block' : 'none'; ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Image Upload</label>
                            <?php if (!empty($editAd['image']) && strpos($editAd['image'], 'http') !== 0): ?>
                                <div class="mb-2">
                                    <img src="<?php echo upload_url($editAd['image']); ?>" alt="Current Ad" style="max-height:80px; border-radius:4px;">
                                    <small class="d-block text-muted">Current image</small>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image_file" class="form-control" accept="image/*">
                            <div class="form-text">Upload image for display ads. Leave empty to keep current.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Or Image URL</label>
                            <input type="url" name="image_url" class="form-control" 
                                   value="<?php echo (!empty($editAd['image']) && strpos($editAd['image'], 'http') === 0) ? $editAd['image'] : ''; ?>" 
                                   placeholder="https://example.com/ad-image.jpg">
                        </div>
                    </div>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Link URL</label>
                            <input type="url" name="link_url" class="form-control" 
                                   value="<?php echo esc_attr($editAd['link_url'] ?? ''); ?>" 
                                   placeholder="https://example.com">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?php echo ($editAd['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="paused" <?php echo ($editAd['status'] ?? '') === 'paused' ? 'selected' : ''; ?>>Paused</option>
                                <option value="scheduled" <?php echo ($editAd['status'] ?? '') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                <option value="expired" <?php echo ($editAd['status'] ?? '') === 'expired' ? 'selected' : ''; ?>>Expired</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Google AdSense -->
            <div id="googleAdsenseFields" style="display: <?php echo ($editAd['ad_type'] ?? '') === 'google_adsense' ? 'block' : 'none'; ?>">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Google AdSense</strong><br>
                    Use the standard HTML ad code from Google AdSense. The code will be automatically injected into the selected placement.
                </div>
                <div class="mb-3">
                    <label class="form-label">Ad Code <span class="text-danger">*</span></label>
                    <textarea name="ad_code" class="form-control" rows="8" placeholder="Paste the entire Google AdSense ad unit code here..."><?php echo esc_attr($editAd['ad_code'] ?? ''); ?></textarea>
                    <div class="form-text">Paste the complete ad unit code from Google AdSense (the script + ins + script tags).</div>
                </div>
            </div>
            
            <!-- Custom HTML Ad -->
            <div id="customHtmlFields" style="display: <?php echo ($editAd['ad_type'] ?? '') === 'custom_html' ? 'block' : 'none'; ?>">
                <div class="mb-3">
                    <label class="form-label">Custom HTML</label>
                    <textarea name="custom_html" class="form-control" rows="6" placeholder="Custom HTML ad content..."><?php echo esc_attr($editAd['custom_html'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- General Ad Code -->
            <div id="generalAdCodeFields" style="display: <?php echo in_array($editAd['ad_type'] ?? '', ['native', 'affiliate']) ? 'block' : 'none'; ?>">
                <div class="mb-3">
                    <label class="form-label">Ad Code</label>
                    <textarea name="ad_code" class="form-control" rows="4" placeholder="Paste ad code here..."><?php echo esc_attr($editAd['ad_code'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo esc_attr($editAd['start_date'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo esc_attr($editAd['end_date'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-3 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="target_blank" id="tb" <?php echo ($editAd['target_blank'] ?? 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="tb">Open in new tab</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="nofollow" id="nf" <?php echo ($editAd['nofollow'] ?? 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="nf">No follow</label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check2 me-2"></i><?php echo $editAd ? 'Update' : 'Create'; ?>
            </button>
            <a href="<?php echo admin_url('ads'); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-2"></i>Cancel
            </a>
        </form>
    </div>
</div>

<script>
// Toggle ad type fields
document.getElementById('adType')?.addEventListener('change', function() {
    const type = this.value;
    document.getElementById('displayAdFields').style.display = type === 'display' ? 'block' : 'none';
    document.getElementById('googleAdsenseFields').style.display = type === 'google_adsense' ? 'block' : 'none';
    document.getElementById('customHtmlFields').style.display = type === 'custom_html' ? 'block' : 'none';
    document.getElementById('generalAdCodeFields').style.display = (type === 'native' || type === 'affiliate') ? 'block' : 'none';
});
</script>

<?php else: ?>
<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?php echo admin_url('ads&action=create'); ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i>New Ad</a>
    <span class="text-muted small ms-2">Total: <?php echo count($ads); ?> ads</span>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Placement</th>
                        <th>Type</th>
                        <th>Impressions</th>
                        <th>Clicks</th>
                        <th>CTR</th>
                        <th>Status</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ads)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No ads found. Click "New Ad" to create your first advertisement.
                        </td>
                    </tr>
                    <?php else: foreach($ads as $a): 
                        $ctr = $a['impression_count'] > 0 ? round(($a['click_count'] / $a['impression_count']) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td class="fw-medium"><?php echo $a['title']; ?></td>
                        <td><span class="badge bg-light text-dark"><?php echo $a['placement_name'] ?? 'Unknown'; ?></span></td>
                        <td>
                            <?php if ($a['ad_type'] === 'google_adsense'): ?>
                                <span class="badge bg-primary"><i class="bi bi-google me-1"></i>AdSense</span>
                            <?php elseif ($a['ad_type'] === 'display'): ?>
                                <span class="badge bg-info">Display</span>
                            <?php elseif ($a['ad_type'] === 'custom_html'): ?>
                                <span class="badge bg-warning text-dark">HTML</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo $a['ad_type']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($a['impression_count']); ?></td>
                        <td><?php echo number_format($a['click_count']); ?></td>
                        <td>
                            <?php if ($ctr > 0): ?>
                                <span class="badge <?php echo $ctr > 2 ? 'bg-success' : 'bg-warning text-dark'; ?>"><?php echo $ctr; ?>%</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">0%</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $a['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($a['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo admin_url('ads&action=edit&id='.$a['id']); ?>" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="<?php echo admin_url('ads&action=delete&id='.$a['id'].'&_token='.csrf_token()); ?>" class="btn btn-outline-danger delete-confirm" title="Delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Delete confirmation
document.querySelectorAll('.delete-confirm').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this ad? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include ADMIN_PATH . 'layout/footer.php'; ?>