# TRENDYPOST - Installation Guide

## System Requirements

- PHP 8.0 or higher (`pdo_mysql`, `mbstring`, `openssl`, `json`, `curl`; `gd` recommended)
- MySQL 8.0+ or MariaDB 10.5+
- Apache with `mod_rewrite`, Nginx, or PHP's built-in server
- Writable `config/`, `assets/uploads/`, `cache/`, and `logs/`

phpMyAdmin is not required. The frontend installer writes the database config and lock file for you.

## Install from the browser

1. Point the web root at this project.
2. Open the site. You are redirected to `/install/` until setup finishes.
3. Complete the wizard:
   - Requirements check
   - Database host, name, user, password (the database is created if missing)
   - If the database already has tables: **Keep existing data** (default) or **Erase everything**
   - Site name, tagline, email, and superadmin account
   - Confirm and install
4. Sign in at `/admin/` and change the password if you used a default account.

Keep existing data unless you explicitly choose erase. Preserve mode applies `database/analytics-migration.sql`, `database/indexing-migration.sql`, and `database/ai-sources.sql` without dropping posts or users.

The installer writes:

- `config/local.php` — database credentials and generated keys (not committed)
- `config/installed.lock` — marks the site as installed

You do not need to edit `config/config.php`.

## Local development

```bash
php -S 0.0.0.0:8000 router.php
```

Open `http://localhost:8000/install/` if the lock file is not present.

## Optional: environment variables

If you prefer not to use the installer, set:

```bash
export DB_HOST=127.0.0.1
export DB_NAME=trendypost
export DB_USER=trendypost
export DB_PASS=your-db-password
export TRENDYPOST_BASE_URL=https://www.yourdomain.com
```

Then create `config/installed.lock` after importing `database/master_db.sql`.

## Folder permissions

```bash
chmod 755 assets/uploads cache logs config
```

## Post-installation

1. Change the superadmin password
2. Admin > Settings: logo, favicon, colors, and remaining site options
3. Admin > AI Sources: add Gemini API keys if you want publish-time rewrite
4. Set `APP_ENV=production` on a live host
5. Enable HTTPS

## Default seeded account (fresh schema only)

| Role | Username | Email | Password |
|------|----------|-------|----------|
| Super Admin | admin | admin@trendypost.com | Admin@123 |

This account is created only on a wipe or empty database. Change it immediately.

## Troubleshooting

### Redirected to /install/
`config/installed.lock` is missing. Finish the wizard, or create the lock after a manual import.

### Database connection failed
Check host, database name, user, and password. Confirm MySQL is running and the user can create the database.

### 404 on all pages
Enable `mod_rewrite` (Apache) or use `php -S 0.0.0.0:8000 router.php`.

### CSS/JS not loading
Confirm the site URL in the browser matches the auto-detected `BASE_URL`, or set `TRENDYPOST_BASE_URL`.

---
TrendyPost v1.0.0 - Enterprise SEO Blogging Platform
