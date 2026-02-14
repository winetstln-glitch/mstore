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

    // Create Overlay if it doesn't exist
    if (!document.getElementById('sidebar-overlay')) {
        const overlay = document.createElement('div');
        overlay.id = 'sidebar-overlay';
        overlay.addEventListener('click', function() {
            body.classList.remove('sb-sidenav-toggled');
        });
        document.body.appendChild(overlay);
    }

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
});

;(function(){
    const ua = navigator.userAgent || '';
    const isWV = /\bwv\b|Android.*; wv/.test(ua) || !!window.ReactNativeWebView;
    if (isWV) {
        try { document.documentElement.classList.add('is-wv'); } catch (e) {}
    }
    function onLinkClick(e){
        const a = e.target.closest('a');
        if(!a) return;
        const href = a.getAttribute('href');
        if(!href) return;
        if(href.startsWith('#') || href.startsWith('javascript:')) return;
        if(isWV){
            e.preventDefault();
            // open all links in same view for WebView
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

// Enhance sidebar history behavior for Android back button in WebView
(function(){
    const body = document.body;
    const overlay = document.getElementById('sidebar-overlay');
    const isWV = document.documentElement.classList.contains('is-wv');
    if (!isWV) return;
    function sidebarOpen(){
        return body.classList.contains('sb-sidenav-toggled');
    }
    function pushIfNeeded(){
        if (sidebarOpen()) {
            try { history.pushState({ sidebar: true }, ''); } catch(e){}
        }
    }
    window.addEventListener('popstate', function(){
        if (sidebarOpen()) {
            body.classList.remove('sb-sidenav-toggled');
        }
    });
    // Observe toggle button and overlay
    const toggle = document.getElementById('sidebarToggle');
    if (toggle) {
        toggle.addEventListener('click', function(){
            setTimeout(pushIfNeeded, 0);
        });
    }
    if (overlay) {
        overlay.addEventListener('click', function(){
            if (sidebarOpen()) {
                try { history.back(); } catch(e){ body.classList.remove('sb-sidenav-toggled'); }
            }
        });
    }
})();
