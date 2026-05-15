document.addEventListener('DOMContentLoaded', () => {
    // Initialization code for the User Management page
    console.log('User Management page loaded');

    // Example of attaching an event listener to the Add User button
    const addUserBtn = document.querySelector('.btn-primary-custom');
    if (addUserBtn) {
        addUserBtn.addEventListener('click', () => {
            console.log('Add User button clicked');
        });
    }

    // Example of attaching event listeners to the role filter
    const roleFilterItems = document.querySelectorAll('.dropdown-menu .dropdown-item');
    roleFilterItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const selectedRole = e.target.textContent;
            console.log(`Filtering by role: ${selectedRole}`);
            // Update the dropdown button text
            const dropdownBtn = document.querySelector('.dropdown-toggle');
            if (dropdownBtn) {
                dropdownBtn.innerHTML = `<i class="bi bi-filter-left" style="font-size: 16px;"></i> ${selectedRole}`;
            }
        });
    });
});
