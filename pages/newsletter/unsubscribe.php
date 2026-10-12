<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$msg = 'Enter your email to unsubscribe.';
$ok = false;
$email = sanitize($_GET['email'] ?? $_POST['email'] ?? '');
$token = sanitize($_GET['token'] ?? $_POST['token'] ?? '');
if ($token !== '') {
    if (NewsletterModel::unsubscribe($token)) {
        $msg = 'You have been unsubscribed. You will no longer receive our newsletter.';
        $ok = true;
    } else {
        $msg = 'That unsubscribe link is invalid or already used.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    if (!is_valid_email($email)) {
        $msg = 'Please enter a valid email address.';
    } elseif (NewsletterModel::unsubscribeByEmail($email)) {
        $msg = 'You have been unsubscribed. You will no longer receive our newsletter.';
        $ok = true;
    } else {
        $msg = 'We could not find an active subscription for that email.';
    }
}
$pageTitle = 'Unsubscribe | TrendyPost';
$seoMeta = meta_tags($pageTitle, '', '', 'noindex,nofollow');
include INCLUDES_PATH . 'Header.php';
?>
<main class="main-content py-5"><div class="container"><div class="row justify-content-center"><div class="col-md-5">
<div class="card border-0 shadow"><div class="card-body p-5">
    <h3 class="text-center mb-4">Unsubscribe</h3>
    <div class="alert alert-<?php echo $ok ? 'success' : 'info'; ?>"><?php echo esc($msg); ?></div>
    <form method="POST">
        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo esc_attr($email); ?>" required></div>
        <button class="btn btn-outline-danger w-100" type="submit">Unsubscribe</button>
    </form>
</div></div></div></div></div></main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>
