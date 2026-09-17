<?php if (!defined('ADMIN')) die('Direct access not permitted.');

// Ensure route is clean
$route = $_GET['route'] ?? 'dashboard';
$route = preg_replace('#^admin/?|^/admin/?#', '', $route);

if (!isset($stats)) { 
    $stats = ['comments' => ['pending' => Database::count('comments', "status = 'pending'") ?? 0]]; 
}
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
    
    <!-- Admin CSS - Using correct path for root domain -->
    <link href="<?php echo ADMIN_ASSETS_URL; ?>admin.css" rel="stylesheet">
    
    <!-- Fallback inline styles -->
    <style>
        /* Quick fix styles in case CSS doesn't load */
        :root {
            --tp-primary: #00C853;
            --tp-secondary: #1a1a2e;
            --tp-gray-100: #f8f9fa;
            --tp-gray-200: #e9ecef;
            --tp-gray-300: #dee2e6;
            --tp-gray-400: #ced4da;
            --tp-gray-500: #adb5bd;
            --tp-gray-600: #6c757d;
            --tp-gray-700: #495057;
            --tp-gray-800: #343a40;
            --tp-gray-900: #212529;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; color: #495057; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 260px; background: #1a1a2e; color: #fff; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; z-index: 1000; transition: transform 0.3s; }
        .admin-sidebar::-webkit-scrollbar { width: 4px; }
        .admin-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }
        .admin-main { margin-left: 260px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
        .admin-topbar { background: #fff; padding: 12px 24px; border-bottom: 1px solid #e9ecef; display: flex; align-items: center; gap: 16px; position: sticky; top: 0; z-index: 100; }
        .admin-content { padding: 24px; flex: 1; }
        .admin-footer { padding: 16px 24px; background: #fff; border-top: 1px solid #e9ecef; }
        .sidebar-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar-brand { display: flex; align-items: center; gap: 10px; color: #fff; text-decoration: none; font-size: 18px; font-weight: 700; }
        .sidebar-brand:hover { color: #fff; }
        .sidebar-nav { list-style: none; padding: 12px 0; margin: 0; }
        .sidebar-nav li { padding: 0 12px; }
        .sidebar-nav .nav-section { padding: 20px 16px 8px; font-size: 11px; text-transform: uppercase; color: rgba(255,255,255,0.3); letter-spacing: 1px; font-weight: 600; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 10px 16px; color: rgba(255,255,255,0.65); text-decoration: none; border-radius: 8px; transition: all 0.2s; font-size: 14px; }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .sidebar-nav a.active { background: #00C853; color: #fff; box-shadow: 0 4px 12px rgba(0,200,83,0.3); }
        .sidebar-nav a i { font-size: 18px; width: 24px; text-align: center; }
        .sidebar-nav a .badge { margin-left: auto; font-size: 11px; padding: 3px 8px; }
        .topbar-toggle { display: none; color: #495057; font-size: 24px; padding: 4px 8px; background: none; border: none; }
        .topbar-toggle:hover { color: #212529; }
        .topbar-search { position: relative; flex: 1; max-width: 400px; }
        .topbar-search .form-control { padding-left: 36px; border-radius: 8px; font-size: 14px; height: 38px; }
        .topbar-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #adb5bd; }
        @media (max-width: 992px) { 
            .admin-sidebar { transform: translateX(-100%); } 
            .admin-main { margin-left: 0; } 
            .topbar-toggle { display: block; } 
            .admin-sidebar.open { transform: translateX(0); } 
        }
        @media (max-width: 576px) { .admin-content { padding: 16px; } }
    </style>
</head>
<body class="admin-body">
<div class="admin-wrapper">
    <!-- Sidebar -->
    <nav class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="<?php echo admin_url('dashboard'); ?>" class="sidebar-brand">
                <img src="<?php echo image_url('trendypost-logo.png'); ?>" alt="TrendyPost" height="32" onerror="this.style.display='none';this.parentElement.innerHTML+='<span class=\'fw-bold text-white\'>TP</span>'">
                <span class="ms-2 fw-bold text-white">TrendyPost</span>
            </a>
            <button class="btn btn-link sidebar-toggle d-lg-none" id="sidebarToggle"><i class="bi bi-x-lg text-white"></i></button>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo admin_url('dashboard'); ?>" class="<?php echo $route==='dashboard'?'active':''; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>

            <li class="nav-section">CONTENT</li>
            <li><a href="<?php echo admin_url('posts'); ?>" class="<?php echo $route==='posts'?'active':''; ?>"><i class="bi bi-file-text"></i> Posts</a></li>
            <li><a href="<?php echo admin_url('categories'); ?>" class="<?php echo $route==='categories'?'active':''; ?>"><i class="bi bi-folder"></i> Categories</a></li>
            <li><a href="<?php echo admin_url('tags'); ?>" class="<?php echo $route==='tags'?'active':''; ?>"><i class="bi bi-tags"></i> Tags</a></li>
            <li><a href="<?php echo admin_url('pages'); ?>" class="<?php echo $route==='pages'?'active':''; ?>"><i class="bi bi-file-earmark"></i> Pages</a></li>
            <li><a href="<?php echo admin_url('comments'); ?>" class="<?php echo $route==='comments'?'active':''; ?>"><i class="bi bi-chat-dots"></i> Comments<?php $pc = $stats['comments']['pending'] ?? 0; if ($pc): ?> <span class="badge bg-warning"><?php echo $pc; ?></span><?php endif; ?></a></li>
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
            <div class="topbar-actions ms-auto d-flex align-items-center gap-3">
                <a href="<?php echo url(); ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-globe"></i> <span>View Site</span></a>
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