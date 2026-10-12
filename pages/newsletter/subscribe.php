<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$msg = '';
$ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_token'] ?? '')) {
        $msg = 'Invalid request.';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!is_valid_email($email)) {
            $msg = 'Please enter a valid email address.';
        } else {
            $result = NewsletterModel::subscribe($email);
            $ok = !empty($result['success']);
            $msg = $result['message'] ?? ($ok ? 'You are subscribed.' : 'Could not subscribe.');
        }
    }
}
$pageTitle = 'Subscribe | TrendyPost';
$seoMeta = meta_tags($pageTitle, 'Subscribe to the TrendyPost newsletter.', '', 'noindex,nofollow');
include INCLUDES_PATH . 'Header.php';
?>
<main class="main-content py-5"><div class="container"><div class="row justify-content-center"><div class="col-md-5">
<div class="card border-0 shadow"><div class="card-body p-5">
    <h3 class="text-center mb-4">Subscribe</h3>
    <?php if ($msg): ?><div class="alert alert-<?php echo $ok ? 'success' : 'danger'; ?>"><?php echo esc($msg); ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
        <button class="btn btn-success w-100" type="submit" style="background-color:var(--tp-primary);border-color:var(--tp-primary)">Subscribe</button>
    </form>
</div></div></div></div></div></main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>
