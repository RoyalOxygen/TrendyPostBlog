<?php
if (!defined('ADMIN')) die('Direct access not permitted.');

$isEdit = ($action === 'edit');
$post = $isEdit ? Post::getById(intval($_GET['id'] ?? 0)) : null;
$categories = Category::getAll();
$tags = Tag::getAll();

// Handle image upload - Updated to support different folders
function handleImageUpload($file, $existingImage = '', $subfolder = 'posts') {
    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return $existingImage;
    }

    $uploadDir = UPLOADS_PATH . $subfolder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    if (!in_array($ext, $allowed)) {
        flash_set('error', 'Invalid file type. Allowed: ' . implode(', ', $allowed));
        return $existingImage;
    }

    // Check file size (max 5MB)
    if ($file['size'] > 5242880) {
        flash_set('error', 'File size too large. Maximum 5MB allowed.');
        return $existingImage;
    }

    $filename = $subfolder . '_' . time() . '_' . uniqid() . '.' . $ext;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $relative = $subfolder . '/' . $filename;
        if (class_exists('Media')) {
            Media::register($relative, $file['name'] ?? $filename, '', Auth::id());
        }
        return $relative;
    }

    return $existingImage;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    // Handle featured image upload
    $featuredImage = '';
    if ($isEdit && $post) {
        $featuredImage = $post['featured_image'] ?? '';
    }

    // Check if image URL is provided
    if (!empty($_POST['featured_image_media'])) {
        $featuredImage = Media::relativePath(sanitize($_POST['featured_image_media']));
    } elseif (!empty($_POST['featured_image_url'])) {
        $remote = sanitize($_POST['featured_image_url']);
        $featuredImage = class_exists('Media') ? (Media::localizePath($remote, 'posts', $_POST['title'] ?? '', Auth::id()) ?: $featuredImage) : $remote;
    }
    // Check if file was uploaded
    elseif (isset($_FILES['featured_image_file']) && $_FILES['featured_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadedImage = handleImageUpload($_FILES['featured_image_file'], $featuredImage, 'posts');
        if ($uploadedImage !== $featuredImage) {
            $featuredImage = $uploadedImage;
        }
    }

    // Handle OG Image upload
    $ogImage = $post['og_image'] ?? '';
    if (!empty($_POST['og_image_url'])) {
        $remoteOg = sanitize($_POST['og_image_url']);
        $ogImage = class_exists('Media') ? (Media::localizePath($remoteOg, 'og', $_POST['title'] ?? '', Auth::id()) ?: $ogImage) : $remoteOg;
    } elseif (isset($_FILES['og_image_file']) && $_FILES['og_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadedOgImage = handleImageUpload($_FILES['og_image_file'], $ogImage, 'og');
        if ($uploadedOgImage !== $ogImage) {
            $ogImage = $uploadedOgImage;
        }
    }

    // Handle Twitter Image upload
    $twitterImage = $post['twitter_image'] ?? '';
    if (!empty($_POST['twitter_image_url'])) {
        $remoteTw = sanitize($_POST['twitter_image_url']);
        $twitterImage = class_exists('Media') ? (Media::localizePath($remoteTw, 'twitter', $_POST['title'] ?? '', Auth::id()) ?: $twitterImage) : $remoteTw;
    } elseif (isset($_FILES['twitter_image_file']) && $_FILES['twitter_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadedTwitterImage = handleImageUpload($_FILES['twitter_image_file'], $twitterImage, 'twitter');
        if ($uploadedTwitterImage !== $twitterImage) {
            $twitterImage = $uploadedTwitterImage;
        }
    }

    // Base data
    $data = [
        'title' => sanitize($_POST['title'] ?? ''),
        'slug' => create_slug($_POST['slug'] ?? $_POST['title'] ?? ''),
        'excerpt' => $_POST['excerpt'] ?? '',
        'content' => class_exists('PublishSeo') ? PublishSeo::stripFaqBlocks($_POST['content'] ?? '') : ($_POST['content'] ?? ''),
        'category_id' => intval($_POST['category_id'] ?? 0) ?: null,
        'status' => $_POST['status'] ?? 'draft',
        'post_type' => $_POST['post_type'] ?? 'standard',
        'visibility' => $_POST['visibility'] ?? 'public',
        'password' => !empty($_POST['password']) ? hash_password($_POST['password']) : null,
        'featured_image' => $featuredImage,
        'featured_image_alt' => sanitize($_POST['featured_image_alt'] ?? ''),
        'featured_image_caption' => sanitize($_POST['featured_image_caption'] ?? ''),
        'seo_title' => sanitize($_POST['seo_title'] ?? ''),
        'seo_description' => sanitize($_POST['seo_description'] ?? ''),
        'seo_keywords' => sanitize($_POST['seo_keywords'] ?? ''),
        'focus_keyword' => sanitize($_POST['focus_keyword'] ?? ''),
        'secondary_keywords' => sanitize($_POST['secondary_keywords'] ?? ''),
        'meta_robots' => sanitize($_POST['meta_robots'] ?? 'index,follow'),
        'schema_type' => sanitize($_POST['schema_type'] ?? 'NewsArticle'),
        'canonical_url' => sanitize($_POST['canonical_url'] ?? ''),
        'og_title' => sanitize($_POST['og_title'] ?? ''),
        'og_description' => sanitize($_POST['og_description'] ?? ''),
        'og_image' => $ogImage,
        'twitter_card' => sanitize($_POST['twitter_card'] ?? 'summary_large_image'),
        'twitter_title' => sanitize($_POST['twitter_title'] ?? ''),
        'twitter_description' => sanitize($_POST['twitter_description'] ?? ''),
        'twitter_image' => $twitterImage,
        'allow_comments' => isset($_POST['allow_comments']) ? 1 : 0,
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_breaking' => isset($_POST['is_breaking']) ? 1 : 0,
        'is_sticky' => isset($_POST['is_sticky']) ? 1 : 0,
        'author_id' => $isEdit ? ($post['author_id'] ?? Auth::id()) : Auth::id(),
    ];

    // Handle password protection
    if ($data['visibility'] === 'password_protected') {
        if (!empty($_POST['password'])) {
            $data['password'] = hash_password($_POST['password']);
        } elseif ($isEdit && !empty($post['password'])) {
            $data['password'] = $post['password'];
        } else {
            $data['password'] = null;
        }
    } else {
        $data['password'] = null;
    }

    // ============================================================
    // PROPER PUBLISHED_AT HANDLING - FIXED
    // ============================================================
    if ($data['status'] === 'published') {
        // If this is a new post being published, or an existing post that wasn't published before
        if (!$isEdit || $post['status'] !== 'published') {
            // Set published_at to current time
            $data['published_at'] = date('Y-m-d H:i:s');
        } elseif ($isEdit && empty($post['published_at'])) {
            // If editing and published_at is empty, set it
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        // If already published, keep existing published_at
    }

    // Set scheduled_at if scheduled
    if ($data['status'] === 'scheduled' && !empty($_POST['scheduled_at'])) {
        $data['scheduled_at'] = $_POST['scheduled_at'];
        // Clear published_at for scheduled posts
        $data['published_at'] = null;
    }

    // Calculate reading time and word count
    $data['reading_time'] = reading_time($data['content']);
    $data['word_count'] = str_word_count(strip_tags($data['content']));

    if ($isEdit && $post) {
        if (!empty($post['source_id'])) {
            $data['source_id'] = $post['source_id'];
        }
        if (!empty($post['original_url'])) {
            $data['original_url'] = $post['original_url'];
            $data['original_title'] = $post['original_title'] ?? null;
        }
        $data['slug'] = unique_slug('posts', $data['slug'], $post['id']);
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('posts', $data, 'id = ?', [$post['id']]);
        $postId = $post['id'];
    } else {
        $data['slug'] = unique_slug('posts', $data['slug']);
        $postId = Database::insert('posts', $data);
    }

    // Handle tags
    Database::delete('post_tags', 'post_id = ?', [$postId]);
    if (!empty($_POST['tag_ids'])) {
        foreach ($_POST['tag_ids'] as $tid) {
            Database::insert('post_tags', ['post_id' => $postId, 'tag_id' => intval($tid)]);
        }
    }

    // Handle custom meta
    if (!empty($_POST['meta_keys']) && !empty($_POST['meta_values'])) {
        foreach ($_POST['meta_keys'] as $index => $key) {
            if (!empty($key) && isset($_POST['meta_values'][$index])) {
                $value = $_POST['meta_values'][$index];
                $exists = Database::fetchOne("SELECT id FROM post_meta WHERE post_id = ? AND meta_key = ?", [$postId, $key]);
                if ($exists) {
                    Database::update('post_meta', ['meta_value' => $value], 'post_id = ? AND meta_key = ?', [$postId, $key]);
                } else {
                    Database::insert('post_meta', ['post_id' => $postId, 'meta_key' => $key, 'meta_value' => $value]);
                }
            }
        }
    }

    $wasPublished = $isEdit && ($post['status'] ?? '') === 'published';
    $useAiRewrite = isset($_POST['use_ai_rewrite']);
    if ($data['status'] === 'published' && $useAiRewrite && class_exists('PublishSeo')) {
        try {
            $enhanced = PublishSeo::enhancePost((int) $postId, true);
            if ($enhanced) {
                $data = array_merge($data, $enhanced);
            }
        } catch (Throwable $e) {
            flash_set('error', 'AI rewrite failed. The post was saved without rewriting. ' . $e->getMessage());
            header('Location: ' . admin_url('posts&action=edit&id=' . (int) $postId));
            exit;
        }
    }
    if ($data['status'] === 'published') {
        if (!$wasPublished && class_exists('Mailer')) {
            try {
                Mailer::sendPublishNotice(array_merge($data, ['id' => $postId]), Auth::user() ?? []);
            } catch (Throwable $e) {
                error_log('Publish notice failed: ' . $e->getMessage());
            }
            Auth::logActivity('publish', 'Published post: ' . $data['title']);
        } else {
            Auth::logActivity($isEdit ? 'post_update' : 'post_create', ($isEdit ? 'Updated' : 'Created') . ' post: ' . $data['title']);
        }
        if (class_exists('SearchIndex')) {
            SearchIndex::notifyPublishedPost(array_merge($data, ['id' => $postId, 'slug' => $data['slug'] ?? ($post['slug'] ?? '')]));
        }
    } else {
        Auth::logActivity($isEdit ? 'post_update' : 'post_create', ($isEdit ? 'Updated' : 'Created') . ' post: ' . $data['title']);
    }

    flash_set('success', 'Post ' . ($isEdit ? 'updated' : 'created') . ' successfully.');
    header('Location: ' . admin_url('posts'));
    exit;
}

$pageTitle = ($isEdit ? 'Edit' : 'Create') . ' Post | Admin';
include ADMIN_PATH . 'layout/header.php';
?>

<style>
    .nav-tabs .nav-link { border: none; color: #495057; font-weight: 500; }
    .nav-tabs .nav-link.active { border-bottom: 3px solid var(--tp-primary, #00C853); color: var(--tp-primary, #00C853); background: transparent; }
    .form-section-title { font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid #e9ecef; }
    
    .image-preview-container {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        transition: all 0.3s;
        position: relative;
        background: #f8f9fa;
    }
    .image-preview-container.dragover {
        border-color: var(--tp-primary, #00C853);
        background: rgba(0, 200, 83, 0.05);
    }
    .image-preview-container img {
        max-width: 100%;
        max-height: 150px;
        border-radius: 4px;
    }
    .image-preview-container .upload-placeholder {
        color: #adb5bd;
    }
    .image-preview-container .upload-placeholder i {
        font-size: 36px;
        display: block;
        margin-bottom: 8px;
    }
    .image-upload-tabs .nav-link {
        padding: 6px 16px;
        font-size: 13px;
    }
    .image-upload-tabs .nav-link.active {
        color: var(--tp-primary, #00C853);
        border-bottom: 2px solid var(--tp-primary, #00C853);
    }
    .remove-image-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        z-index: 10;
        padding: 2px 8px;
        font-size: 12px;
    }
    .image-preview-sm {
        max-width: 100px;
        max-height: 60px;
        border-radius: 4px;
        border: 1px solid #dee2e6;
        padding: 2px;
    }
    .image-upload-group {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #e9ecef;
    }
    .image-upload-group .form-label {
        font-weight: 500;
        font-size: 13px;
    }
    
    /* TinyMCE Custom Styles */
    .tox-tinymce {
        border-radius: 8px !important;
        border: 1px solid #dee2e6 !important;
    }
    .tox-tinymce:focus {
        border-color: #00C853 !important;
        box-shadow: 0 0 0 3px rgba(0, 200, 83, 0.1) !important;
    }
    .tox .tox-editor-header {
        border-bottom: 1px solid #e9ecef !important;
    }
    .tox .tox-toolbar__primary {
        background: #f8f9fa !important;
    }
    .tox .tox-statusbar {
        border-top: 1px solid #e9ecef !important;
    }
    
    /* Current time display */
    .current-time {
        font-size: 12px;
        color: #6c757d;
        padding: 4px 10px;
        background: #f8f9fa;
        border-radius: 4px;
        border: 1px solid #e9ecef;
        display: inline-block;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-file-text me-2"></i><?php echo $isEdit ? 'Edit' : 'Create'; ?> Post</h4>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?php echo admin_url('posts'); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        <?php if ($isEdit && $post): ?>
        <a href="<?php echo post_url($post['slug']); ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye me-1"></i>View</a>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-info d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <span><i class="bi bi-clock me-2"></i> Server Time: <strong><?php echo date('Y-m-d H:i:s'); ?></strong> (<?php echo date_default_timezone_get(); ?>)</span>
    <span class="badge bg-success">Post will be visible immediately when published</span>
</div>

<form method="POST" id="postForm" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">

    <div class="row g-4">
        <!-- Main Content Column -->
        <div class="col-lg-8">

            <?php if (!empty($post['original_url'])): ?>
            <div class="alert alert-info">
                <i class="bi bi-robot me-1"></i>
                AI draft from source.
                Original:
                <a href="<?php echo esc_attr($post['original_url']); ?>" target="_blank" rel="noopener">
                    <?php echo esc($post['original_title'] ?: $post['original_url']); ?>
                </a>
                Stay in <strong>Draft</strong> until you are ready to publish.
            </div>
            <?php endif; ?>

            <!-- Title & Content -->
            <div class="card border-0 mb-4 shadow-sm">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Post Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="postTitle" class="form-control form-control-lg" 
                               value="<?php echo esc_attr($post['title'] ?? ''); ?>" required 
                               oninput="generateSlug(this.value)" 
                               placeholder="Enter post title">
                        <div class="form-text">The title should be compelling and include your focus keyword.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Slug / URL</label>
                        <input type="text" name="slug" id="postSlug" class="form-control" 
                               value="<?php echo esc_attr($post['slug'] ?? ''); ?>" 
                               placeholder="auto-generated from title">
                        <div class="form-text">The URL-friendly version of the title. Leave empty to auto-generate.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Excerpt</label>
                        <textarea name="excerpt" class="form-control" rows="3" 
                                  placeholder="Short description of the post (max 200 characters)"><?php echo esc_attr($post['excerpt'] ?? ''); ?></textarea>
                        <div class="form-text">Appears in search results and social shares.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Content <span class="text-danger">*</span></label>
                        <textarea name="content" id="postContent" rows="20" 
                                  placeholder="Write your article content here..."><?php echo esc_attr($post['content'] ?? ''); ?></textarea>
                        <div class="form-text">Use the editor toolbar to format your content. You can add images, links, and more.</div>
                    </div>
                </div>
            </div>

            <!-- Featured Image with Upload -->
            <div class="card border-0 mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-image me-2"></i>Featured Image</h6>
                </div>
                <div class="card-body">
                    <!-- Image Upload Tabs -->
                    <ul class="nav nav-tabs image-upload-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload-panel" type="button" role="tab">
                                <i class="bi bi-upload me-1"></i>Upload from Device
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="url-tab" data-bs-toggle="tab" data-bs-target="#url-panel" type="button" role="tab">
                                <i class="bi bi-link me-1"></i>Enter URL
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="media-tab" data-bs-toggle="tab" data-bs-target="#media-panel" type="button" role="tab">
                                <i class="bi bi-folder me-1"></i>Media Library
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Upload from Device -->
                        <div class="tab-pane fade show active" id="upload-panel" role="tabpanel">
                            <div class="image-preview-container" id="dropZone">
                                <div id="imagePreviewContent">
                                    <?php 
                                    $featuredImg = $post['featured_image'] ?? '';
                                    if (!empty($featuredImg)):
                                        $imgUrl = media_url($featuredImg);
                                    ?>
                                        <img src="<?php echo $imgUrl; ?>" alt="Featured Image" id="previewImg">
                                        <button type="button" class="btn btn-sm btn-danger remove-image-btn" onclick="removeImage()">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    <?php else: ?>
                                        <div class="upload-placeholder">
                                            <i class="bi bi-cloud-upload"></i>
                                            <p>Drag & drop an image here or click to browse</p>
                                            <span class="text-muted small">Supports: JPG, PNG, GIF, WebP, SVG (Max 5MB)</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="featured_image_file" id="featuredImageFile" accept="image/*" style="display:none">
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('featuredImageFile').click()">
                                    <i class="bi bi-folder2-open me-1"></i>Browse Files
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeImage()">
                                    <i class="bi bi-trash me-1"></i>Remove
                                </button>
                            </div>
                        </div>

                        <!-- Enter URL -->
                        <div class="tab-pane fade" id="url-panel" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Image URL</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-link"></i></span>
                                    <input type="url" name="featured_image_url" id="featuredImageUrl" class="form-control" 
                                           value="<?php echo (!empty($featuredImg) && strpos($featuredImg, 'http') === 0) ? $featuredImg : ''; ?>" 
                                           placeholder="https://example.com/image.jpg">
                                    <button type="button" class="btn btn-outline-secondary" onclick="loadUrlImage()">
                                        <i class="bi bi-eye"></i> Preview
                                    </button>
                                </div>
                                <div class="form-text">Enter a direct URL to an image hosted online.</div>
                            </div>
                            <div id="urlPreviewContainer" class="mt-2" style="display: none;">
                                <div class="image-preview-container" style="min-height: 150px;">
                                    <img id="urlPreviewImg" src="" alt="Preview" style="max-height: 200px;">
                                    <button type="button" class="btn btn-sm btn-danger remove-image-btn" onclick="clearUrlPreview()">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Media Library -->
                        <div class="tab-pane fade" id="media-panel" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Select from Media Library</label>
                                <div class="row g-2" id="mediaLibraryGrid">
                                    <?php
                                    $mediaFiles = Database::fetchAll(
                                        "SELECT * FROM media WHERE file_type IN ('jpg','jpeg','png','gif','webp','svg','image/jpeg','image/png','image/gif','image/webp','image/svg+xml') ORDER BY created_at DESC LIMIT 40"
                                    );
                                    if (!empty($mediaFiles)):
                                        foreach ($mediaFiles as $media):
                                            $isSelected = ($post['featured_image'] ?? '') === Media::relativePath($media['file_path'] ?: $media['filename']);
                                    ?>
                                        <div class="col-3">
                                            <div class="media-item <?php echo $isSelected ? 'selected' : ''; ?>" 
                                                 onclick="selectMedia('<?php echo Media::relativePath($media['file_path'] ?: $media['filename']); ?>', this)" 
                                                 style="cursor:pointer; border:2px solid <?php echo $isSelected ? 'var(--tp-primary, #00C853)' : 'transparent'; ?>; border-radius:8px; padding:4px; background:#f8f9fa;">
                                                <img src="<?php echo media_url($media['file_path'] ?: $media['filename']); ?>" alt="<?php echo esc_attr($media['alt_text'] ?? $media['filename']); ?>" 
                                                     style="width:100%; height:80px; object-fit:cover; border-radius:4px;">
                                                <small class="d-block text-truncate text-muted" style="font-size:10px;"><?php echo $media['filename']; ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12 text-center text-muted py-3">
                                            <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
                                            No media files found. Upload an image first.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2">
                                    <input type="hidden" name="featured_image_media" id="featuredImageMedia" value="">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshMediaLibrary()">
                                        <i class="bi bi-arrow-repeat me-1"></i>Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image Details -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Alt Text</label>
                                <input type="text" name="featured_image_alt" class="form-control" 
                                       value="<?php echo esc_attr($post['featured_image_alt'] ?? ''); ?>" 
                                       placeholder="Description for accessibility and SEO">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Caption</label>
                                <input type="text" name="featured_image_caption" class="form-control" 
                                       value="<?php echo esc_attr($post['featured_image_caption'] ?? ''); ?>" 
                                       placeholder="Image caption (displayed below image)">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEO Settings -->
            <div class="card border-0 mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-search me-2"></i>SEO Settings</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">SEO Title</label>
                                <input type="text" name="seo_title" class="form-control" 
                                       value="<?php echo esc_attr($post['seo_title'] ?? ''); ?>" 
                                       placeholder="50-60 characters" maxlength="70">
                                <div class="form-text"><span id="seoTitleCount"><?php echo strlen($post['seo_title'] ?? ''); ?></span>/70 characters</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Focus Keyword</label>
                                <input type="text" name="focus_keyword" class="form-control" 
                                       value="<?php echo esc_attr($post['focus_keyword'] ?? ''); ?>" 
                                       placeholder="Primary keyword for this post">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meta Description</label>
                        <textarea name="seo_description" class="form-control" rows="2" 
                                  placeholder="150-160 characters" maxlength="160" 
                                  oninput="document.getElementById('seoDescCount').textContent = this.value.length"><?php echo esc_attr($post['seo_description'] ?? ''); ?></textarea>
                        <div class="form-text"><span id="seoDescCount"><?php echo strlen($post['seo_description'] ?? ''); ?></span>/160 characters</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Meta Keywords</label>
                                <input type="text" name="seo_keywords" class="form-control" 
                                       value="<?php echo esc_attr($post['seo_keywords'] ?? ''); ?>" 
                                       placeholder="keyword1, keyword2, keyword3">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Secondary Keywords</label>
                                <input type="text" name="secondary_keywords" class="form-control" 
                                       value="<?php echo esc_attr($post['secondary_keywords'] ?? ''); ?>" 
                                       placeholder="Related keywords (comma separated)">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Meta Robots</label>
                                <select name="meta_robots" class="form-select">
                                    <option value="index,follow" <?php echo ($post['meta_robots'] ?? '') === 'index,follow' ? 'selected' : ''; ?>>Index, Follow</option>
                                    <option value="index,nofollow" <?php echo ($post['meta_robots'] ?? '') === 'index,nofollow' ? 'selected' : ''; ?>>Index, No Follow</option>
                                    <option value="noindex,follow" <?php echo ($post['meta_robots'] ?? '') === 'noindex,follow' ? 'selected' : ''; ?>>No Index, Follow</option>
                                    <option value="noindex,nofollow" <?php echo ($post['meta_robots'] ?? '') === 'noindex,nofollow' ? 'selected' : ''; ?>>No Index, No Follow</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Schema Type</label>
                                <select name="schema_type" class="form-select">
                                    <option value="NewsArticle" <?php echo ($post['schema_type'] ?? '') === 'NewsArticle' ? 'selected' : ''; ?>>NewsArticle</option>
                                    <option value="Article" <?php echo ($post['schema_type'] ?? '') === 'Article' ? 'selected' : ''; ?>>Article</option>
                                    <option value="BlogPosting" <?php echo ($post['schema_type'] ?? '') === 'BlogPosting' ? 'selected' : ''; ?>>BlogPosting</option>
                                    <option value="TechArticle" <?php echo ($post['schema_type'] ?? '') === 'TechArticle' ? 'selected' : ''; ?>>TechArticle</option>
                                    <option value="VideoObject" <?php echo ($post['schema_type'] ?? '') === 'VideoObject' ? 'selected' : ''; ?>>VideoObject</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Canonical URL</label>
                        <input type="url" name="canonical_url" class="form-control" 
                               value="<?php echo esc_attr($post['canonical_url'] ?? ''); ?>" 
                               placeholder="https://example.com/preferred-url">
                        <div class="form-text">Leave empty to use the post's own URL.</div>
                    </div>
                </div>
            </div>

            <!-- Open Graph & Twitter Cards -->
            <div class="card border-0 mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-share me-2"></i>Social Sharing (Open Graph & Twitter Cards)</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Open Graph</h6>
                            <div class="mb-3">
                                <label class="form-label">OG Title</label>
                                <input type="text" name="og_title" class="form-control" 
                                       value="<?php echo esc_attr($post['og_title'] ?? ''); ?>" 
                                       placeholder="Title for social sharing">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">OG Description</label>
                                <textarea name="og_description" class="form-control" rows="2" 
                                          placeholder="Description for social sharing"><?php echo esc_attr($post['og_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- OG Image Upload -->
                            <div class="image-upload-group">
                                <label class="form-label fw-medium">OG Image</label>
                                <?php if (!empty($post['og_image'])): 
                                    $ogImgUrl = strpos($post['og_image'], 'http') === 0 ? $post['og_image'] : upload_url($post['og_image']);
                                ?>
                                    <div class="mb-2">
                                        <img src="<?php echo $ogImgUrl; ?>" alt="OG Image" class="image-preview-sm" 
                                             onerror="this.src='<?php echo image_url('placeholder.jpg'); ?>';">
                                        <small class="text-muted d-block">Current OG image</small>
                                    </div>
                                <?php endif; ?>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small">Upload Image</label>
                                        <input type="file" name="og_image_file" class="form-control form-control-sm" accept="image/*">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Or Image URL</label>
                                        <input type="url" name="og_image_url" class="form-control form-control-sm" 
                                               placeholder="https://example.com/og-image.jpg"
                                               value="<?php echo (!empty($post['og_image']) && strpos($post['og_image'], 'http') === 0) ? $post['og_image'] : ''; ?>">
                                    </div>
                                </div>
                                <div class="form-text small">Recommended: 1200x630 pixels. Leave empty to use featured image.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Twitter Card</h6>
                            <div class="mb-3">
                                <label class="form-label">Card Type</label>
                                <select name="twitter_card" class="form-select">
                                    <option value="summary" <?php echo ($post['twitter_card'] ?? '') === 'summary' ? 'selected' : ''; ?>>Summary</option>
                                    <option value="summary_large_image" <?php echo ($post['twitter_card'] ?? '') === 'summary_large_image' ? 'selected' : ''; ?>>Summary with Large Image</option>
                                    <option value="app" <?php echo ($post['twitter_card'] ?? '') === 'app' ? 'selected' : ''; ?>>App</option>
                                    <option value="player" <?php echo ($post['twitter_card'] ?? '') === 'player' ? 'selected' : ''; ?>>Player</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Twitter Title</label>
                                <input type="text" name="twitter_title" class="form-control" 
                                       value="<?php echo esc_attr($post['twitter_title'] ?? ''); ?>" 
                                       placeholder="Title for Twitter">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Twitter Description</label>
                                <textarea name="twitter_description" class="form-control" rows="2" 
                                          placeholder="Description for Twitter"><?php echo esc_attr($post['twitter_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Twitter Image Upload -->
                            <div class="image-upload-group">
                                <label class="form-label fw-medium">Twitter Image</label>
                                <?php if (!empty($post['twitter_image'])): 
                                    $twImgUrl = strpos($post['twitter_image'], 'http') === 0 ? $post['twitter_image'] : upload_url($post['twitter_image']);
                                ?>
                                    <div class="mb-2">
                                        <img src="<?php echo $twImgUrl; ?>" alt="Twitter Image" class="image-preview-sm" 
                                             onerror="this.src='<?php echo image_url('placeholder.jpg'); ?>';">
                                        <small class="text-muted d-block">Current Twitter image</small>
                                    </div>
                                <?php endif; ?>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small">Upload Image</label>
                                        <input type="file" name="twitter_image_file" class="form-control form-control-sm" accept="image/*">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Or Image URL</label>
                                        <input type="url" name="twitter_image_url" class="form-control form-control-sm" 
                                               placeholder="https://example.com/twitter-image.jpg"
                                               value="<?php echo (!empty($post['twitter_image']) && strpos($post['twitter_image'], 'http') === 0) ? $post['twitter_image'] : ''; ?>">
                                    </div>
                                </div>
                                <div class="form-text small">Recommended: 1200x600 pixels. Leave empty to use featured image.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Meta -->
            <div class="card border-0 mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-code-square me-2"></i>Custom Meta Fields</h6>
                </div>
                <div class="card-body" id="customMetaContainer">
                    <?php
                    $meta = $isEdit ? Post::getMeta($post['id']) : [];
                    ?>
                    <div id="metaFields">
                        <?php if (!empty($meta)): ?>
                            <?php foreach ($meta as $key => $value): ?>
                                <div class="row mb-2 meta-field">
                                    <div class="col-md-5">
                                        <input type="text" name="meta_keys[]" class="form-control form-control-sm" value="<?php echo esc_attr($key); ?>" placeholder="Meta key">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="meta_values[]" class="form-control form-control-sm" value="<?php echo esc_attr($value); ?>" placeholder="Meta value">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-meta"><i class="bi bi-x"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="row mb-2 meta-field">
                                <div class="col-md-5">
                                    <input type="text" name="meta_keys[]" class="form-control form-control-sm" placeholder="Meta key">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="meta_values[]" class="form-control form-control-sm" placeholder="Meta value">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-meta"><i class="bi bi-x"></i></button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addMetaField">
                        <i class="bi bi-plus-lg me-1"></i>Add Meta Field
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="col-lg-4">

            <!-- Publish Box -->
            <div class="card border-0 mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-clock me-2"></i>Publish</h6>
                </div>
                <div class="card-body">
                    <!-- Published At Info -->
                    <?php if ($isEdit && !empty($post['published_at'])): ?>
                        <div class="alert alert-success py-1 px-2 mb-3" style="font-size:13px;">
                            <i class="bi bi-check-circle me-1"></i> Published: <strong><?php echo date('Y-m-d H:i:s', strtotime($post['published_at'])); ?></strong>
                            <br><small class="text-muted">Post will remain published with this date</small>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" id="postStatus">
                            <option value="draft" <?php echo ($post['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="scheduled" <?php echo ($post['status'] ?? '') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="pending_review" <?php echo ($post['status'] ?? '') === 'pending_review' ? 'selected' : ''; ?>>Pending Review</option>
                        </select>
                        <?php if (!$isEdit || $post['status'] !== 'published'): ?>
                            <div class="form-text text-success">Publishing will set the date to current server time</div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3" id="scheduledAtField" style="display: <?php echo ($post['status'] ?? '') === 'scheduled' ? 'block' : 'none'; ?>">
                        <label class="form-label">Schedule Date</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" 
                               value="<?php echo !empty($post['scheduled_at']) ? date('Y-m-d\TH:i', strtotime($post['scheduled_at'])) : ''; ?>">
                        <div class="form-text">Post will be published at this date and time</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Visibility</label>
                        <select name="visibility" class="form-select" id="visibilitySelect">
                            <option value="public" <?php echo ($post['visibility'] ?? 'public') === 'public' ? 'selected' : ''; ?>>Public</option>
                            <option value="password_protected" <?php echo ($post['visibility'] ?? '') === 'password_protected' ? 'selected' : ''; ?>>Password Protected</option>
                            <option value="private" <?php echo ($post['visibility'] ?? '') === 'private' ? 'selected' : ''; ?>>Private</option>
                        </select>
                    </div>

                    <div class="mb-3" id="passwordField" style="display: <?php echo ($post['visibility'] ?? '') === 'password_protected' ? 'block' : 'none'; ?>">
                        <label class="form-label">Post Password</label>
                        <input type="text" name="password" class="form-control" placeholder="Enter password to protect this post">
                        <?php if ($isEdit && !empty($post['password'])): ?>
                            <div class="form-text text-success">✓ Password is currently set. Leave blank to keep current.</div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Post Type</label>
                        <select name="post_type" class="form-select">
                            <option value="standard" <?php echo ($post['post_type'] ?? 'standard') === 'standard' ? 'selected' : ''; ?>>Standard</option>
                            <option value="video" <?php echo ($post['post_type'] ?? '') === 'video' ? 'selected' : ''; ?>>Video</option>
                            <option value="audio" <?php echo ($post['post_type'] ?? '') === 'audio' ? 'selected' : ''; ?>>Audio</option>
                            <option value="gallery" <?php echo ($post['post_type'] ?? '') === 'gallery' ? 'selected' : ''; ?>>Gallery</option>
                            <option value="quote" <?php echo ($post['post_type'] ?? '') === 'quote' ? 'selected' : ''; ?>>Quote</option>
                        </select>
                    </div>

                    <hr>

                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="feat" <?php echo ($post['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="feat">Featured</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_breaking" id="break" <?php echo ($post['is_breaking'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="break">Breaking News</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_sticky" id="sticky" <?php echo ($post['is_sticky'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="sticky">Sticky</label>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="allow_comments" id="com" <?php echo !isset($post['allow_comments']) || $post['allow_comments'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="com">Allow Comments</label>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="use_ai_rewrite" id="useAiRewrite" value="1">
                        <label class="form-check-label" for="useAiRewrite">Use AI rewrite</label>
                        <div class="form-text">Tick this to regenerate title, excerpt, body, SEO, and social fields with Gemini before publishing. Leave unticked to publish the current draft as-is.</div>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check2 me-2"></i><?php echo $isEdit ? 'Update Post' : 'Publish Post'; ?>
                    </button>

                    <?php if ($isEdit && $post): ?>
                        <div class="text-center mt-2">
                            <small class="text-muted">ID: <?php echo $post['id']; ?> | Updated: <?php echo time_ago($post['updated_at']); ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Category -->
            <div class="card border-0 mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-folder me-2"></i>Category</h6>
                </div>
                <div class="card-body">
                    <select name="category_id" class="form-select">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($post['category_id'] ?? 0) == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo str_repeat('— ', $c['parent_id'] ? 1 : 0) . $c['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Tags -->
            <div class="card border-0 mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-tags me-2"></i>Tags</h6>
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <div class="mb-2">
                        <input type="text" id="tagSearch" class="form-control form-control-sm" placeholder="Search tags...">
                    </div>
                    <div id="tagList">
                        <?php 
                        $postTagIds = $isEdit ? array_column(Post::getTags($post['id'] ?? 0), 'id') : [];
                        foreach ($tags as $t): 
                        ?>
                        <div class="form-check">
                            <input class="form-check-input tag-checkbox" type="checkbox" name="tag_ids[]" 
                                   value="<?php echo $t['id']; ?>" id="tag<?php echo $t['id']; ?>" 
                                   <?php echo in_array($t['id'], $postTagIds) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="tag<?php echo $t['id']; ?>">
                                <?php echo $t['name']; ?>
                                <?php if ($t['post_count'] > 0): ?>
                                    <small class="text-muted">(<?php echo $t['post_count']; ?>)</small>
                                <?php endif; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <?php if ($isEdit && $post): ?>
            <div class="card border-0 mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Post Stats</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="small text-muted">Views</div>
                            <div class="fw-bold"><?php echo format_number($post['view_count']); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Comments</div>
                            <div class="fw-bold"><?php echo $post['comment_count'] ?? 0; ?></div>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="small text-muted">Reading Time</div>
                            <div class="fw-bold"><?php echo $post['reading_time'] ?? '—'; ?> min</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Word Count</div>
                            <div class="fw-bold"><?php echo $post['word_count'] ?? '—'; ?></div>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="small text-muted">SEO Score</div>
                            <div class="fw-bold <?php echo ($post['seo_score'] ?? 0) >= 70 ? 'text-success' : 'text-warning'; ?>">
                                <?php echo $post['seo_score'] ?? 0; ?>%
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Readability</div>
                            <div class="fw-bold <?php echo ($post['readability_score'] ?? 0) >= 70 ? 'text-success' : 'text-warning'; ?>">
                                <?php echo $post['readability_score'] ?? 0; ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</form>

<!-- TinyMCE Script -->
<script src="https://cdn.tiny.cloud/1/hpd2shkn1zr3ivuiajcdz5y4udymj0artto2hllqngu7qya2/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
// Initialize TinyMCE
document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
        selector: '#postContent',
        height: 500,
        menubar: true,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | help | link image media | fullscreen | code',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 16px; line-height: 1.6; }',
        skin: 'oxide',
        setup: function(editor) {
            editor.on('change', function() {
                editor.save();
            });
        },
        // Image upload handler
        images_upload_handler: function(blobInfo, success, failure) {
            var formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            
            // You can implement server-side upload here
            // For now, we'll convert to base64 for demo
            var reader = new FileReader();
            reader.onload = function(e) {
                success(e.target.result);
            };
            reader.readAsDataURL(blobInfo.blob());
        }
    });
});

// Generate slug from title
function generateSlug(title) {
    const slugField = document.getElementById('postSlug');
    if (!slugField.value || slugField.dataset.autoGenerated === 'true') {
        const slug = title.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
        slugField.value = slug;
        slugField.dataset.autoGenerated = 'true';
    }
}

// ============================================================
// IMAGE UPLOAD HANDLING
// ============================================================

// Featured Image - Drag and drop
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('featuredImageFile');

if (dropZone) {
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            previewFile(files[0]);
        }
    });

    dropZone.addEventListener('click', function() {
        fileInput.click();
    });
}

// Featured Image - File input change
fileInput?.addEventListener('change', function() {
    if (this.files.length > 0) {
        previewFile(this.files[0]);
    }
});

// Preview file for featured image
function previewFile(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const previewContent = document.getElementById('imagePreviewContent');
        previewContent.innerHTML = `
            <img src="${e.target.result}" alt="Preview" id="previewImg">
            <button type="button" class="btn btn-sm btn-danger remove-image-btn" onclick="removeImage()">
                <i class="bi bi-x"></i>
            </button>
        `;
        // Clear URL input if file is uploaded
        document.getElementById('featuredImageUrl').value = '';
        document.getElementById('featuredImageMedia').value = '';
    };
    reader.readAsDataURL(file);
}

// Remove featured image
function removeImage() {
    const previewContent = document.getElementById('imagePreviewContent');
    previewContent.innerHTML = `
        <div class="upload-placeholder">
            <i class="bi bi-cloud-upload"></i>
            <p>Drag & drop an image here or click to browse</p>
            <span class="text-muted small">Supports: JPG, PNG, GIF, WebP, SVG</span>
        </div>
    `;
    fileInput.value = '';
    document.getElementById('featuredImageUrl').value = '';
    document.getElementById('featuredImageMedia').value = '';
}

// Load URL image for featured image
function loadUrlImage() {
    const url = document.getElementById('featuredImageUrl').value;
    const container = document.getElementById('urlPreviewContainer');
    const img = document.getElementById('urlPreviewImg');
    
    if (url) {
        img.src = url;
        container.style.display = 'block';
        // Update the main preview as well
        const previewContent = document.getElementById('imagePreviewContent');
        previewContent.innerHTML = `
            <img src="${url}" alt="Preview" id="previewImg">
            <button type="button" class="btn btn-sm btn-danger remove-image-btn" onclick="removeImage()">
                <i class="bi bi-x"></i>
            </button>
        `;
    } else {
        container.style.display = 'none';
    }
}

// Clear URL preview
function clearUrlPreview() {
    document.getElementById('urlPreviewContainer').style.display = 'none';
    document.getElementById('featuredImageUrl').value = '';
}

// Select from media library
function selectMedia(filePath, element) {
    // Deselect all
    document.querySelectorAll('.media-item').forEach(el => {
        el.style.borderColor = 'transparent';
    });
    // Select this one
    element.style.borderColor = 'var(--tp-primary, #00C853)';
    
    document.getElementById('featuredImageMedia').value = filePath;
    
    // Update preview
    const previewContent = document.getElementById('imagePreviewContent');
    previewContent.innerHTML = `
        <img src="${'<?php echo UPLOADS_URL; ?>' + filePath}" alt="Preview" id="previewImg">
        <button type="button" class="btn btn-sm btn-danger remove-image-btn" onclick="removeImage()">
            <i class="bi bi-x"></i>
        </button>
    `;
}

// Refresh media library
function refreshMediaLibrary() {
    location.reload();
}

// ============================================================
// OTHER FORM HANDLING
// ============================================================

// SEO character counters
document.querySelector('input[name="seo_title"]')?.addEventListener('input', function() {
    document.getElementById('seoTitleCount').textContent = this.value.length;
});

// Toggle visibility fields
document.getElementById('visibilitySelect')?.addEventListener('change', function() {
    const passwordField = document.getElementById('passwordField');
    if (this.value === 'password_protected') {
        passwordField.style.display = 'block';
    } else {
        passwordField.style.display = 'none';
    }
});

// Toggle scheduled field
document.getElementById('postStatus')?.addEventListener('change', function() {
    const scheduledField = document.getElementById('scheduledAtField');
    if (this.value === 'scheduled') {
        scheduledField.style.display = 'block';
    } else {
        scheduledField.style.display = 'none';
    }
});

// Tag search
document.getElementById('tagSearch')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const checkboxes = document.querySelectorAll('.tag-checkbox');
    checkboxes.forEach(function(cb) {
        const label = cb.nextElementSibling;
        if (label) {
            const text = label.textContent.toLowerCase();
            cb.closest('.form-check').style.display = text.includes(searchTerm) ? '' : 'none';
        }
    });
});

// Custom meta fields
document.getElementById('addMetaField')?.addEventListener('click', function() {
    const container = document.getElementById('metaFields');
    const row = document.createElement('div');
    row.className = 'row mb-2 meta-field';
    row.innerHTML = `
        <div class="col-md-5">
            <input type="text" name="meta_keys[]" class="form-control form-control-sm" placeholder="Meta key">
        </div>
        <div class="col-md-6">
            <input type="text" name="meta_values[]" class="form-control form-control-sm" placeholder="Meta value">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger remove-meta"><i class="bi bi-x"></i></button>
        </div>
    `;
    container.appendChild(row);
    row.querySelector('.remove-meta').addEventListener('click', function() {
        row.remove();
    });
});

// Remove meta fields
document.querySelectorAll('.remove-meta').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const row = this.closest('.meta-field');
        if (document.querySelectorAll('.meta-field').length > 1) {
            row.remove();
        } else {
            alert('At least one meta field is required.');
        }
    });
});

// Auto-generate slug on first load if empty
document.addEventListener('DOMContentLoaded', function() {
    const titleField = document.getElementById('postTitle');
    const slugField = document.getElementById('postSlug');
    if (!slugField.value && titleField.value) {
        generateSlug(titleField.value);
    }
});

// Show/hide scheduled field based on status change
document.getElementById('postStatus')?.addEventListener('change', function() {
    const scheduledField = document.getElementById('scheduledAtField');
    if (this.value === 'scheduled') {
        scheduledField.style.display = 'block';
    } else {
        scheduledField.style.display = 'none';
    }
});
</script>

<?php include ADMIN_PATH . 'layout/footer.php'; ?>