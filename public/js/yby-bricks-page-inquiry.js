/* Page-owned Bricks inquiry modal: routing and scoped design only.
 * All validation, submissions, attribution and Case IDs remain in Andy Core. */
(function () {
  "use strict";
  function initialize() {
    var nodes = document.querySelectorAll('[data-yby-inquiry-page-module]');
    if (!nodes.length) { return; }
    // A landing page owns exactly one page-level inquiry popup.
    var marker = nodes[0];
    var id = marker.getAttribute('data-yby-modal-id') || '';
    var modal = id ? document.getElementById(id) : null;
    if (!modal || !modal.matches('[data-yby-inquiry-modal]')) { return; }
    modal.classList.add('yby-page-inquiry-owned');
    var width = marker.getAttribute('data-yby-modal-width');
    if (['760', '900', '960', '1040'].indexOf(width) !== -1) {
      modal.style.setProperty('--yby-inquiry-max-width', width + 'px');
    }
    var color = marker.getAttribute('data-yby-modal-accent');
    if (color && /^#[0-9a-fA-F]{6}$/.test(color)) {
      modal.style.setProperty('--yby-inquiry-primary', color);
      modal.style.setProperty('--yby-theme-cta-background', color);
    }
    if (marker.getAttribute('data-yby-mobile-image') === 'show') {
      modal.classList.add('yby-page-inquiry-mobile-image');
    }
    if (marker.getAttribute('data-yby-cta-routing') !== 'page_all') { return; }
    document.querySelectorAll('[data-yby-inquiry-trigger], a[href="#yby-inquiry"]').forEach(function (cta) {
      // Explicit targets from another owner must never be hijacked.
      if (!cta.hasAttribute('data-yby-modal-open')) {
        cta.setAttribute('data-yby-modal-open', id);
      }
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
  } else {
    initialize();
  }
})();
