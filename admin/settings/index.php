<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('settings.manage');

if (!class_exists('Media')) {
    require_once INCLUDES_PATH . 'Media.php';
}

$groupMap = [
    'site_name' => 'general', 'site_tagline' => 'general', 'site_email' => 'general', 'site_phone' => 'general',
    'site_address' => 'general', 'footer_about' => 'general', 'footer_copyright' => 'general',
    'posts_per_page' => 'content', 'posts_per_home' => 'content', 'excerpt_length' => 'content',
    'related_posts_count' => 'content',
    'nested_comments' => 'comments',
    'social_facebook' => 'social', 'social_twitter' => 'social', 'social_instagram' => 'social',
    'social_youtube' => 'social', 'social_linkedin' => 'social', 'social_pinterest' => 'social', 'social_tiktok' => 'social',
    'smtp_host' => 'email', 'smtp_port' => 'email', 'smtp_username' => 'email', 'smtp_password' => 'email',
    'smtp_encryption' => 'email', 'smtp_from_email' => 'email', 'smtp_from_name' => 'email',
    'notify_welcome_email' => 'email', 'notify_on_comment' => 'email', 'notify_on_publish' => 'email', 'notify_on_contact' => 'email',
    'google_adsense_id' => 'monetization', 'adsterra_key' => 'monetization', 'monetag_key' => 'monetization',
    'max_login_attempts' => 'security', 'lockout_duration' => 'security',
    'primary_color' => 'appearance', 'secondary_color' => 'appearance',
    'header_style' => 'appearance', 'footer_style' => 'appearance', 'homepage_layout' => 'appearance',
    'cache_duration' => 'system',
];
$rawKeys = ['smtp_password', 'footer_about', 'footer_copyright'];
$checkboxes = [
    'notify_welcome_email' => 'email',
    'notify_on_comment' => 'email',
    'notify_on_publish' => 'email',
    'notify_on_contact' => 'email',
    'show_author_box' => 'content',
    'show_related_posts' => 'content',
    'comment_moderation' => 'comments',
    'allow_guest_comments' => 'comments',
    'dark_mode_enabled' => 'appearance',
    'maintenance_mode' => 'system',
    'registration_enabled' => 'system',
    'cache_enabled' => 'system',
    'lazy_loading' => 'system',
];
$uploads = [
    'site_logo' => ['folder' => 'branding', 'allowed' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']],
    'site_logo_footer' => ['folder' => 'branding', 'allowed' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']],
    'site_favicon' => ['folder' => 'branding', 'allowed' => ['ico', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg']],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    if (($_POST['action'] ?? '') === 'test_email') {
        $to = sanitize($_POST['test_email'] ?? (Auth::user()['email'] ?? ''));
        $ok = class_exists('Mailer') && Mailer::send($to, 'TrendyPost test email', '<p>SMTP is working. This is a test message from your admin settings.</p>');
        flash_set($ok ? 'success' : 'error', $ok ? 'Test email sent to ' . $to : 'Failed to send test email. Check SMTP settings.');
        header('Location: ' . admin_url('settings'));
        exit;
    }
    foreach ($checkboxes as $cb => $group) {
        Setting::set($cb, isset($_POST[$cb]) ? '1' : '0', $group);
    }
    foreach ($groupMap as $key => $group) {
        if (isset($checkboxes[$key])) {
            continue;
        }
        if (!array_key_exists($key, $_POST)) {
            continue;
        }
        $value = $_POST[$key];
        if ($key === 'smtp_password' && $value === '') {
            continue;
        }
        if (in_array($key, ['primary_color', 'secondary_color'], true)) {
            $value = trim((string) $value);
            if (!is_hex_color($value)) {
                continue;
            }
        }
        if (!in_array($key, $rawKeys, true)) {
            $value = sanitize((string) $value);
        }
        Setting::set($key, $value, $group);
    }
    foreach ($uploads as $key => $meta) {
        if (!empty($_FILES[$key]) && ($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $rel = Media::fromUpload($_FILES[$key], $meta['folder'], $key, Auth::id(), $meta['allowed']);
            if ($rel) {
                Setting::set($key, $rel, 'general');
            }
        }
    }
    Auth::logActivity('settings', 'Updated site settings');
    flash_set('success', 'Settings saved.');
    header('Location: ' . admin_url('settings'));
    exit;
}

$pageTitle = 'Settings | Admin';
$general = Setting::getGroup('general');
$social = Setting::getGroup('social');
$email = Setting::getGroup('email');
$monetization = Setting::getGroup('monetization');
$security = Setting::getGroup('security');
$appearance = Setting::getGroup('appearance');
$content = Setting::getGroup('content');
$comments = Setting::getGroup('comments');
$system = Setting::getGroup('system');
$logoUrl = site_asset_url($general['site_logo'] ?? 'assets/images/logo.png', image_url('logo.png'));
$footerLogoUrl = site_asset_url($general['site_logo_footer'] ?? '', '');
$faviconUrl = site_asset_url($general['site_favicon'] ?? 'assets/images/favicon.ico', image_url('favicon.ico'));
include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2"><h4 class="mb-0"><i class="bi bi-gear me-2"></i>Settings</h4></div>
<div class="card border-0"><div class="card-body">
    <form method="POST" enctype="multipart/form-data"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
    <ul class="nav nav-tabs mb-4" id="settingsTab">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#general">General</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#branding">Branding</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#content">Content</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#social">Social</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#email">Email</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#security">Security</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#system">System</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#monetization">Monetization</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="general">
            <div class="mb-3"><label class="form-label">Site Name</label><input type="text" name="site_name" class="form-control" value="<?php echo esc_attr($general['site_name'] ?? 'TrendyPost'); ?>"></div>
            <div class="mb-3"><label class="form-label">Tagline</label><input type="text" name="site_tagline" class="form-control" value="<?php echo esc_attr($general['site_tagline'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Site Email</label><input type="email" name="site_email" class="form-control" value="<?php echo esc_attr($general['site_email'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Site Phone</label><input type="text" name="site_phone" class="form-control" value="<?php echo esc_attr($general['site_phone'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Site Address</label><input type="text" name="site_address" class="form-control" value="<?php echo esc_attr($general['site_address'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Footer About</label><textarea name="footer_about" class="form-control" rows="3"><?php echo esc($general['footer_about'] ?? ''); ?></textarea></div>
            <div class="mb-3"><label class="form-label">Footer Copyright</label><input type="text" name="footer_copyright" class="form-control" value="<?php echo esc_attr($general['footer_copyright'] ?? ''); ?>"></div>
        </div>
        <div class="tab-pane fade" id="branding">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Site logo</label>
                    <?php if ($logoUrl): ?><div class="mb-2"><img src="<?php echo esc_attr($logoUrl); ?>" alt="Logo" style="max-height:48px"></div><?php endif; ?>
                    <input type="file" name="site_logo" class="form-control" accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Footer logo</label>
                    <?php if ($footerLogoUrl): ?><div class="mb-2"><img src="<?php echo esc_attr($footerLogoUrl); ?>" alt="Footer logo" style="max-height:48px;background:#111;padding:6px"></div><?php endif; ?>
                    <input type="file" name="site_logo_footer" class="form-control" accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
                    <div class="form-text">Optional. Falls back to the site logo.</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Favicon</label>
                    <?php if ($faviconUrl): ?><div class="mb-2"><img src="<?php echo esc_attr($faviconUrl); ?>" alt="Favicon" width="32" height="32"></div><?php endif; ?>
                    <input type="file" name="site_favicon" class="form-control" accept=".ico,.png,.jpg,.jpeg,.gif,.webp,.svg">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Primary color</label><input type="color" name="primary_color" class="form-control form-control-color" value="<?php echo esc_attr(is_hex_color((string) ($appearance['primary_color'] ?? '')) ? $appearance['primary_color'] : '#00C853'); ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Secondary color</label><input type="color" name="secondary_color" class="form-control form-control-color" value="<?php echo esc_attr(is_hex_color((string) ($appearance['secondary_color'] ?? '')) ? $appearance['secondary_color'] : '#1a1a1a'); ?>"></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Header style</label>
                    <select name="header_style" class="form-select">
                        <?php foreach (['default' => 'Default', 'minimal' => 'Minimal', 'centered' => 'Centered'] as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo ($appearance['header_style'] ?? 'default') === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3"><label class="form-label">Footer style</label>
                    <select name="footer_style" class="form-select">
                        <?php foreach (['default' => 'Default', 'compact' => 'Compact'] as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo ($appearance['footer_style'] ?? 'default') === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3"><label class="form-label">Homepage layout</label>
                    <select name="homepage_layout" class="form-select">
                        <?php foreach (['magazine' => 'Magazine', 'list' => 'List', 'grid' => 'Grid'] as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo ($appearance['homepage_layout'] ?? 'magazine') === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="dark_mode_enabled" id="dm" <?php echo ($appearance['dark_mode_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="dm">Enable dark mode class on the public site</label></div>
        </div>
        <div class="tab-pane fade" id="content">
            <div class="mb-3"><label class="form-label">Posts Per Page</label><input type="number" min="1" max="50" name="posts_per_page" class="form-control" value="<?php echo esc_attr($content['posts_per_page'] ?? $general['posts_per_page'] ?? '10'); ?>"></div>
            <div class="mb-3"><label class="form-label">Posts on homepage</label><input type="number" min="1" max="50" name="posts_per_home" class="form-control" value="<?php echo esc_attr($content['posts_per_home'] ?? '6'); ?>"></div>
            <div class="mb-3"><label class="form-label">Excerpt length</label><input type="number" min="40" max="500" name="excerpt_length" class="form-control" value="<?php echo esc_attr($content['excerpt_length'] ?? '200'); ?>"></div>
            <div class="mb-3"><label class="form-label">Related posts count</label><input type="number" min="0" max="12" name="related_posts_count" class="form-control" value="<?php echo esc_attr($content['related_posts_count'] ?? '4'); ?>"></div>
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="show_author_box" id="sab" <?php echo ($content['show_author_box'] ?? '1') === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="sab">Show author box on posts</label></div>
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="show_related_posts" id="srp" <?php echo ($content['show_related_posts'] ?? '1') === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="srp">Show related posts</label></div>
            <hr>
            <h6>Comments</h6>
            <div class="mb-3"><label class="form-label">Nested comment depth</label><input type="number" min="0" max="5" name="nested_comments" class="form-control" value="<?php echo esc_attr($comments['nested_comments'] ?? '3'); ?>"></div>
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="comment_moderation" id="cm" <?php echo ($comments['comment_moderation'] ?? '1') === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="cm">Hold comments for moderation</label></div>
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="allow_guest_comments" id="agc" <?php echo ($comments['allow_guest_comments'] ?? '1') === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="agc">Allow guest comments</label></div>
        </div>
        <div class="tab-pane fade" id="social">
            <div class="mb-3"><label class="form-label">Facebook</label><input type="url" name="social_facebook" class="form-control" value="<?php echo esc_attr($social['social_facebook'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Twitter/X</label><input type="url" name="social_twitter" class="form-control" value="<?php echo esc_attr($social['social_twitter'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Instagram</label><input type="url" name="social_instagram" class="form-control" value="<?php echo esc_attr($social['social_instagram'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">YouTube</label><input type="url" name="social_youtube" class="form-control" value="<?php echo esc_attr($social['social_youtube'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">LinkedIn</label><input type="url" name="social_linkedin" class="form-control" value="<?php echo esc_attr($social['social_linkedin'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Pinterest</label><input type="url" name="social_pinterest" class="form-control" value="<?php echo esc_attr($social['social_pinterest'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">TikTok</label><input type="url" name="social_tiktok" class="form-control" value="<?php echo esc_attr($social['social_tiktok'] ?? ''); ?>"></div>
        </div>
        <div class="tab-pane fade" id="email">
            <div class="mb-3"><label class="form-label">SMTP Host</label><input type="text" name="smtp_host" class="form-control" value="<?php echo esc_attr($email['smtp_host'] ?? ''); ?>"></div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">SMTP Port</label><input type="number" name="smtp_port" class="form-control" value="<?php echo esc_attr($email['smtp_port'] ?? '587'); ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Encryption</label>
                    <select name="smtp_encryption" class="form-select">
                        <?php foreach (['tls' => 'TLS / STARTTLS', 'ssl' => 'SSL', 'none' => 'None'] as $enc => $label): ?>
                        <option value="<?php echo $enc; ?>" <?php echo ($email['smtp_encryption'] ?? 'tls') === $enc ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3"><label class="form-label">SMTP Username</label><input type="text" name="smtp_username" class="form-control" value="<?php echo esc_attr($email['smtp_username'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">SMTP Password</label><input type="password" name="smtp_password" class="form-control" placeholder="<?php echo !empty($email['smtp_password']) ? 'Leave blank to keep current' : ''; ?>"></div>
            <div class="mb-3"><label class="form-label">From Email</label><input type="email" name="smtp_from_email" class="form-control" value="<?php echo esc_attr($email['smtp_from_email'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">From Name</label><input type="text" name="smtp_from_name" class="form-control" value="<?php echo esc_attr($email['smtp_from_name'] ?? 'TrendyPost'); ?>"></div>
            <hr>
            <h6>Notifications</h6>
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="notify_welcome_email" id="nw" <?php echo ($email['notify_welcome_email'] ?? '1') === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="nw">Send welcome email to new users</label></div>
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="notify_on_comment" id="nc" <?php echo ($email['notify_on_comment'] ?? '1') === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="nc">Email staff / post author when a comment is posted</label></div>
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="notify_on_publish" id="np" <?php echo ($email['notify_on_publish'] ?? '1') === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="np">Email superadmin when a post is published</label></div>
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="notify_on_contact" id="nct" <?php echo ($email['notify_on_contact'] ?? '1') === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="nct">Email staff when the contact form is submitted</label></div>
        </div>
        <div class="tab-pane fade" id="security">
            <div class="mb-3"><label class="form-label">Max login attempts</label><input type="number" min="1" max="50" name="max_login_attempts" class="form-control" value="<?php echo esc_attr($security['max_login_attempts'] ?? '5'); ?>"><div class="form-text">Failed logins before lockout. Stored in the database and applied on the login form.</div></div>
            <div class="mb-3"><label class="form-label">Lockout duration (seconds)</label><input type="number" min="60" max="86400" name="lockout_duration" class="form-control" value="<?php echo esc_attr($security['lockout_duration'] ?? '900'); ?>"><div class="form-text">How long an IP or account stays locked after too many failures. Default 900 (15 minutes).</div></div>
        </div>
        <div class="tab-pane fade" id="system">
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="registration_enabled" id="re" <?php echo ($system['registration_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="re">Allow public registration</label></div>
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="maintenance_mode" id="mm" <?php echo ($system['maintenance_mode'] ?? '0') === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="mm">Maintenance mode (public visitors see a holding page; staff can still log in)</label></div>
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="cache_enabled" id="ce" <?php echo ($system['cache_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="ce">Enable cache</label></div>
            <div class="mb-3"><label class="form-label">Cache duration (seconds)</label><input type="number" min="60" max="86400" name="cache_duration" class="form-control" value="<?php echo esc_attr($system['cache_duration'] ?? '3600'); ?>"></div>
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="lazy_loading" id="ll" <?php echo ($system['lazy_loading'] ?? '1') === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="ll">Lazy-load images</label></div>
        </div>
        <div class="tab-pane fade" id="monetization">
            <div class="mb-3"><label class="form-label">Google AdSense ID</label><input type="text" name="google_adsense_id" class="form-control" value="<?php echo esc_attr($monetization['google_adsense_id'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Adsterra Key</label><input type="text" name="adsterra_key" class="form-control" value="<?php echo esc_attr($monetization['adsterra_key'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">Monetag Key</label><input type="text" name="monetag_key" class="form-control" value="<?php echo esc_attr($monetization['monetag_key'] ?? ''); ?>"></div>
            <p class="text-muted small">Network keys are stored here. Create the actual units under Advertisements using AdSense / custom HTML / affiliate types and assign a placement.</p>
        </div>
    </div>
    <button type="submit" class="btn btn-success mt-3">Save Settings</button>
    </form>
    <hr>
    <form method="POST" class="row g-2 align-items-end">
        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="test_email">
        <div class="col-md-6">
            <label class="form-label">Send test email</label>
            <input type="email" name="test_email" class="form-control" value="<?php echo esc_attr(Auth::user()['email'] ?? ''); ?>" required>
        </div>
        <div class="col-md-3">
            <button class="btn btn-outline-secondary" type="submit">Send test</button>
        </div>
    </form>
</div></div>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>
