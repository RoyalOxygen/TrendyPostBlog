<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('ads.manage');
$action = $_GET['action'] ?? 'index';
$pageTitle = 'Advertisements | Admin';

$adTypes = [
    'display' => 'Display / Image Banner',
    'banner' => 'Banner',
    'native' => 'Native / In-feed',
    'custom_html' => 'Custom HTML',
    'affiliate' => 'Affiliate Link',
    'google_adsense' => 'Google AdSense',
    'smartlink' => 'Smartlink',
    'in_page_push' => 'In-Page Push',
    'push_notification' => 'Push Notification',
    'vignette' => 'Vignette',
    'popunder' => 'Popunder',
    'video' => 'Video',
    'multitag' => 'Multi-tag / Network Script',
];

$codeTypes = ['google_adsense', 'native', 'affiliate', 'smartlink', 'in_page_push', 'push_notification', 'vignette', 'popunder', 'video', 'multitag', 'custom_html'];
$imageTypes = ['display', 'banner', 'native', 'affiliate'];

$locationOptions = [
    'header' => 'Header',
    'footer' => 'Footer',
    'sidebar' => 'Sidebar',
    'in_content' => 'In content',
    'before_content' => 'Before content',
    'after_content' => 'After content',
    'sticky_sidebar' => 'Sticky sidebar',
    'floating' => 'Floating',
    'popunder' => 'Popunder',
    'vignette' => 'Vignette',
    'native' => 'Native',
    'related_content' => 'Related / in-feed',
    'breaking_news' => 'Breaking news',
    'breadcrumb' => 'Breadcrumb',
    'after_paragraph_2' => 'After paragraph 2',
    'after_paragraph_5' => 'After paragraph 5',
    'mobile_only' => 'Mobile only',
    'desktop_only' => 'Desktop only',
];

