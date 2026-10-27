/* Contract/behavior regression for the opt-in Andy Core notification UI engine. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'public/assets/js/andy-core-notification-ui.js'), 'utf8');
const css = fs.readFileSync(path.join(root, 'public/assets/css/andy-core-notification-ui.css'), 'utf8');
assert.match(css, /:host\(/, 'shadow styles must be scoped');
assert.doesNotMatch(css, /(^|\s)\.woocommerce-(message|error|info)/, 'no WooCommerce global hiding');
assert.doesNotMatch(css, /@keyframes countdown|\.progress-bar|\.hint\W/, 'compact surface has no countdown/footer');
assert.doesNotMatch(source, /fetch\(|XMLHttpRequest/, 'R2 frontend must not issue network requests');

let currentMs = 0, nextTimer = 0;
const timers = new Map();
function timeout(callback, ms) {
  const n = ++nextTimer;
  timers.set(n, { callback, due: currentMs + ms });
  return n;
}
function advance(ms) {
  const end = currentMs + ms;
  while (true) {
    const eligible = Array.from(timers.entries())
      .filter(([_, timer]) => timer.due <= end)
      .sort((a, b) => a[1].due - b[1].due);
    if (!eligible.length) { break; }
    const [n, timer] = eligible[0];
    timers.delete(n); currentMs = timer.due; timer.callback();
  }
  currentMs = end;
}
class FakeElement {
  constructor(tag) {
    this.tagName = tag; this.children = []; this.listeners = {};
    this.attributes = {}; this.dataset = {}; this.hidden = false;
    this.textContent = ''; this.parent = null;
    const names = new Set();
    this.classList = {
      add: (...n) => n.forEach(x => names.add(x)),
      remove: (...n) => n.forEach(x => names.delete(x)),
      contains: n => names.has(n)
    };
  }
  setAttribute(n,v) { this.attributes[n] = String(v); }
  getAttribute(n) { return this.attributes[n]; }
  addEventListener(n, cb) { (this.listeners[n] ||= []).push(cb); }
  appendChild(child) {
    this.children.push(child); child.parent = this;
    if (this.tagName === 'body') {
      const link = child.shadowRoot && child.shadowRoot.children.find(e => e.tagName === 'link');
      if (link) { (link.listeners.load || []).forEach(cb => cb()); }
    }
    return child;
  }
  attachShadow(opts) {
    assert.equal(opts.mode, 'open'); this.shadowRoot = new FakeElement('shadow-root');
    return this.shadowRoot;
  }
}
const doc = { readyState: 'complete', body: new FakeElement('body'), createElement: tag => new FakeElement(tag) };
const events = {};
const win = {
  AndyCoreNotificationUIConfig: {
    cssUrl: '/wp-content/plugins/yby-core/public/assets/css/andy-core-notification-ui.css',
    holdMs: 2000, exitMs: 230, dedupeMs: 1200, criticalMs: 3000
  },
  setTimeout: timeout, clearTimeout: id => timers.delete(id),
  addEventListener: (name, callback) => { events[name] = callback; }
};
vm.runInNewContext(source, {window:win, document:doc, Map, Date, Object, Number, String}, {filename:'andy-core-notification-ui.js'});
const api = win.AndyCoreNotificationUI, host = doc.body.children[0];
assert.equal(api.version, '1.0.0');
assert.equal(host.tagName, 'andy-core-notification-ui');
assert.equal(typeof win.AndyCore.notify, 'function');
assert.equal(host.getAttribute('data-module'), 'notification_ui');
assert.equal(host.getAttribute('aria-hidden'), 'true');
assert.equal(api.notify({type:'nonsense',title:'Invalid'}), null);

const first = api.notify({type:'success',title:'Coupon applied',key:'coupon-1'});
assert.ok(first); assert.equal(api.getState().type, 'success');
assert.equal(host.classList.contains('is-visible'), true);
assert.equal(api.notify({type:'success',title:'Duplicate',key:'coupon-1'}), null);
assert.equal(host.shadowRoot.children[1].children[0].children[1].children[0].textContent, 'Coupon applied');
advance(2000);
assert.equal(host.classList.contains('is-leaving'), true);
advance(230);
assert.equal(api.getState(), null);
const running = api.notify({type:'running',title:'Saving',key:'saving-2'});
advance(9000);
assert.equal(api.getState().type, 'running', 'running has no timeout');
const critical = api.notify({type:'error',title:'Payment declined',message:'Please retry',key:'payment-3'});
assert.equal(api.getState().type, 'error', 'latest wins over running');
assert.equal(running.complete({type:'success',title:'Stale completion'}), false);
advance(3000);
assert.equal(api.getState().compact, true, 'critical retracts to compact');
api.notify({type:'info',title:'New info',key:'info-4'});
assert.equal(api.getState().type, 'info');
assert.equal(api.getState().compact, false, 'compact never blocks a new notification');
events['andy-core:notify']({detail:{type:'success',title:'From event',key:'event-5'}});
assert.equal(api.getState().type, 'success');
assert.equal(critical.dismiss(), false, 'stale handles cannot dismiss newest');
advance(2230);
assert.equal(api.getState(), null, 'success auto-dismisses');
assert.equal(doc.body.children.length, 1, 'single island node');
console.log('PASS notification-ui-v1-r2-harness (LELO, latest-wins, stale, compact, shadow, dedupe)');
