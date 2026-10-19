<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('ai.sources');

if (!class_exists('AiIngest')) {
    require_once INCLUDES_PATH . 'AiIngest.php';
}

$action = $_GET['action'] ?? 'index';
$pageTitle = 'AI Sources | Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'create') {
        $name = sanitize($_POST['name'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $freq = max(15, intval($_POST['frequency_minutes'] ?? 360));
        $max = min(15, max(1, intval($_POST['max_posts'] ?? 5)));
        if ($name === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            flash_set('error', 'Name and a valid source URL are required.');
        } else {
            Database::insert('ai_sources', [
                'name' => $name,
                'url' => $url,
                'frequency_minutes' => $freq,
                'max_posts' => $max,
                'enabled' => 1,
            ]);
            Auth::logActivity('ai_source_create', 'Added AI source: ' . $name);
            flash_set('success', 'Source added. The scheduler will visit it on the cadence you set.');
        }
        header('Location: ' . admin_url('ai-sources'));
        exit;
    }

    if ($postAction === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $source = AiIngest::getById($id);
        if ($source) {
            Database::update('ai_sources', ['enabled' => $source['enabled'] ? 0 : 1], 'id = ?', [$id]);
            Auth::logActivity('ai_source_toggle', ($source['enabled'] ? 'Paused' : 'Enabled') . ' AI source: ' . ($source['name'] ?? ('#' . $id)));
            flash_set('success', $source['enabled'] ? 'Source paused.' : 'Source enabled.');
        }
        header('Location: ' . admin_url('ai-sources'));
        exit;
    }

    if ($postAction === 'ingest_url') {
        $url = trim((string) ($_POST['post_url'] ?? ''));
        try {
            $result = AiIngest::ingestSingleUrl($url, (int) (Auth::id() ?? 0));
            Auth::logActivity('ai_single_ingest', 'Ingested single post URL: ' . $url);
            if (!empty($result['created'])) {
                flash_set('success', $result['message'] . ' It is saved as a draft (post #' . (int) $result['post_id'] . ').');
            } else {
                flash_set('warning', $result['message']);
            }
        } catch (Throwable $e) {
            flash_set('error', $e->getMessage());
        }
        header('Location: ' . admin_url('ai-sources'));
        exit;
    }

    if ($postAction === 'run') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $result = AiIngest::ingestSource($id, true);
            Auth::logActivity('ai_source_run', 'Ran AI ingest for source #' . $id . ': ' . ($result['message'] ?? ''));
            flash_set('success', $result['message']);
        } catch (Throwable $e) {
            flash_set('error', $e->getMessage());
        }
        header('Location: ' . admin_url('ai-sources&view=' . $id));
        exit;
    }

    if ($postAction === 'save_llm') {
        if (!class_exists('GeminiClient')) {
            require_once INCLUDES_PATH . 'GeminiClient.php';
        }
        $savedModel = trim($_POST['gemini_model'] ?? 'gemini-2.0-flash');
        GeminiClient::saveKeys((string) ($_POST['gemini_api_keys'] ?? ''), $savedModel);
        Auth::logActivity('ai_settings', 'Updated Gemini rewrite settings');
        flash_set('success', 'Gemini settings saved.');
        header('Location: ' . admin_url('ai-sources'));
        exit;
    }
}

$sources = AiIngest::getSources();
$viewId = intval($_GET['view'] ?? 0);
$logs = $viewId ? AiIngest::getLogs($viewId) : Database::fetchAll(
    "SELECT l.*, s.name as source_name FROM ai_source_logs l LEFT JOIN ai_sources s ON s.id = l.source_id ORDER BY l.id DESC LIMIT 20"
);
if (!class_exists('GeminiClient')) {
    require_once INCLUDES_PATH . 'GeminiClient.php';
}
$geminiKeys = (string) Setting::get('gemini_api_keys', '');
$geminiModel = GeminiClient::getModel();
$llmConfigured = GeminiClient::isConfigured();