if ($action === 'placement' && $_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    $slug = create_slug($_POST['slug'] ?? $_POST['name'] ?? '');
    $pdata = [
        'name' => sanitize($_POST['name'] ?? ''),
        'slug' => $slug,
        'description' => sanitize($_POST['description'] ?? ''),
        'location' => $_POST['location'] ?? 'sidebar',
        'device_target' => $_POST['device_target'] ?? 'all',
        'priority' => intval($_POST['priority'] ?? 0),
        'max_ads' => max(1, intval($_POST['max_ads'] ?? 1)),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    if (!array_key_exists($pdata['location'], $locationOptions)) {
        $pdata['location'] = 'sidebar';
    }
    if (!empty($_POST['id'])) {
        $pdata['slug'] = unique_slug('ad_placements', $pdata['slug'], intval($_POST['id']));
        Database::update('ad_placements', $pdata, 'id = ?', [intval($_POST['id'])]);
        Auth::logActivity('ad_placement_update', 'Updated placement: ' . $pdata['name']);
        flash_set('success', 'Placement updated.');
    } else {
        $pdata['slug'] = unique_slug('ad_placements', $pdata['slug']);
        Database::insert('ad_placements', $pdata);
        Auth::logActivity('ad_placement_create', 'Created placement: ' . $pdata['name']);
        flash_set('success', 'Placement created. You can now assign ads to it.');
    }
    header('Location: ' . admin_url('ads&action=placements'));
    exit;
}

if ($action === 'placement-delete' && verify_csrf($_GET['_token'] ?? '')) {
    $id = intval($_GET['id'] ?? 0);
    $used = Database::count('advertisements', 'placement_id = ?', [$id]);
    if ($used > 0) {
        flash_set('error', 'Cannot delete a placement that still has ads.');
    } else {
        Database::delete('ad_placements', 'id = ?', [$id]);
        Auth::logActivity('ad_placement_delete', 'Deleted placement #' . $id);
        flash_set('success', 'Placement deleted.');
    }
    header('Location: ' . admin_url('ads&action=placements'));
    exit;
}

if ($action === 'network' && $_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    $ndata = [
        'name' => sanitize($_POST['name'] ?? ''),
        'slug' => unique_slug('ad_networks', create_slug($_POST['slug'] ?? $_POST['name'] ?? '')),
        'code' => sanitize($_POST['code'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    if (!empty($_POST['id'])) {
        unset($ndata['slug']);
        Database::update('ad_networks', $ndata, 'id = ?', [intval($_POST['id'])]);
        flash_set('success', 'Network updated.');
    } else {
        Database::insert('ad_networks', $ndata);
        flash_set('success', 'Network created.');
    }
    Auth::logActivity('ad_network', 'Saved ad network: ' . $ndata['name']);
    header('Location: ' . admin_url('ads&action=networks'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '') && in_array($action, ['index', 'create', 'edit', ''], true)) {
    $editAd = !empty($_POST['id']) ? Database::fetchOne("SELECT * FROM advertisements WHERE id = ?", [intval($_POST['id'])]) : null;
    $image = $editAd['image'] ?? '';
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
                if (class_exists('Media')) {
                    Media::register($image, $_FILES['image_file']['name'] ?? $filename, '', Auth::id());
                }
            }
        }
    }
    if (!empty($_POST['image_url'])) {
        $remote = sanitize($_POST['image_url']);
        $image = class_exists('Media') ? (Media::localizePath($remote, 'ads', $_POST['title'] ?? '', Auth::id()) ?: $remote) : $remote;
    }

    $adType = $_POST['ad_type'] ?? 'display';
    if (!isset($adTypes[$adType])) {
        $adType = 'display';
    }

    $data = [
        'title' => sanitize($_POST['title'] ?? ''),
        'placement_id' => intval($_POST['placement_id'] ?? 0),
        'network_id' => intval($_POST['network_id'] ?? 0) ?: null,
        'ad_type' => $adType,
        'ad_code' => $_POST['ad_code'] ?? '',
        'custom_html' => $_POST['custom_html'] ?? '',
        'image' => $image,
        'link_url' => sanitize($_POST['link_url'] ?? ''),
        'status' => $_POST['status'] ?? 'active',
        'start_date' => $_POST['start_date'] ?: null,
        'end_date' => $_POST['end_date'] ?: null,
        'target_blank' => isset($_POST['target_blank']) ? 1 : 0,
        'nofollow' => isset($_POST['nofollow']) ? 1 : 0,
        'created_by' => Auth::id(),
    ];

    if (!empty($_POST['id'])) {
        unset($data['created_by']);
        Database::update('advertisements', $data, 'id = ?', [intval($_POST['id'])]);
        Auth::logActivity('ad_update', 'Updated ad: ' . $data['title']);
        flash_set('success', 'Ad updated successfully.');
    } else {
        Database::insert('advertisements', $data);
        Auth::logActivity('ad_create', 'Created ad: ' . $data['title']);
        flash_set('success', 'Ad created successfully.');
    }
    header('Location: ' . admin_url('ads'));
    exit;
}

if ($action === 'delete' && verify_csrf($_GET['_token'] ?? '')) {
    $id = intval($_GET['id'] ?? 0);
    $ad = Database::fetchOne("SELECT title, image FROM advertisements WHERE id = ?", [$id]);
    Database::delete('advertisements', 'id = ?', [$id]);
    Auth::logActivity('ad_delete', 'Deleted ad: ' . ($ad['title'] ?? ('#' . $id)));
    flash_set('success', 'Ad deleted successfully.');
    header('Location: ' . admin_url('ads'));
    exit;
}

$placements = Database::fetchAll("SELECT * FROM ad_placements ORDER BY location, name");
$networks = Database::fetchAll("SELECT * FROM ad_networks ORDER BY name");
$ads = Database::fetchAll("SELECT a.*, ap.name as placement_name, ap.location, ap.slug as placement_slug, an.name as network_name FROM advertisements a LEFT JOIN ad_placements ap ON a.placement_id = ap.id LEFT JOIN ad_networks an ON a.network_id = an.id ORDER BY a.created_at DESC LIMIT 100");
$editAd = ($action === 'edit' && !empty($_GET['id'])) ? Database::fetchOne("SELECT * FROM advertisements WHERE id = ?", [intval($_GET['id'])]) : null;
$editPlacement = ($action === 'placement-edit' && !empty($_GET['id'])) ? Database::fetchOne("SELECT * FROM ad_placements WHERE id = ?", [intval($_GET['id'])]) : null;

include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-badge-ad me-2"></i>Advertisements</h4>
    <div class="btn-group btn-group-sm">
        <a href="<?php echo admin_url('ads'); ?>" class="btn <?php echo !in_array($action, ['placements','networks','placement','placement-edit'], true) ? 'btn-success' : 'btn-outline-secondary'; ?>">Ads</a>
        <a href="<?php echo admin_url('ads&action=placements'); ?>" class="btn <?php echo in_array($action, ['placements','placement','placement-edit'], true) ? 'btn-success' : 'btn-outline-secondary'; ?>">Placements</a>
        <a href="<?php echo admin_url('ads&action=networks'); ?>" class="btn <?php echo $action === 'networks' ? 'btn-success' : 'btn-outline-secondary'; ?>">Networks</a>
    </div>
</div>

<?php if ($action === 'placements' || $action === 'placement' || $action === 'placement-edit'): ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="mb-3"><?php echo $editPlacement ? 'Edit' : 'New'; ?> Placement</h6>
                <form method="POST" action="<?php echo admin_url('ads&action=placement'); ?>">
                    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                    <?php if ($editPlacement): ?><input type="hidden" name="id" value="<?php echo $editPlacement['id']; ?>"><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo esc_attr($editPlacement['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="<?php echo esc_attr($editPlacement['slug'] ?? ''); ?>" placeholder="auto-from-name">
                        <div class="form-text">Used in templates via get_ads('slug').</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <select name="location" class="form-select">
                            <?php foreach ($locationOptions as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo ($editPlacement['location'] ?? '') === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Device</label>
                        <select name="device_target" class="form-select">
                            <?php foreach (['all','desktop','mobile','tablet'] as $d): ?>
                            <option value="<?php echo $d; ?>" <?php echo ($editPlacement['device_target'] ?? 'all') === $d ? 'selected' : ''; ?>><?php echo ucfirst($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3"><label class="form-label">Priority</label><input type="number" name="priority" class="form-control" value="<?php echo esc_attr($editPlacement['priority'] ?? '0'); ?>"></div>
                        <div class="col-6 mb-3"><label class="form-label">Max ads</label><input type="number" name="max_ads" class="form-control" value="<?php echo esc_attr($editPlacement['max_ads'] ?? '1'); ?>"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?php echo esc_attr($editPlacement['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="plActive" <?php echo ($editPlacement['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="plActive">Active</label>
                    </div>
                    <button class="btn btn-success" type="submit"><?php echo $editPlacement ? 'Update' : 'Create'; ?> Placement</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>Name</th><th>Slug</th><th>Location</th><th>Device</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($placements as $p): ?>
                        <tr>
                            <td><?php echo esc($p['name']); ?></td>
                            <td><code><?php echo esc($p['slug']); ?></code></td>
                            <td><?php echo esc($p['location']); ?></td>
                            <td><?php echo esc($p['device_target']); ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo admin_url('ads&action=placement-edit&id='.$p['id']); ?>"><i class="bi bi-pencil"></i></a>
                                <a class="btn btn-sm btn-outline-danger delete-confirm" href="<?php echo admin_url('ads&action=placement-delete&id='.$p['id'].'&_token='.csrf_token()); ?>"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($action === 'networks'): ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h6 class="mb-3">New Network</h6>
            <form method="POST" action="<?php echo admin_url('ads&action=network'); ?>">
                <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Code</label><input type="text" name="code" class="form-control" placeholder="adsense, adsterra..."></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" id="nAct" checked><label class="form-check-label" for="nAct">Active</label></div>
                <button class="btn btn-success" type="submit">Save Network</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <table class="table"><thead><tr><th>Name</th><th>Code</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($networks as $n): ?>
            <tr><td><?php echo esc($n['name']); ?></td><td><?php echo esc($n['code']); ?></td><td><?php echo $n['is_active'] ? 'Active' : 'Off'; ?></td></tr>
            <?php endforeach; ?></tbody></table>
        </div></div>
    </div>
</div>

<?php elseif ($action === 'create' || $editAd): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="mb-3"><?php echo $editAd ? 'Edit' : 'Create'; ?> Advertisement</h6>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
            <?php if ($editAd): ?><input type="hidden" name="id" value="<?php echo $editAd['id']; ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Ad Title</label>
                    <input type="text" name="title" class="form-control" value="<?php echo esc_attr($editAd['title'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ad Type</label>
                    <select name="ad_type" class="form-select" id="adType">
                        <?php foreach ($adTypes as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo ($editAd['ad_type'] ?? 'display') === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Placement</label>
                    <select name="placement_id" class="form-select" required>
                        <option value="">Select placement...</option>
                        <?php foreach ($placements as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo ($editAd['placement_id'] ?? 0) == $p['id'] ? 'selected' : ''; ?>>
                            <?php echo esc($p['name']); ?> (<?php echo esc($p['slug']); ?> / <?php echo esc($p['location']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text"><a href="<?php echo admin_url('ads&action=placements'); ?>">Manage placements</a></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Network</label>
                    <select name="network_id" class="form-select">
                        <option value="">None</option>
                        <?php foreach ($networks as $n): ?>
                        <option value="<?php echo $n['id']; ?>" <?php echo ($editAd['network_id'] ?? '') == $n['id'] ? 'selected' : ''; ?>><?php echo esc($n['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="imageAdFields" class="mt-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Image upload</label>
                        <?php if (!empty($editAd['image'])): ?>
                            <div class="mb-2"><img src="<?php echo media_url($editAd['image']); ?>" alt="" style="max-height:80px;border-radius:4px;"></div>
                        <?php endif; ?>
                        <input type="file" name="image_file" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Or image URL</label>
                        <input type="url" name="image_url" class="form-control" value="<?php echo (!empty($editAd['image']) && strpos($editAd['image'], 'http') === 0) ? $editAd['image'] : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Click / affiliate URL</label>
                        <input type="url" name="link_url" class="form-control" value="<?php echo esc_attr($editAd['link_url'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div id="codeAdFields" class="mt-3">
                <label class="form-label">Ad code / script</label>
                <textarea name="ad_code" class="form-control" rows="6" placeholder="Paste AdSense, Adsterra, Monetag, or other network code"><?php echo esc_attr($editAd['ad_code'] ?? ''); ?></textarea>
            </div>
            <div id="htmlAdFields" class="mt-3">
                <label class="form-label">Custom HTML</label>
                <textarea name="custom_html" class="form-control" rows="5" placeholder="Optional HTML body / native copy"><?php echo esc_attr($editAd['custom_html'] ?? ''); ?></textarea>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['active','paused','scheduled','expired'] as $st): ?>
                        <option value="<?php echo $st; ?>" <?php echo ($editAd['status'] ?? 'active') === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Start date</label><input type="date" name="start_date" class="form-control" value="<?php echo esc_attr($editAd['start_date'] ?? ''); ?>"></div>
                <div class="col-md-4"><label class="form-label">End date</label><input type="date" name="end_date" class="form-control" value="<?php echo esc_attr($editAd['end_date'] ?? ''); ?>"></div>
            </div>
            <div class="d-flex gap-3 my-3">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="target_blank" id="tb" <?php echo ($editAd['target_blank'] ?? 1) ? 'checked' : ''; ?>><label class="form-check-label" for="tb">Open in new tab</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="nofollow" id="nf" <?php echo ($editAd['nofollow'] ?? 1) ? 'checked' : ''; ?>><label class="form-check-label" for="nf">No follow</label></div>
            </div>
            <button type="submit" class="btn btn-success"><?php echo $editAd ? 'Update' : 'Create'; ?></button>
            <a href="<?php echo admin_url('ads'); ?>" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<script>
(function(){
    const imageTypes = <?php echo json_encode(array_values($imageTypes)); ?>;
    const typeEl = document.getElementById('adType');
    function sync() {
        const t = typeEl.value;
        document.getElementById('imageAdFields').style.display = imageTypes.indexOf(t) !== -1 ? 'block' : 'none';
    }
    typeEl.addEventListener('change', sync);
    sync();
})();
</script>

<?php else: ?>
<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?php echo admin_url('ads&action=create'); ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i>New Ad</a>
    <a href="<?php echo admin_url('ads&action=placements'); ?>" class="btn btn-outline-secondary btn-sm">Placements</a>
    <span class="text-muted small ms-2">Total: <?php echo count($ads); ?> ads</span>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Title</th><th>Placement</th><th>Type</th><th>Impressions</th><th>Clicks</th><th>CTR</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (empty($ads)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No ads found. Create a placement, then add an ad.</td></tr>
                    <?php else: foreach ($ads as $a):
                        $ctr = $a['impression_count'] > 0 ? round(($a['click_count'] / $a['impression_count']) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td class="fw-medium"><?php echo esc($a['title']); ?></td>
                        <td><span class="badge bg-light text-dark"><?php echo esc($a['placement_name'] ?? 'Unknown'); ?></span><br><small class="text-muted"><?php echo esc($a['placement_slug'] ?? ''); ?></small></td>
                        <td><span class="badge bg-secondary"><?php echo esc($a['ad_type']); ?></span></td>
                        <td><?php echo number_format($a['impression_count']); ?></td>
                        <td><?php echo number_format($a['click_count']); ?></td>
                        <td><?php echo $ctr; ?>%</td>
                        <td><span class="badge bg-<?php echo $a['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo esc($a['status']); ?></span></td>
                        <td>
                            <a href="<?php echo admin_url('ads&action=edit&id='.$a['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="<?php echo admin_url('ads&action=delete&id='.$a['id'].'&_token='.csrf_token()); ?>" class="btn btn-sm btn-outline-danger delete-confirm"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>
