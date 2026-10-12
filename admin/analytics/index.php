<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('analytics.view');
$period = $_GET['period'] ?? '7d';
$days = ['7d'=>7,'30d'=>30,'90d'=>90];
$dayCount = $days[$period] ?? 7;
$viewsData = Analytics::getRecentViews($dayCount);
$popularPosts = Analytics::getPopularPosts(10);
$totalViews = array_sum(array_column($viewsData, 'page_views'));
$totalVisitors = array_sum(array_column($viewsData, 'unique_visitors'));
$pageTitle = 'Analytics | Admin';
include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-graph-up me-2"></i>Analytics</h4>
    <div class="btn-group"><a href="<?php echo admin_url('analytics&period=7d'); ?>" class="btn btn-sm <?php echo $period==='7d'?'btn-success':'btn-outline-secondary'; ?>">7 Days</a><a href="<?php echo admin_url('analytics&period=30d'); ?>" class="btn btn-sm <?php echo $period==='30d'?'btn-success':'btn-outline-secondary'; ?>">30 Days</a><a href="<?php echo admin_url('analytics&period=90d'); ?>" class="btn btn-sm <?php echo $period==='90d'?'btn-success':'btn-outline-secondary'; ?>">90 Days</a></div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card stat-card border-0 p-4 text-center h-100"><h3 class="text-success"><?php echo number_format($totalViews); ?></h3><p class="text-muted mb-0">Total Page Views</p></div></div>
    <div class="col-md-4"><div class="card stat-card border-0 p-4 text-center h-100"><h3 class="text-info"><?php echo number_format($totalVisitors); ?></h3><p class="text-muted mb-0">Unique Visitors</p></div></div>
    <div class="col-md-4"><div class="card stat-card border-0 p-4 text-center h-100"><h3 class="text-primary"><?php echo number_format(Post::count()); ?></h3><p class="text-muted mb-0">Total Articles</p></div></div>
</div>
<div class="card border-0 mb-4"><div class="card-body"><div class="chart-box"><canvas id="analyticsChart"></canvas></div></div></div>
<div class="card border-0"><div class="card-header bg-white pt-4 px-4"><h5 class="mb-0">Top Articles</h5></div><div class="card-body"><div class="table-responsive">
    <table class="table table-hover"><thead><tr><th>#</th><th>Title</th><th class="text-end">Views</th></tr></thead>
    <tbody><?php foreach($popularPosts as $idx=>$p): ?><tr>
        <td width="40"><?php echo $idx+1; ?></td><td><a href="<?php echo post_url($p['slug']); ?>" target="_blank" class="text-decoration-none"><?php echo $p['title']; ?></a></td><td class="text-end fw-medium"><?php echo number_format($p['view_count']); ?></td>
    </tr><?php endforeach; ?></tbody>
</table></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('analyticsChart'), {
    type: 'bar',
    data: { labels: <?php echo json_encode(array_map(fn($v)=>date('M j',strtotime($v['date'])),$viewsData)); ?>, datasets: [
        { label: 'Page Views', data: <?php echo json_encode(array_map(fn($v)=>$v['page_views'],$viewsData)); ?>, backgroundColor: 'rgba(0,200,83,0.7)', borderRadius: 6 },
        { label: 'Visitors', data: <?php echo json_encode(array_map(fn($v)=>$v['unique_visitors']??0,$viewsData)); ?>, backgroundColor: 'rgba(33,150,243,0.5)', borderRadius: 6 }
    ]},
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
});
</script>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>