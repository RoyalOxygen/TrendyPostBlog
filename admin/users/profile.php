<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
$user = Auth::user();
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    if (!empty($_POST['current_password']) || !empty($_POST['new_password'])) {
        $result = Auth::changePassword($user['id'], $_POST['current_password'] ?? '', $_POST['new_password'] ?? '');
        if (!$result['success']) { $error = $result['message']; }
        else { $success = true; flash_set('success', 'Password changed.'); header('Location: ' . admin_url('profile')); exit; }
    } else {
        $update = ['display_name'=>sanitize($_POST['display_name']??''),'first_name'=>sanitize($_POST['first_name']??''),'last_name'=>sanitize($_POST['last_name']??''),'bio'=>sanitize($_POST['bio']??'')];
        Auth::updateProfile($user['id'], $update);
        flash_set('success', 'Profile updated.');
        header('Location: ' . admin_url('profile'));
        exit;
    }
}
$weekStats = Analytics::getAuthorStats((int) $user['id'], 7);
$monthStats = Analytics::getAuthorStats((int) $user['id'], 30);
$pageTitle = 'My Profile | Admin';
include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2"><h4 class="mb-0"><i class="bi bi-person me-2"></i>My Profile</h4>
    <a href="<?php echo admin_url('analytics'); ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-graph-up me-1"></i>Site analytics</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="card border-0 h-100"><div class="card-body text-center">
        <h3 class="mb-1"><?php echo number_format($monthStats['posts']); ?></h3>
        <p class="text-muted mb-0 small">Published posts</p>
    </div></div></div>
    <div class="col-6 col-xl-3"><div class="card border-0 h-100"><div class="card-body text-center">
        <h3 class="mb-1 text-success"><?php echo number_format($monthStats['all_time_views']); ?></h3>
        <p class="text-muted mb-0 small">All-time post views</p>
    </div></div></div>
    <div class="col-6 col-xl-3"><div class="card border-0 h-100"><div class="card-body text-center">
        <h3 class="mb-1 text-primary"><?php echo number_format($weekStats['views']); ?></h3>
        <p class="text-muted mb-0 small">Views (last 7 days)</p>
        <small class="text-muted"><?php echo number_format($weekStats['uniques']); ?> unique</small>
    </div></div></div>
    <div class="col-6 col-xl-3"><div class="card border-0 h-100"><div class="card-body text-center">
        <h3 class="mb-1 text-info"><?php echo number_format($monthStats['views']); ?></h3>
        <p class="text-muted mb-0 small">Views (last 30 days)</p>
        <small class="text-muted"><?php echo number_format($monthStats['uniques']); ?> unique</small>
    </div></div></div>
</div>
<div class="card border-0 mb-4">
    <div class="card-header bg-white pt-4 px-4 d-flex justify-content-between align-items-center">
        <h6 class="mb-0">My post performance (last 30 days)</h6>
        <span class="text-muted small"><?php echo number_format($monthStats['uniques']); ?> unique readers</span>
    </div>
    <div class="card-body">
        <?php if (empty($monthStats['top_posts'])): ?>
        <p class="text-muted mb-0">No published posts yet.</p>
        <?php else: ?>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead><tr><th>Post</th><th class="text-end">Views (30d)</th><th class="text-end">Total views</th><th class="text-end">Status</th></tr></thead>
            <tbody>
            <?php foreach ($monthStats['top_posts'] as $sp): ?>
            <tr>
                <td><a href="<?php echo post_url($sp['slug']); ?>" target="_blank" class="text-decoration-none"><?php echo esc(mb_strimwidth((string) $sp['title'], 0, 70, '...')); ?></a></td>
                <td class="text-end"><?php echo number_format((int) $sp['period_views']); ?></td>
                <td class="text-end"><?php echo number_format((int) $sp['view_count']); ?></td>
                <td class="text-end"><a href="<?php echo admin_url('posts&action=edit&id=' . (int) $sp['id']); ?>" class="btn btn-sm btn-outline-secondary">Edit</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0"><div class="card-body">
            <h6 class="mb-3">Profile Information</h6>
            <?php if(isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <?php if($success): ?><div class="alert alert-success">Password changed.</div><?php endif; ?>
            <form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
            <div class="row g-3"><div class="col-md-6"><div class="mb-3"><label class="form-label">Username</label><input type="text" class="form-control" value="<?php echo $user['username']; ?>" disabled></div></div><div class="col-md-6"><div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" value="<?php echo $user['email']; ?>" disabled></div></div></div>
            <div class="row g-3"><div class="col-md-6"><div class="mb-3"><label class="form-label">Display Name</label><input type="text" name="display_name" class="form-control" value="<?php echo esc_attr($user['display_name']??''); ?>"></div></div><div class="col-md-6"><div class="mb-3"><label class="form-label">Role</label><input type="text" class="form-control" value="<?php echo $user['role_display']??''; ?>" disabled></div></div></div>
            <div class="row g-3"><div class="col-md-6"><div class="mb-3"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" value="<?php echo esc_attr($user['first_name']??''); ?>"></div></div><div class="col-md-6"><div class="mb-3"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" value="<?php echo esc_attr($user['last_name']??''); ?>"></div></div></div>
            <div class="mb-3"><label class="form-label">Bio</label><textarea name="bio" class="form-control" rows="3"><?php echo esc_attr($user['bio']??''); ?></textarea></div>
            <button type="submit" class="btn btn-success">Update Profile</button>
            </form>
        </div></div>
        <div class="card border-0 mt-4"><div class="card-body">
            <h6 class="mb-3">Change Password</h6>
            <form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
            <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required minlength="8"></div>
            <button type="submit" class="btn btn-warning">Change Password</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0"><div class="card-body text-center">
            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width:100px;height:100px;font-size:40px;font-weight:700;background-color:var(--tp-primary)!important"><?php echo strtoupper(substr($user['display_name']??$user['username'],0,1)); ?></div>
            <h5><?php echo $user['display_name'] ?? $user['username']; ?></h5>
            <p class="text-muted mb-1"><?php echo $user['role_display'] ?? ''; ?></p>
            <p class="text-muted small"><?php echo $user['email']; ?></p>
            <p class="text-muted small">Member since <?php echo format_date($user['created_at'] ?? 'now'); ?></p>
        </div></div>
    </div>
</div>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>