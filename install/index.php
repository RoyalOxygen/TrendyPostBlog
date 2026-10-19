<?php

if (!defined('TRENDYPOST')) {
    define('TRENDYPOST', true);
    require_once dirname(__DIR__) . '/config/config.php';
}

require_once INCLUDES_PATH . 'Installer.php';
require_once INCLUDES_PATH . 'Helpers.php';

Installer::bootstrapSession();

if (Installer::isInstalled()) {
    header('Location: ' . BASE_URL);
    exit;
}

$state = Installer::state();
$errors = [];
$done = false;
$step = (int) ($state['step'] ?? 1);
if ($step < 1) {
    $step = 1;
}
if ($step > 5) {
    $step = 5;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_token'] ?? '')) {
        $errors[] = 'Invalid request. Refresh and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? 'next');
        foreach (['db_host', 'db_name', 'db_user', 'db_pass', 'wipe', 'site_name', 'site_tagline', 'site_email', 'admin_username', 'admin_email', 'admin_password', 'admin_password_confirm'] as $key) {
            if (array_key_exists($key, $_POST)) {
                $state[$key] = is_string($_POST[$key]) ? trim($_POST[$key]) : $_POST[$key];
            }
        }

        if ($action === 'back') {
            $step = max(1, $step - 1);
            if ($step === 3 && empty($state['existing'])) {
                $step = 2;
            }
        } elseif ($action === 'install') {
            $errors = array_merge($errors, Installer::validateDatabase($state));
            $errors = array_merge($errors, Installer::validateSite($state));
            if (!$errors) {
                try {
                    Installer::run($state);
                    $done = true;
                } catch (Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }
        } else {
            if ($step === 1) {
                $req = Installer::requirements();
                if (!$req['ok']) {
                    $errors[] = 'Fix the failed requirements before continuing.';
                }
            } elseif ($step === 2) {
                $errors = array_merge($errors, Installer::validateDatabase($state));
                if (!$errors) {
                    try {
                        $pdo = Installer::ensureDatabase($state['db_host'], $state['db_user'], $state['db_pass'], $state['db_name']);
                        $info = Installer::inspect($pdo);
                        $state = array_merge($state, $info);
                        if (!$info['existing']) {
                            $state['wipe'] = '0';
                            $step = 3;
                        }
                    } catch (Throwable $e) {
                        $errors[] = 'Could not connect: ' . $e->getMessage();
                    }
                }
            } elseif ($step === 3) {
                if (!in_array((string) $state['wipe'], ['0', '1'], true)) {
                    $errors[] = 'Choose whether to keep or erase existing data.';
                }
            } elseif ($step === 4) {
                $errors = array_merge($errors, Installer::validateSite($state));
            }
            if (!$errors) {
                $step++;
                if ($step > 5) {
                    $step = 5;
                }
            }
        }
        if (!$done) {
            $state['step'] = $step;
            Installer::saveState($state);
        }
    }
}

