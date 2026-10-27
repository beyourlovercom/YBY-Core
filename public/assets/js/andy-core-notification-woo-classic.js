/* WooCommerce Classic DOM success notice bridge — Andy Core R3.
 * No API requests, order/cart mutations, theme dependency or blanket CSS.
 * Native notice remains visible until the island is loaded AND accepts it.
 */
(function (win, doc) {
  'use strict';
  if (win.AndyCoreWooNoticeBridge) { return; }

  var SELECTOR = '.woocommerce-notices-wrapper .woocommerce-message, .woocommerce .woocommerce-message, .woocommerce-notices-wrapper .wc-block-components-notice-banner.is-success, .woocommerce .wc-block-components-notice-banner.is-success';
  var watched = new Map();
  var observer = null, pollTimer = 0, attempts = 0, disposed = false;
  var stats = { accepted: 0, duplicates: 0, skipped: 0 };
  var ready = function () {
    var api = win.AndyCoreNotificationUI;
    return !!(api && typeof api.isReady === 'function' && api.isReady() &&
      typeof api.notify === 'function' && typeof api.getState === 'function');
  };
  function isCheckoutOrCart() {
    return !!doc.body && (doc.body.classList.contains('woocommerce-checkout') ||
      doc.body.classList.contains('woocommerce-cart'));
  }
  function isEligible(node) {
    if (!node || !node.isConnected || !node.matches(SELECTOR) ||
      node.hasAttribute('data-andy-core-notice-suppressed') ||
      node.hidden || node.getAttribute('aria-hidden') === 'true') { return false; }
    if (node.closest('.woocommerce-error, .woocommerce-info, .wc-block-components-notice-banner.is-error, .wc-block-components-notice-banner.is-warning') ||
      node.querySelector('a,button,input,select,textarea,[role="button"]')) { return false; }
    if (!isCheckoutOrCart()) { return false; }
    var style = win.getComputedStyle(node);
    if (style.display === 'none' || style.visibility === 'hidden') { return false; }
    return !!String(node.textContent || '').trim();
  }
  function getTitle(node) {
    return String(node.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 140);
  }
  function fingerprint(title) {
    return 'woo-classic:' + (win.location && win.location.pathname || '/') +
      ':' + title.toLocaleLowerCase();
  }
  function hideNative(node) {
    if (watched.has(node)) { return; }
    watched.set(node, {
      hidden: node.hidden,
      aria: node.getAttribute('aria-hidden'),
      display: node.style.getPropertyValue('display'),
      priority: node.style.getPropertyPriority('display')
    });
    node.hidden = true;
    node.setAttribute('aria-hidden', 'true');
    node.setAttribute('data-andy-core-notice-suppressed', '1');
    node.style.setProperty('display', 'none', 'important');
  }
  function restoreNative() {
    watched.forEach(function (previous, node) {
      if (node && node.isConnected) {
        node.hidden = previous.hidden;
        if (previous.aria === null) { node.removeAttribute('aria-hidden'); }
        else { node.setAttribute('aria-hidden', previous.aria); }
        node.removeAttribute('data-andy-core-notice-suppressed');
        if (previous.display) { node.style.setProperty('display', previous.display, previous.priority); }
        else { node.style.removeProperty('display'); }
      }
    });
    watched.clear();
  }
  function bridge(node) {
    if (!isEligible(node) || !ready()) { return false; }
    var title = getTitle(node);
    var key = fingerprint(title);
    var api = win.AndyCoreNotificationUI;
    var previous = api.getState();
    var accepted = false;
    try {
      if (previous && previous.type === 'success' && previous.key === key) {
        // Second native DOM copy for exactly the same already visible island.
        accepted = true; stats.duplicates++;
      } else {
        var handle = api.notify({ type:'success', title:title, key:key, confirmed:true });
        var shown = api.getState();
        accepted = !!(handle && shown && shown.id === handle.id && shown.key === key &&
          shown.type === 'success' && api.isReady());
      }
    } catch (error) {
      restoreNative();
      return false;
    }
    if (!accepted) { return false; }
    hideNative(node);
    stats.accepted++;
    return true;
  }
  function scan() {
    if (disposed || !doc.body || !isCheckoutOrCart()) { return; }
    if (!ready()) {
      if (++attempts > 35) { return; }
      win.clearTimeout(pollTimer);
      pollTimer = win.setTimeout(scan, 100);
      return;
    }
    attempts = 0;
    Array.prototype.forEach.call(doc.querySelectorAll(SELECTOR), bridge);
  }
  function onError() { dispose(); }
  function start() {
    if (!doc.body || !isCheckoutOrCart() || disposed) { return; }
    if (typeof win.MutationObserver === 'function') {
      observer = new win.MutationObserver(function (mutations) {
        if (mutations.some(function (m) { return m.type === 'childList'; })) { scan(); }
      });
      observer.observe(doc.body, { subtree: true, childList: true });
    }
    scan();
  }
  function dispose() {
    disposed = true;
    win.clearTimeout(pollTimer);
    if (observer) { observer.disconnect(); observer = null; }
    restoreNative();
    win.removeEventListener('error', onError);
    win.removeEventListener('unhandledrejection', onError);
    win.removeEventListener('pagehide', restoreNative);
  }
  var api = {
    version: '1.0.0', scan: scan, dispose: dispose, restoreNative: restoreNative,
    stats: function () { return {accepted:stats.accepted, duplicates:stats.duplicates, skipped:stats.skipped, suppressed:watched.size}; }
  };
  win.AndyCoreWooNoticeBridge = api;
  win.addEventListener('error', onError);
  win.addEventListener('unhandledrejection', onError);
  win.addEventListener('pagehide', restoreNative);
  if (doc.readyState === 'loading') { doc.addEventListener('DOMContentLoaded', start, {once:true}); }
  else { start(); }
})(window, document);
