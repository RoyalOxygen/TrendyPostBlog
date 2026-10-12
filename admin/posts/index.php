<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('posts.view');

$action = $_GET['action'] ?? 'index';
$pageTitle = 'Posts | Admin';

if ($action === 'create' || $action === 'edit') {
    Auth::requirePermission('posts.create');
    include __DIR__ . '/form.php';
} elseif ($action === 'delete') {
    Auth::requirePermission('posts.delete');
    $id = intval($_GET['id'] ?? 0);
    if ($id && verify_csrf($_GET['_token'] ?? '')) {
        // Get post image to delete it
        $post = Post::getById($id);
        if ($post && !empty($post['featured_image'])) {
            $imagePath = UPLOADS_PATH . $post['featured_image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        Database::delete('posts', 'id = ?', [$id]);
        Auth::logActivity('post_delete', 'Deleted post #' . $id . ($post['title'] ?? '' ? ': ' . $post['title'] : ''));
        flash_set('success', 'Post deleted successfully.');
    }
    header('Location: ' . admin_url('posts'));
    exit;
} else {
    // List posts with filters
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = 15;
    $statusFilter = $_GET['status'] ?? '';
    $searchQuery = trim($_GET['search'] ?? '');
    
    // Build filters
    $filters = [];
    if ($statusFilter) {
        $filters['status'] = $statusFilter;
    }
    if ($searchQuery) {
        $filters['search'] = $searchQuery;
    }
    
    // Get posts and count
    $posts = Post::getList($filters, $page, $perPage);
    $total = Post::count($filters);
    
    include ADMIN_PATH . 'layout/header.php';
?>

<style>
    .post-thumbnail {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        background: #f8f9fa;
        transition: transform 0.2s;
    }
    .post-thumbnail:hover {
        transform: scale(1.1);
    }
    .post-thumbnail-placeholder {
        width: 50px;
        height: 50px;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
        font-size: 20px;
    }
    .status-badge {
        font-size: 11px;
        padding: 4px 10px;
    }
    .filter-btn {
        transition: all 0.2s;
    }
    .filter-btn:hover {
        transform: translateY(-1px);
    }
    .filter-btn.active {
        box-shadow: 0 2px 8px rgba(0,200,83,0.3);
    }
    .table-row-hover:hover {
        background-color: #f8f9fa;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-file-text me-2"></i>Posts</h4>
    <a href="<?php echo admin_url('posts&action=create'); ?>" class="btn btn-success">
        <i class="bi bi-plus-lg me-1"></i>New Post
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <!-- Filters -->
        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?php echo admin_url('posts'); ?>" 
                       class="btn btn-sm filter-btn <?php echo !$statusFilter ? 'btn-success active' : 'btn-outline-secondary'; ?>">
                        <i class="bi bi-collection me-1"></i>All 
                        <span class="badge bg-light text-dark ms-1"><?php echo Database::count('posts', "status != 'trash'"); ?></span>
                    </a>
                    <a href="<?php echo admin_url('posts&status=published'); ?>" 
                       class="btn btn-sm filter-btn <?php echo $statusFilter === 'published' ? 'btn-success active' : 'btn-outline-secondary'; ?>">
                        <i class="bi bi-check-circle me-1"></i>Published 
                        <span class="badge bg-light text-dark ms-1"><?php echo Database::count('posts', "status = 'published'"); ?></span>
                    </a>
                    <a href="<?php echo admin_url('posts&status=draft'); ?>" 
                       class="btn btn-sm filter-btn <?php echo $statusFilter === 'draft' ? 'btn-success active' : 'btn-outline-secondary'; ?>">
                        <i class="bi bi-pencil me-1"></i>Drafts 
                        <span class="badge bg-light text-dark ms-1"><?php echo Database::count('posts', "status = 'draft'"); ?></span>
                    </a>
                    <a href="<?php echo admin_url('posts&status=scheduled'); ?>" 
                       class="btn btn-sm filter-btn <?php echo $statusFilter === 'scheduled' ? 'btn-success active' : 'btn-outline-secondary'; ?>">
                        <i class="bi bi-clock me-1"></i>Scheduled 
                        <span class="badge bg-light text-dark ms-1"><?php echo Database::count('posts', "status = 'scheduled'"); ?></span>
                    </a>
                    <a href="<?php echo admin_url('posts&status=pending_review'); ?>" 
                       class="btn btn-sm filter-btn <?php echo $statusFilter === 'pending_review' ? 'btn-success active' : 'btn-outline-secondary'; ?>">
                        <i class="bi bi-hourglass-split me-1"></i>Pending Review 
                        <span class="badge bg-light text-dark ms-1"><?php echo Database::count('posts', "status = 'pending_review'"); ?></span>
                    </a>
                    <a href="<?php echo admin_url('posts&status=trash'); ?>" 
                       class="btn btn-sm filter-btn <?php echo $statusFilter === 'trash' ? 'btn-success active' : 'btn-outline-secondary'; ?>">
                        <i class="bi bi-trash me-1"></i>Trash 
                        <span class="badge bg-light text-dark ms-1"><?php echo Database::count('posts', "status = 'trash'"); ?></span>
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <form method="GET" action="<?php echo admin_url('posts'); ?>" class="d-flex gap-2">
                    <input type="hidden" name="route" value="posts">
                    <?php if ($statusFilter): ?>
                        <input type="hidden" name="status" value="<?php echo $statusFilter; ?>">
                    <?php endif; ?>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search posts..." value="<?php echo esc_attr($searchQuery); ?>">
                        <?php if ($searchQuery): ?>
                            <a href="<?php echo admin_url('posts' . ($statusFilter ? '&status=' . $statusFilter : '')); ?>" 
                               class="btn btn-outline-danger">
                                <i class="bi bi-x"></i>
                            </a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-outline-secondary">
                            Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Posts Table -->
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">Image</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th class="text-center">Views</th>
                        <th class="text-center">Comments</th>
                        <th>Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No posts found.
                                <?php if ($searchQuery): ?>
                                    <br>Try a different search term.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: foreach ($posts as $p): 
                        // Get featured image - FIXED for all filters
                        $thumbnail = image_url('placeholder.jpg');
                        $hasImage = false;
                        
                        if (!empty($p['featured_image'])) {
                            $thumbnail = media_url($p['featured_image']);
                            $hasImage = $thumbnail !== image_url('placeholder.jpg');
                        }
                        
                        // Status badge class
                        $statusClass = 'secondary';
                        $statusIcon = 'bi-circle';
                        switch ($p['status']) {
                            case 'published':
                                $statusClass = 'success';
                                $statusIcon = 'bi-check-circle-fill';
                                break;
                            case 'draft':
                                $statusClass = 'secondary';
                                $statusIcon = 'bi-pencil-square';
                                break;
                            case 'scheduled':
                                $statusClass = 'warning';
                                $statusIcon = 'bi-clock-fill';
                                break;
                            case 'pending_review':
                                $statusClass = 'info';
                                $statusIcon = 'bi-hourglass-split';
                                break;
                            case 'trash':
                                $statusClass = 'danger';
                                $statusIcon = 'bi-trash-fill';
                                break;
                        }
                    ?>
                    <tr class="table-row-hover">
                        <!-- Image -->
                        <td>
                            <a href="<?php echo admin_url('posts&action=edit&id=' . $p['id']); ?>" title="Edit post">
                                <?php if ($hasImage): ?>
                                    <img src="<?php echo $thumbnail; ?>" alt="<?php echo esc_attr($p['title']); ?>" 
                                         class="post-thumbnail"
                                         onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'post-thumbnail-placeholder\'><i class=\'bi bi-image\'></i></div>'">
                                <?php else: ?>
                                    <div class="post-thumbnail-placeholder">
                                        <i class="bi bi-image"></i>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </td>
                        
                        <!-- Title -->
                        <td>
                            <a href="<?php echo admin_url('posts&action=edit&id=' . $p['id']); ?>" class="fw-medium text-decoration-none text-dark">
                                <?php echo truncate($p['title'], 60); ?>
                            </a>
                            <div class="mt-1">
                                <?php if ($p['is_featured']): ?>
                                    <span class="badge bg-warning text-dark me-1" title="Featured">
                                        <i class="bi bi-star-fill"></i> Featured
                                    </span>
                                <?php endif; ?>
                                <?php if ($p['is_breaking']): ?>
                                    <span class="badge bg-danger me-1" title="Breaking News">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Breaking
                                    </span>
                                <?php endif; ?>
                                <?php if ($p['is_sticky']): ?>
                                    <span class="badge bg-primary me-1" title="Sticky">
                                        <i class="bi bi-pin-fill"></i> Sticky
                                    </span>
                                <?php endif; ?>
                                <br>
                                <small class="text-muted">
                                    <i class="bi bi-tag me-1"></i><?php echo $p['slug']; ?>
                                </small>
                            </div>
                        </td>
                        
                        <!-- Author -->
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if (!empty($p['author_avatar'])): ?>
                                    <img src="<?php echo upload_url($p['author_avatar']); ?>" 
                                         style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; margin-right: 6px;"
                                         onerror="this.style.display='none';">
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-1" 
                                         style="width: 24px; height: 24px; font-size: 10px; font-weight: 600;">
                                        <?php echo strtoupper(substr($p['author_name'] ?? 'A', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <span><?php echo $p['author_name'] ?? 'Admin'; ?></span>
                            </div>
                        </td>
                        
                        <!-- Category -->
                        <td>
                            <?php if (!empty($p['category_name'])): ?>
                                <span class="badge" style="background: <?php echo $p['category_color'] ?? '#6c757d'; ?>; color: #fff;">
                                    <?php echo $p['category_name']; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">Uncategorized</span>
                            <?php endif; ?>
                        </td>
                        
                        <!-- Status -->
                        <td>
                            <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                <i class="bi <?php echo $statusIcon; ?> me-1"></i>
                                <?php echo ucfirst($p['status']); ?>
                            </span>
                            <?php if ($p['status'] === 'scheduled' && !empty($p['scheduled_at'])): ?>
                                <br>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    <?php echo date('M j, g:i A', strtotime($p['scheduled_at'])); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        
                        <!-- Views -->
                        <td class="text-center">
                            <span class="fw-semibold"><?php echo format_number($p['view_count'] ?? 0); ?></span>
                        </td>
                        
                        <!-- Comments -->
                        <td class="text-center">
                            <a href="<?php echo admin_url('comments&post_id=' . $p['id']); ?>" class="text-decoration-none">
                                <span class="fw-semibold"><?php echo $p['comment_count'] ?? 0; ?></span>
                            </a>
                        </td>
                        
                        <!-- Date -->
                        <td>
                            <div class="small">
                                <div><?php echo format_date($p['published_at'] ?? $p['created_at']); ?></div>
                                <div class="text-muted" style="font-size: 11px;">
                                    <?php echo time_ago($p['published_at'] ?? $p['created_at']); ?>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Actions -->
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?php echo admin_url('posts&action=edit&id=' . $p['id']); ?>" 
                                   class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($p['status'] !== 'trash'): ?>
                                <a href="<?php echo post_url($p['slug']); ?>" target="_blank" 
                                   class="btn btn-outline-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php endif; ?>
                                <a href="<?php echo admin_url('posts&action=delete&id=' . $p['id'] . '&_token=' . csrf_token()); ?>" 
                                   class="btn btn-outline-danger delete-confirm" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total > $perPage): ?>
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <div class="text-muted small">
                    Showing <?php echo min($total, (($page - 1) * $perPage) + 1); ?> to 
                    <?php echo min($total, $page * $perPage); ?> of <?php echo $total; ?> posts
                </div>
                <?php 
                $baseUrl = admin_url('posts');
                if ($statusFilter) {
                    $baseUrl .= '&status=' . $statusFilter;
                }
                if ($searchQuery) {
                    $baseUrl .= '&search=' . urlencode($searchQuery);
                }
                echo pagination($total, $perPage, $page, $baseUrl); 
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Delete confirmation
document.querySelectorAll('.delete-confirm').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});

// Quick search keyboard shortcut
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput && document.activeElement !== searchInput) {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    }
});

// Auto-submit search on enter
document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        this.closest('form').submit();
    }
});
</script>

<?php include ADMIN_PATH . 'layout/footer.php'; } ?>