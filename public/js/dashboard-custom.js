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
});