$req = Installer::requirements();
$labels = [1 => 'Requirements', 2 => 'Database', 3 => 'Data', 4 => 'Site', 5 => 'Install'];

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install TrendyPost</title>
    <meta name="robots" content="noindex,nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --tp-primary:#00C853; }
        body { background:#f4f6f8; font-family: Inter, system-ui, sans-serif; }
        .install-card { max-width: 760px; margin: 2.5rem auto; }
        .step-pill { font-size: 12px; }
        .btn-success { background: var(--tp-primary); border-color: var(--tp-primary); }
    </style>
</head>
<body>
<div class="container">
    <div class="install-card card border-0 shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <h1 class="h4 mb-1">TrendyPost installer</h1>
                <p class="text-muted mb-0">Frontend setup. No phpMyAdmin required.</p>
            </div>
            <?php if ($done): ?>
                <div class="alert alert-success">Installation complete. Existing content was <?php echo ((string) ($state['wipe'] ?? '0') === '1') ? 'erased and replaced with a fresh schema' : 'preserved'; ?>.</div>
                <p>You can sign in at the admin panel with the superadmin account you just set (or the account that already existed).</p>
                <a class="btn btn-success" href="<?php echo htmlspecialchars(rtrim(BASE_URL, '/') . '/admin/', ENT_QUOTES, 'UTF-8'); ?>">Open admin</a>
                <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>">View site</a>
            <?php else: ?>
                <div class="d-flex justify-content-between mb-4">
                    <?php foreach ($labels as $n => $label): ?>
                        <span class="step-pill badge <?php echo $n === $step ? 'text-bg-success' : ($n < $step ? 'text-bg-secondary' : 'text-bg-light text-muted'); ?>"><?php echo $n . '. ' . $label; ?></span>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
                <form method="post">
                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if ($step === 1): ?>
                        <h2 class="h5">Server requirements</h2>
                        <ul class="list-group mb-3">
                            <?php foreach ($req['checks'] as $check): ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><?php echo htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="<?php echo $check['ok'] ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $check['ok'] ? 'OK' : 'Fail'; ?>
                                        <small class="text-muted"><?php echo htmlspecialchars((string) $check['detail'], ENT_QUOTES, 'UTF-8'); ?></small>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php elseif ($step === 2): ?>
                        <h2 class="h5">Database connection</h2>
                        <p class="text-muted">Enter MySQL/MariaDB credentials. The database is created if it does not exist.</p>
                        <div class="mb-3"><label class="form-label">Host</label><input class="form-control" name="db_host" value="<?php echo htmlspecialchars($state['db_host'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
                        <div class="mb-3"><label class="form-label">Database name</label><input class="form-control" name="db_name" value="<?php echo htmlspecialchars($state['db_name'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
                        <div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="db_user" value="<?php echo htmlspecialchars($state['db_user'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
                        <div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="db_pass" value="<?php echo htmlspecialchars($state['db_pass'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <?php elseif ($step === 3): ?>
                        <h2 class="h5">Existing data found</h2>
                        <p>This database already has <strong><?php echo (int) $state['table_count']; ?></strong> tables, <strong><?php echo (int) $state['user_count']; ?></strong> users and <strong><?php echo (int) $state['post_count']; ?></strong> posts.</p>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="wipe" id="keepData" value="0" <?php echo (string) $state['wipe'] !== '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="keepData"><strong>Keep existing data</strong> (recommended). Missing tables are added; posts and users stay.</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="wipe" id="wipeData" value="1" <?php echo (string) $state['wipe'] === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="wipeData"><strong>Erase everything</strong> and install a fresh schema. This cannot be undone.</label>
                        </div>
                    <?php elseif ($step === 4): ?>
                        <h2 class="h5">Site and superadmin</h2>
                        <div class="mb-3"><label class="form-label">Site name</label><input class="form-control" name="site_name" value="<?php echo htmlspecialchars($state['site_name'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
                        <div class="mb-3"><label class="form-label">Tagline</label><input class="form-control" name="site_tagline" value="<?php echo htmlspecialchars($state['site_tagline'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                        <div class="mb-3"><label class="form-label">Site email</label><input class="form-control" type="email" name="site_email" value="<?php echo htmlspecialchars($state['site_email'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                        <hr>
                        <p class="text-muted"><?php echo !empty($state['existing']) && (string) $state['wipe'] !== '1' && (int) $state['user_count'] > 0 ? 'Optional: update the superadmin login. Leave password blank to keep the existing account.' : 'Create the first superadmin account.'; ?></p>
                        <div class="mb-3"><label class="form-label">Admin username</label><input class="form-control" name="admin_username" value="<?php echo htmlspecialchars($state['admin_username'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                        <div class="mb-3"><label class="form-label">Admin email</label><input class="form-control" type="email" name="admin_email" value="<?php echo htmlspecialchars($state['admin_email'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                        <div class="mb-3"><label class="form-label">Admin password</label><input class="form-control" type="password" name="admin_password" minlength="8"></div>
                        <div class="mb-3"><label class="form-label">Confirm password</label><input class="form-control" type="password" name="admin_password_confirm" minlength="8"></div>
                    <?php else: ?>
                        <h2 class="h5">Ready to install</h2>
                        <ul class="list-group mb-3">
                            <li class="list-group-item">Database: <?php echo htmlspecialchars($state['db_user'] . '@' . $state['db_host'] . '/' . $state['db_name'], ENT_QUOTES, 'UTF-8'); ?></li>
                            <li class="list-group-item">Data: <?php echo ((string) $state['wipe'] === '1') ? 'Erase and reinstall' : (!empty($state['existing']) ? 'Preserve existing tables and content' : 'Fresh schema'); ?></li>
                            <li class="list-group-item">Site: <?php echo htmlspecialchars($state['site_name'], ENT_QUOTES, 'UTF-8'); ?></li>
                            <li class="list-group-item">Superadmin: <?php echo htmlspecialchars($state['admin_email'] ?: '(keep existing)', ENT_QUOTES, 'UTF-8'); ?></li>
                        </ul>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mt-4">
                        <?php if ($step > 1): ?>
                            <button class="btn btn-outline-secondary" name="action" value="back" type="submit">Back</button>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>
                        <?php if ($step < 5): ?>
                            <button class="btn btn-success" name="action" value="next" type="submit">Continue</button>
                        <?php else: ?>
                            <button class="btn btn-success" name="action" value="install" type="submit">Install</button>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
