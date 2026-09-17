<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Database::insert('contact_messages', [
        'name' => sanitize($_POST['name'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'subject' => sanitize($_POST['subject'] ?? ''),
        'message' => sanitize($_POST['message'] ?? ''),
        'ip_address' => client_ip()
    ]);
    $success = true;
}
$pageTitle = 'Contact Us | TrendyPost';
$seoMeta = meta_tags($pageTitle, 'Contact TrendyPost for inquiries and feedback.', '', 'index,follow');
include INCLUDES_PATH . 'Header.php';
?>
<section class="page-header"><div class="container"><h1>Contact Us</h1></div></section>
<main class="main-content py-5"><div class="container"><div class="row"><div class="col-lg-8">
<?php if($success): ?><div class="alert alert-success">Thank you! Your message has been sent.</div><?php endif; ?>
<div class="single-post"><form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
<div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div></div>
<div class="mb-3"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control"></div>
<div class="mb-3"><label class="form-label">Message</label><textarea name="message" class="form-control" rows="6" required></textarea></div>
<button type="submit" class="btn btn-success" style="background-color:var(--tp-primary);border-color:var(--tp-primary)">Send Message</button>
</form></div>
</div><aside class="col-lg-4"><div class="widget"><h5 class="widget-title">Contact Info</h5>
<p><i class="bi bi-envelope text-success me-2"></i> <?php echo Setting::get('site_email', 'admin@trendypost.com'); ?></p>
<?php if($phone=Setting::get('site_phone', '')): ?><p><i class="bi bi-telephone text-success me-2"></i> <?php echo $phone; ?></p><?php endif; ?></div></aside></div></div></main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>