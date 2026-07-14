/**
 * Android Interaction Enhancements
 * - Ripple effects
 * - Responsive table labeling
 * - Bottom sheet interactions (future)
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
    // 3. Input Focus Effects
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
