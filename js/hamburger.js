function setupHamburger(hamburgerId, dropdownId) {
    const hamburger = document.getElementById(hamburgerId);
    const dropdown = document.getElementById(dropdownId);

    if (!hamburger || !dropdown) return;

    hamburger.addEventListener('click', () => {
        dropdown.classList.toggle('show');      // toggle dropdown visibility
        hamburger.classList.toggle('active');   // animate hamburger into X
    });
}

// Setup for both headers
setupHamburger('hamburger1', 'dropdown1');
setupHamburger('hamburger2', 'dropdown2');
