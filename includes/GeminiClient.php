<?php

if (!defined('TRENDYPOST')) {
    die('Direct access not permitted.');
}

class GeminiClient {

    private const DEFAULT_MODEL = 'gemini-2.0-flash';
    private const FALLBACK_MODEL = 'gemini-1.5-flash';
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const COOLDOWN_SECONDS = 90;
    private const MAX_RETRIES = 2;

    public static function isConfigured(): bool {
        return self::getKeys() !== [];
    }

    public static function getModel(): string {
        $model = trim((string) Setting::get('gemini_model', ''));
        if ($model === '' || stripos($model, 'deepseek') !== false) {
            return self::DEFAULT_MODEL;
        }
        return $model;
    }

    public static function getKeys(): array {
        $raw = (string) Setting::get('gemini_api_keys', getenv('USER_GEMINI_API_KEY') ?: '');
        $keys = [];
        foreach (preg_split('/[\r\n,]+/', $raw) ?: [] as $key) {
            $key = trim($key);
            if ($key === '' || $key === 'your-api-key-here') {
                continue;
            }
            $keys[] = $key;
        }
        return array_values(array_unique($keys));
    }

    public static function saveKeys(string $keysText, string $model): void {
        $model = trim($model);
        if ($model === '' || stripos($model, 'deepseek') !== false) {
            $model = self::DEFAULT_MODEL;
        }
        Setting::set('gemini_api_keys', trim($keysText), 'ai');
        Setting::set('gemini_model', $model, 'ai');
        Setting::set('user_llm_api_key', '', 'ai');
        Setting::set('user_llm_base_url', 'https://generativelanguage.googleapis.com', 'ai');
        Setting::set('user_llm_model', $model, 'ai');
    }

