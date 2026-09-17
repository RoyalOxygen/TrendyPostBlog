/**
 * TRENDYPOST - Main Theme JavaScript
 * ==================================
 * Vanilla JS - ES6+ features.
 * Handles UI interactions, lazy loading, scroll effects, and AJAX.
 */

document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // SCROLL TO TOP
    // ============================================================
    const scrollTopBtn = document.getElementById('scrollTop');
    if (scrollTopBtn) {
        window.addEventListener('scroll', () => {
            scrollTopBtn.classList.toggle('show', window.scrollY > 400);
        });
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // ============================================================
    // NAVBAR SCROLL EFFECT
    // ============================================================
    const mainNav = document.querySelector('.main-nav');
    if (mainNav) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                mainNav.classList.add('navbar-scrolled');
            } else {
                mainNav.classList.remove('navbar-scrolled');
            }
        });
    }
    
    // ============================================================
    // LAZY LOADING IMAGES (Native + Fallback)
    // ============================================================
    if ('loading' in HTMLImageElement.prototype) {
        // Browser supports native lazy loading
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    } else {
        // Fallback for older browsers
        const lazyImages = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });
        lazyImages.forEach(img => imageObserver.observe(img));
    }
    
    // ============================================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ============================================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    
    // ============================================================
    // CLOSE FLOATING AD
    // ============================================================
    document.querySelectorAll('.close-floating-ad').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.floating-ad').style.display = 'none';
            localStorage.setItem('floatingAdClosed', Date.now());
        });
    });
    
    // Re-show floating ad after 1 hour
    const adClosed = localStorage.getItem('floatingAdClosed');
    if (adClosed && Date.now() - parseInt(adClosed) > 3600000) {
        localStorage.removeItem('floatingAdClosed');
    }
    
    // ============================================================
    // INSTANT SEARCH (AJAX)
    // ============================================================
    const searchInput = document.getElementById('instantSearch');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;
    
    if (searchInput && searchResults) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                searchResults.innerHTML = '';
                searchResults.classList.add('d-none');
                return;
            }
            
            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`${BASE_URL}api/search?q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    
                    if (data.success && data.results.length > 0) {
                        searchResults.innerHTML = data.results.map(post => `
                            <a href="${post.url}" class="search-result-item d-flex align-items-center gap-3 p-2 text-decoration-none">
                                <img src="${post.thumbnail || '/assets/images/placeholder.jpg'}" alt="" width="50" height="40" class="rounded" style="object-fit:cover">
                                <div>
                                    <h6 class="mb-1 fs-14 text-dark">${post.title}</h6>
                                    <small class="text-muted">${post.category || ''}</small>
                                </div>
                            </a>
                        `).join('');
                        searchResults.classList.remove('d-none');
                    } else {
                        searchResults.innerHTML = '<div class="p-3 text-muted text-center">No results found</div>';
                        searchResults.classList.remove('d-none');
                    }
                } catch (err) {
                    console.error('Search error:', err);
                }
            }, 300);
        });
        
        // Close search results on click outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('d-none');
            }
        });
    }
    
    // ============================================================
    // COMMENT FORM AJAX SUBMISSION
    // ============================================================
    const commentForm = document.getElementById('ajaxCommentForm');
    if (commentForm) {
        commentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(commentForm);
            const submitBtn = commentForm.querySelector('[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Posting...';
            
            try {
                const response = await fetch(`${BASE_URL}api/comment`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    showNotification('success', data.message);
                    commentForm.reset();
                    // Optionally append new comment
                    if (data.comment) {
                        appendNewComment(data.comment);
                    }
                } else {
                    showNotification('warning', data.message);
                }
            } catch (err) {
                showNotification('danger', 'Error posting comment. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
    
    // ============================================================
    // REPLY TOGGLE
    // ============================================================
    document.querySelectorAll('.btn-reply').forEach(btn => {
        btn.addEventListener('click', () => {
            const commentId = btn.dataset.commentId;
            const replyForm = document.getElementById(`reply-form-${commentId}`);
            if (replyForm) {
                replyForm.classList.toggle('d-none');
            }
        });
    });
    
    // ============================================================
    // IN-CONTENT AD INJECTION (After paragraphs)
    // ============================================================
    injectInContentAds();
    
    // ============================================================
    // READING PROGRESS BAR
    // ============================================================
    const progressBar = document.getElementById('readingProgress');
    if (progressBar) {
        window.addEventListener('scroll', () => {
            const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const progress = (scrollTop / scrollHeight) * 100;
            progressBar.style.width = `${progress}%`;
        });
    }
    
    // ============================================================
    // TABLE OF CONTENTS (Auto-generate from H2 headings)
    // ============================================================
    const tocContainer = document.getElementById('tableOfContents');
    const articleContent = document.querySelector('.post-content');
    if (tocContainer && articleContent) {
        const headings = articleContent.querySelectorAll('h2, h3');
        if (headings.length > 2) {
            let tocHtml = '<h5 class="mb-3">Table of Contents</h5><ul class="list-unstyled toc-list">';
            headings.forEach((heading, index) => {
                const id = `section-${index}`;
                heading.id = id;
                tocHtml += `<li class="${heading.tagName === 'H3' ? 'ms-3' : ''}"><a href="#${id}" class="text-decoration-none">${heading.textContent}</a></li>`;
            });
            tocHtml += '</ul>';
            tocContainer.innerHTML = tocHtml;
            tocContainer.classList.remove('d-none');
        }
    }
    
    // ============================================================
    // SOCIAL SHARE POPUP
    // ============================================================
    document.querySelectorAll('.share-popup').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const url = btn.href;
            const width = 600, height = 400;
            const left = (screen.width - width) / 2;
            const top = (screen.height - height) / 2;
            window.open(url, 'share', `width=${width},height=${height},left=${left},top=${top}`);
        });
    });
    
    // ============================================================
    // ANALYTICS: TIME ON PAGE TRACKING
    // ============================================================
    let timeOnPage = 0;
    setInterval(() => { timeOnPage += 5; }, 5000);
    
    window.addEventListener('beforeunload', () => {
        if (timeOnPage > 10) {
            navigator.sendBeacon?.(`${BASE_URL}api/track-time`, JSON.stringify({
                time: timeOnPage,
                url: window.location.href
            }));
        }
    });
});

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Show notification toast
 */
function showNotification(type, message) {
    const container = document.getElementById('notificationContainer') || document.body;
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 15px rgba(0,0,0,0.15);';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

/**
 * Append new comment to list
 */
function appendNewComment(comment) {
    const commentsList = document.getElementById('commentsList');
    if (!commentsList) return;
    
    const commentHtml = `
        <div class="comment-item fade-in-up">
            <img src="${comment.avatar || '/assets/images/default-avatar.png'}" alt="" class="comment-avatar">
            <div class="comment-body">
                <div class="comment-header">
                    <span class="comment-author">${comment.author_name}</span>
                    <span class="comment-date">Just now</span>
                </div>
                <p class="comment-text">${comment.content}</p>
            </div>
        </div>
    `;
    commentsList.insertAdjacentHTML('afterbegin', commentHtml);
}

/**
 * Inject ads after specific paragraphs
 */
function injectInContentAds() {
    const content = document.querySelector('.post-content');
    if (!content) return;
    
    const paragraphs = content.querySelectorAll('p');
    
    // Try to fetch ad placements from data attributes
    const adSlots = [];
    if (document.querySelector('[data-ad-after-2]')) adSlots.push({ after: 2, html: document.querySelector('[data-ad-after-2]').innerHTML });
    if (document.querySelector('[data-ad-after-5]')) adSlots.push({ after: 5, html: document.querySelector('[data-ad-after-5]').innerHTML });
    
    adSlots.forEach(slot => {
        const paraIndex = slot.after - 1;
        if (paragraphs[paraIndex]) {
            const adDiv = document.createElement('div');
            adDiv.className = 'ad-in-content';
            adDiv.innerHTML = slot.html;
            paragraphs[paraIndex].after(adDiv);
        }
    });
}

/**
 * Copy text to clipboard
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showNotification('success', 'Copied to clipboard!');
    } catch (err) {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showNotification('success', 'Copied to clipboard!');
    }
}

/**
 * Debounce utility
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle utility
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Expose BASE_URL globally
window.BASE_URL = document.querySelector('base')?.href || '/';
