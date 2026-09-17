<?php
/**
 * TRENDYPOST - Admin Dashboard
 * =============================
 * Overview with analytics cards, charts, recent activity.
 */
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('dashboard.view');

$stats = Analytics::getDashboardStats();
$recentPosts = Post::getLatest(5);
$recentComments = Database::fetchAll("SELECT c.*, p.title as post_title, p.slug as post_slug FROM comments c JOIN posts p ON c.post_id = p.id ORDER BY c.created_at DESC LIMIT 5");
$popularPosts = Post::getPopular(5);
$recentViews = Analytics::getRecentViews(7);
$recentUsers = User::getRecent(5);

$pageTitle = 'Dashboard | Admin';
include ADMIN_PATH . 'layout/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h4>
        <span class="text-muted"><?php echo date('F j, Y'); ?></span>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-primary-soft"><i class="bi bi-file-text text-primary"></i></div>
                    <div class="ms-3">
                        <h6 class="mb-0 text-muted">Total Posts</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['posts']['total']); ?></h3>
                        <small class="text-success"><i class="bi bi-arrow-up"></i> Published</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-success-soft"><i class="bi bi-people text-success"></i></div>
                    <div class="ms-3">
                        <h6 class="mb-0 text-muted">Total Users</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['users']['total']); ?></h3>
                        <small class="text-muted"><?php echo $stats['users']['authors']; ?> Authors</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-warning-soft"><i class="bi bi-chat-dots text-warning"></i></div>
                    <div class="ms-3">
                        <h6 class="mb-0 text-muted">Comments</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['comments']['total']); ?></h3>
                        <?php if ($stats['comments']['pending'] > 0): ?><small class="text-warning"><i class="bi bi-exclamation-circle"></i> <?php echo $stats['comments']['pending']; ?> pending</small><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-info-soft"><i class="bi bi-eye text-info"></i></div>
                    <div class="ms-3">
                        <h6 class="mb-0 text-muted">Today's Views</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['today']['page_views'] ?? 0); ?></h3>
                        <small class="text-muted">Page views today</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Second Row Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 p-3 text-center">
                <h5 class="text-primary mb-1"><?php echo $stats['posts']['draft']; ?></h5>
                <small class="text-muted">Draft Posts</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 p-3 text-center">
                <h5 class="text-warning mb-1"><?php echo $stats['posts']['scheduled']; ?></h5>
                <small class="text-muted">Scheduled</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 p-3 text-center">
                <h5 class="text-success mb-1"><?php echo $stats['categories']; ?></h5>
                <small class="text-muted">Categories</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 p-3 text-center">
                <h5 class="text-info mb-1"><?php echo $stats['subscribers']; ?></h5>
                <small class="text-muted">Subscribers</small>
            </div>
        </div>
    </div>
    
    <!-- Charts & Tables Row -->
    <div class="row g-4">
        <!-- Page Views Chart -->
        <div class="col-lg-8">
            <div class="card border-0">
                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center pt-4 px-4">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Page Views (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="viewsChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Popular Posts -->
        <div class="col-lg-4">
            <div class="card border-0">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="mb-0"><i class="bi bi-fire me-2"></i>Popular Posts</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($popularPosts as $idx => $pp): ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="badge bg-success">#<?php echo $idx + 1; ?></span>
                        <div class="flex-grow-1 min-width-0">
                            <a href="<?php echo post_url($pp['slug']); ?>" class="text-decoration-none d-block text-truncate fw-semibold"><?php echo $pp['title']; ?></a>
                            <small class="text-muted"><?php echo format_number($pp['view_count']); ?> views</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity Row -->
    <div class="row g-4 mt-1">
        <!-- Recent Posts -->
        <div class="col-lg-6">
            <div class="card border-0">
                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center pt-4 px-4">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Posts</h5>
                    <a href="<?php echo admin_url('posts'); ?>" class="btn btn-sm btn-outline-success">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Title</th><th>Author</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentPosts as $rp): ?>
                                <tr>
                                    <td><a href="<?php echo post_url($rp['slug']); ?>" class="text-decoration-none fw-medium"><?php echo truncate($rp['title'], 40); ?></a></td>
                                    <td><?php echo $rp['author_name'] ?? 'Admin'; ?></td>
                                    <td><span class="badge bg-<?php echo $rp['status']==='published'?'success':($rp['status']==='draft'?'secondary':'warning'); ?>"><?php echo $rp['status']; ?></span></td>
                                    <td><small class="text-muted"><?php echo time_ago($rp['published_at'] ?? $rp['created_at']); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Comments -->
        <div class="col-lg-6">
            <div class="card border-0">
                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center pt-4 px-4">
                    <h5 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Recent Comments</h5>
                    <a href="<?php echo admin_url('comments'); ?>" class="btn btn-sm btn-outline-success">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Author</th><th>Comment</th><th>Post</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentComments as $rc): ?>
                                <tr>
                                    <td><small><?php echo $rc['author_name']; ?></small></td>
                                    <td><small class="text-muted"><?php echo truncate($rc['content'], 50); ?></small></td>
                                    <td><a href="<?php echo post_url($rc['post_slug']); ?>" class="text-decoration-none small"><?php echo truncate($rc['post_title'], 25); ?></a></td>
                                    <td><span class="badge bg-<?php echo $rc['status']==='approved'?'success':($rc['status']==='pending'?'warning':'danger'); ?>"><?php echo $rc['status']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card border-0">
                <div class="card-body d-flex flex-wrap gap-2">
                    <a href="<?php echo admin_url('posts&action=create'); ?>" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>New Post</a>
                    <a href="<?php echo admin_url('categories&action=create'); ?>" class="btn btn-outline-success"><i class="bi bi-folder-plus me-1"></i>New Category</a>
                    <a href="<?php echo admin_url('pages&action=create'); ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-plus me-1"></i>New Page</a>
                    <a href="<?php echo admin_url('users&action=create'); ?>" class="btn btn-outline-success"><i class="bi bi-person-plus me-1"></i>New User</a>
                    <a href="<?php echo admin_url('ads&action=create'); ?>" class="btn btn-outline-success"><i class="bi bi-badge-ad me-1"></i>New Ad</a>
                    <a href="<?php echo admin_url('media'); ?>" class="btn btn-outline-success"><i class="bi bi-cloud-upload me-1"></i>Upload Media</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('viewsChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_map(fn($v) => date('M j', strtotime($v['date'])), $recentViews)); ?>,
        datasets: [{
            label: 'Page Views',
            data: <?php echo json_encode(array_map(fn($v) => $v['page_views'], $recentViews)); ?>,
            borderColor: '#00C853',
            backgroundColor: 'rgba(0,200,83,0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#00C853'
        }, {
            label: 'Unique Visitors',
            data: <?php echo json_encode(array_map(fn($v) => $v['unique_visitors'] ?? 0, $recentViews)); ?>,
            borderColor: '#2196F3',
            backgroundColor: 'rgba(33,150,243,0.05)',
            fill: true,
            tension: 0.4,
            pointRadius: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }, x: { grid: { display: false } } }
    }
});
</script>

<?php include ADMIN_PATH . 'layout/footer.php'; ?>
