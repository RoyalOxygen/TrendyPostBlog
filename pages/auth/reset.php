<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$error = '';
$success = '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_token'] ?? '')) {
        $error = 'Invalid request.';
    } elseif (empty($_POST['password']) || $_POST['password'] !== ($_POST['password_confirm'] ?? '')) {
        $error = 'Passwords do not match.';
    } else {
        $result = Auth::resetPassword($token, $_POST['password']);
        if ($result['success']) {
            $success = $result['message'];
            Auth::logActivity('password_reset', 'Password was reset');
        } else {
            $error = $result['message'];
        }
    }
}
$pageTitle = 'Reset Password | TrendyPost';
$seoMeta = meta_tags($pageTitle, '', '', 'noindex,nofollow');
include INCLUDES_PATH . 'Header.php';
?>
<main class="main-content py-5"><div class="container"><div class="row justify-content-center"><div class="col-md-5">
<div class="card border-0 shadow"><div class="card-body p-5">
    <h3 class="text-center mb-4">Set New Password</h3>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo esc($error); ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo esc($success); ?></div>
        <p class="text-center mb-0"><a href="<?php echo url('login'); ?>">Go to login</a></p>
    <?php else: ?>
    <form method="POST">
        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
        <div class="mb-3"><label class="form-label">New password</label><input type="password" name="password" class="form-control" required minlength="8"></div>
        <div class="mb-3"><label class="form-label">Confirm password</label><input type="password" name="password_confirm" class="form-control" required minlength="8"></div>
        <button type="submit" class="btn btn-success w-100" style="background-color:var(--tp-primary);border-color:var(--tp-primary)">Update password</button>
    </form>
    <?php endif; ?>
</div></div>
</div></div></div></main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>
