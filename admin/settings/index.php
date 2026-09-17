<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('settings.manage');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    $groups = ['general','social','appearance','email','monetization'];
    foreach ($_POST as $key => $value) { if (!in_array($key, ['_token','action']) && !empty($value)) { Setting::set($key, is_array($value) ? json_encode($value) : sanitize($value)); } }
    flash_set('success', 'Settings saved.'); header('Location: ' . admin_url('settings')); exit;
}
$pageTitle = 'Settings | Admin';
$general = Setting::getGroup('general');
$social = Setting::getGroup('social');
$email = Setting::getGroup('email');
$monetization = Setting::getGroup('monetization');
include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><h4 class="mb-0"><i class="bi bi-gear me-2"></i>Settings</h4></div>
<div class="card border-0" style="max-width:900px"><div class="card-body">
    <form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
    <ul class="nav nav-tabs mb-4" id="settingsTab"><li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#general">General</a></li><li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#social">Social</a></li><li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#email">Email</a></li><li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#monetization">Monetization</a></li></ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="general">
            <div class="mb-3"><label class="form-label">Site Name</label><input type="text" name="site_name" class="form-control" value="<?php echo esc_attr($general['site_name'] ?? 'TrendyPost'); ?>"></div>
            <div class="mb-3"><label class="form-label">Tagline</label><input type="text" name="site_tagline" class="form-control" value="<?php echo esc_attr($general['site_tagline'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Site Email</label><input type="email" name="site_email" class="form-control" value="<?php echo esc_attr($general['site_email'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Site Phone</label><input type="text" name="site_phone" class="form-control" value="<?php echo esc_attr($general['site_phone'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Footer About</label><textarea name="footer_about" class="form-control" rows="3"><?php echo esc_attr($general['footer_about'] ?? ''); ?></textarea></div>
            <div class="mb-3"><label class="form-label">Posts Per Page</label><input type="number" name="posts_per_page" class="form-control" value="<?php echo esc_attr($general['posts_per_page'] ?? '10'); ?>"></div>
        </div>
        <div class="tab-pane fade" id="social">
            <div class="mb-3"><label class="form-label">Facebook</label><input type="url" name="social_facebook" class="form-control" value="<?php echo esc_attr($social['social_facebook'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Twitter/X</label><input type="url" name="social_twitter" class="form-control" value="<?php echo esc_attr($social['social_twitter'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Instagram</label><input type="url" name="social_instagram" class="form-control" value="<?php echo esc_attr($social['social_instagram'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">YouTube</label><input type="url" name="social_youtube" class="form-control" value="<?php echo esc_attr($social['social_youtube'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">LinkedIn</label><input type="url" name="social_linkedin" class="form-control" value="<?php echo esc_attr($social['social_linkedin'] ?? ''); ?>"></div>
        </div>
        <div class="tab-pane fade" id="email">
            <div class="mb-3"><label class="form-label">SMTP Host</label><input type="text" name="smtp_host" class="form-control" value="<?php echo esc_attr($email['smtp_host'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">SMTP Port</label><input type="number" name="smtp_port" class="form-control" value="<?php echo esc_attr($email['smtp_port'] ?? '587'); ?>"></div>
            <div class="mb-3"><label class="form-label">SMTP Username</label><input type="text" name="smtp_username" class="form-control" value="<?php echo esc_attr($email['smtp_username'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">SMTP Password</label><input type="password" name="smtp_password" class="form-control" value="<?php echo esc_attr($email['smtp_password'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">From Email</label><input type="email" name="smtp_from_email" class="form-control" value="<?php echo esc_attr($email['smtp_from_email'] ?? ''); ?>"></div>
        </div>
        <div class="tab-pane fade" id="monetization">
            <div class="mb-3"><label class="form-label">Google AdSense ID</label><input type="text" name="google_adsense_id" class="form-control" value="<?php echo esc_attr($monetization['google_adsense_id'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Adsterra Key</label><input type="text" name="adsterra_key" class="form-control" value="<?php echo esc_attr($monetization['adsterra_key'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Monetag Key</label><input type="text" name="monetag_key" class="form-control" value="<?php echo esc_attr($monetization['monetag_key'] ?? ''); ?>"></div>
        </div>
    </div>
    <button type="submit" class="btn btn-success mt-3">Save Settings</button>
    </form>
</div></div>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>