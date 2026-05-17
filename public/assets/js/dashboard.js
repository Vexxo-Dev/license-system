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
      window.location.href = 'licence.php';
    });
  }

  function makeCardLink(card, href) {
    if (!card) {
      return;
    }

    card.style.cursor = 'pointer';
    card.addEventListener('click', function () {
      window.location.href = href;
    });
    card.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        window.location.href = href;
      }
    });
  }

  // ── Stat card navigation
  const cardClients = document.getElementById('cardTotalClients');
  makeCardLink(cardClients, 'clients.php');

  const cardLicenses = document.getElementById('cardTotalLicenses');
  makeCardLink(cardLicenses, 'licence.php');

  const cardActiveUsers = document.getElementById('cardActiveUsers');
  makeCardLink(cardActiveUsers, 'users.php');

})();
