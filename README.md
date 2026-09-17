# TRENDYPOST - Enterprise SEO Blogging & Digital Publishing Platform

A complete, enterprise-grade SEO-first digital publishing platform built with PHP 8+, MySQL 8, Bootstrap 5.3+, and vanilla JavaScript.

## Features

### SEO Engine
- XML Sitemap Generator (posts, categories, tags, authors, pages)
- Dynamic Sitemap Index with auto-ping
- Schema.org markup (Article, NewsArticle, BlogPosting, BreadcrumbList, Organization, WebSite)
- Open Graph tags and Twitter Cards
- Custom meta titles, descriptions, keywords per article
- Canonical URLs
- robots.txt management
- Google Analytics 4 integration
- Google Tag Manager support
- Google Search Console verification

### Ad Management System
- Multiple ad networks (AdSense, Adsterra, Monetag, Custom)
- Flexible ad placements (header, footer, sidebar, sticky, in-content, floating)
- Device targeting (desktop, mobile, tablet)
- Ad scheduling with start/end dates
- Impression and click tracking
- CTR analytics

### Content Management
- Post management with rich SEO fields
- Category and tag management
- Page management
- Media library with upload support
- Comment system with moderation
- Newsletter system with subscriber management

### User Management
- 6 user roles (Super Admin, Admin, Editor, Author, Contributor, Subscriber)
- Role-based access control (RBAC)
- User profile management
- Password reset functionality

### Analytics
- Page view tracking
- Unique visitor counts
- Popular posts
- Device type breakdown
- 7/30/90 day trend charts

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8+ (native, no framework) |
| Database | MySQL 8+ with PDO |
| Frontend | Bootstrap 5.3+ |
| JavaScript | Vanilla ES6+ (no jQuery) |
| AJAX | Fetch API |
| Charts | Chart.js |
| Icons | Bootstrap Icons |

## Installation

See [INSTALL.md](INSTALL.md) for detailed installation instructions.

## Quick Start

1. Import `database/schema.sql` into MySQL
2. Copy files to web root
3. Configure `config/config.php`
4. Access at `http://localhost/trendypost/`
5. Login: admin@trendypost.com / Admin@123

## License

Commercial Software - All Rights Reserved

Copyright 2026 TrendyPost
