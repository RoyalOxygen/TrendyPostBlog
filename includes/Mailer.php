<?php
if (!defined('TRENDYPOST')) {
    die('Direct access not permitted.');
}

if (!class_exists('Setting') && defined('INCLUDES_PATH')) {
    require_once INCLUDES_PATH . 'Models.php';
}

class Mailer
{
    public static function send(string $to, string $subject, string $html, string $toName = ''): bool
    {
        $to = trim($to);
        if ($to === '' || !is_valid_email($to)) {
            return false;
        }

        $fromEmail = (string) Setting::get('smtp_from_email', Setting::get('site_email', 'noreply@trendypost.com'));
        $fromName = (string) Setting::get('smtp_from_name', Setting::get('site_name', 'TrendyPost'));
        $host = trim((string) Setting::get('smtp_host', ''));
        $port = intval(Setting::get('smtp_port', 587));
        $username = (string) Setting::get('smtp_username', '');
        $password = (string) Setting::get('smtp_password', '');
        $encryption = strtolower((string) Setting::get('smtp_encryption', 'tls'));

        $body = self::wrap($subject, $html);
        $encodedSubject = self::encodeHeader($subject);
        $fromHeader = self::formatAddress($fromEmail, $fromName);
        $toHeader = self::formatAddress($to, $toName);

        if ($host !== '') {
            try {
                $ok = self::sendSmtp($host, $port, $username, $password, $encryption, $fromEmail, $fromHeader, $to, $toHeader, $encodedSubject, $body);
                if ($ok) {
                    return true;
                }
            } catch (Throwable $e) {
                error_log('SMTP send failed: ' . $e->getMessage());
            }
        }

        if (!function_exists('mail')) {
            error_log('Mail skipped: PHP mail() is not available. Configure SMTP under Settings.');
            return false;
        }

        $sendmailPath = trim((string) ini_get('sendmail_path'));
        $sendmailBin = $sendmailPath !== '' ? trim(explode(' ', $sendmailPath, 2)[0]) : '';
        if ($sendmailBin === '' || !is_executable($sendmailBin)) {
            error_log('Mail skipped: SMTP is not configured and sendmail is unavailable.');
            return false;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $fromHeader,
            'Reply-To: ' . $fromEmail,
            'X-Mailer: TrendyPost'
        ];

        return @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
    }

    public static function sendWelcome(array $user): bool
    {
        $site = Setting::get('site_name', 'TrendyPost');
        $name = $user['display_name'] ?? $user['username'] ?? 'there';
        $html = '<p>Hi ' . esc($name) . ',</p>'
            . '<p>Welcome to <strong>' . esc($site) . '</strong>. Your account is ready.</p>'
            . '<p><a href="' . esc(url('login')) . '" style="display:inline-block;background:#00C853;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;">Sign in</a></p>';
        return self::send($user['email'] ?? '', 'Welcome to ' . $site, $html, $name);
    }

    public static function sendPasswordReset(array $user, string $token): bool
    {
        $site = Setting::get('site_name', 'TrendyPost');
        $link = url('reset-password?token=' . urlencode($token));
        $name = $user['display_name'] ?? $user['username'] ?? '';
        $html = '<p>Hi ' . esc($name) . ',</p>'
            . '<p>We received a request to reset your password.</p>'
            . '<p><a href="' . esc($link) . '" style="display:inline-block;background:#00C853;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;">Reset password</a></p>'
            . '<p>This link expires in 1 hour. If you did not request this, you can ignore this email.</p>';
        return self::send($user['email'] ?? '', 'Reset your ' . $site . ' password', $html, $name);
    }

    public static function sendCommentNotice(array $comment, array $post): void
    {
        try {
            self::sendCommentNoticeInner($comment, $post);
        } catch (Throwable $e) {
            error_log('Comment notice failed: ' . $e->getMessage());
        }
    }

    private static function sendCommentNoticeInner(array $comment, array $post): void
    {
        if (!(string) Setting::get('notify_on_comment', '1')) {
            return;
        }
        $site = Setting::get('site_name', 'TrendyPost');
        $authorName = $comment['author_name'] ?? 'Someone';
        $postTitle = $post['title'] ?? 'a post';
        $postLink = !empty($post['slug']) ? post_url($post['slug']) : url();
        $html = '<p><strong>' . esc($authorName) . '</strong> commented on <strong>' . esc($postTitle) . '</strong>.</p>'
            . '<blockquote style="border-left:3px solid #00C853;padding-left:12px;color:#555;">' . nl2br(esc($comment['content'] ?? '')) . '</blockquote>'
            . '<p>Status: ' . esc($comment['status'] ?? 'pending') . '</p>'
            . '<p><a href="' . esc($postLink) . '">View post</a> &nbsp;|&nbsp; <a href="' . esc(admin_url('comments')) . '">Moderate comments</a></p>';

        $sent = [];
        foreach (self::staffEmails() as $email) {
            if (!in_array($email, $sent, true)) {
                self::send($email, '[' . $site . '] New comment on ' . $postTitle, $html);
                $sent[] = $email;
            }
        }

        if (!empty($post['author_email']) && is_valid_email($post['author_email']) && !in_array($post['author_email'], $sent, true)) {
            self::send($post['author_email'], '[' . $site . '] New comment on ' . $postTitle, $html, $post['author_name'] ?? '');
        }
    }

