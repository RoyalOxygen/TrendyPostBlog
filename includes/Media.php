<?php
if (!defined('TRENDYPOST')) {
    die('Direct access not permitted.');
}

class Media
{
    private const USER_AGENT = 'Mozilla/5.0 (compatible; TrendyPostBot/1.0) AppleWebKit/537.36';
    private const MAX_BYTES = 8388608;
    private const MIME_EXT = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public static function relativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return '';
        }
        if (defined('UPLOADS_URL') && str_starts_with($path, UPLOADS_URL)) {
            $path = substr($path, strlen(UPLOADS_URL));
        }
        if (defined('BASE_URL')) {
            $prefix = rtrim(BASE_URL, '/') . '/assets/uploads/';
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }
        if (defined('UPLOADS_PATH') && str_starts_with($path, UPLOADS_PATH)) {
            $path = substr($path, strlen(UPLOADS_PATH));
        }
        if (defined('ROOT_PATH') && str_starts_with($path, ROOT_PATH)) {
            $path = substr($path, strlen(ROOT_PATH));
        }
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'assets/uploads/')) {
            $path = substr($path, strlen('assets/uploads/'));
        }
        return ltrim($path, '/');
    }

    public static function diskPath(string $relative): string
    {
        return rtrim(UPLOADS_PATH, '/') . '/' . self::relativePath($relative);
    }

    public static function isRemote(?string $path): bool
    {
        $path = trim((string) $path);
        if ($path === '' || str_starts_with($path, 'data:')) {
            return false;
        }
        if (!preg_match('#^https?://#i', $path)) {
            return false;
        }
        if (defined('UPLOADS_URL') && str_starts_with($path, UPLOADS_URL)) {
            return false;
        }
        if (defined('BASE_URL') && str_starts_with($path, rtrim(BASE_URL, '/') . '/assets/uploads/')) {
            return false;
        }
        return true;
    }

    public static function ensureDir(string $folder): string
    {
        $folder = trim($folder, '/');
        $dir = rtrim(UPLOADS_PATH, '/') . '/' . $folder;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public static function downloadRemote(string $url, string $folder = 'posts', string $alt = '', ?int $userId = null): ?string
    {
        $url = trim($url);
        if ($url === '' || !self::isRemote($url)) {
            return $url !== '' ? self::relativePath($url) : null;
        }

        $existing = Database::fetchOne("SELECT filename, file_path FROM media WHERE description = ? LIMIT 1", ['source:' . $url]);
        if ($existing) {
            $rel = self::relativePath($existing['file_path'] ?: $existing['filename']);
            if ($rel !== '' && is_file(self::diskPath($rel))) {
                return $rel;
            }
        }

        $binary = self::fetchBinary($url);
        if ($binary === null) {
            return null;
        }

        $ext = $binary['ext'];
        $dir = self::ensureDir($folder);
        $name = $folder . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $abs = $dir . '/' . $name;
        if (file_put_contents($abs, $binary['body']) === false) {
            return null;
        }

        $relative = $folder . '/' . $name;
        self::register($relative, $binary['name'], $alt, $userId, 'source:' . $url, $binary['mime']);
        return $relative;
    }

    public static function register(string $relative, string $original = '', string $alt = '', ?int $userId = null, string $description = '', string $mime = ''): int
    {
        $relative = self::relativePath($relative);
        if ($relative === '') {
            return 0;
        }
        $found = Database::fetchOne("SELECT id FROM media WHERE filename = ? OR file_path = ? LIMIT 1", [$relative, $relative]);
        if ($found) {
            return (int) $found['id'];
        }
        $abs = self::diskPath($relative);
        $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        $size = is_file($abs) ? (int) filesize($abs) : 0;
        $width = null;
        $height = null;
        if (is_file($abs) && in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $info = @getimagesize($abs);
            if (is_array($info)) {
                $width = $info[0] ?? null;
                $height = $info[1] ?? null;
                if ($mime === '') {
                    $mime = $info['mime'] ?? '';
                }
            }
        }
        return (int) Database::insert('media', [
            'filename' => $relative,
            'original_name' => $original !== '' ? $original : basename($relative),
            'file_path' => $relative,
            'file_url' => upload_url($relative),
            'file_type' => $ext,
            'mime_type' => $mime ?: null,
            'file_size' => $size,
            'width' => $width,
            'height' => $height,
            'alt_text' => $alt !== '' ? $alt : null,
            'description' => $description !== '' ? $description : null,
            'uploaded_by' => $userId,
        ]);
    }

    public static function localizePath(?string $path, string $folder = 'posts', string $alt = '', ?int $userId = null): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }
        if (!self::isRemote($path)) {
            $rel = self::relativePath($path);
            return $rel !== '' ? $rel : null;
        }
        return self::downloadRemote($path, $folder, $alt, $userId);
    }

    public static function localizeHtml(string $html, string $folder = 'posts', ?int $userId = null): string
    {
        if ($html === '' || stripos($html, '<img') === false) {
            return $html;
        }
        return preg_replace_callback('/(<img\b[^>]*\bsrc\s*=\s*)([\'"])([^\'"]+)\2/i', function ($m) use ($folder, $userId) {
            $src = html_entity_decode($m[3], ENT_QUOTES, 'UTF-8');
            $local = self::localizePath($src, $folder, '', $userId);
            if (!$local) {
                return $m[0];
            }
            return $m[1] . $m[2] . htmlspecialchars(upload_url($local), ENT_QUOTES, 'UTF-8') . $m[2];
        }, $html) ?? $html;
    }

    public static function localizeExisting(?int $userId = null): array
    {
        $updated = 0;
        $failed = 0;
        $posts = Database::fetchAll("SELECT id, title, featured_image, og_image, twitter_image, content FROM posts");
        foreach ($posts as $row) {
            $feat = self::localizePath($row['featured_image'] ?? '', 'posts', $row['title'] ?? '', $userId);
            $og = self::localizePath($row['og_image'] ?? '', 'og', $row['title'] ?? '', $userId);
            $tw = self::localizePath($row['twitter_image'] ?? '', 'twitter', $row['title'] ?? '', $userId);
            $content = self::localizeHtml((string) ($row['content'] ?? ''), 'posts', $userId);
            $data = [];
            if ($feat && $feat !== (string) ($row['featured_image'] ?? '') && !self::isRemote($feat)) {
                $data['featured_image'] = $feat;
            }
            if ($og && $og !== (string) ($row['og_image'] ?? '') && !self::isRemote($og)) {
                $data['og_image'] = $og;
            }
            if ($tw && $tw !== (string) ($row['twitter_image'] ?? '') && !self::isRemote($tw)) {
                $data['twitter_image'] = $tw;
            }
            if ($content !== (string) ($row['content'] ?? '')) {
                $data['content'] = $content;
            }
            if ($data) {
                Database::update('posts', $data, 'id = ?', [$row['id']]);
                $updated++;
            } elseif (self::isRemote($row['featured_image'] ?? '') || self::isRemote($row['og_image'] ?? '')) {
                $failed++;
            }
        }
        $pages = Database::fetchAll("SELECT id, title, featured_image, content FROM pages");
        foreach ($pages as $row) {
            $feat = self::localizePath($row['featured_image'] ?? '', 'pages', $row['title'] ?? '', $userId);
            $content = self::localizeHtml((string) ($row['content'] ?? ''), 'pages', $userId);
            $data = [];
            if ($feat && $feat !== (string) ($row['featured_image'] ?? '') && !self::isRemote($feat)) {
                $data['featured_image'] = $feat;
            }
            if ($content !== (string) ($row['content'] ?? '')) {
                $data['content'] = $content;
            }
            if ($data) {
                Database::update('pages', $data, 'id = ?', [$row['id']]);
                $updated++;
            }
        }
        $cats = Database::fetchAll("SELECT id, name, image FROM categories");
        foreach ($cats as $row) {
            $img = self::localizePath($row['image'] ?? '', 'categories', $row['name'] ?? '', $userId);
            if ($img && $img !== (string) ($row['image'] ?? '') && !self::isRemote($img)) {
                Database::update('categories', ['image' => $img], 'id = ?', [$row['id']]);
                $updated++;
            }
        }
        return ['updated' => $updated, 'failed' => $failed];
    }

    public static function syncFromDisk(): int
    {
        $root = rtrim(UPLOADS_PATH, '/');
        if (!is_dir($root)) {
            mkdir($root, 0755, true);
            return 0;
        }
        $added = 0;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'mp4', 'mp3', 'doc', 'docx'], true)) {
                continue;
            }
            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($root))), '/');
            $before = Database::fetchOne("SELECT id FROM media WHERE filename = ? OR file_path = ? LIMIT 1", [$relative, $relative]);
            if ($before) {
                continue;
            }
            self::register($relative, $file->getFilename());
            $added++;
        }
        return $added;
    }

    public static function replace(int $id, array $file, int $userId): bool
    {
        $row = Database::fetchOne("SELECT * FROM media WHERE id = ?", [$id]);
        if (!$row || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return false;
        }
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return false;
        }
        $oldRel = self::relativePath($row['file_path'] ?: $row['filename']);
        $oldExt = strtolower(pathinfo($oldRel, PATHINFO_EXTENSION));
        $dir = dirname(self::diskPath($oldRel));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if ($ext === $oldExt || ($oldExt === 'jpeg' && $ext === 'jpg') || ($oldExt === 'jpg' && $ext === 'jpeg')) {
            $targetRel = $oldRel;
            $targetAbs = self::diskPath($oldRel);
        } else {
            $base = pathinfo($oldRel, PATHINFO_DIRNAME);
            $stem = pathinfo($oldRel, PATHINFO_FILENAME);
            $targetRel = ($base === '.' ? '' : $base . '/') . $stem . '.' . $ext;
            $targetAbs = self::diskPath($targetRel);
        }
        if (!move_uploaded_file($file['tmp_name'], $targetAbs)) {
            return false;
        }
        $mime = $file['type'] ?? '';
        $size = is_file($targetAbs) ? (int) filesize($targetAbs) : 0;
        $width = null;
        $height = null;
        $info = @getimagesize($targetAbs);
        if (is_array($info)) {
            $width = $info[0] ?? null;
            $height = $info[1] ?? null;
            $mime = $info['mime'] ?? $mime;
        }
        Database::update('media', [
            'filename' => $targetRel,
            'original_name' => $file['name'] ?? basename($targetRel),
            'file_path' => $targetRel,
            'file_url' => upload_url($targetRel),
            'file_type' => $ext,
            'mime_type' => $mime,
            'file_size' => $size,
            'width' => $width,
            'height' => $height,
            'uploaded_by' => $userId,
        ], 'id = ?', [$id]);
        if ($targetRel !== $oldRel) {
            self::retarget($oldRel, $targetRel);
        }
        return true;
    }

    public static function retarget(string $old, string $new): void
    {
        $old = self::relativePath($old);
        $new = self::relativePath($new);
        if ($old === '' || $new === '' || $old === $new) {
            return;
        }
        $oldUrl = upload_url($old);
        $newUrl = upload_url($new);
        foreach (['featured_image', 'og_image', 'twitter_image'] as $col) {
            Database::query("UPDATE posts SET {$col} = ? WHERE {$col} = ?", [$new, $old]);
        }
        Database::query("UPDATE posts SET content = REPLACE(content, ?, ?) WHERE content LIKE ?", [$oldUrl, $newUrl, '%' . $oldUrl . '%']);
        Database::query("UPDATE posts SET content = REPLACE(content, ?, ?) WHERE content LIKE ?", [$old, $new, '%' . $old . '%']);
        Database::query("UPDATE pages SET featured_image = ? WHERE featured_image = ?", [$new, $old]);
        Database::query("UPDATE pages SET content = REPLACE(content, ?, ?) WHERE content LIKE ?", [$oldUrl, $newUrl, '%' . $oldUrl . '%']);
        Database::query("UPDATE categories SET image = ? WHERE image = ?", [$new, $old]);
        Database::query("UPDATE advertisements SET image = ? WHERE image = ?", [$new, $old]);
        Database::query("UPDATE users SET avatar = ? WHERE avatar = ?", [$new, $old]);
    }

    public static function usageCount(string $relative): int
    {
        $relative = self::relativePath($relative);
        if ($relative === '') {
            return 0;
        }
        $url = upload_url($relative);
        $n = 0;
        $n += (int) Database::fetchColumn("SELECT COUNT(*) FROM posts WHERE featured_image = ? OR og_image = ? OR twitter_image = ? OR content LIKE ?", [$relative, $relative, $relative, '%' . $relative . '%']);
        $n += (int) Database::fetchColumn("SELECT COUNT(*) FROM pages WHERE featured_image = ? OR content LIKE ?", [$relative, '%' . $relative . '%']);
        $n += (int) Database::fetchColumn("SELECT COUNT(*) FROM categories WHERE image = ?", [$relative]);
        $n += (int) Database::fetchColumn("SELECT COUNT(*) FROM advertisements WHERE image = ?", [$relative]);
        $n += (int) Database::fetchColumn("SELECT COUNT(*) FROM users WHERE avatar = ?", [$relative]);
        $n += (int) Database::fetchColumn("SELECT COUNT(*) FROM posts WHERE content LIKE ?", ['%' . $url . '%']);
        return $n;
    }

    private static function fetchBinary(string $url): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => ['Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_MAXFILESIZE => self::MAX_BYTES,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ctype = strtolower((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
        curl_close($ch);
        if ($body === false || $body === '' || $code >= 400 || strlen($body) > self::MAX_BYTES) {
            return null;
        }
        $mime = trim(explode(';', $ctype)[0]);
        $ext = self::MIME_EXT[$mime] ?? null;
        if (!$ext) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($body) ?: '';
            $ext = self::MIME_EXT[$mime] ?? null;
        }
        if (!$ext) {
            $urlPath = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
            $urlExt = pathinfo($urlPath, PATHINFO_EXTENSION);
            if ($urlExt === 'jfif' || $urlExt === 'jpeg') {
                $ext = 'jpg';
                $mime = 'image/jpeg';
            } elseif (in_array($urlExt, ['jpg', 'png', 'gif', 'webp'], true)) {
                $ext = $urlExt;
            }
        }
        if (!$ext) {
            return null;
        }
        $name = basename(parse_url($url, PHP_URL_PATH) ?? '') ?: ('image.' . $ext);
        return ['body' => $body, 'ext' => $ext, 'mime' => $mime, 'name' => $name];
    }
}
