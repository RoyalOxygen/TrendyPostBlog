document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('adminOverlay');
    const topbarToggle = document.getElementById('topbarToggle');
    const sidebarClose = document.getElementById('sidebarToggle');

    const closeSidebar = () => {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('show');
        document.body.style.overflow = '';
    };

    const openSidebar = () => {
        sidebar?.classList.add('open');
        overlay?.classList.add('show');
        if (window.innerWidth <= 991) {
            document.body.style.overflow = 'hidden';
        }
    };

    topbarToggle?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (sidebar?.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    sidebarClose?.addEventListener('click', (e) => {
        e.preventDefault();
        closeSidebar();
    });

    overlay?.addEventListener('click', closeSidebar);

    document.addEventListener('click', (e) => {
        if (window.innerWidth > 991) {
            return;
        }
        if (!sidebar?.classList.contains('open')) {
            return;
        }
        if (sidebar.contains(e.target) || topbarToggle?.contains(e.target)) {
            return;
        }
        closeSidebar();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 991) {
            closeSidebar();
        }
    });

    document.querySelectorAll('.delete-confirm').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('.alert:not(.alert-permanent)').forEach((alert) => {
        setTimeout(() => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.click();
            }
        }, 5000);
    });
});
