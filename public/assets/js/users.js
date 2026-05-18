document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('usersTableBody');
    const globalSearchInput = document.getElementById('globalSearch');
    const roleButton = document.getElementById('userRoleFilterButton');
    const roleButtonText = roleButton ? roleButton.querySelector('span') : null;
    const roleItems = document.querySelectorAll('.dropdown-menu [data-role]');
    const resultCount = document.getElementById('usersResultCount');
    const editModal = document.getElementById('editUserModal');
    const editUserId = document.getElementById('editUserId');
    const editFullName = document.getElementById('editFullName');
    const editEmail = document.getElementById('editEmail');
    const editUserClientId = document.getElementById('editUserClientId');
    const editRole = document.getElementById('editRole');
    const editStatus = document.getElementById('editStatus');
    const editPassword = document.getElementById('editPassword');

    if (!tableBody) {
        return;
    }

    const rows = Array.from(tableBody.querySelectorAll('tr'));
    const total = rows.length;
    let selectedRole = 'all';

    function updateCount(visible) {
        if (!resultCount) {
            return;
        }

        resultCount.textContent = visible > 0
            ? `Showing 1-${visible} of ${total} users`
            : `Showing 0 of ${total} users`;
    }

    function applyFilters() {
        const term = globalSearchInput ? globalSearchInput.value.trim().toLowerCase() : '';
        let visible = 0;

        rows.forEach(function (row) {
            const name = row.dataset.fullName || '';
            const email = row.dataset.email || '';
            const clientName = row.dataset.clientName || '';
            const role = row.dataset.role || '';
            const status = row.dataset.status || '';
            const searchableText = `${name} ${email} ${clientName} ${role} ${status}`.toLowerCase();
            const matchesSearch = term === '' || searchableText.includes(term);
            const matchesRole = selectedRole === 'all' || row.dataset.role === selectedRole;
            const show = matchesSearch && matchesRole;

            row.style.display = show ? '' : 'none';
            if (show) {
                visible += 1;
            }
        });

        updateCount(visible);
    }

    if (globalSearchInput) {
        globalSearchInput.addEventListener('input', applyFilters);
    }

    roleItems.forEach(function (item) {
        item.addEventListener('click', function (event) {
            event.preventDefault();
            selectedRole = item.dataset.role || 'all';
            if (roleButtonText) {
                roleButtonText.textContent = item.textContent.trim();
            }
            applyFilters();
        });
    });

    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const row = button ? button.closest('tr') : null;

            if (!row) {
                return;
            }

            editUserId.value = row.dataset.userId || '';
            editFullName.value = row.dataset.fullName || '';
            editEmail.value = row.dataset.email || '';
            editUserClientId.value = row.dataset.clientId === '0' ? '' : (row.dataset.clientId || '');
            editRole.value = row.dataset.role || 'viewer';
            editStatus.value = row.dataset.status || 'active';
            editPassword.value = '';
        });
    }

    document.querySelectorAll('.delete-user-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const row = form.closest('tr');
            const name = row ? row.dataset.fullName : 'this user';

            if (!window.confirm(`Delete ${name}? This cannot be undone.`)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.user-status-switch').forEach(function (statusSwitch) {
        statusSwitch.addEventListener('change', function () {
            const form = statusSwitch.closest('form');
            const row = statusSwitch.closest('tr');
            const name = row ? row.dataset.fullName : 'this user';
            const action = statusSwitch.checked ? 'activate' : 'deactivate';

            if (!window.confirm(`Are you sure you want to ${action} ${name}?`)) {
                statusSwitch.checked = !statusSwitch.checked;
                return;
            }

            if (form) {
                form.submit();
            }
        });
    });

    // Handle Client Company visibility based on Role selection
    const addUserRole = document.getElementById('addUserRole');
    const addUserClientDiv = document.getElementById('addUserClientDiv');
    const editRoleElement = document.getElementById('editRole');
    const editUserClientDiv = document.getElementById('editUserClientDiv');

    function toggleClientFieldVisibility(roleField, clientDiv, isEditMode = false) {
        if (!roleField || !clientDiv) return;

        const roleValue = roleField.value.trim();
        // Hide if: role is empty (not selected) OR role is admin
        const shouldHide = roleValue === '' || roleValue === 'admin';
        clientDiv.style.display = shouldHide ? 'none' : 'block';

        if (roleValue === 'admin') {
            // Reset client selection to "No client" when admin is selected
            const clientSelect = clientDiv.querySelector('select');
            if (clientSelect) {
                clientSelect.value = '';
            }
        }
    }

    if (addUserRole) {
        // Set initial state on page load (hidden by default since no role selected)
        toggleClientFieldVisibility(addUserRole, addUserClientDiv, false);

        // Listen to role changes in Add User modal
        addUserRole.addEventListener('change', function () {
            toggleClientFieldVisibility(addUserRole, addUserClientDiv, false);
        });
    }

    if (editRoleElement) {
        // Set initial state when edit modal is shown
        const editModal = document.getElementById('editUserModal');
        if (editModal) {
            editModal.addEventListener('shown.bs.modal', function () {
                toggleClientFieldVisibility(editRoleElement, editUserClientDiv, true);
            });
        }

        // Listen to role changes in Edit User modal
        editRoleElement.addEventListener('change', function () {
            toggleClientFieldVisibility(editRoleElement, editUserClientDiv, true);
        });
    }

    applyFilters();
});
