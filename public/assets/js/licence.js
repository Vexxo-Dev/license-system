document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('licensesTableBody');
    const globalSearchInput = document.getElementById('globalSearch');
    const statusButton = document.getElementById('licenseStatusFilterButton');
    const statusButtonText = statusButton ? statusButton.querySelector('.d-flex span') : null;
    const statusItems = document.querySelectorAll('.dropdown-menu [data-status]');
    const resultCount = document.getElementById('licensesResultCount');
    const editModal = document.getElementById('editLicenseModal');
    const editLicenseId = document.getElementById('editLicenseId');
    const editLicenseClientSelect = document.getElementById('editLicenseClientSelect');
    const editLicenseKey = document.getElementById('editLicenseKey');
    const editLicenseType = document.getElementById('editLicenseType');
    const editLicenseStatus = document.getElementById('editLicenseStatus');
    const editLicenseExpiresAt = document.getElementById('editLicenseExpiresAt');

    if (!tableBody) {
        return;
    }

    const rows = Array.from(tableBody.querySelectorAll('tr'));
    const total = rows.length;
    let selectedStatus = 'all';

    function updateCount(visible) {
        if (!resultCount) {
            return;
        }

        resultCount.textContent = visible > 0
            ? `Showing 1-${visible} of ${total} licenses`
            : `Showing 0 of ${total} licenses`;
    }

    function applyFilters() {
        const term = globalSearchInput ? globalSearchInput.value.trim().toLowerCase() : '';
        let visible = 0;

        rows.forEach(function (row) {
            const licenseKey = row.dataset.licenseKey || '';
            const clientName = row.dataset.clientName || '';
            const status = row.dataset.status || '';
            const type = row.dataset.type || '';
            const expiresAt = row.dataset.expiresAt || '';
            const searchableText = `${licenseKey} ${clientName} ${status} ${type} ${expiresAt}`.toLowerCase();
            const matchesSearch = term === '' || searchableText.includes(term);
            const matchesStatus = selectedStatus === 'all' || row.dataset.status === selectedStatus;
            const show = matchesSearch && matchesStatus;

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

    statusItems.forEach(function (item) {
        item.addEventListener('click', function (event) {
            event.preventDefault();
            selectedStatus = item.dataset.status || 'all';
            if (statusButtonText) {
                statusButtonText.textContent = item.textContent.trim();
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

            editLicenseId.value = row.dataset.licenseId || '';
            editLicenseClientSelect.value = row.dataset.clientId || '';
            editLicenseKey.value = row.dataset.licenseKey || '';
            editLicenseType.value = (row.dataset.type || 'standard').toUpperCase();
            editLicenseStatus.value = row.dataset.status || 'active';
            editLicenseExpiresAt.value = row.dataset.expiresAt || '';
        });
    }

    document.querySelectorAll('.revoke-license-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const row = form.closest('tr');
            const key = row ? row.dataset.licenseKey : 'this license';

            if (!window.confirm(`Revoke ${key}?`)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.delete-license-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const row = form.closest('tr');
            const key = row ? row.dataset.licenseKey : 'this license';

            if (!window.confirm(`Delete ${key}? This cannot be undone.`)) {
                event.preventDefault();
            }
        });
    });

    applyFilters();
});
