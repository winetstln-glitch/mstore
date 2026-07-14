document.addEventListener('DOMContentLoaded', function() {
    
    /* -------------------------------------------------------------------------- */
    /*                               Theme Toggle Logic                           */
    /* -------------------------------------------------------------------------- */
    const themeToggleBtn = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const htmlElement = document.documentElement;

    // Function to set theme
    function setTheme(theme) {
        htmlElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
        
        // Update Icon
        if (themeIcon) {
            if (theme === 'dark') {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            } else {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        }

        // Dispatch Custom Event for Maps etc.
        const event = new CustomEvent('themeChanged', { detail: { theme: theme } });
        window.dispatchEvent(event);
    }

    // Initialize Theme
    const storedTheme = localStorage.getItem('theme');
    if (storedTheme) {
        setTheme(storedTheme);
    } else {
        // Default to light or system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            setTheme('dark');
        } else {
            setTheme('light');
        }
    }

    // Event Listener
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function() {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        });
    }


    /* -------------------------------------------------------------------------- */
    /*                               Sidebar Logic                                */
    /* -------------------------------------------------------------------------- */
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const body = document.body;

    // Sidebar Overlay Logic
    let overlay = document.getElementById('sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }
    
    // Ensure click listener is attached
    overlay.addEventListener('click', function() {
        body.classList.remove('sb-sidenav-toggled');
    });

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            body.classList.toggle('sb-sidenav-toggled');
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', function(e) {
            e.preventDefault();
            body.classList.remove('sb-sidenav-toggled');
        });
    }
    if (window.bootstrap && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });
    }

    const responsiveTables = document.querySelectorAll('.table-responsive-mobile table');
    responsiveTables.forEach(function(table) {
        const headers = Array.from(table.querySelectorAll('thead th')).map(function(th) {
            return th.textContent.trim();
        });
        if (!headers.length) {
            return;
        }
        table.querySelectorAll('tbody tr').forEach(function(row) {
            Array.from(row.children).forEach(function(cell, index) {
                if (!cell.getAttribute('data-label') && headers[index]) {
                    cell.setAttribute('data-label', headers[index]);
                }
            });
        });
    });
});

;(function(){
    const ua = navigator.userAgent || '';
    const isWV = /\bwv\b|Android.*; wv/.test(ua) || !!window.ReactNativeWebView;
    function onLinkClick(e){
        const a = e.target.closest('a');
        if(!a) return;
        const href = a.getAttribute('href');
        if(!href) return;
        if(href.startsWith('#') || href.startsWith('javascript:')) return;
        if(isWV){
            e.preventDefault();
            window.location.assign(href);
        }
    }
    document.addEventListener('click', onLinkClick);
    const origOpen = window.open;
    window.open = function(url){
        if(isWV){
            window.location.assign(url);
            return null;
        }
        return origOpen.apply(window, arguments);
    };
})();
