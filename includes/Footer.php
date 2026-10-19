<?php
/**
 * TRENDYPOST - Theme Footer
 * =========================
 * Frontend footer matching the reference UI design.
 */

if (!defined('TRENDYPOST')) die('Direct access not permitted.');

$primaryColor = is_hex_color((string) Setting::get('primary_color', '#00C853')) ? Setting::get('primary_color', '#00C853') : '#00C853';
$social = Setting::getGroup('social');
$footerAbout = Setting::get('footer_about', '');
$footerCopyright = Setting::get('footer_copyright', '&copy; 2026 TrendyPost. All rights reserved.');
$siteName = Setting::get('site_name', 'TrendyPost');
$footerLogo = site_asset_url(Setting::get('site_logo_footer', '') ?: Setting::get('site_logo', 'assets/images/TrendyPost_Logo_Text_White.png'), image_url('TrendyPost_Logo_Text_White.png'));
?>

    <!-- Footer Ad -->
    <?php
    $footerAds = get_ads('footer_banner', 1);
    if (empty($footerAds)) {
        $footerAds = get_ads('mobile_footer', 1);
    }
    if (!empty($footerAds)): ?>
    <div class="container mb-3">
        <div class="text-center">
            <?php echo render_ad($footerAds[0]); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Newsletter Section -->
    <section class="newsletter-section py-5" style="background-color:<?php echo $primaryColor; ?>;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-white">
                    <h3 class="mb-2"><i class="bi bi-envelope-paper me-2"></i>Subscribe to Our Newsletter</h3>
                    <p class="mb-0 text-white-75">Get the latest news and updates delivered to your inbox.</p>
                </div>
                <div class="col-md-6">
                    <form id="newsletterForm" class="d-flex gap-2">
                        <input type="email" id="newsletterEmail" class="form-control" placeholder="Enter your email address" required>
                        <button type="submit" class="btn btn-dark">Subscribe</button>
                    </form>
                    <div id="newsletterMsg" class="mt-2"></div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer bg-dark text-white pt-5 pb-3">
        <div class="container">
            <div class="row">
                <!-- About Column -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="footer-title mb-3">
                        <img src="<?php echo esc_attr($footerLogo); ?>" alt="<?php echo esc_attr($siteName); ?>" height="28" class="mb-2" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                        <span style="display:none"><?php echo $siteName; ?></span>
                    </h5>
                    <p class="text-white-50" style="font-size: 14px;"><?php echo $footerAbout; ?></p>
                    <div class="social-links mt-3">
                        <?php if (!empty($social['social_facebook'])): ?>
                        <a href="<?php echo $social['social_facebook']; ?>" class="btn btn-outline-light btn-sm rounded-circle me-2" target="_blank" rel="noopener" style="width:32px;height:32px;padding:0;line-height:32px;font-size:14px;"><i class="bi bi-facebook"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_twitter'])): ?>
                        <a href="<?php echo $social['social_twitter']; ?>" class="btn btn-outline-light btn-sm rounded-circle me-2" target="_blank" rel="noopener" style="width:32px;height:32px;padding:0;line-height:32px;font-size:14px;"><i class="bi bi-twitter-x"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_instagram'])): ?>
                        <a href="<?php echo $social['social_instagram']; ?>" class="btn btn-outline-light btn-sm rounded-circle me-2" target="_blank" rel="noopener" style="width:32px;height:32px;padding:0;line-height:32px;font-size:14px;"><i class="bi bi-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_youtube'])): ?>
                        <a href="<?php echo $social['social_youtube']; ?>" class="btn btn-outline-light btn-sm rounded-circle me-2" target="_blank" rel="noopener" style="width:32px;height:32px;padding:0;line-height:32px;font-size:14px;"><i class="bi bi-youtube"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_pinterest'])): ?>
                        <a href="<?php echo $social['social_pinterest']; ?>" class="btn btn-outline-light btn-sm rounded-circle me-2" target="_blank" rel="noopener" style="width:32px;height:32px;padding:0;line-height:32px;font-size:14px;"><i class="bi bi-pinterest"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_tiktok'])): ?>
                        <a href="<?php echo $social['social_tiktok']; ?>" class="btn btn-outline-light btn-sm rounded-circle me-2" target="_blank" rel="noopener" style="width:32px;height:32px;padding:0;line-height:32px;font-size:14px;"><i class="bi bi-tiktok"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="footer-title mb-3 text-uppercase" style="font-size: 12px; letter-spacing: 1px; opacity: 0.7;">Quick Links</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="<?php echo url(); ?>" class="text-white-50 text-decoration-none" style="font-size: 14px;">Home</a></li>
                        <li><a href="<?php echo url('archive'); ?>" class="text-white-50 text-decoration-none" style="font-size: 14px;">Archive</a></li>
                        <li><a href="<?php echo url('categories'); ?>" class="text-white-50 text-decoration-none" style="font-size: 14px;">Categories</a></li>
                        <li><a href="<?php echo url('tags'); ?>" class="text-white-50 text-decoration-none" style="font-size: 14px;">Tags</a></li>
                        <li><a href="<?php echo url('authors'); ?>" class="text-white-50 text-decoration-none" style="font-size: 14px;">Authors</a></li>
                        <li><a href="<?php echo url('contact'); ?>" class="text-white-50 text-decoration-none" style="font-size: 14px;">Contact</a></li>
                    </ul>
                </div>
                
                <!-- Categories -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="footer-title mb-3 text-uppercase" style="font-size: 12px; letter-spacing: 1px; opacity: 0.7;">Categories</h6>
                    <ul class="list-unstyled footer-links">
                        <?php foreach (Category::getAll() as $cat): ?>
                        <li><a href="<?php echo category_url($cat['slug']); ?>" class="text-white-50 text-decoration-none" style="font-size: 14px;"><?php echo $cat['name']; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Resources -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="footer-title mb-3 text-uppercase" style="font-size: 12px; letter-spacing: 1px; opacity: 0.7;">Resources</h6>
                    <ul class="list-unstyled footer-links">
                        <?php foreach (Page::getMenuPages() as $page): ?>
                        <li><a href="<?php echo page_url($page['slug']); ?>" class="text-white-50 text-decoration-none" style="font-size: 14px;"><?php echo $page['title']; ?></a></li>
                        <?php endforeach; ?>
                        <li><a href="<?php echo url('page/privacy-policy'); ?>" class="text-white-50 text-decoration-none" style="font-size: 14px;">Privacy Policy</a></li>
                        <li><a href="<?php echo url('page/terms-of-service'); ?>" class="text-white-50 text-decoration-none" style="font-size: 14px;">Terms of Service</a></li>
                    </ul>
                </div>
                
                <!-- Instagram/Social Feed -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="footer-title mb-3 text-uppercase" style="font-size: 12px; letter-spacing: 1px; opacity: 0.7;">Instagram Feed</h6>
                    <div class="instagram-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px;">
                        <?php 
                        $popular = Post::getPopular(6);
                        foreach ($popular as $pop):
                            $img = media_url($pop['featured_image']);
                        ?>
                        <a href="<?php echo post_url($pop['slug']); ?>" class="insta-item" style="display:block;aspect-ratio:1;overflow:hidden;border-radius:4px;">
                            <img src="<?php echo $img; ?>" alt="<?php echo esc_attr($pop['title']); ?>" class="img-fluid" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <hr class="border-secondary my-4" style="opacity: 0.2;">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-white-50 small" style="font-size: 13px;"><?php echo $footerCopyright; ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="social-links">
                        <?php if (!empty($social['social_facebook'])): ?>
                        <a href="<?php echo $social['social_facebook']; ?>" class="text-white-50 me-3" target="_blank" rel="noopener" style="font-size: 16px;"><i class="bi bi-facebook"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_twitter'])): ?>
                        <a href="<?php echo $social['social_twitter']; ?>" class="text-white-50 me-3" target="_blank" rel="noopener" style="font-size: 16px;"><i class="bi bi-twitter-x"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_instagram'])): ?>
                        <a href="<?php echo $social['social_instagram']; ?>" class="text-white-50 me-3" target="_blank" rel="noopener" style="font-size: 16px;"><i class="bi bi-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_linkedin'])): ?>
                        <a href="<?php echo $social['social_linkedin']; ?>" class="text-white-50" target="_blank" rel="noopener" style="font-size: 16px;"><i class="bi bi-linkedin"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scroll to Top -->
    <button id="scrollTop" class="btn btn-success scroll-top" style="background-color:<?php echo $primaryColor; ?>;border-color:<?php echo $primaryColor; ?>;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;position:fixed;bottom:30px;right:30px;z-index:1000;opacity:0;visibility:hidden;transition:all 0.3s;box-shadow:0 2px 10px rgba(0,0,0,0.2);">
        <i class="bi bi-arrow-up" style="font-size:20px;"></i>
    </button>
    
    <script>
    // Scroll to Top Button
    document.addEventListener('DOMContentLoaded', function() {
        const scrollBtn = document.getElementById('scrollTop');
        if (scrollBtn) {
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    scrollBtn.style.opacity = '1';
                    scrollBtn.style.visibility = 'visible';
                } else {
                    scrollBtn.style.opacity = '0';
                    scrollBtn.style.visibility = 'hidden';
                }
            });
            
            scrollBtn.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    });
    </script>
    
    <?php foreach (array_merge(get_ads_by_location('popunder', 2), get_ads_by_location('vignette', 2)) as $scriptAd): ?>
        <?php echo render_ad($scriptAd); ?>
    <?php endforeach; ?>

    <!-- Sticky/Floating Ad -->
    <?php $floatingAds = get_ads('floating_bottom', 1); if (!empty($floatingAds)): ?>
    <div class="floating-ad" style="position:fixed;bottom:0;left:0;right:0;z-index:999;background:rgba(255,255,255,0.95);box-shadow:0 -2px 10px rgba(0,0,0,0.1);padding:10px;text-align:center;display:flex;align-items:center;justify-content:center;gap:10px;">
        <?php echo render_ad($floatingAds[0]); ?>
        <button class="btn btn-sm btn-dark close-floating-ad" onclick="this.parentElement.style.display='none'" style="position:absolute;top:-10px;right:10px;border-radius:50%;width:24px;height:24px;padding:0;line-height:24px;font-size:14px;">&times;</button>
    </div>
    <?php endif; ?>
    
    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Theme JS -->
    <script src="<?php echo js_url('theme.js'); ?>"></script>
    
    <!-- Newsletter AJAX -->
    <script>
    document.getElementById('newsletterForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const email = document.getElementById('newsletterEmail').value;
        const msg = document.getElementById('newsletterMsg');
        try {
            const res = await fetch('<?php echo api_url('newsletter/subscribe'); ?>', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: JSON.stringify({email: email})
            });
            const data = JSON.parse(await res.text());
            const box = document.createElement('div');
            box.className = 'alert alert-' + (data.success ? 'success' : 'warning') + ' py-1';
            box.style.fontSize = '14px';
            box.textContent = data.message || (data.success ? 'You are subscribed.' : 'Could not subscribe.');
            msg.innerHTML = '';
            msg.appendChild(box);
            if (data.success) document.getElementById('newsletterEmail').value = '';
        } catch(err) {
            msg.innerHTML = '<div class="alert alert-danger py-1" style="font-size:14px;">Error subscribing. Please try again.</div>';
        }
    });
    </script>
    
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1796797159544561"
     crossorigin="anonymous"></script>
<ins class="adsbygoogle"
     style="display:block"
     data-ad-format="autorelaxed"
     data-ad-client="ca-pub-1796797159544561"
     data-ad-slot="2022548740"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
    
    
</body>
</html>