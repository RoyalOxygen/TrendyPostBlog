<?php
if (!defined('TRENDYPOST')) {
    die('Direct access not permitted.');
}

class SearchIndex
{
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
        self::indexNow([$url]);
        self::googleIndexingApi($url, $type);
    }

    public static function notifyPublishedPost(array $post): void
    {
        if (empty($post['slug'])) {
            return;
        }
        self::notifyUrl(post_url($post['slug']));
        self::pingSitemaps();
    }

    public static function notifyPublishedPage(array $page): void
    {
        if (empty($page['slug'])) {
            return;
        }
        self::notifyUrl(page_url($page['slug']));
        self::pingSitemaps();
    }

    public static function pingSitemaps(): void
    {
        if (!self::isEnabled()) {
            return;
        }
        $sitemap = url('sitemap.xml');
        $targets = [
            'https://www.google.com/ping?sitemap=' . rawurlencode($sitemap),
            'https://www.bing.com/ping?sitemap=' . rawurlencode($sitemap),
        ];
        foreach ($targets as $pingUrl) {
            self::httpGet($pingUrl);
        }
    }

    public static function isExcluded(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $blocked = ['/admin', '/api/', '/login', '/register', '/forgot-password', '/reset-password', '/preview/', '/install'];
        foreach ($blocked as $needle) {
            if (stripos($path, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private static function indexNow(array $urls): void
    {
        $key = trim((string) SEOSetting::get('indexing_api_key', ''));
        if ($key === '' || strlen($key) < 8 || strpos($key, '{') === 0 || strpos($key, '-----BEGIN') === 0) {
            $key = substr(hash('sha256', ENCRYPTION_KEY . 'indexnow'), 0, 32);
        }
        $key = preg_replace('/[^a-zA-Z0-9\-]/', '', $key);
        if ($key === '' || strlen($key) < 8) {
            return;
        }
        $host = parse_url(BASE_URL, PHP_URL_HOST);
        if (!$host) {
            return;
        }
        $payload = json_encode([
            'host' => $host,
            'key' => $key,
            'keyLocation' => url($key . '.txt'),
            'urlList' => array_values($urls),
        ]);
        self::httpPost('https://api.indexnow.org/indexnow', $payload, ['Content-Type: application/json; charset=utf-8']);
    }

    private static function googleIndexingApi(string $url, string $type): void
    {
        $raw = trim((string) SEOSetting::get('indexing_api_key', ''));
        if ($raw === '' || (strpos($raw, '{') !== 0 && strpos($raw, '-----BEGIN') === false)) {
            return;
        }
        $token = self::googleAccessToken($raw);
        if ($token === '') {
            return;
        }
        self::httpPost(
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
        $ok = openssl_sign($unsigned, $signature, $creds['private_key'], 'sha256WithRSAEncryption');
        if (!$ok) {
            return '';
        }
        $jwt = $unsigned . '.' . self::b64url($signature);
        $raw = self::httpPost(
            'https://oauth2.googleapis.com/token',
            http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            ['Content-Type: application/x-www-form-urlencoded']
        );
        $data = json_decode((string) $raw, true);
        return is_array($data) ? (string) ($data['access_token'] ?? '') : '';
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function httpPost(string $url, string $body, array $headers = []): string
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
        curl_close($ch);
        return is_string($raw) ? $raw : '';
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
