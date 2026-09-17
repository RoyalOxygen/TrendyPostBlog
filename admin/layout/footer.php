        </div>
        <!-- Footer -->
        <footer class="admin-footer text-center py-3 text-muted">
            <small>TrendyPost v<?php echo APP_VERSION; ?> &copy; <?php echo date('Y'); ?></small>
        </footer>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Admin JS - Using correct path for root domain -->
<script src="<?php echo ADMIN_ASSETS_URL; ?>admin.js"></script>

<!-- Fallback JS for sidebar toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle for mobile
    const topbarToggle = document.getElementById('topbarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const sidebarClose = document.getElementById('sidebarToggle');
    
    if (topbarToggle) {
        topbarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('open');
        });
    }
    
    if (sidebarClose) {
        sidebarClose.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.remove('open');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 992) {
            if (sidebar && sidebar.classList.contains('open')) {
                if (!sidebar.contains(e.target) && !topbarToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        }
    });
    
    // Delete confirmation
    document.querySelectorAll('.delete-confirm').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(function(alert) {
        setTimeout(function() {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.click();
            }
        }, 5000);
    });
});
</script>
</body>
</html>