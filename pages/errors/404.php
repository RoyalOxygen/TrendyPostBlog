<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');

$pageTitle = 'Page Not Found | ' . Setting::get('site_name', 'TrendyPost');
$seoMeta = meta_tags($pageTitle, 'The page you are looking for could not be found.', '', 'noindex,nofollow');
$canonicalUrl = canonical_url();

include INCLUDES_PATH . 'Header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>404 - Page Not Found</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url(); ?>">Home</a></li>
                <li class="breadcrumb-item active">404</li>
            </ol>
        </nav>
    </div>
</section>

<main class="main-content py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="display-1 text-muted mb-4" style="font-size: 120px; font-weight: 700; color: var(--tp-gray-300);">404</div>
                <h2 class="mb-3">Oops! Page Not Found</h2>
                <p class="text-muted mb-4">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="<?php echo url(); ?>" class="btn btn-success" style="background-color:var(--tp-primary);border-color:var(--tp-primary);">
                        <i class="bi bi-house"></i> Go to Homepage
                    </a>
                    <a href="<?php echo url('archive'); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-archive"></i> Browse Archive
                    </a>
                    <a href="<?php echo url('search'); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-search"></i> Search
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include INCLUDES_PATH . 'Footer.php'; ?>