<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
if (Auth::check()) { header('Location: ' . admin_url('dashboard')); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_token'] ?? '')) { $error = 'Invalid request.'; }
    else {
        $result = Auth::login($_POST['email'] ?? '', $_POST['password'] ?? '', !empty($_POST['remember']));
        if ($result['success']) { header('Location: ' . $result['redirect']); exit; }
        else { $error = $result['message']; }
    }
}
$pageTitle = 'Login | TrendyPost';
$seoMeta = meta_tags($pageTitle, '', '', 'noindex,nofollow');
include INCLUDES_PATH . 'Header.php';
?>
<main class="main-content py-5"><div class="container"><div class="row justify-content-center"><div class="col-md-5">
<div class="card border-0 shadow"><div class="card-body p-5">
    <h3 class="text-center mb-4">Welcome Back</h3>
    <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        <div class="mb-3"><label class="form-label">Email or Username</label><input type="text" name="email" class="form-control" required autofocus></div>
        <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="remember" id="remember"><label class="form-check-label" for="remember">Remember me</label></div>
            <a href="<?php echo url('forgot-password'); ?>" class="text-decoration-none small">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-success w-100" style="background-color:var(--tp-primary);border-color:var(--tp-primary)">Login</button>
    </form>
    <p class="text-center mt-3 mb-0 text-muted">Don't have an account? <a href="<?php echo url('register'); ?>">Register</a></p>
</div></div>
</div></div></div></main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>