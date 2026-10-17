# TRENDYPOST - Enterprise SEO Blogging & Digital Publishing Platform

A complete, SEO-first digital publishing platform built with PHP 8+, MySQL 8, Bootstrap 5.3+, and vanilla JavaScript.

## Highlights

- **Every public URL is individually indexable**: posts, pages, categories, tags, authors and archives each emit their own title, description, canonical, Open Graph, Twitter Card and structured data.
- **Images are submitted with their article**: an image sitemap plus `ImageObject` schema ties every featured and in-content image to the post title, so Google Images can index them against the article.
- **Related articles are surfaced in schema**: each post emits an `ItemList` of related posts, homepage and archives emit `ItemList` of their listings.
- **Automatic indexing requests**: publishing a post or page queues the URL and pushes it to IndexNow (Bing/Yandex/Seznam) and, when configured, the Google Indexing API. Failures retry with exponential backoff from a queue.
- **Real traffic analytics**: first-party, cookie-based unique visits. Admin, staff and bots are excluded; the home page has its own counter; per-post views dedupe per visitor per day.
- **Opt-in AI rewrite at publish** using Gemini. Ingest only extracts drafts; nothing is rewritten or published until you tick "Use AI rewrite" and publish.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2+ (native, no framework) |
| Database | MySQL 8 / MariaDB 10.6+ with PDO |
| Frontend | Bootstrap 5.3+ |
| JavaScript | Vanilla ES6+ |
| Charts | Chart.js |
| Icons | Bootstrap Icons |

## Requirements

- PHP 8.2 or newer with `pdo_mysql`, `curl`, `openssl`, `mbstring`, `gd`
- MySQL 8.0+ or MariaDB 10.6+
- Apache with `mod_rewrite`, or Nginx, or PHP's built-in server for local development
- HTTPS in production (required by the Google Indexing API and strongly recommended for canonical URLs)

## Installation

1. **Create the database and import the schema**

   ```bash
   mysql -u root -p -e "CREATE DATABASE trendypost CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p trendypost < database/master_db.sql
   ```

   `database/master_db.sql` is the full schema with seed data (32 tables, default categories, pages, and the super admin account).

2. **Configure environment variables** (or edit `config/config.php`):

   ```bash
   export DB_HOST=127.0.0.1
   export DB_NAME=trendypost
   export DB_USER=trendypost
   export DB_PASS=your-db-password
   export TRENDYPOST_BASE_URL=https://www.trendypost.com.ng
   ```

3. **Point the web root at the project directory** and confirm `uploads/` and `cache/` are writable.

4. **Apply migrations on an existing install**

   ```bash
   mysql -u trendypost -p trendypost < database/analytics-migration.sql
   mysql -u trendypost -p trendypost < database/indexing-migration.sql
   ```

5. **Log in** at `/admin` with the seeded account and change the password immediately:

   - Email: `admin@trendypost.com`
   - Password: `Admin@123`

## Local Development

```bash
# Built-in server with the router (required for clean URLs)
php -S 0.0.0.0:8000 router.php

# Apply the indexing queue migration to your local DB once
mysql -u trendypost -p trendypost < database/indexing-migration.sql
```

## SEO Setup (complete procedure)

The platform works out of the box, but full indexing requires the steps below. Do them once per deployment.

### 1. Set the canonical domain

`TRENDYPOST_BASE_URL` must be the exact production origin, including `https://` and the preferred `www` (or non-`www`) form. Every canonical, Open Graph URL, sitemap entry and IndexNow payload is derived from it. Never mix both host forms.

### 2. Verify in Google Search Console

1. Add both the `www` and non-`www` properties (or a Domain property).
2. Copy the verification token into **Admin > SEO > Google Verification Code**.
3. The meta tag is rendered in `<head>` on every public page.
4. Submit `https://your-domain/sitemap.xml` under **Sitemaps**.

### 3. Verify in Bing Webmaster Tools

1. Add the site and copy the verification token into **Admin > SEO > Bing Verification Code**.
2. Submit the same sitemap index.

### 4. Enable automatic indexing

**Admin > SEO > Automatic Google indexing**:

- Keep **Ping Google/Bing sitemaps and IndexNow whenever a post or page is published** checked.
- **IndexNow** works without extra configuration. The key is derived from `ENCRYPTION_KEY` and served at `/{key}.txt` through `pages/indexnow-key.php`. Bing, Yandex and Seznam accept it immediately.
- **Google Indexing API (optional)** requires a service account:
  1. Create a Google Cloud project and enable the **Indexing API**.
  2. Create a service account, download the JSON key.
  3. In Search Console, add the service account email as an **Owner** of the property.
  4. Paste the whole JSON into **Google Indexing API service-account JSON** and save.

Publishing then queues the URL in `indexing_queue` without slowing the editor (no remote call during save). The queue worker `cron/indexing.php` drains it every 10 minutes and retries failures with backoff (5, 15, 45, 135 minutes; up to 5 attempts). Use **Admin > SEO > Indexing queue** to see pending/failed counts, run the queue immediately, or re-submit every published URL after a migration or domain change.

### 5. Schedule the cron jobs

