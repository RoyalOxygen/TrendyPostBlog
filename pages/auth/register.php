<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
if (Auth::check()) { header('Location: ' . admin_url('dashboard')); exit; }
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_token'] ?? '')) { $error = 'Invalid request.'; }
    else {
        $result = Auth::register($_POST);
        if ($result['success']) { $success = $result['message']; }
        else { $error = $result['message']; }
    }
}
$pageTitle = 'Register | TrendyPost';
$seoMeta = meta_tags($pageTitle, '', '', 'noindex,nofollow');
include INCLUDES_PATH . 'Header.php';
?>
<main class="main-content py-5"><div class="container"><div class="row justify-content-center"><div class="col-md-5">
<div class="card border-0 shadow"><div class="card-body p-5">
    <h3 class="text-center mb-4">Create Account</h3>
    <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Display Name</label><input type="text" name="display_name" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="8"></div>
        <div class="mb-3"><label class="form-label">Confirm Password</label><input type="password" name="password_confirm" class="form-control" required minlength="8"></div>
        <button type="submit" class="btn btn-success w-100" style="background-color:var(--tp-primary);border-color:var(--tp-primary)">Register</button>
    </form>
    <p class="text-center mt-3 mb-0 text-muted">Already have an account? <a href="<?php echo url('login'); ?>">Login</a></p>
</div></div>
</div></div></div></main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>