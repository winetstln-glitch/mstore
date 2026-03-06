/**
 * Android Interaction Enhancements
 * - Ripple effects
 * - Responsive table labeling
 * - Bottom sheet interactions (future)
 * - FAB Interaction
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // -------------------------------------------------------------------------
    // 1. Responsive Table Labels
    // -------------------------------------------------------------------------
    // Automatically adds data-label attribute to table cells based on header text
    const tables = document.querySelectorAll('.table-responsive-mobile');
    
    tables.forEach(table => {
        // Find headers in thead
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                // Only add label if header exists for this column
                if (headers[index]) {
                    cell.setAttribute('data-label', headers[index]);
                }
            });
        });
    });

    // -------------------------------------------------------------------------
    // 2. Ripple Effect for Buttons and Nav Items
    // -------------------------------------------------------------------------
    // Adds a material design ripple effect on click
    const rippleElements = document.querySelectorAll('.btn, .nav-link, .sidebar-item, .mbn-item');
    
    rippleElements.forEach(el => {
        el.addEventListener('click', function(e) {
            // Don't create ripple if element is disabled
            if (this.hasAttribute('disabled') || this.classList.contains('disabled')) return;

            // Remove existing ripples
            const existingRipples = this.querySelectorAll('.ripple-effect');
            existingRipples.forEach(r => r.remove());

            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            
            this.appendChild(ripple);
            
            // Clean up after animation
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // -------------------------------------------------------------------------
    // 3. Floating Action Button (FAB) Interaction
    // -------------------------------------------------------------------------
    const fabBtn = document.getElementById('mainFabBtn');
    const fabMenu = document.getElementById('fabMenu');
    
    if (fabBtn && fabMenu) {
        fabBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent document click from closing immediately
            
            fabMenu.classList.toggle('active');
            
            // Toggle Icon
            const icon = this.querySelector('i');
            if (icon) {
                if (fabMenu.classList.contains('active')) {
                    icon.classList.remove('fa-plus');
                    icon.classList.add('fa-times');
                    // Add rotation effect via class if desired
                    icon.style.transform = 'rotate(90deg)';
                    icon.style.transition = 'transform 0.2s';
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-plus');
                    icon.style.transform = 'rotate(0deg)';
                }
            }
        });
        
        // Close FAB when clicking outside
        document.addEventListener('click', function(e) {
            if (fabMenu.classList.contains('active')) {
                if (!fabBtn.contains(e.target) && !fabMenu.contains(e.target)) {
                    fabMenu.classList.remove('active');
                    const icon = fabBtn.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-plus');
                        icon.style.transform = 'rotate(0deg)';
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // 4. Input Focus Effects
    // -------------------------------------------------------------------------
    const formControls = document.querySelectorAll('.form-control, .form-select');
    formControls.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('input-focused');
        });
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('input-focused');
        });
    });
});
