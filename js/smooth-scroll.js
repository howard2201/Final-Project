// Smooth scroll to services section
document.addEventListener('DOMContentLoaded', function() {
    const servicesLink = document.querySelector('.services-link');
    
    if (servicesLink) {
        servicesLink.addEventListener('click', function(e) {
            // Check if we're on the index page
            const currentPage = window.location.pathname;
            const isIndexPage = currentPage.endsWith('index.php') || currentPage.endsWith('/') || currentPage.includes('FinalProject-fixed') && !currentPage.includes('/');
            
            if (isIndexPage) {
                e.preventDefault();
                const servicesSection = document.getElementById('services');
                
                if (servicesSection) {
                    servicesSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            } else {
                // If not on index page, navigate to index.php#services
                window.location.href = 'index.php#services';
            }
        });
    }
    
    // Handle direct navigation to #services (e.g., from another page)
    if (window.location.hash === '#services') {
        setTimeout(function() {
            const servicesSection = document.getElementById('services');
            if (servicesSection) {
                servicesSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }, 100);
    }
});

