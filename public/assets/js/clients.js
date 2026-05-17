document.addEventListener('DOMContentLoaded', function () {
    const clientsGrid = document.getElementById('clientsGrid');
    const pageSearchInput = document.querySelector('.toolbar-search input');
    const globalSearchInput = document.getElementById('globalSearch');
    const statusButton = document.getElementById('clientStatusFilterButton');
    const statusButtonText = statusButton ? statusButton.querySelector('span') : null;
    const clearButton = document.getElementById('clearClientFilters');
    const resultCount = document.getElementById('clientsResultCount');
    const statusItems = document.querySelectorAll('.dropdown-menu [data-status]');
    const detailsModal = document.getElementById('clientDetailsModal');
    const openEditClientModal = document.getElementById('openEditClientModal');
    const editClientModalEl = document.getElementById('editClientModal');
    let selectedClientCard = null;

    if (!clientsGrid) {
        return;
    }

    const cards = Array.from(clientsGrid.querySelectorAll('.client-grid-item'));
    const total = cards.length;
    let selectedStatus = 'all';

    function updateCount(visible) {
        if (!resultCount) {
            return;
        }

        resultCount.textContent = visible > 0
            ? `Showing 1-${visible} of ${total} clients`
            : `Showing 0 of ${total} clients`;
    }

    function applyFilters() {
        const pageTerm = pageSearchInput ? pageSearchInput.value.trim().toLowerCase() : '';
        const globalTerm = globalSearchInput ? globalSearchInput.value.trim().toLowerCase() : '';
        let visible = 0;

        cards.forEach(function (card) {
            const text = card.textContent.toLowerCase();
            const matchesPageSearch = pageTerm === '' || text.includes(pageTerm);
            const matchesGlobalSearch = globalTerm === '' || text.includes(globalTerm);
            const matchesStatus = selectedStatus === 'all' || card.dataset.status === selectedStatus;
            const show = matchesPageSearch && matchesGlobalSearch && matchesStatus;

            card.style.display = show ? '' : 'none';
            if (show) {
                visible += 1;
            }
        });

        updateCount(visible);
    }

    [pageSearchInput, globalSearchInput].forEach(function (input) {
        if (input) {
            input.addEventListener('input', applyFilters);
        }
    });

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

    if (clearButton) {
        clearButton.addEventListener('click', function () {
            selectedStatus = 'all';
            if (pageSearchInput) {
                pageSearchInput.value = '';
            }
            if (globalSearchInput) {
                globalSearchInput.value = '';
            }
            if (statusButtonText) {
                statusButtonText.textContent = 'All Statuses';
            }
            applyFilters();
        });
    }

    if (detailsModal) {
        detailsModal.addEventListener('show.bs.modal', function (event) {
            const link = event.relatedTarget;
            const card = link ? link.closest('.client-grid-item') : null;

            if (!card) {
                return;
            }

            selectedClientCard = card;
            const name = card.dataset.name || 'Client Details';
            const industry = card.dataset.industry || 'Industry';
            const status = card.dataset.status || 'active';
            const statusLabel = card.dataset.statusLabel || 'Active';
            const totalLicenses = Number(card.dataset.totalLicenses || 0);
            const activeUsers = Number(card.dataset.activeUsers || 0);
            const utilization = Number(card.dataset.utilization || 0);
            const contactName = card.dataset.contactName || 'Not set';
            const contactEmail = card.dataset.contactEmail || 'Not set';
            const initials = name
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part[0].toUpperCase())
                .join('') || 'CL';

            document.getElementById('clientDetailsModalLabel').textContent = name;
            document.getElementById('detailsClientInitials').textContent = initials;
            document.getElementById('detailsIndustry').textContent = industry;
            document.getElementById('detailsTotalLicenses').textContent = totalLicenses.toLocaleString();
            document.getElementById('detailsActiveUsers').textContent = activeUsers.toLocaleString();
            document.getElementById('detailsUtilization').textContent = `${utilization}%`;
            document.getElementById('detailsUtilizationValue').textContent = `${utilization}%`;
            document.getElementById('detailsContactName').textContent = contactName;
            document.getElementById('detailsContactEmail').textContent = contactEmail;

            const statusEl = document.getElementById('detailsStatus');
            statusEl.textContent = statusLabel;
            statusEl.className = 'badge-status';
            if (status === 'over_limit') {
                statusEl.classList.add('badge-danger');
            } else if (status === 'inactive') {
                statusEl.classList.add('badge-inactive');
            } else {
                statusEl.classList.add('badge-active');
            }

            const utilizationHeader = document.getElementById('detailsUtilizationHeader');
            const utilizationBar = document.getElementById('detailsUtilizationBar');
            utilizationHeader.classList.toggle('danger', status === 'over_limit' || activeUsers > totalLicenses);
            utilizationBar.classList.toggle('danger', status === 'over_limit' || activeUsers > totalLicenses);
            utilizationBar.style.width = `${Math.min(100, utilization)}%`;
        });
    }

    function fillEditClientForm(card) {
        if (!card) {
            return;
        }

        document.getElementById('editClientId').value = card.dataset.clientId || '';
        document.getElementById('editClientName').value = card.dataset.name || '';
        document.getElementById('editClientIndustry').value = card.dataset.industry || '';
        document.getElementById('editClientStatus').value = card.dataset.status || 'active';
        document.getElementById('editClientContactName').value = card.dataset.contactName === 'Not set' ? '' : (card.dataset.contactName || '');
        document.getElementById('editClientContactEmail').value = card.dataset.contactEmail === 'Not set' ? '' : (card.dataset.contactEmail || '');
    }

    if (openEditClientModal && editClientModalEl) {
        openEditClientModal.addEventListener('click', function () {
            fillEditClientForm(selectedClientCard);

            const detailsInstance = bootstrap.Modal.getInstance(detailsModal);
            if (detailsInstance) {
                detailsInstance.hide();
            }

            bootstrap.Modal.getOrCreateInstance(editClientModalEl).show();
        });
    }

    applyFilters();
});