    public static function sendPublishNotice(array $post, array $publisher = []): void
    {
        try {
            self::sendPublishNoticeInner($post, $publisher);
        } catch (Throwable $e) {
            error_log('Publish notice failed: ' . $e->getMessage());
        }
    }

    private static function sendPublishNoticeInner(array $post, array $publisher = []): void
    {
        if (!(string) Setting::get('notify_on_publish', '1')) {
            return;
        }
        $site = Setting::get('site_name', 'TrendyPost');
        $title = decode_entities((string) ($post['title'] ?? 'Untitled'));
        $by = $publisher['display_name'] ?? $publisher['username'] ?? 'An editor';
        $link = !empty($post['slug']) ? post_url($post['slug']) : admin_url('posts');
        $html = '<p><strong>' . esc($by) . '</strong> published a post.</p>'
            . '<p style="font-size:18px;margin:12px 0;"><a href="' . esc($link) . '">' . esc($title) . '</a></p>'
            . '<p><a href="' . esc(admin_url('posts')) . '">Open posts admin</a></p>';

        foreach (self::superAdminEmails() as $email) {
            self::send($email, '[' . $site . '] Post published: ' . $title, $html);
        }
    }

    public static function sendNewsletter(array $newsletter, string $email, string $unsubToken = ''): bool
    {
        $html = $newsletter['content'] ?? '';
        $unsub = $unsubToken !== '' ? url('newsletter/unsubscribe?token=' . urlencode($unsubToken)) : url('newsletter/unsubscribe?email=' . urlencode($email));
        $html .= '<p style="font-size:12px;color:#777;margin-top:24px;">You received this because you subscribed to ' . esc(Setting::get('site_name', 'TrendyPost')) . '. <a href="' . esc($unsub) . '">Unsubscribe</a></p>';
        return self::send($email, $newsletter['subject'] ?? 'Newsletter', $html);
    }

    public static function sendContactNotice(array $message): void
    {
        try {
            self::sendContactNoticeInner($message);
        } catch (Throwable $e) {
            error_log('Contact notice failed: ' . $e->getMessage());
        }
    }

    private static function sendContactNoticeInner(array $message): void
    {
        if (!(string) Setting::get('notify_on_contact', '1')) {
            return;
        }
        $site = Setting::get('site_name', 'TrendyPost');
        $html = '<p>New contact form message from <strong>' . esc($message['name'] ?? '') . '</strong> &lt;' . esc($message['email'] ?? '') . '&gt;.</p>'
            . '<p><strong>Subject:</strong> ' . esc($message['subject'] ?: '(No subject)') . '</p>'
            . '<blockquote style="border-left:3px solid #00C853;padding-left:12px;color:#555;">' . nl2br(esc($message['message'] ?? '')) . '</blockquote>'
            . '<p><a href="' . esc(admin_url('messages')) . '">Open inbox</a></p>';
        $sent = [];
        foreach (self::staffEmails() as $email) {
            if (!in_array($email, $sent, true)) {
                self::send($email, '[' . $site . '] Contact: ' . ($message['subject'] ?: $message['name'] ?? 'New message'), $html);
                $sent[] = $email;
            }
        }
        $fallback = Setting::get('site_email', '');
        if (is_valid_email((string) $fallback) && !in_array($fallback, $sent, true)) {
            self::send($fallback, '[' . $site . '] Contact: ' . ($message['subject'] ?: $message['name'] ?? 'New message'), $html);
        }
    }

    public static function superAdminEmails(): array
    {
        $rows = Database::fetchAll(
            "SELECT u.email FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'super_admin' AND u.status = 'active' AND u.email IS NOT NULL AND u.email != ''"
        );
        $emails = array_values(array_filter(array_map(static fn($r) => $r['email'] ?? '', $rows), 'is_valid_email'));
        if (empty($emails)) {
            $fallback = Setting::get('site_email', '');
            if (is_valid_email((string) $fallback)) {
                $emails[] = $fallback;
            }
        }
        return array_values(array_unique($emails));
    }

