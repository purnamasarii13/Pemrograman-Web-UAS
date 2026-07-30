document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggle = document.getElementById('sidebarToggle');

    const closeSidebar = () => {
        sidebar?.classList.remove('show');
        backdrop?.classList.remove('show');
        document.body.classList.remove('sidebar-open');
    };

    const openSidebar = () => {
        sidebar?.classList.add('show');
        backdrop?.classList.add('show');
        document.body.classList.add('sidebar-open');
    };

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            if (sidebar.classList.contains('show')) closeSidebar();
            else openSidebar();
        });
    }
    backdrop?.addEventListener('click', closeSidebar);

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) closeSidebar();
    });

    const darkToggle = document.getElementById('darkModeToggle');
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('siakad-theme') || 'light';
    html.setAttribute('data-bs-theme', savedTheme);
    const updateDarkIcon = () => {
        if (!darkToggle) return;
        const icon = darkToggle.querySelector('i');
        if (!icon) return;
        const isDark = html.getAttribute('data-bs-theme') === 'dark';
        icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon-stars';
    };
    updateDarkIcon();
    darkToggle?.addEventListener('click', () => {
        const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', next);
        localStorage.setItem('siakad-theme', next);
        updateDarkIcon();
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', e => {
            const msg = form.getAttribute('data-confirm') || 'Yakin ingin melanjutkan?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    document.querySelectorAll('form:not([data-confirm])').forEach(form => {
        if (form.classList.contains('no-loader')) return;
        form.addEventListener('submit', () => {
            const loader = document.getElementById('pageLoader');
            if (loader) loader.classList.remove('d-none');
            const btn = form.querySelector('button[type="submit"]:not(.no-loading)');
            if (btn && !btn.disabled) {
                btn.dataset.originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
            }
        });
    });

    document.querySelectorAll('.table-search input').forEach(input => {
        const tableId = input.dataset.tableTarget;
        const table = tableId ? document.getElementById(tableId) : input.closest('.card')?.querySelector('table');
        if (!table) return;
        input.addEventListener('input', () => {
            const q = input.value.toLowerCase().trim();
            table.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });

    const loginForm = document.getElementById('loginForm');
    loginForm?.addEventListener('submit', () => {
        const btn = loginForm.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses login...';
        }
    });
});
