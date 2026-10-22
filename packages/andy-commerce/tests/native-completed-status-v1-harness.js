'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const script = fs.readFileSync(path.join(__dirname, '../assets/native-orders/native-orders.js'), 'utf8');

function simulate({ status = 'wc-processing', hasSave = true, hasCompleted = true, buttonDisabled = false } = {}) {
  const recorded = { nativeUpdates: 0, changes: 0, confirms: 0, alerts: [], events: {} };
  const button = {
    disabled: buttonDisabled,
    dataset: { bylAction: 'status-primary', primaryStatus: 'completed' },
    closest(selector) { return selector === '[data-byl-action]' ? this : null; },
  };
  const optionList = [{ value: 'wc-processing', textContent: 'Processing' }];
  if (hasCompleted) optionList.push({ value: 'wc-completed', textContent: 'Completed' });
  const select = {
    disabled: false, value: status, options: optionList,
    dispatchEvent() { recorded.changes++; },
  };
  const nativeSave = { disabled: false, click() { recorded.nativeUpdates++; } };
  const elements = {
    '#order_status': select,
    'select[name="order_status"]': select,
    '[name="order_status"]': select,
    '[data-byl-action="status-primary"]': button,
  };
  if (hasSave) elements['#woocommerce-order-actions button.save_order'] = nativeSave;
  const document = {
    body: { classList: { contains(v) { return v === 'byl-native-orders-shopify-v1'; } } },
    querySelector(selector) { return elements[selector] ?? null; },
    querySelectorAll() { return []; },
    addEventListener(type, fn) { recorded.events[type] = fn; },
  };
  const window = {
    BYLNativeOrders: {},
    alert(message) { recorded.alerts.push(message); },
    confirm() { recorded.confirms++; throw new Error('Unexpected confirmation dialog'); },
    requestAnimationFrame(fn) { fn(); },
  };
  const context = {
    document, window, Event: class Event { constructor(type) { this.type = type; } },
    navigator: {}, console, setTimeout: () => {},
  };
  vm.runInNewContext(script, context, { filename: 'native-orders.js', timeout: 1000 });
  assert.equal(typeof recorded.events.click, 'function', 'click event must be registered');
  function fire(target = button) {
    recorded.events.click({
      target, preventDefault() {}, stopPropagation() {},
    });
  }
  return { recorded, button, select, fire };
}

{
  const a = simulate();
  a.fire();
  assert.equal(a.select.value, 'wc-completed', 'native Woo order_status must use exact wc-completed option');
  assert.equal(a.recorded.changes, 1, 'native change notification dispatched');
  assert.equal(a.recorded.nativeUpdates, 1, 'exactly one native Woo Update click');
  assert.equal(a.recorded.confirms, 0, 'never show Shopify confirmation');
  assert.deepEqual(a.recorded.alerts, []);
  assert.equal(a.button.disabled, true, 'button disabled while WooCommerce saves');
  a.fire();
  assert.equal(a.recorded.nativeUpdates, 1, 'repeat click cannot submit twice');
}
{
  const a = simulate({ hasSave: false });
  a.fire();
  assert.equal(a.select.value, 'wc-processing', 'no native Update => fail closed');
  assert.equal(a.recorded.nativeUpdates, 0);
  assert.equal(a.recorded.alerts.length, 1);
}
{
  const a = simulate({ hasCompleted: false });
  a.fire();
  assert.equal(a.select.value, 'wc-processing', 'missing completed option => no status mutation');
  assert.equal(a.recorded.nativeUpdates, 0);
}
{
  const a = simulate({ status: 'wc-completed' });
  a.fire();
  assert.equal(a.recorded.nativeUpdates, 0, 'already completed => no redundant submit');
}
{
  const a = simulate();
  const item = { disabled: false, dataset: { bylAction: 'set-status', status: 'completed' },
    closest(selector) { return selector === '[data-byl-action]' ? this : null; } };
  a.fire(item);
  assert.equal(a.recorded.nativeUpdates, 0, 'dropdown selection does not auto-submit');
  assert.equal(a.recorded.confirms, 0);
}
console.log('PASS native Completed direct WooCommerce Update, no confirmation, duplicate guard, fail-closed guards');
