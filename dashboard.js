/**
 * dashboard.js – LicensePro Admin Dashboard
 */

(function () {
  'use strict';

  // ── Animate distribution bars on load
  document.querySelectorAll('.dist-bar').forEach(function (bar) {
    const targetWidth = bar.style.width;
    bar.style.width = '0%';
    requestAnimationFrame(function () {
      setTimeout(function () {
        bar.style.width = targetWidth;
      }, 100);
    });
  });

  // ── Global search – placeholder interaction
  const globalSearch = document.getElementById('globalSearch');
  if (globalSearch) {
    globalSearch.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        globalSearch.value = '';
        globalSearch.blur();
      }
    });
  }

  // ── New License button
  const newLicenseBtn = document.getElementById('newLicenseBtn');
  if (newLicenseBtn) {
    newLicenseBtn.addEventListener('click', function () {
      window.location.href = 'licence.html';
    });
  }

  // ── Notifications button (placeholder)
  const notificationsBtn = document.getElementById('notificationsBtn');
  if (notificationsBtn) {
    notificationsBtn.addEventListener('click', function () {
      alert('You have 3 unread notifications.');
    });
  }

  // ── Stat card click navigation
  const cardClients = document.getElementById('cardTotalClients');
  if (cardClients) {
    cardClients.style.cursor = 'pointer';
    cardClients.addEventListener('click', function () {
      window.location.href = 'clients.html';
    });
  }

  const cardLicenses = document.getElementById('cardTotalLicenses');
  if (cardLicenses) {
    cardLicenses.style.cursor = 'pointer';
    cardLicenses.addEventListener('click', function () {
      window.location.href = 'licence.html';
    });
  }

  // ── Distribution menu button (placeholder)
  const distMenu = document.getElementById('licenseDistributionMenu');
  if (distMenu) {
    distMenu.addEventListener('click', function () {
      // Future: open dropdown with export / refresh options
    });
  }

  // ── View All Activity link
  const viewAll = document.getElementById('viewAllActivityLink');
  if (viewAll) {
    viewAll.addEventListener('click', function (e) {
      e.preventDefault();
      // Future: navigate to full activity log page
    });
  }
})();
