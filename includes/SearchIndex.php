<?php
if (!defined('TRENDYPOST')) {
    die('Direct access not permitted.');
}

/**
 * Search engine indexing.
 *
 * Publishing a post/page queues its URL, then attempts an immediate push to
 * IndexNow (Bing/Yandex/Seznam) and the Google Indexing API. Failures are
 * retried from `indexing_queue` by `cron/indexing.php` with exponential
 * backoff, so a temporary outage never silently loses an indexing request.
 */
class SearchIndex
{
    public const MAX_ATTEMPTS = 5;

    public static function isEnabled(): bool
    {
        $flag = SEOSetting::get('auto_indexing', '1');
        return $flag === '1' || $flag === 'true' || $flag === 1 || $flag === true;
    }

    public static function notifyUrl(string $url, string $type = 'URL_UPDATED'): void
    {
        $url = trim($url);
        if ($url === '' || !self::isEnabled() || self::isExcluded($url)) {
            return;
        }
        // Queue only: publishing stays fast and never waits on a remote API.
        // cron/indexing.php drains the queue within minutes.
        self::queueUrl($url, $type);
    }

    public static function notifyPublishedPost(array $post): void
    {
        if (empty($post['slug'])) {
            return;
        }
        self::notifyUrl(Seo::absolute(post_url($post['slug'])));
        self::pingSitemaps();
    }

    public static function notifyPublishedPage(array $page): void
    {
        if (empty($page['slug'])) {
            return;
        }
        self::notifyUrl(Seo::absolute(page_url($page['slug'])));
        self::pingSitemaps();
    }

