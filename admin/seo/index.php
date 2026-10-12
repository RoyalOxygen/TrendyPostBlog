<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('seo.manage');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    $settings = ['google_verification','bing_verification','google_analytics_4','gtm_container_id','facebook_app_id','facebook_pixel','twitter_site'];
    foreach ($settings as $key) { SEOSetting::set($key, sanitize($_POST[$key] ?? ''), $_POST['group_' . $key] ?? 'general'); }
    SEOSetting::set('robots_txt', (string) ($_POST['robots_txt'] ?? ''), 'robots');
    SEOSetting::set('auto_indexing', isset($_POST['auto_indexing']) ? '1' : '0', 'indexing');
    SEOSetting::set('indexing_api_key', trim((string) ($_POST['indexing_api_key'] ?? '')), 'indexing');
    Setting::set('seo_default_title', sanitize($_POST['seo_default_title'] ?? ''), 'seo');
    Setting::set('seo_default_description', sanitize($_POST['seo_default_description'] ?? ''), 'seo');
    Setting::set('seo_default_keywords', sanitize($_POST['seo_default_keywords'] ?? ''), 'seo');
    Auth::logActivity('seo', 'Updated SEO settings');
    if (isset($_POST['auto_indexing']) && class_exists('SearchIndex')) {
        SearchIndex::pingSitemaps();
    }
    flash_set('success', 'SEO settings saved.');
    header('Location: ' . admin_url('seo')); exit;
}
$pageTitle = 'SEO Settings | Admin';
include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2"><h4 class="mb-0"><i class="bi bi-search me-2"></i>SEO Settings</h4></div>
<div class="card border-0"><div class="card-body">
    <form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
    <h6 class="mb-3">Search Console & Analytics</h6>
    <div class="row g-3"><div class="col-md-6"><div class="mb-3"><label class="form-label">Google Verification Code</label><input type="text" name="google_verification" class="form-control" value="<?php echo esc_attr(SEOSetting::get('google_verification', '')); ?>"></div></div><div class="col-md-6"><div class="mb-3"><label class="form-label">Bing Verification Code</label><input type="text" name="bing_verification" class="form-control" value="<?php echo esc_attr(SEOSetting::get('bing_verification', '')); ?>"></div></div></div>
    <div class="row g-3"><div class="col-md-6"><div class="mb-3"><label class="form-label">Google Analytics 4 ID</label><input type="text" name="google_analytics_4" class="form-control" value="<?php echo esc_attr(SEOSetting::get('google_analytics_4', '')); ?>" placeholder="G-XXXXXXXXXX"></div></div><div class="col-md-6"><div class="mb-3"><label class="form-label">Google Tag Manager</label><input type="text" name="gtm_container_id" class="form-control" value="<?php echo esc_attr(SEOSetting::get('gtm_container_id', '')); ?>" placeholder="GTM-XXXXXX"></div></div></div>

    <h6 class="mb-3 mt-4">Social Media</h6>
    <div class="row g-3"><div class="col-md-6"><div class="mb-3"><label class="form-label">Facebook App ID</label><input type="text" name="facebook_app_id" class="form-control" value="<?php echo esc_attr(SEOSetting::get('facebook_app_id', '')); ?>"></div></div><div class="col-md-6"><div class="mb-3"><label class="form-label">Twitter Handle</label><input type="text" name="twitter_site" class="form-control" value="<?php echo esc_attr(SEOSetting::get('twitter_site', '@trendypost')); ?>"></div></div></div>

    <h6 class="mb-3 mt-4">Default SEO</h6>
    <div class="mb-3"><label class="form-label">Default Title</label><input type="text" name="seo_default_title" class="form-control" value="<?php echo esc_attr(Setting::get('seo_default_title', '')); ?>"></div>
    <div class="mb-3"><label class="form-label">Default Description</label><textarea name="seo_default_description" class="form-control" rows="2"><?php echo esc_attr(Setting::get('seo_default_description', '')); ?></textarea></div>
    <div class="mb-3"><label class="form-label">Default Keywords</label><input type="text" name="seo_default_keywords" class="form-control" value="<?php echo esc_attr(Setting::get('seo_default_keywords', '')); ?>"></div>

    <h6 class="mb-3 mt-4">Robots.txt</h6>
    <div class="mb-3"><textarea name="robots_txt" class="form-control font-monospace" rows="8"><?php echo esc_attr(SEOSetting::getRobotsTxt()); ?></textarea>
    <div class="form-text">Admin, API, login, and preview URLs stay noindex. Public posts and pages should remain allowed.</div></div>

    <h6 class="mb-3 mt-4">Automatic Google indexing</h6>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="auto_indexing" id="auto_indexing" value="1" <?php echo SEOSetting::get('auto_indexing', '1') === '1' ? 'checked' : ''; ?>>
        <label class="form-check-label" for="auto_indexing">Ping Google/Bing sitemaps and IndexNow whenever a post or page is published</label>
    </div>
    <div class="mb-3">
        <label class="form-label">Google Indexing API service-account JSON (optional)</label>
        <textarea name="indexing_api_key" class="form-control font-monospace" rows="5" placeholder="Paste service account JSON to also call Google Indexing API"><?php echo esc_attr(SEOSetting::get('indexing_api_key', '')); ?></textarea>
        <div class="form-text">Leave blank to use IndexNow + sitemap ping only. Admin routes are never submitted.</div>
    </div>

    <button type="submit" class="btn btn-success">Save SEO Settings</button>
    </form>
</div></div>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>