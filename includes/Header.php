<?php
/**
 * TRENDYPOST - Theme Header
 * =========================
 * Frontend header matching the reference UI design.
 * Primary color: Green (#00C853) from TrendyPost logo.
 */

if (!defined('TRENDYPOST')) die('Direct access not permitted.');

// Get site settings
$siteName = Setting::get('site_name', 'TrendyPost');
$siteTagline = Setting::get('site_tagline', 'Your Source for Trending News');
$primaryColor = Setting::get('primary_color', '#00C853');
$secondaryColor = Setting::get('secondary_color', '#1a1a1a');
$social = Setting::getGroup('social');
$siteEmail = Setting::get('site_email', '');
$sitePhone = Setting::get('site_phone', '');

// Get breaking news
$breakingNews = Post::getBreaking(3);

// Get categories for nav
$navCategories = Category::getParentCategories();

// Get current user
$currentUser = Auth::user();
?><!DOCTYPE html>
<html lang="<?php echo APP_LOCALE; ?>">
<head>
    <meta name="bitmedia-site-verification" content="f982186b8145faff4a99d5b357e5e3c8" />
    <script async custom-element="amp-ad" src="https://cdn.ampproject.org/v0/amp-ad-0.1.js"></script>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1796797159544561"
     crossorigin="anonymous"></script>
    <script>(function(s){s.dataset.zone='11183686',s.src='https://n6wxm.com/vignette.min.js'})([document.documentElement, document.body].filter(Boolean).pop().appendChild(document.createElement('script')))</script>
    <script>(function(s){s.dataset.zone='11183673',s.src='https://nap5k.com/tag.min.js'})([document.documentElement, document.body].filter(Boolean).pop().appendChild(document.createElement('script')))</script>
    <script src="https://5gvci.com/act/files/tag.min.js?z=11183653" data-cfasync="false" async></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <?php echo $seoMeta ?? meta_tags(); ?>
    
    <!-- Open Graph -->
    <?php echo $ogTags ?? og_tags(); ?>
    
    <!-- Twitter Cards -->
    <?php echo $twitterTags ?? twitter_tags(); ?>
    
    <!-- Canonical -->
    <?php if (!empty($canonicalUrl)): ?>
    <link rel="canonical" href="<?php echo $canonicalUrl; ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo image_url('favicon.ico'); ?>">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo css_url('theme.css'); ?>" rel="stylesheet">
    
    <!-- Schema JSON-LD -->
    <?php echo $schemaMarkup ?? ''; ?>
    
    <!-- Google Analytics -->
    <?php $gaId = SEOSetting::get('google_analytics_4', ''); if ($gaId): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $gaId; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo $gaId; ?>');
    </script>
    <?php endif; ?>
</head>
<body>
    
    <!-- Top Bar -->
    <div class="top-bar bg-dark text-white py-1">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <?php if (!empty($social['social_facebook'])): ?>
                        <a href="<?php echo $social['social_facebook']; ?>" class="text-white-50 hover-white" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_twitter'])): ?>
                        <a href="<?php echo $social['social_twitter']; ?>" class="text-white-50 hover-white" target="_blank" rel="noopener"><i class="bi bi-twitter-x"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_instagram'])): ?>
                        <a href="<?php echo $social['social_instagram']; ?>" class="text-white-50 hover-white" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_youtube'])): ?>
                        <a href="<?php echo $social['social_youtube']; ?>" class="text-white-50 hover-white" target="_blank" rel="noopener"><i class="bi bi-youtube"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_linkedin'])): ?>
                        <a href="<?php echo $social['social_linkedin']; ?>" class="text-white-50 hover-white" target="_blank" rel="noopener"><i class="bi bi-linkedin"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-white-50">
                        <?php if ($sitePhone): ?><i class="bi bi-telephone me-1"></i><?php echo $sitePhone; ?> &nbsp;|&nbsp;<?php endif; ?>
                        <?php if ($siteEmail): ?><i class="bi bi-envelope me-1"></i><?php echo $siteEmail; ?><?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Logo Area -->
    <div class="logo-area py-3 border-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <a href="<?php echo url(); ?>" class="text-decoration-none">
                        <img src="<?php echo image_url('TrendyPost_Logo_Text.png'); ?>" alt="<?php echo $siteName; ?>" class="logo-img" height="45">
                    </a>
                </div>
                <div class="col-md-4 text-center">
                    <span class="tagline text-muted fst-italic"><?php echo $siteTagline; ?></span>
                </div>
                <div class="col-md-4 text-end">
                    <form action="<?php echo search_url(); ?>" method="GET" class="search-form">
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" placeholder="Search..." aria-label="Search" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                            <button class="btn btn-success" type="submit" style="background-color:<?php echo $primaryColor; ?>;border-color:<?php echo $primaryColor; ?>"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark main-nav" style="background-color:<?php echo $secondaryColor; ?>;">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url(); ?>">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('archive'); ?>">ARCHIVE</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">CATEGORY</a>
                        <ul class="dropdown-menu">
                            <?php foreach ($navCategories as $cat): ?>
                            <li><a class="dropdown-item" href="<?php echo category_url($cat['slug']); ?>"><?php echo $cat['name']; ?></a></li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo url('categories'); ?>">All Categories</a></li>
                        </ul>
                    </li>
                    
                    <?php foreach (Page::getMenuPages() as $menuPage): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo page_url($menuPage['slug']); ?>"><?php echo strtoupper($menuPage['title']); ?></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($currentUser): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $currentUser['display_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (Auth::isAdmin()): ?>
                            <li><a class="dropdown-item" href="<?php echo admin_url('dashboard'); ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?php echo url('logout'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('login'); ?>"><i class="bi bi-person"></i> LOGIN</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Breaking News Ticker -->
    <?php if (!empty($breakingNews)): ?>
    <div class="breaking-news bg-success py-2" style="background-color:<?php echo $primaryColor; ?>!important;">
        <div class="container">
            <div class="d-flex align-items-center">
                <span class="badge bg-dark me-3">BREAKING NEWS</span>
                <div class="breaking-ticker flex-grow-1 overflow-hidden">
                    <div class="ticker-wrap">
                        <?php foreach ($breakingNews as $news): ?>
                        <a href="<?php echo post_url($news['slug']); ?>" class="text-white text-decoration-none me-4">
                            <?php echo $news['title']; ?> <span class="text-white-50">|</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Header Ad -->
    <?php $headerAds = get_ads('header_banner', 1); if (!empty($headerAds)): ?>
    <div class="container mt-3">
        <div class="text-center">
            <?php echo render_ad($headerAds[0]); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Flash Messages -->
    <div class="container mt-3">
        <?php echo flash_display(); ?>
    </div>