    /**
     * Add (or refresh) a URL in the indexing queue.
     */
    public static function queueUrl(string $url, string $type = 'URL_UPDATED'): void
    {
        if (!self::queueReady()) {
            return;
        }
        $hash = sha1($url);
        try {
            $existing = Database::fetchOne('SELECT id, status FROM indexing_queue WHERE url_hash = ?', [$hash]);
            if ($existing) {
                if ($existing['status'] === 'done') {
                    Database::update('indexing_queue', [
                        'type' => $type,
                        'status' => 'pending',
                        'attempts' => 0,
                        'last_error' => null,
                        'available_at' => date('Y-m-d H:i:s'),
                        'processed_at' => null,
                    ], 'id = ?', [$existing['id']]);
                }
                return;
            }
            Database::insert('indexing_queue', [
                'url' => mb_substr($url, 0, 500),
                'url_hash' => $hash,
                'type' => $type,
                'status' => 'pending',
                'attempts' => 0,
                'available_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // Queue unavailable (migration not run) - fall back to direct push.
            self::pushNow($url, $type);
        }
    }

    /**
     * Drain due queue items. Called by cron and opportunistically on publish.
     *
     * @return array{processed:int,succeeded:int,failed:int,remaining:int}
     */
    public static function processQueue(int $batch = 10): array
    {
        $result = ['processed' => 0, 'succeeded' => 0, 'failed' => 0, 'remaining' => 0];
        if (!self::queueReady() || !self::isEnabled()) {
            return $result;
        }
        $batch = max(1, min(50, $batch));
        try {
            $rows = Database::fetchAll(
                "SELECT * FROM indexing_queue
                 WHERE status = 'pending' AND available_at <= NOW()
                 ORDER BY available_at ASC, id ASC
                 LIMIT {$batch}"
            );
        } catch (Throwable $e) {
            return $result;
        }

        foreach ($rows as $row) {
            $result['processed']++;
            $push = self::pushNow((string) $row['url'], (string) $row['type']);
            $attempts = (int) $row['attempts'] + 1;
            if ($push['ok']) {
                $result['succeeded']++;
                Database::update('indexing_queue', [
                    'status' => 'done',
                    'attempts' => $attempts,
                    'last_error' => null,
                    'processed_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$row['id']]);
                continue;
            }
            $result['failed']++;
            if ($attempts >= self::MAX_ATTEMPTS) {
                Database::update('indexing_queue', [
                    'status' => 'failed',
                    'attempts' => $attempts,
                    'last_error' => mb_substr((string) ($push['error'] ?? 'Unknown error'), 0, 500),
                    'processed_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$row['id']]);
            } else {
                $delayMinutes = (int) (5 * (3 ** ($attempts - 1)));
                Database::update('indexing_queue', [
                    'attempts' => $attempts,
                    'last_error' => mb_substr((string) ($push['error'] ?? 'Unknown error'), 0, 500),
                    'available_at' => date('Y-m-d H:i:s', time() + $delayMinutes * 60),
                ], 'id = ?', [$row['id']]);
            }
        }

        try {
            $result['remaining'] = (int) Database::fetchColumn(
                "SELECT COUNT(*) FROM indexing_queue WHERE status IN ('pending','failed')"
            );
        } catch (Throwable $e) {
            $result['remaining'] = 0;
        }
        return $result;
    }

    public static function stats(): array
    {
        $stats = ['pending' => 0, 'failed' => 0, 'done' => 0];
        if (!self::queueReady()) {
            return $stats;
        }
        try {
            $rows = Database::fetchAll("SELECT status, COUNT(*) AS total FROM indexing_queue GROUP BY status");
            foreach ($rows as $row) {
                if (isset($stats[$row['status']])) {
                    $stats[$row['status']] = (int) $row['total'];
                }
            }
        } catch (Throwable $e) {
            return $stats;
        }
        return $stats;
    }

    public static function pingSitemaps(): void
    {
        if (!self::isEnabled()) {
            return;
        }
        // Throttle to once per hour so unpublishing several posts does not
        // hammer the ping endpoints.
        $last = (int) SEOSetting::get('last_sitemap_ping', 0);
        if ($last > 0 && (time() - $last) < 3600) {
            return;
        }
        $sitemap = url('sitemap.xml');
        $targets = [
            'https://www.bing.com/ping?sitemap=' . rawurlencode($sitemap),
        ];
        foreach ($targets as $pingUrl) {
            self::httpGet($pingUrl);
        }
        if (class_exists('SEOSetting') && method_exists('SEOSetting', 'set')) {
            SEOSetting::set('last_sitemap_ping', (string) time());
        }
    }

    public static function isExcluded(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $blocked = ['/admin', '/api/', '/login', '/register', '/forgot-password', '/reset-password', '/preview/', '/install', '/search', '/newsletter/'];
        foreach ($blocked as $needle) {
            if (stripos($path, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private static function queueReady(): bool
    {
        try {
            Database::fetchColumn('SELECT COUNT(*) FROM indexing_queue');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Push one URL to every configured engine.
     *
     * @return array{ok:bool,error:?string,indexnow:int,google:int}
     */
    private static function pushNow(string $url, string $type): array
    {
        $result = ['ok' => false, 'error' => null, 'indexnow' => 0, 'google' => 0];
        $indexNow = self::indexNow([$url]);
        $result['indexnow'] = $indexNow['code'];
        if ($indexNow['code'] >= 200 && $indexNow['code'] < 300) {
            $result['ok'] = true;
        } elseif ($indexNow['error'] !== null) {
            $result['error'] = 'IndexNow: ' . $indexNow['error'];
        }

        if (self::hasGoogleKey()) {
            $google = self::googleIndexingApi($url, $type);
            $result['google'] = $google['code'];
            if ($google['code'] >= 200 && $google['code'] < 300) {
                $result['ok'] = true;
            } elseif ($google['error'] !== null) {
                $result['error'] = trim(($result['error'] ?? '') . ' Google: ' . $google['error']);
            }
        }

        if (!$result['ok'] && $result['error'] === null) {
            $result['error'] = 'No indexing endpoint accepted the URL';
        }
        return $result;
    }

    private static function hasGoogleKey(): bool
    {
        $raw = trim((string) SEOSetting::get('indexing_api_key', ''));
        return $raw !== '' && (strpos($raw, '{') === 0 || strpos($raw, '-----BEGIN') === 0);
    }

    /**
     * @return array{code:int,body:string,error:?string}
     */
    private static function indexNow(array $urls): array
    {
        $key = trim((string) SEOSetting::get('indexing_api_key', ''));
        if ($key === '' || strlen($key) < 8 || strpos($key, '{') === 0 || strpos($key, '-----BEGIN') === 0) {
            $key = substr(hash('sha256', ENCRYPTION_KEY . 'indexnow'), 0, 32);
        }
        $key = preg_replace('/[^a-zA-Z0-9\-]/', '', $key);
        if ($key === '' || strlen($key) < 8) {
            return ['code' => 0, 'body' => '', 'error' => 'Invalid IndexNow key'];
        }
        $host = parse_url(BASE_URL, PHP_URL_HOST);
        if (!$host) {
            return ['code' => 0, 'body' => '', 'error' => 'Invalid site host'];
        }
        $payload = json_encode([
            'host' => $host,
            'key' => $key,
            'keyLocation' => url($key . '.txt'),
            'urlList' => array_values($urls),
        ]);
        return self::httpPost('https://api.indexnow.org/indexnow', $payload, ['Content-Type: application/json; charset=utf-8']);
    }

    /**
     * @return array{code:int,body:string,error:?string}
     */
    private static function googleIndexingApi(string $url, string $type): array
    {
        $raw = trim((string) SEOSetting::get('indexing_api_key', ''));
        if ($raw === '' || (strpos($raw, '{') !== 0 && strpos($raw, '-----BEGIN') === false)) {
            return ['code' => 0, 'body' => '', 'error' => null];
        }
        $token = self::googleAccessToken($raw);
        if ($token === '') {
            return ['code' => 0, 'body' => '', 'error' => 'Could not obtain Google access token'];
        }
        return self::httpPost(
            'https://indexing.googleapis.com/v3/urlNotifications:publish',
            json_encode(['url' => $url, 'type' => $type]),
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ]
        );
    }

    private static function googleAccessToken(string $jsonKey): string
    {
        $creds = json_decode($jsonKey, true);
        if (!is_array($creds) || empty($creds['client_email']) || empty($creds['private_key'])) {
            return '';
        }
        $now = time();
        $header = self::b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = self::b64url(json_encode([
            'iss' => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/indexing',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));
        $unsigned = $header . '.' . $claim;
        $signature = '';
        if (!openssl_sign($unsigned, $signature, $creds['private_key'], 'sha256WithRSAEncryption')) {
            return '';
        }
        $jwt = $unsigned . '.' . self::b64url($signature);
        $response = self::httpPost(
            'https://oauth2.googleapis.com/token',
            http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            ['Content-Type: application/x-www-form-urlencoded']
        );
        $data = json_decode($response['body'], true);
        return is_array($data) ? (string) ($data['access_token'] ?? '') : '';
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @return array{code:int,body:string,error:?string}
     */
    private static function httpPost(string $url, string $body, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'code' => $code,
            'body' => is_string($raw) ? $raw : '',
            'error' => $raw === false ? ($error !== '' ? $error : 'Network error') : null,
        ];
    }

    private static function httpGet(string $url): void
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
