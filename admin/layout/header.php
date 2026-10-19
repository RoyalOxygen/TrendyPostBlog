<?php if (!defined('ADMIN')) die('Direct access not permitted.');

// Ensure route is clean
$route = $_GET['route'] ?? 'dashboard';
$route = preg_replace('#^admin/?|^/admin/?#', '', $route);

if (!isset($stats)) { 
    $stats = ['comments' => ['pending' => Database::count('comments', "status = 'pending'") ?? 0]]; 
}
$newMessages = Database::count('contact_messages', "status = 'new'") ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin | TrendyPost'; ?></title>
    <meta name="robots" content="noindex,nofollow">
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="<?php echo ADMIN_ASSETS_URL; ?>admin.css?v=<?php echo APP_VERSION; ?>" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-overlay" id="adminOverlay"></div>
<div class="admin-wrapper">
    <!-- Sidebar -->
    <nav class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="<?php echo admin_url('dashboard'); ?>" class="sidebar-brand">
                <img src="<?php echo esc_attr(site_asset_url(Setting::get('site_logo', 'assets/images/trendypost-logo.png'), image_url('trendypost-logo.png'))); ?>" alt="<?php echo esc_attr(Setting::get('site_name', 'TrendyPost')); ?>" height="32" onerror="this.style.display='none';this.parentElement.innerHTML+='<span class=\'fw-bold text-white\'>TP</span>'">
                <span class="ms-2 fw-bold text-white"><?php echo esc(Setting::get('site_name', 'TrendyPost')); ?></span>
            </a>
            <button class="btn btn-link sidebar-toggle d-lg-none" id="sidebarToggle"><i class="bi bi-x-lg text-white"></i></button>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo admin_url('dashboard'); ?>" class="<?php echo $route==='dashboard'?'active':''; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>

            <li class="nav-section">CONTENT</li>
            <li><a href="<?php echo admin_url('posts'); ?>" class="<?php echo $route==='posts'?'active':''; ?>"><i class="bi bi-file-text"></i> Posts</a></li>
            <li><a href="<?php echo admin_url('ai-sources'); ?>" class="<?php echo $route==='ai-sources'?'active':''; ?>"><i class="bi bi-robot"></i> AI Sources</a></li>
            <li><a href="<?php echo admin_url('categories'); ?>" class="<?php echo $route==='categories'?'active':''; ?>"><i class="bi bi-folder"></i> Categories</a></li>
            <li><a href="<?php echo admin_url('tags'); ?>" class="<?php echo $route==='tags'?'active':''; ?>"><i class="bi bi-tags"></i> Tags</a></li>
            <li><a href="<?php echo admin_url('pages'); ?>" class="<?php echo $route==='pages'?'active':''; ?>"><i class="bi bi-file-earmark"></i> Pages</a></li>
            <li><a href="<?php echo admin_url('comments'); ?>" class="<?php echo $route==='comments'?'active':''; ?>"><i class="bi bi-chat-dots"></i> Comments<?php $pc = $stats['comments']['pending'] ?? 0; if ($pc): ?> <span class="badge bg-warning"><?php echo $pc; ?></span><?php endif; ?></a></li>
            <?php if (Auth::isAdmin() || Auth::can('contact.view')): ?>
            <li><a href="<?php echo admin_url('messages'); ?>" class="<?php echo $route==='messages'?'active':''; ?>"><i class="bi bi-inbox"></i> Messages<?php if ($newMessages): ?> <span class="badge bg-warning"><?php echo $newMessages; ?></span><?php endif; ?></a></li>
            <?php endif; ?>
            <li><a href="<?php echo admin_url('media'); ?>" class="<?php echo $route==='media'?'active':''; ?>"><i class="bi bi-images"></i> Media Library</a></li>

            <li class="nav-section">USERS</li>
            <li><a href="<?php echo admin_url('users'); ?>" class="<?php echo $route==='users'?'active':''; ?>"><i class="bi bi-people"></i> Users</a></li>

            <li class="nav-section">MONETIZATION</li>
            <li><a href="<?php echo admin_url('ads'); ?>" class="<?php echo $route==='ads'?'active':''; ?>"><i class="bi bi-badge-ad"></i> Advertisements</a></li>

            <li class="nav-section">SEO & ANALYTICS</li>
            <li><a href="<?php echo admin_url('seo'); ?>" class="<?php echo $route==='seo'?'active':''; ?>"><i class="bi bi-search"></i> SEO Settings</a></li>
            <li><a href="<?php echo admin_url('analytics'); ?>" class="<?php echo $route==='analytics'?'active':''; ?>"><i class="bi bi-graph-up"></i> Analytics</a></li>
            <li><a href="<?php echo admin_url('newsletters'); ?>" class="<?php echo $route==='newsletters'?'active':''; ?>"><i class="bi bi-envelope"></i> Newsletters</a></li>

            <li class="nav-section">SYSTEM</li>
            <li><a href="<?php echo admin_url('menus'); ?>" class="<?php echo $route==='menus'?'active':''; ?>"><i class="bi bi-menu-button"></i> Menus</a></li>
            <?php if (Auth::isSuperAdmin() || Auth::can('audit.view')): ?>
            <li><a href="<?php echo admin_url('activity'); ?>" class="<?php echo $route==='activity'?'active':''; ?>"><i class="bi bi-clock-history"></i> Activity Logs</a></li>
            <?php endif; ?>
            <li><a href="<?php echo admin_url('settings'); ?>" class="<?php echo $route==='settings'?'active':''; ?>"><i class="bi bi-gear"></i> Settings</a></li>

            <li class="nav-section">ACCOUNT</li>
            <li><a href="<?php echo admin_url('profile'); ?>"><i class="bi bi-person"></i> Profile</a></li>
            <li><a href="<?php echo url('logout'); ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <button class="btn btn-link topbar-toggle" id="topbarToggle"><i class="bi bi-list"></i></button>
            <div class="topbar-search d-none d-md-block">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search..." class="form-control form-control-sm">
            </div>
            <div class="topbar-actions ms-auto d-flex align-items-center gap-2 gap-md-3">
                <a href="<?php echo url(); ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-globe"></i> <span class="d-none d-sm-inline">View Site</span></a>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:35px;height:35px;font-size:14px;font-weight:600;background-color:var(--tp-primary, #00C853)!important">
                            <?php echo strtoupper(substr(Auth::user()['display_name'] ?? 'A', 0, 1)); ?>
                        </div>
                        <span class="d-none d-md-block fw-medium"><?php echo Auth::user()['display_name'] ?? 'Admin'; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo admin_url('profile'); ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo url('logout'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="admin-content">
            <?php echo flash_display(); ?>