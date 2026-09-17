<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
$user = Auth::user();
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        $result = Auth::changePassword($user['id'], $_POST['current_password'], $_POST['new_password']);
        if (!$result['success']) { $error = $result['message']; }
        else { $success = true; }
    }
    $update = ['display_name'=>sanitize($_POST['display_name']??''),'first_name'=>sanitize($_POST['first_name']??''),'last_name'=>sanitize($_POST['last_name']??''),'bio'=>sanitize($_POST['bio']??'')];
    Database::update('users', $update, 'id = ?', [$user['id']]);
    Auth::updateProfile($user['id'], $update);
    if (!isset($error)) { flash_set('success', 'Profile updated.'); header('Location: ' . admin_url('profile')); exit; }
}
$pageTitle = 'My Profile | Admin';
include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><h4 class="mb-0"><i class="bi bi-person me-2"></i>My Profile</h4></div>
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