    public static function generateJson(string $prompt, array $options = []): ?array {
        $text = self::generateText($prompt, array_merge($options, ['json' => true]));
        if ($text === null || $text === '') {
            return null;
        }
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text));
        $parsed = json_decode($text, true);
        if (!is_array($parsed) && preg_match('/\{.*\}/s', $text, $m)) {
            $parsed = json_decode($m[0], true);
        }
        return is_array($parsed) ? $parsed : null;
    }

    public static function generateText(string $prompt, array $options = []): ?string {
        $keys = self::orderedKeys();
        if (!$keys) {
            return null;
        }
        $model = $options['model'] ?? self::getModel();
        $json = !empty($options['json']);
        $maxTokens = (int) ($options['max_tokens'] ?? 4096);
        $lastError = '';
        foreach ($keys as $key) {
            $result = self::callKey($key, $model, $prompt, $json, $maxTokens);
            if ($result['ok']) {
                self::recordSuccess($key);
                return $result['text'];
            }
            $lastError = $result['error'];
            self::recordFailure($key, $lastError, $result['cooldown']);
            if ($result['retry_model'] && $model !== self::FALLBACK_MODEL) {
                $fallback = self::callKey($key, self::FALLBACK_MODEL, $prompt, $json, $maxTokens);
                if ($fallback['ok']) {
                    self::recordSuccess($key);
                    Setting::set('gemini_model', self::FALLBACK_MODEL, 'ai');
                    return $fallback['text'];
                }
                $lastError = $fallback['error'];
                self::recordFailure($key, $lastError, $fallback['cooldown']);
            }
        }
        error_log('GeminiClient failed: ' . $lastError);
        return null;
    }

    public static function lastError(): string {
        $stats = self::stats();
        foreach (array_reverse($stats) as $row) {
            if (!empty($row['last_error'])) {
                return (string) $row['last_error'];
            }
        }
        return '';
    }

    private static function orderedKeys(): array {
        $keys = self::getKeys();
        if (count($keys) < 2) {
            return $keys;
        }
        $stats = self::stats();
        $now = time();
        usort($keys, function ($a, $b) use ($stats, $now) {
            $sa = $stats[self::keyId($a)] ?? [];
            $sb = $stats[self::keyId($b)] ?? [];
            $ca = (int) ($sa['cooldown_until'] ?? 0) > $now ? 1 : 0;
            $cb = (int) ($sb['cooldown_until'] ?? 0) > $now ? 1 : 0;
            if ($ca !== $cb) {
                return $ca <=> $cb;
            }
            return ((int) ($sa['fail'] ?? 0)) <=> ((int) ($sb['fail'] ?? 0));
        });
        return $keys;
    }

    private static function callKey(string $key, string $model, string $prompt, bool $json, int $maxTokens = 4096): array {
        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => max(1024, min(8192, $maxTokens)),
            ],
        ];
        if ($json) {
            $payload['generationConfig']['responseMimeType'] = 'application/json';
        }
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $attempts = 0;
        $last = ['ok' => false, 'text' => '', 'error' => 'empty', 'cooldown' => false, 'retry_model' => false];
        while ($attempts <= self::MAX_RETRIES) {
            $attempts++;
            $url = sprintf(self::ENDPOINT, rawurlencode($model)) . '?key=' . rawurlencode($key);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 90,
                CURLOPT_CONNECTTIMEOUT => 15,
            ]);
            $raw = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = (string) curl_error($ch);
            curl_close($ch);

            if ($raw === false || $code >= 400) {
                $detail = self::errorDetail($code, $raw, $curlErr);
                $quota = self::isQuotaError($code, $raw);
                $invalidModel = $code === 404 || stripos($detail, 'not found') !== false;
                $retryable = $code >= 500 || $code === 0 || $code === 408;
                $last = [
                    'ok' => false,
                    'text' => '',
                    'error' => $detail,
                    'cooldown' => $quota,
                    'retry_model' => $invalidModel,
                ];
                if ($quota || $code === 401 || $code === 403) {
                    return $last;
                }
                if ($retryable && $attempts <= self::MAX_RETRIES) {
                    usleep(400000 * $attempts);
                    continue;
                }
                return $last;
            }

            $data = json_decode((string) $raw, true);
            $text = '';
            if (is_array($data)) {
                foreach (($data['candidates'] ?? []) as $candidate) {
                    foreach (($candidate['content']['parts'] ?? []) as $part) {
                        if (!empty($part['text'])) {
                            $text .= $part['text'];
                        }
                    }
                }
            }
            $text = trim($text);
            if ($text === '') {
                $last = [
                    'ok' => false,
                    'text' => '',
                    'error' => 'Empty Gemini response',
                    'cooldown' => false,
                    'retry_model' => false,
                ];
                if ($attempts <= self::MAX_RETRIES) {
                    usleep(300000 * $attempts);
                    continue;
                }
                return $last;
            }
            return ['ok' => true, 'text' => $text, 'error' => '', 'cooldown' => false, 'retry_model' => false];
        }
        return $last;
    }

    private static function isQuotaError(int $code, $raw): bool {
        if ($code === 429) {
            return true;
        }
        $blob = is_string($raw) ? strtolower($raw) : '';
        return str_contains($blob, 'resource_exhausted')
            || str_contains($blob, 'quota')
            || str_contains($blob, 'rate limit')
            || str_contains($blob, 'insufficient');
    }

    private static function errorDetail(int $code, $raw, string $curlErr = ''): string {
        $msg = 'HTTP ' . $code;
        if ($curlErr !== '') {
            $msg .= ' curl: ' . $curlErr;
        }
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($decoded)) {
            $err = $decoded['error']['message'] ?? $decoded['error'] ?? $decoded['message'] ?? null;
            if (is_array($err)) {
                $err = $err['message'] ?? json_encode($err);
            }
            if ($err) {
                $msg .= ' ' . substr((string) $err, 0, 240);
            }
        } elseif (is_string($raw) && $raw !== '') {
            $msg .= ' ' . substr(trim(strip_tags($raw)), 0, 180);
        }
        return $msg;
    }

    private static function keyId(string $key): string {
        return substr(hash('sha256', $key), 0, 12);
    }

    private static function stats(): array {
        $raw = Setting::get('gemini_key_stats', []);
        return is_array($raw) ? $raw : [];
    }

    private static function saveStats(array $stats): void {
        Setting::set('gemini_key_stats', $stats, 'ai');
    }

    private static function recordSuccess(string $key): void {
        $stats = self::stats();
        $id = self::keyId($key);
        $row = $stats[$id] ?? ['ok' => 0, 'fail' => 0];
        $row['ok'] = (int) ($row['ok'] ?? 0) + 1;
        $row['last_ok'] = date('Y-m-d H:i:s');
        $row['last_error'] = '';
        $row['cooldown_until'] = 0;
        $row['suffix'] = substr($key, -4);
        $stats[$id] = $row;
        self::saveStats($stats);
    }

    private static function recordFailure(string $key, string $error, bool $cooldown): void {
        $stats = self::stats();
        $id = self::keyId($key);
        $row = $stats[$id] ?? ['ok' => 0, 'fail' => 0];
        $row['fail'] = (int) ($row['fail'] ?? 0) + 1;
        $row['last_error'] = substr($error, 0, 240);
        $row['last_fail'] = date('Y-m-d H:i:s');
        $row['suffix'] = substr($key, -4);
        if ($cooldown) {
            $row['cooldown_until'] = time() + self::COOLDOWN_SECONDS;
        }
        $stats[$id] = $row;
        self::saveStats($stats);
    }
}