```bash
# Ingest new drafts from configured sources and prune old analytics (every minute)
* * * * * php /path/to/trendypost/cron/ai-ingest.php >/dev/null 2>&1

# Drain the search indexing queue and ping sitemaps (every 10 minutes)
*/10 * * * * php /path/to/trendypost/cron/indexing.php >/dev/null 2>&1
```

### 6. What is indexable

| URL | Robots | Schema |
|-----|--------|--------|
| `/` | `index,follow` | `WebSite`, `Organization`, `CollectionPage`, `ItemList` |
| `/post/{slug}` | `index,follow` (per-post override available) | `NewsArticle`, `BreadcrumbList`, `ItemList` (related) |
| `/page/{slug}` | `index,follow` | `WebPage`, `BreadcrumbList` |
| `/category/{slug}`, `/tag/{slug}`, `/author/{username}` | `index,follow` | `CollectionPage`, `BreadcrumbList`, `ItemList` |
| `/categories`, `/tags`, `/authors`, `/archive/{year}[/{month}]` | `index,follow` | `CollectionPage`, `BreadcrumbList`, `ItemList` |
| `/search?q=...` | `noindex,follow` | `SearchResultsPage` |
| `/preview/{id}`, `/login`, `/register`, `/newsletter/*` | `noindex,nofollow` | none |

`robots.txt` blocks `/admin/`, `/api/`, `/preview/`, `/newsletter/`, `/install/` and the auth routes, and always allows public content and the sitemaps. Never `Disallow: /post` — posts must stay crawlable.

### 7. Sitemaps

| Sitemap | Contents |
|---------|----------|
| `/sitemap.xml` | Index of all sitemaps below |
| `/sitemap-posts.xml` | Posts with `<image:image>` for featured and in-content images |
| `/sitemap-images.xml` | Every post image with `<image:title>` set to the post title |
| `/sitemap-news.xml` | Google News sitemap (posts from the last 2 days, with images) |
| `/sitemap-categories.xml` | Categories |
| `/sitemap-tags.xml` | Tags |
| `/sitemap-authors.xml` | Authors |
| `/sitemap-pages.xml` | Static pages |

Each post URL includes up to five images; the image sitemap includes up to ten. Images are only listed when the file exists on disk, so broken paths never reach Google.

### 8. Analytics and GA4

- First-party analytics run automatically using a hashed `tp_visitor` cookie; bots, admins and signed-in staff are excluded. GA4/GTM are optional and only load when configured, so add a consent banner if your jurisdiction requires one.
- **Admin > Analytics** shows homepage views, per-post unique views, today's unique visitors and traffic sources.
- For GA4, paste the measurement ID in **Admin > SEO > Google Analytics 4 ID** (`G-XXXXXXXXXX`); for Tag Manager use **Google Tag Manager** (`GTM-XXXXXX`). Both load on every public page.
- Old analytics rows are pruned after 180 days by `cron/ai-ingest.php`.

### 9. Post-level SEO fields

Each post supports `seo_title`, `seo_description`, `seo_keywords`, `meta_robots`, `canonical_url`, Open Graph and Twitter overrides, `schema_type`, `featured_image_alt` and `featured_image_caption`. Empty fields fall back to the generated values, so you never publish an empty title or description.

### 10. Verification checklist

After deploying:

```bash
# Title, description, canonical, robots, OG, Twitter and JSON-LD on a post
curl -s https://your-domain/post/your-slug | grep -E 'canonical|og:image|twitter:image|application/ld\+json|<title>'

# Sitemap index and image sitemap
curl -s https://your-domain/sitemap.xml
curl -s https://your-domain/sitemap-images.xml | head -20

# Robots
curl -s https://your-domain/robots.txt
```

Then:

- Run the [Rich Results Test](https://search.google.com/test/rich-results) on a post URL.
- Confirm Search Console reports the URL as "URL is on Google" after the queue runs.
- Use **URL Inspection > Request indexing** for the first few posts; subsequent posts are requested automatically.

## AI Sources and Gemini

Admins add source websites under **Admin > AI Sources**. A cron job visits each source on the cadence you set, extracts recent articles and saves them as **drafts**. Extraction never calls an LLM: drafts contain the extracted title, excerpt, content and image.

When you publish a draft with **Use AI rewrite** ticked, the post is rewritten once by Gemini. If the rewrite fails (missing key, quota, network), the post is still saved and published as-is and an error is shown, so publishing is never blocked.

Configure Gemini in **Admin > AI Sources > Gemini API keys**. The client rotates through multiple keys and retries on quota errors. No key is read from the server environment and no key is ever written into the site's HTML.

## Ad Management

Multiple ad networks (AdSense, Adsterra, Monetag, Custom), placements (header, footer, sidebar, sticky, in-content, floating), device targeting, scheduling, and impression/click tracking with CTR reporting.

## Content and Users

- Posts with revisions, scheduling, featured images and per-post SEO.
- Categories, tags, pages and a media library.
- Comments with moderation and spam controls.
- Newsletter subscribers with double opt-in tokens.
- Six roles (Super Admin, Admin, Editor, Author, Contributor, Subscriber) with RBAC.
- Audit log of admin actions.

## License

Commercial Software - All Rights Reserved

Copyright 2026 TrendyPost
