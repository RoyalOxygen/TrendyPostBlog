<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('categories.manage');
$action = $_GET['action'] ?? 'index';
$pageTitle = 'Categories | Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    // Get parent_id - handle both numeric and 'none' values
    $parentId = $_POST['parent_id'] ?? 0;
    if ($parentId === 'none' || $parentId === '0') {
        $parentId = null;
    } else {
        $parentId = intval($parentId) ?: null;
    }
    
    $data = [
        'name' => sanitize($_POST['name'] ?? ''),
        'slug' => create_slug($_POST['slug'] ?? $_POST['name'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'parent_id' => $parentId,
        'image' => sanitize($_POST['image'] ?? ''),
        'color' => sanitize($_POST['color'] ?? '#00C853'),
        'seo_title' => sanitize($_POST['seo_title'] ?? ''),
        'seo_description' => sanitize($_POST['seo_description'] ?? ''),
        'seo_keywords' => sanitize($_POST['seo_keywords'] ?? ''),
        'schema_type' => sanitize($_POST['schema_type'] ?? 'CollectionPage'),
        'meta_robots' => sanitize($_POST['meta_robots'] ?? 'index,follow'),
        'canonical_url' => sanitize($_POST['canonical_url'] ?? ''),
        'og_image' => sanitize($_POST['og_image'] ?? ''),
        'sort_order' => intval($_POST['sort_order'] ?? 0),
        'status' => sanitize($_POST['status'] ?? 'active')
    ];
    
    // Handle image upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = UPLOADS_PATH . 'categories/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        
        if (in_array($ext, $allowed)) {
            $filename = 'category_' . time() . '_' . uniqid() . '.' . $ext;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $filepath)) {
                // Delete old image if exists and updating
                if (!empty($_POST['id']) && !empty($editCat['image'])) {
                    $oldPath = UPLOADS_PATH . $editCat['image'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $data['image'] = 'categories/' . $filename;
                if (class_exists('Media')) {
                    Media::register($data['image'], $_FILES['image_file']['name'] ?? $filename, $data['name'] ?? '', Auth::id());
                }
            }
        }
    }
    
    // If image URL is provided, use it instead
    if (!empty($_POST['image_url'])) {
        $remote = sanitize($_POST['image_url']);
        $data['image'] = class_exists('Media') ? (Media::localizePath($remote, 'categories', $data['name'] ?? '', Auth::id()) ?: $remote) : $remote;
    } elseif (!empty($data['image']) && class_exists('Media') && !Media::isRemote($data['image'])) {
        Media::register($data['image'], basename($data['image']), $data['name'] ?? '', Auth::id());
    }
    
    if (!empty($_POST['id'])) {
        $data['slug'] = unique_slug('categories', $data['slug'], intval($_POST['id']));
        Database::update('categories', $data, 'id = ?', [intval($_POST['id'])]);
        Auth::logActivity('category_update', 'Updated category: ' . $data['name']);
        flash_set('success', 'Category updated successfully.');
    } else {
        $data['slug'] = unique_slug('categories', $data['slug']);
        Database::insert('categories', $data);
        Auth::logActivity('category_create', 'Created category: ' . $data['name']);
        flash_set('success', 'Category created successfully.');
    }
    header('Location: ' . admin_url('categories'));
    exit;
}

if ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id && verify_csrf($_GET['_token'] ?? '')) {
        // Get category to delete image
        $cat = Category::getById($id);
        if ($cat && !empty($cat['image'])) {
            $imagePath = UPLOADS_PATH . $cat['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        Database::delete('categories', 'id = ?', [$id]);
        Auth::logActivity('category_delete', 'Deleted category #' . $id . (!empty($cat['name']) ? ': ' . $cat['name'] : ''));
        flash_set('success', 'Category deleted successfully.');
    }
    header('Location: ' . admin_url('categories'));
    exit;
}

$categories = Category::getAll(false);
$editId = ($action === 'edit') ? intval($_GET['id'] ?? 0) : 0;
$editCat = $editId ? Category::getById($editId) : null;
$parentCategories = Category::getParentCategories();

include ADMIN_PATH . 'layout/header.php';
?>

<style>
    .category-image-preview {
        max-width: 100px;
        max-height: 100px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        padding: 4px;
    }
    .category-image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 4px;
    }
    .color-preview {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-block;
        border: 2px solid #dee2e6;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-folder me-2"></i>Categories</h4>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="badge bg-light text-dark">Total: <?php echo count($categories); ?></span>
        <a href="<?php echo url('categories'); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-eye me-1"></i>View All
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Categories List -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">Image</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Posts</th>
                                <th>Status</th>
                                <th>Order</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
                                    No categories found. Create your first category!
                                </td>
                            </tr>
                            <?php else: foreach($categories as $c): 
                                $hasImage = !empty($c['image']);
                                $imgUrl = media_url($c['image'] ?? '');
                            ?>
                            <tr>
                                <td>
                                    <img src="<?php echo $imgUrl; ?>" alt="<?php echo esc_attr($c['name']); ?>" 
                                         style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #e9ecef;"
                                         onerror="this.src='<?php echo image_url('placeholder.jpg'); ?>';">
                                </td>
                                <td>
                                    <span class="fw-medium"><?php echo $c['name']; ?></span>
                                    <?php if ($c['parent_id']): ?>
                                        <span class="badge bg-light text-muted ms-1"><i class="bi bi-arrow-return-right"></i> Sub</span>
                                    <?php endif; ?>
                                </td>
                                <td><code class="small"><?php echo $c['slug']; ?></code></td>
                                <td><span class="badge bg-light text-dark"><?php echo $c['post_count'] ?? 0; ?></span></td>
                                <td>
                                    <?php if ($c['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $c['sort_order'] ?? 0; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo admin_url('categories&action=edit&id=' . $c['id']); ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?php echo admin_url('categories&action=delete&id=' . $c['id'] . '&_token=' . csrf_token()); ?>" 
                                           class="btn btn-outline-danger delete-confirm" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <a href="<?php echo category_url($c['slug']); ?>" target="_blank" 
                                           class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Category Form -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    <i class="bi bi-<?php echo $editCat ? 'pencil-square' : 'plus-circle'; ?> me-2"></i>
                    <?php echo $editCat ? 'Edit' : 'Add'; ?> Category
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                    <?php if($editCat): ?>
                        <input type="hidden" name="id" value="<?php echo $editCat['id']; ?>">
                    <?php endif; ?>
                    
                    <!-- Basic Information -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="catName" class="form-control" 
                               value="<?php echo esc_attr($editCat['name'] ?? ''); ?>" 
                               oninput="document.getElementById('catSlug').value = this.value.toLowerCase().replace(/[^\w\s-]/g,'').replace(/[\s_-]+/g,'-').replace(/^-+|-+$/g,'')"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Slug</label>
                        <input type="text" name="slug" id="catSlug" class="form-control" 
                               value="<?php echo esc_attr($editCat['slug'] ?? ''); ?>"
                               placeholder="Auto-generated from name">
                        <div class="form-text">URL-friendly version of the name. Leave empty to auto-generate.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Parent Category</label>
                        <select name="parent_id" class="form-select">
                            <option value="none">None (Top Level)</option>
                            <?php foreach($parentCategories as $pc): 
                                if ($editCat && $pc['id'] == $editCat['id']) continue;
                            ?>
                            <option value="<?php echo $pc['id']; ?>" <?php echo ($editCat && $editCat['parent_id'] == $pc['id']) ? 'selected' : ''; ?>>
                                <?php echo $pc['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Make this a sub-category of another category.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Brief description of the category"><?php echo esc_attr($editCat['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Category Image -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Category Image</label>
                        <?php if (!empty($editCat['image'])): 
                            $imgUrl = media_url($editCat['image']);
                        ?>
                            <div class="mb-2">
                                <img src="<?php echo $imgUrl; ?>" alt="Current Image" class="category-image-preview" 
                                     onerror="this.src='<?php echo image_url('placeholder.jpg'); ?>';">
                                <small class="text-muted d-block">Current image</small>
                            </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Upload Image</label>
                                <input type="file" name="image_file" class="form-control" accept="image/*">
                                <div class="form-text">JPG, PNG, GIF, WebP, SVG (max 2MB)</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Or Image URL</label>
                                <input type="url" name="image_url" class="form-control" 
                                       placeholder="https://example.com/image.jpg"
                                       value="<?php echo (isset($editCat['image']) && strpos($editCat['image'], 'http') === 0) ? $editCat['image'] : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Appearance -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Color</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="color" name="color" class="form-control form-control-color" 
                                   value="<?php echo esc_attr($editCat['color'] ?? '#00C853'); ?>" style="width: 60px; padding: 2px;">
                            <span id="colorDisplay" style="font-size: 13px; color: #6c757d;"><?php echo esc_attr($editCat['color'] ?? '#00C853'); ?></span>
                        </div>
                        <div class="form-text">Color used for category badges and highlights.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" 
                               value="<?php echo esc_attr($editCat['sort_order'] ?? 0); ?>" min="0">
                        <div class="form-text">Lower numbers appear first.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo ($editCat && $editCat['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($editCat && $editCat['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <hr>
                    
                    <!-- SEO Settings -->
                    <h6 class="fw-semibold text-muted mb-3"><i class="bi bi-search me-2"></i>SEO Settings</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">SEO Title</label>
                        <input type="text" name="seo_title" class="form-control" 
                               value="<?php echo esc_attr($editCat['seo_title'] ?? ''); ?>" 
                               placeholder="50-60 characters" maxlength="70">
                        <div class="form-text"><span id="seoTitleCount"><?php echo strlen($editCat['seo_title'] ?? ''); ?></span>/70 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">SEO Description</label>
                        <textarea name="seo_description" class="form-control" rows="2" 
                                  placeholder="150-160 characters" maxlength="160"
                                  oninput="document.getElementById('seoDescCount').textContent = this.value.length"><?php echo esc_attr($editCat['seo_description'] ?? ''); ?></textarea>
                        <div class="form-text"><span id="seoDescCount"><?php echo strlen($editCat['seo_description'] ?? ''); ?></span>/160 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">SEO Keywords</label>
                        <input type="text" name="seo_keywords" class="form-control" 
                               value="<?php echo esc_attr($editCat['seo_keywords'] ?? ''); ?>" 
                               placeholder="keyword1, keyword2, keyword3">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Schema Type</label>
                                <select name="schema_type" class="form-select">
                                    <option value="CollectionPage" <?php echo ($editCat && $editCat['schema_type'] === 'CollectionPage') ? 'selected' : ''; ?>>CollectionPage</option>
                                    <option value="CategoryPage" <?php echo ($editCat && $editCat['schema_type'] === 'CategoryPage') ? 'selected' : ''; ?>>CategoryPage</option>
                                    <option value="WebPage" <?php echo ($editCat && $editCat['schema_type'] === 'WebPage') ? 'selected' : ''; ?>>WebPage</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Meta Robots</label>
                                <select name="meta_robots" class="form-select">
                                    <option value="index,follow" <?php echo ($editCat && $editCat['meta_robots'] === 'index,follow') ? 'selected' : ''; ?>>Index, Follow</option>
                                    <option value="index,nofollow" <?php echo ($editCat && $editCat['meta_robots'] === 'index,nofollow') ? 'selected' : ''; ?>>Index, No Follow</option>
                                    <option value="noindex,follow" <?php echo ($editCat && $editCat['meta_robots'] === 'noindex,follow') ? 'selected' : ''; ?>>No Index, Follow</option>
                                    <option value="noindex,nofollow" <?php echo ($editCat && $editCat['meta_robots'] === 'noindex,nofollow') ? 'selected' : ''; ?>>No Index, No Follow</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Canonical URL</label>
                        <input type="url" name="canonical_url" class="form-control" 
                               value="<?php echo esc_attr($editCat['canonical_url'] ?? ''); ?>" 
                               placeholder="https://example.com/preferred-url">
                        <div class="form-text">Leave empty to use the category's own URL.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">OG Image</label>
                        <input type="text" name="og_image" class="form-control" 
                               value="<?php echo esc_attr($editCat['og_image'] ?? ''); ?>" 
                               placeholder="/path/to/og-image.jpg">
                        <div class="form-text">Image for social sharing. Leave empty to use category image.</div>
                    </div>
                    
                    <hr>
                    
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check2 me-2"></i><?php echo $editCat ? 'Update Category' : 'Create Category'; ?>
                    </button>
                    
                    <?php if($editCat): ?>
                        <a href="<?php echo admin_url('categories'); ?>" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// SEO character counters
document.querySelector('input[name="seo_title"]')?.addEventListener('input', function() {
    document.getElementById('seoTitleCount').textContent = this.value.length;
});

// Color preview update
document.querySelector('input[name="color"]')?.addEventListener('input', function() {
    document.getElementById('colorDisplay').textContent = this.value;
});

// Delete confirmation
document.querySelectorAll('.delete-confirm').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});

// Auto-generate slug on name change if slug is empty
document.addEventListener('DOMContentLoaded', function() {
    const nameField = document.getElementById('catName');
    const slugField = document.getElementById('catSlug');
    
    if (nameField && slugField && !slugField.value) {
        nameField.addEventListener('input', function() {
            const slug = this.value.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            slugField.value = slug;
        });
    }
});
</script>

<?php include ADMIN_PATH . 'layout/footer.php'; ?>