include ADMIN_PATH . 'layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-robot me-2"></i>AI Sources</h4>
    <a href="<?php echo admin_url('posts&status=draft'); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-pencil me-1"></i>Review drafts
    </a>
</div>

<div class="alert alert-info">
    Sources save extracted articles as drafts. Tick <strong>Use AI rewrite</strong> on the post editor when you are ready to publish.
</div>
<?php if (!$llmConfigured): ?>
<div class="alert alert-warning">
    Gemini rewriting is off until you save at least one API key below.
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-3">Ingest a single post</h6>
                <form method="POST">
                    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="ingest_url">
                    <div class="mb-3">
                        <label class="form-label">Post URL</label>
                        <input type="url" name="post_url" class="form-control" placeholder="https://politicsnigeria.com/2026/10/18/just-in-peter-obi-will-not-step-down-from-2027-race-for-adc-kwankwaso/" required>
                        <div class="form-text">Imports only this article as a draft. Links on the page are not crawled and no schedule is created.</div>
                    </div>
                    <button class="btn btn-outline-success" type="submit"><i class="bi bi-download me-1"></i>Ingest this post</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-3">Add source website</h6>
                <form method="POST">
                    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Punch NG" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Website URL</label>
                        <input type="url" name="url" class="form-control" placeholder="https://punchng.com/" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Check every (minutes)</label>
                            <input type="number" min="15" name="frequency_minutes" class="form-control" value="360">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Max new posts / run</label>
                            <input type="number" min="1" max="15" name="max_posts" class="form-control" value="5">
                        </div>
                    </div>
                    <button class="btn btn-success" type="submit"><i class="bi bi-plus-lg me-1"></i>Add source</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-3">Gemini settings</h6>
                <form method="POST">
                    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="save_llm">
                    <div class="mb-3">
                        <label class="form-label">Gemini API keys</label>
                        <textarea name="gemini_api_keys" class="form-control" rows="4" placeholder="One key per line" autocomplete="off"><?php echo esc($geminiKeys); ?></textarea>
                        <div class="form-text">Multiple keys rotate on quota or auth errors. Ingest and publish keep going while any key remains.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" name="gemini_model" class="form-control" value="<?php echo esc_attr($geminiModel); ?>" placeholder="gemini-2.0-flash">
                        <div class="form-text">gemini-2.0-flash (default) or gemini-1.5-flash. DeepSeek is no longer used.</div>
                    </div>
                    <button class="btn btn-outline-success" type="submit">Save Gemini settings</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-3">Configured sources</h6>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Cadence</th>
                                <th>Last check</th>
                                <th>Drafts</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$sources): ?>
                            <tr><td colspan="5" class="text-muted">No sources yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($sources as $s): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc($s['name']); ?></strong>
                                    <div class="small text-muted"><?php echo esc($s['url']); ?></div>
                                    <span class="badge <?php echo $s['enabled'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $s['enabled'] ? 'enabled' : 'paused'; ?>
                                    </span>
                                </td>
                                <td>every <?php echo (int) $s['frequency_minutes']; ?> min</td>
                                <td>
                                    <div><?php echo $s['last_checked_at'] ?: 'never'; ?></div>
                                    <div class="small text-muted"><?php echo esc($s['last_status'] ?? ''); ?></div>
                                </td>
                                <td><?php echo (int) $s['draft_count']; ?> / <?php echo (int) $s['post_count']; ?></td>
                                <td class="text-nowrap">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="action" value="run">
                                        <input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
                                        <button class="btn btn-sm btn-success">Run now</button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
                                        <button class="btn btn-sm btn-outline-secondary"><?php echo $s['enabled'] ? 'Pause' : 'Enable'; ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-3">Ingest log</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <tbody>
                        <?php if (!$logs): ?>
                            <tr><td class="text-muted">No jobs yet. Add a source and run it.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="text-muted small text-nowrap"><?php echo esc($log['created_at']); ?></td>
                                <td><?php echo esc($log['source_name'] ?? ''); ?></td>
                                <td><?php echo esc($log['message']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include ADMIN_PATH . 'layout/footer.php'; ?>
