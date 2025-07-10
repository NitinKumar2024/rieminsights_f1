/**
 * Admin Panel JavaScript
 * Handles responsive sidebar and other admin panel functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle functionality
    const sidebarToggleBtn = document.querySelector('.navbar-toggler');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    // Create backdrop element for mobile sidebar
    const backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);
    
    // Toggle sidebar when navbar toggler is clicked
    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
            document.body.classList.toggle('sidebar-open');
        });
    }
    
    // Close sidebar when backdrop is clicked
    backdrop.addEventListener('click', function() {
        sidebar.classList.remove('show');
        backdrop.classList.remove('show');
        document.body.classList.remove('sidebar-open');
        // Also collapse the navbar if expanded
        const navbarCollapse = document.querySelector('.navbar-collapse');
        if (navbarCollapse && navbarCollapse.classList.contains('show')) {
            document.querySelector('.navbar-toggler').click();
        }
    });
    
    // Close sidebar when window is resized to desktop size
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
    });
    
    // Add active class to current page in sidebar
    const currentPath = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    
    sidebarLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (currentPath.endsWith(linkPath)) {
            link.classList.add('active');
        } else if (linkPath === 'index.php' && currentPath.endsWith('/admin/')) {
            link.classList.add('active');
        }
    });
    
    // Initialize tooltips if Bootstrap 5 is used
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Add responsive table wrapper if not already present
    document.querySelectorAll('table.table').forEach(table => {
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
    
    // Add action-buttons class to action button containers for better mobile display
    document.querySelectorAll('td').forEach(td => {
        if (td.querySelectorAll('.btn-action').length > 1) {
            td.classList.add('action-buttons');
        }
    });
});