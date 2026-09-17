<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = Auth::forgotPassword($_POST['email'] ?? '');
    if ($result['success']) { $msg = 'If the email exists, a reset link has been sent.'; }
}
$pageTitle = 'Forgot Password | TrendyPost';
$seoMeta = meta_tags($pageTitle, '', '', 'noindex,nofollow');
include INCLUDES_PATH . 'Header.php';
?>
<main class="main-content py-5"><div class="container"><div class="row justify-content-center"><div class="col-md-5">
<div class="card border-0 shadow"><div class="card-body p-5">
    <h3 class="text-center mb-4">Reset Password</h3>
    <?php if($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>
    <form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        <div class="mb-3"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" required></div>
        <button type="submit" class="btn btn-success w-100" style="background-color:var(--tp-primary);border-color:var(--tp-primary)">Send Reset Link</button>
    </form>
</div></div>
</div></div></div></main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>