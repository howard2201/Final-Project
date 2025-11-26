// File: ../js/responsive.js
function toggleLogoVisibility() {
    const logo = document.querySelector('.backlogin img'); // selects the logo in login/register
    if (!logo) return;

    if (window.innerWidth <= 768) { // adjust breakpoint as needed
        logo.style.display = 'none';
    } else {
        logo.style.display = 'block';
    }
}

// Run on page load
window.addEventListener('load', toggleLogoVisibility);

// Run on window resize
window.addEventListener('resize', toggleLogoVisibility);
