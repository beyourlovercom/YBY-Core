/* Andy Core Notification UI V1 — isolated opt-in presentation engine (R2).
 * No WooCommerce hooks, output replacement, network calls, or message interception.
 */
(function (win, doc) {
  'use strict';
  if (win.AndyCoreNotificationUI && win.AndyCoreNotificationUI.version === '1.0.0') { return; }

  var cfg = win.AndyCoreNotificationUIConfig || {};
  var HOLD = Math.max(0, Number(cfg.holdMs) || 2000);
  var EXIT = Math.max(0, Number(cfg.exitMs) || 230);
  var DEDUPE = Math.max(0, Number(cfg.dedupeMs) || 1200);
  var CRITICAL_HOLD = Math.max(0, Number(cfg.criticalMs) || 3000);
  var ICONS = { success: '✓', info: 'i', warning: '!', error: '×', blocked: '!', running: '…' };
  var CRITICAL = { error: true, blocked: true, warning: true };
  var host = null, surface = null, headline = null, detail = null, mark = null;
  var close = null, styleState = 'pending', pending = null, active = null;
  var serial = 0, holdTimer = null, exitTimer = null, retractTimer = null;
  var recent = new Map();

  function clearTimers() {
    win.clearTimeout(holdTimer);
    win.clearTimeout(exitTimer);
    win.clearTimeout(retractTimer);
    holdTimer = exitTimer = retractTimer = null;
  }
  function escapeText(value, max) { return String(value == null ? '' : value).trim().slice(0, max); }
  function normalized(value) {
    if (!value || typeof value !== 'object') { return null; }
    var type = escapeText(value.type || 'info', 16).toLowerCase();
    if (!Object.prototype.hasOwnProperty.call(ICONS, type)) { return null; }
    var title = escapeText(value.title, 140);
    if (!title) { return null; }
    return {
      id: ++serial, type: type, title: title,
      message: escapeText(value.message, 360),
      key: escapeText(value.key || (type + ':' + title), 180),
      critical: value.critical === true || (value.critical !== false && !!CRITICAL[type])
    };
  }
  function setHidden() {
    if (!host) { return; }
    host.classList.remove('is-visible', 'is-compact', 'is-leaving');
    host.setAttribute('aria-hidden', 'true');
  }
  function setExpanded() {
    if (!host || !active) { return; }
    host.classList.remove('is-compact', 'is-leaving');
    host.classList.add('is-visible');
    host.setAttribute('aria-hidden', 'false');
    if (close) { close.hidden = false; }
    if (detail) { detail.hidden = !active.message; }
    clearTimers();
    if (active.critical) {
      retractTimer = win.setTimeout(function () {
        if (host && active && host.classList.contains('is-visible')) {
          host.classList.add('is-compact');
          if (close) { close.hidden = true; }
          if (detail) { detail.hidden = true; }
        }
      }, CRITICAL_HOLD);
    } else if (active.type !== 'running') {
      holdTimer = win.setTimeout(function () { dismiss(active.id); }, HOLD);
    }
  }
  function render(item) {
    if (!host || styleState !== 'ready') { pending = item; return; }
    clearTimers(); active = item; pending = null;
    host.dataset.type = item.type;
    headline.textContent = item.title;
    detail.textContent = item.message;
    mark.textContent = ICONS[item.type];
    surface.setAttribute('role', item.critical ? 'alert' : 'status');
    surface.setAttribute('aria-live', item.critical ? 'assertive' : 'polite');
    setExpanded();
  }
  function dismiss(expectedId) {
    if (!active || (expectedId != null && active.id !== expectedId)) { return false; }
    clearTimers();
    var original = active.id;
    if (host) { host.classList.add('is-leaving'); host.classList.remove('is-visible'); }
    exitTimer = win.setTimeout(function () {
      if (active && active.id === original) { active = null; setHidden(); }
    }, EXIT);
    return true;
  }
  function notify(value) {
    var item = normalized(value);
    if (!item) { return null; }
    var now = Date.now();
    var last = recent.get(item.key) || 0;
    if (last && now - last < DEDUPE) { return null; }
    recent.set(item.key, now);
    if (recent.size > 48) {
      recent.forEach(function (time, key) { if (now - time > DEDUPE) { recent.delete(key); } });
    }
    // Latest Wins: never wait for compact, running, or an exiting old message.
    active = item;
    clearTimers();
    if (styleState !== 'failed') { render(item); }
    return {
      id: item.id,
      complete: function (next) {
        if (!active || active.id !== item.id) { return false; }
        if (next && typeof next === 'object') {
          return !!notify(Object.assign({ key: 'completion:' + item.id }, next));
        }
        return dismiss(item.id);
      },
      dismiss: function () { return dismiss(item.id); }
    };
  }
  function setup() {
    if (host || styleState === 'failed' || !doc.body) { return; }
    host = doc.createElement('andy-core-notification-ui');
    host.setAttribute('aria-hidden', 'true');
    host.setAttribute('data-module', 'notification_ui');
    var shadow = host.attachShadow({ mode: 'open' });
    var link = doc.createElement('link');
    link.rel = 'stylesheet';
    link.href = String(cfg.cssUrl || '');
    if (!link.href || !cfg.cssUrl) { styleState = 'failed'; return; }
    shadow.appendChild(link);
    surface = doc.createElement('section');
    surface.className = 'island';
    surface.setAttribute('role', 'status');
    surface.setAttribute('aria-live', 'polite');
    var main = doc.createElement('div');
    main.className = 'island-main';
    mark = doc.createElement('span'); mark.className = 'island-icon'; mark.setAttribute('aria-hidden', 'true');
    var copy = doc.createElement('div'); copy.className = 'island-copy';
    headline = doc.createElement('strong'); headline.className = 'island-title';
    detail = doc.createElement('span'); detail.className = 'island-detail';
    close = doc.createElement('button'); close.className = 'island-dismiss'; close.type = 'button';
    close.setAttribute('aria-label', 'Dismiss notification'); close.textContent = '×';
    close.addEventListener('click', function (e) { e.stopPropagation(); dismiss(); });
    copy.appendChild(headline); copy.appendChild(detail);
    main.appendChild(mark); main.appendChild(copy); main.appendChild(close);
    surface.appendChild(main); shadow.appendChild(surface);
    surface.addEventListener('click', function () {
      if (active && host.classList.contains('is-compact')) { setExpanded(); }
    });
    surface.addEventListener('keydown', function (e) {
      if ((e.key === 'Enter' || e.key === ' ') && host.classList.contains('is-compact')) {
        e.preventDefault(); setExpanded();
      }
    });
    surface.tabIndex = 0;
    link.addEventListener('load', function () {
      styleState = 'ready';
      if (pending) { render(pending); }
    }, { once: true });
    link.addEventListener('error', function () {
      styleState = 'failed'; pending = null; active = null; setHidden();
    }, { once: true });
    doc.body.appendChild(host);
  }
  function onEvent(e) { if (e && e.detail) { notify(e.detail); } }
  win.addEventListener('andy-core:notify', onEvent);
  var api = {
    version: '1.0.0', notify: notify, dismiss: dismiss,
    isReady: function () { return styleState === 'ready' && !!(host && host.isConnected && surface); },
    getState: function () { return active ? { id: active.id, type: active.type, key: active.key, title: active.title, compact: !!(host && host.classList.contains('is-compact')) } : null; }
  };
  win.AndyCoreNotificationUI = api;
  if (!win.AndyCore) { win.AndyCore = {}; }
  if (typeof win.AndyCore === 'object' && typeof win.AndyCore.notify !== 'function') {
    win.AndyCore.notify = notify;
  }
  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', setup, { once: true });
  } else { setup(); }
})(window, document);
