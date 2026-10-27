/* Andy Core All-Notice Dynamic Island — WooCommerce frontend adapter.
 * Safely bridges success, info, warning and error. Keeps native business state
 * and field-level validation. Per-node suppression only after verified render.
 */
(function (win, doc) {
  'use strict';
  if (win.AndyCoreWooNoticeBridge) { return; }
  var SELECTOR = [
    '.woocommerce-message', '.woocommerce-error', '.woocommerce-info',
    '.wc-block-components-notice-banner.is-success',
    '.wc-block-components-notice-banner.is-error',
    '.wc-block-components-notice-banner.is-warning',
    '.wc-block-components-notice-banner.is-info',
    '.coupon-error-notice'
  ].join(', ');
  var watched = new Map(), timer = 0, observer = null, disposed = false, attempts = 0;
  var stats = {accepted:0, duplicates:0, skipped:0, contextual:0};
  function engine() {
    var api = win.AndyCoreNotificationUI;
    return api && typeof api.isReady === 'function' && api.isReady() &&
      typeof api.notify === 'function' && typeof api.getState === 'function' ? api : null;
  }
  function kind(node) {
    if (node.matches('.woocommerce-error, .wc-block-components-notice-banner.is-error, .coupon-error-notice')) return 'error';
    if (node.matches('.wc-block-components-notice-banner.is-warning')) return 'warning';
    if (node.matches('.woocommerce-info, .wc-block-components-notice-banner.is-info')) return 'info';
    return 'success';
  }
  function isContextual(node) {
    return !!node.closest('.form-row, .woocommerce-invalid, .woocommerce-input-wrapper, [data-field-validation]');
  }
  function details(node) {
    var full = String(node.textContent || '').replace(/\s+/g,' ').trim();
    var text = full.slice(0, 500);
    var action = null, links = node.querySelectorAll('a[href],button,input,select,textarea,[role="button"]');
    if (links.length > 1) return {text:text, action:null, suppressible:false};
    if (links.length === 1) {
      var a = links[0];
      if (a.tagName !== 'A' || a.hasAttribute('onclick') || a.hasAttribute('download') ||
          a.hasAttribute('data-ajax') || a.classList.contains('ajax_add_to_cart')) {
        return {text:text, action:null, suppressible:false};
      }
      try {
        var u = new URL(a.href, win.location.href);
        if (!/^https?:$/.test(u.protocol) || u.origin !== win.location.origin) return {text:text, action:null, suppressible:false};
        action = {label:String(a.textContent || '').trim().slice(0,50),url:u.href};
        if (!action.label) return {text:text, action:null, suppressible:false};
      } catch (e) { return {text:text, action:null, suppressible:false}; }
      // Deduplicate the transferred call-to-action label from the title.
      if (text.indexOf(action.label) === 0) text = text.slice(action.label.length).trim();
    }
    return {text:text,action:action,suppressible:full.length <= 500};
  }
  function eligible(node) {
    if (!node || !node.isConnected || !node.matches(SELECTOR) ||
        node.hidden || node.getAttribute('aria-hidden') === 'true' ||
        node.hasAttribute('data-andy-core-notice-suppressed')) return false;
    // Do not process elements inside another already-handled notice.
    if (node.parentElement && node.parentElement.closest(SELECTOR)) return false;
    var css = win.getComputedStyle(node);
    return css.display !== 'none' && css.visibility !== 'hidden' &&
      !!String(node.textContent || '').trim();
  }
  function suppress(node) {
    if (watched.has(node)) return;
    watched.set(node,{hidden:node.hidden,aria:node.getAttribute('aria-hidden'),
      display:node.style.getPropertyValue('display'),priority:node.style.getPropertyPriority('display'),
      critical:['error','blocked','warning'].includes(kind(node))});
    node.hidden=true;
    node.setAttribute('aria-hidden','true');
    node.setAttribute('data-andy-core-notice-suppressed','1');
    node.style.setProperty('display','none','important');
  }
  function restoreOne(node,old) {
    if (!node || !node.isConnected) return;
    node.hidden=old.hidden;
    if (old.aria===null) node.removeAttribute('aria-hidden');
    else node.setAttribute('aria-hidden',old.aria);
    node.removeAttribute('data-andy-core-notice-suppressed');
    if (old.display) node.style.setProperty('display',old.display,old.priority);
    else node.style.removeProperty('display');
  }
  function restoreNative() {
    watched.forEach(function (old,node) {restoreOne(node,old);});
    watched.clear();
  }
  function restoreCriticalNative() {
    watched.forEach(function (old,node) {
      if (!old.critical) return;
      restoreOne(node,old); watched.delete(node);
    });
  }
  function bridge(node) {
    if (!eligible(node)) {stats.skipped++;return false;}
    var api=engine();
    if (!api) return false;
    var type=kind(node),d=details(node);
    if (!d.text) return false;
    var contextual=isContextual(node);
    var key='woo:'+type+':'+((win.location&&win.location.pathname)||'/').slice(0,60)+':'+d.text.slice(0,75).toLowerCase();
    var old=api.getState(),ok=false;
    try {
      if (old && old.key===key && old.type===type) {ok=true;stats.duplicates++;}
      else {
        // Never leave old notices hidden when Latest Wins changes content.
        if (old && old.key!==key && ['error','warning','blocked'].includes(old.type)) restoreCriticalNative();
        var h=api.notify({type:type,title:d.text.slice(0,140),message:d.text.slice(140),key:key,action:d.action});
        var state=api.getState();
        ok=!!(h && state && state.id===h.id && state.key===key && state.type===type && api.isReady());
      }
    } catch(err) { dispose();return false; }
    if (!ok) return false;
    stats.accepted++;
    if (contextual) stats.contextual++;
    // Contextual inline validation is kept, but styled by the native skin.
    // Complex controls stay operational in place if the island cannot transfer them.
    if (!contextual && d.suppressible) suppress(node);
    return true;
  }
  function scan() {
    if (disposed || !doc.body) return;
    if (!engine()) {
      if (++attempts>45) return;
      win.clearTimeout(timer);
      timer=win.setTimeout(scan,100);
      return;
    }
    attempts=0;
    watched.forEach(function(v,node){if(!node.isConnected)watched.delete(node);});
    Array.prototype.forEach.call(doc.querySelectorAll(SELECTOR),bridge);
  }
  function onError() {dispose();}
  function start() {
    if (disposed || !doc.body) return;
    if (typeof win.MutationObserver === 'function') {
      observer=new win.MutationObserver(function (mutations) {
        if (mutations.some(function(m){return m.type==='childList';})) scan();
      });
      observer.observe(doc.body,{subtree:true,childList:true});
    }
    scan();
  }
  function dispose() {
    disposed=true;win.clearTimeout(timer);
    if (observer) {observer.disconnect();observer=null;}
    restoreNative();
    win.removeEventListener('error',onError);
    win.removeEventListener('unhandledrejection',onError);
    win.removeEventListener('pagehide',restoreNative);
  }
  win.AndyCoreWooNoticeBridge={version:'2.0.0',scan:scan,dispose:dispose,
    restoreNative:restoreNative,stats:function(){return {
      accepted:stats.accepted,duplicates:stats.duplicates,skipped:stats.skipped,
      contextual:stats.contextual,suppressed:watched.size};}};
  win.addEventListener('error',onError);
  win.addEventListener('unhandledrejection',onError);
  win.addEventListener('pagehide',restoreNative);
  if (doc.readyState==='loading')doc.addEventListener('DOMContentLoaded',start,{once:true});
  else start();
})(window,document);