    public static function staffEmails(): array
    {
        $rows = Database::fetchAll(
            "SELECT u.email FROM users u JOIN roles r ON u.role_id = r.id
             WHERE r.name IN ('super_admin','admin','editor') AND u.status = 'active' AND u.email IS NOT NULL AND u.email != ''"
        );
        return array_values(array_unique(array_filter(array_map(static fn($r) => $r['email'] ?? '', $rows), 'is_valid_email')));
    }

    private static function wrap(string $subject, string $html): string
    {
        $site = esc(Setting::get('site_name', 'TrendyPost'));
        $home = esc(url());
        return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f4f5f7;font-family:Inter,Arial,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;"><tr><td align="center">'
            . '<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:10px;overflow:hidden;">'
            . '<tr><td style="background:#00C853;color:#fff;padding:18px 24px;font-size:18px;font-weight:700;">' . $site . '</td></tr>'
            . '<tr><td style="padding:24px;color:#333;font-size:15px;line-height:1.6;">' . $html . '</td></tr>'
            . '<tr><td style="padding:16px 24px;color:#888;font-size:12px;border-top:1px solid #eee;">This email was sent by <a href="' . $home . '" style="color:#00C853;">' . $site . '</a>.</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    private static function encodeHeader(string $text): string
    {
        if (preg_match('/[^\x20-\x7E]/', $text)) {
            return '=?UTF-8?B?' . base64_encode($text) . '?=';
        }
        return $text;
    }

    private static function formatAddress(string $email, string $name = ''): string
    {
        if ($name === '') {
            return $email;
        }
        return '"' . addcslashes($name, '"\\') . '" <' . $email . '>';
    }

    private static function sendSmtp(
        string $host,
        int $port,
        string $username,
        string $password,
        string $encryption,
        string $fromEmail,
        string $fromHeader,
        string $to,
        string $toHeader,
        string $subject,
        string $body
    ): bool {
        $remote = ($encryption === 'ssl' || $port === 465) ? 'ssl://' . $host : $host;
        if ($port <= 0) {
            $port = ($encryption === 'ssl') ? 465 : 587;
        }

        $fp = @stream_socket_client($remote . ':' . $port, $errno, $errstr, 12, STREAM_CLIENT_CONNECT);
        if (!$fp) {
            error_log('SMTP connect failed: ' . $errstr);
            return false;
        }
        stream_set_timeout($fp, 12);

        $read = static function () use ($fp): string {
            $data = '';
            while ($line = fgets($fp, 515)) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };
        $cmd = static function (string $command) use ($fp, $read): string {
            fwrite($fp, $command . "\r\n");
            return $read();
        };

        $greet = $read();
        if (strpos($greet, '220') !== 0) {
            fclose($fp);
            return false;
        }

        $ehloHost = preg_replace('/^www\./', '', parse_url(BASE_URL, PHP_URL_HOST) ?: 'localhost');
        $resp = $cmd('EHLO ' . $ehloHost);
        if (strpos($resp, '250') !== 0) {
            $resp = $cmd('HELO ' . $ehloHost);
            if (strpos($resp, '250') !== 0) {
                fclose($fp);
                return false;
            }
        }

        if ($encryption === 'tls' || $encryption === 'starttls') {
            $resp = $cmd('STARTTLS');
            if (strpos($resp, '220') === 0) {
                $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $crypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                    if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                        $crypto |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                    }
                }
                if (!@stream_socket_enable_crypto($fp, true, $crypto)) {
                    fclose($fp);
                    return false;
                }
                $cmd('EHLO ' . $ehloHost);
            }
        }

        if ($username !== '') {
            $resp = $cmd('AUTH LOGIN');
            if (strpos($resp, '334') !== 0) {
                fclose($fp);
                return false;
            }
            $resp = $cmd(base64_encode($username));
            if (strpos($resp, '334') !== 0) {
                fclose($fp);
                return false;
            }
            $resp = $cmd(base64_encode($password));
            if (strpos($resp, '235') !== 0) {
                fclose($fp);
                return false;
            }
        }

        $resp = $cmd('MAIL FROM:<' . $fromEmail . '>');
        if (strpos($resp, '250') !== 0) {
            fclose($fp);
            return false;
        }
        $resp = $cmd('RCPT TO:<' . $to . '>');
        if (strpos($resp, '250') !== 0 && strpos($resp, '251') !== 0) {
            fclose($fp);
            return false;
        }
        $resp = $cmd('DATA');
        if (strpos($resp, '354') !== 0) {
            fclose($fp);
            return false;
        }

        $payload = 'From: ' . $fromHeader . "\r\n"
            . 'To: ' . $toHeader . "\r\n"
            . 'Subject: ' . $subject . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "\r\n"
            . str_replace("\n.", "\n..", $body)
            . "\r\n.";
        $resp = $cmd($payload);
        $cmd('QUIT');
        fclose($fp);
        return strpos($resp, '250') === 0;
    }
}
