# TRENDYPOST - Installation Guide

## System Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher (or MariaDB 10.5+)
- Apache with mod_rewrite enabled
- XAMPP / WAMP / MAMP recommended for Windows

## Installation Steps

### Step 1: Create Database

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `trendypost`
3. Select utf8mb4_unicode_ci collation

### Step 2: Import Database Schema

1. Open phpMyAdmin
2. Select the `trendypost` database
3. Go to Import tab
4. Choose the file: `database/schema.sql`
5. Click Go/Import

### Step 3: Configure Application

1. Copy the project files to your web root:
   - XAMPP: `C:\xampp\htdocs\trendypost\`
   - WAMP: `C:\wamp64\www\trendypost\`

2. Edit `config/config.php` to set your BASE_URL: 

```php
// For localhost
define('BASE_URL', 'http://localhost/trendypost/');

// For production
define('BASE_URL', 'https://www.yourdomain.com/');
```

3. Set database credentials in `config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'trendypost');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Step 4: Enable mod_rewrite (Apache)

Ensure the `.htaccess` file is present in the root directory.

For XAMPP, make sure in `httpd.conf`:
```
LoadModule rewrite_module modules/mod_rewrite.so
```
is uncommented, and for your document root:
```
<Directory />
    AllowOverride All
</Directory>
```

### Step 5: Set Folder Permissions

Ensure these directories are writable:
```
assets/uploads/  (chmod 755)
cache/           (chmod 755)
logs/            (chmod 755)
```

### Step 6: Access the Application

1. Frontend: `http://localhost/trendypost/`
2. Admin: `http://localhost/trendypost/admin/`
3. Login with:
   - Email: `admin@trendypost.com`
   - Password: `Admin@123`

### Step 7: Post-Installation Security

1. **Change the default admin password immediately**
2. Update the encryption keys in `config/config.php`
3. Set `APP_ENV` to `'production'` when going live
4. Configure your BASE_URL for production
5. Set up SSL/HTTPS
6. Update robots.txt and sitemap settings

## Default User Accounts

| Role | Username | Password |
|------|----------|----------|
| Super Admin | admin | Admin@123 |

**IMPORTANT: Change the default password after first login!**

## File Structure

```
trendypost/
├── admin/              # Admin Panel
│   ├── dashboard/      # Dashboard
│   ├── posts/          # Post Management
│   ├── categories/     # Category Management
│   ├── tags/           # Tag Management
│   ├── pages/          # Page Management
│   ├── comments/       # Comment Moderation
│   ├── users/          # User Management
│   ├── ads/            # Ad Management
│   ├── seo/            # SEO Settings
│   ├── analytics/      # Analytics
│   ├── newsletters/    # Newsletter System
│   ├── menus/          # Menu Management
│   ├── media/          # Media Library
│   ├── settings/       # Site Settings
│   ├── layout/         # Admin Layout (header/footer)
│   └── assets/         # Admin CSS/JS
├── api/                # API Endpoints
├── assets/             # Frontend Assets
│   ├── css/            # Theme Stylesheets
│   ├── js/             # Theme JavaScript
│   ├── images/         # Theme Images
│   └── uploads/        # User Uploads
├── config/             # Configuration
├── database/           # Database Schema
├── includes/           # Core Classes
│   ├── Database.php    # PDO Database Handler
│   ├── Auth.php        # Authentication & RBAC
│   ├── Helpers.php     # Helper Functions
│   ├── Models.php      # Data Models
│   ├── Header.php      # Theme Header
│   └── Footer.php      # Theme Footer
├── pages/              # Frontend Pages
│   ├── auth/           # Login/Register
│   ├── errors/         # Error Pages
│   └── newsletter/     # Newsletter Pages
├── .htaccess           # Apache Rewrite Rules
├── index.php           # Front Controller
└── README.md           # This file
```

## Troubleshooting

### 404 Errors on All Pages
- Ensure mod_rewrite is enabled
- Check that `.htaccess` file exists
- Verify AllowOverride All is set in Apache config

### Database Connection Failed
- Check DB_HOST, DB_NAME, DB_USER, DB_PASS in config.php
- Ensure MySQL is running
- Verify the database exists

### CSS/JS Not Loading
- Check BASE_URL is set correctly
- Ensure file permissions are correct

### Admin Login Issues
- Clear browser cookies and cache
- Verify the users table has the default admin account
- Check PHP session configuration

## Support

For support, visit: https://trendypost.com/support

---
TrendyPost v1.0.0 - Enterprise SEO Blogging Platform
