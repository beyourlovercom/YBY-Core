"use strict";
// Portable client contract exercised without browser/network or real mail.
const fs = require("node:fs");
const vm = require("node:vm");
const assert = require("node:assert/strict");
const source = fs.readFileSync("public/js/yby-newsletter.js", "utf8");
const listeners = {};
const calls = [];
const windowObj = { YBYNewsletterEndpoint: "https://site.example/wp-json/andy-core/v1/newsletter/" };
const ctx = {
  URL, URLSearchParams, Promise, window: windowObj, console,
  location: { href: "https://site.example/newsletter/", origin: "https://site.example", pathname: "/newsletter/", search: "", hash: "" },
  history: { replaceState() {} },
  document: { addEventListener(name, cb) { listeners[name] = cb; }, documentElement: { lang: "en" }, querySelector() { return null; } },
  fetch(url, options) {
    calls.push({ url, options });
    return Promise.resolve({ ok: true, status: 200, json: () => Promise.resolve({ message: "If eligible, please check your inbox." }) });
  }
};
vm.runInNewContext(source, ctx);
assert.equal(typeof windowObj.YBYNewsletter.submit, "function");
assert.equal(typeof listeners.submit, "function");
let status = { textContent: "" }, resetCount = 0;
let consent = { checked: true }, email = { value: "synthetic@example.test", validity: { valid: true } }, button = { disabled: false };
let enabled = "0", base = "https://site.example/wp-json/andy-core/v1/newsletter/";
const form = {
  matches(selector) { return selector === "[data-yby-subscribe-contract]"; },
  getAttribute(key) { return key === "data-yby-newsletter-enabled" ? enabled : key === "data-yby-newsletter-endpoint" ? base : null; },
  querySelector(selector) {
    if (selector === "[data-yby-subscribe-status]") return status;
    if (selector.includes('name="marketing_consent"')) return consent;
    if (selector.includes('name="email"')) return email;
    if (selector.includes('button[type="submit"]')) return button;
    if (selector.includes('name="website"')) return { value: "" };
    return null;
  },
  closest() { return null; },
  reset() { resetCount += 1; }
};
let prevented = 0;
const event = { target: form, preventDefault() { prevented++; } };
function submit() { return windowObj.YBYNewsletter.submit(event); }
assert.equal(submit(), true);
assert.equal(calls.length, 0, "API must never run before approval");
assert.match(status.textContent, /not configured/i);
enabled = "1";
consent.checked = false;
assert.equal(submit(), true);
assert.equal(calls.length, 0, "unchecked consent must block all outbound fetches");
assert.match(status.textContent, /consent/i);
consent.checked = true;
email.validity.valid = false;
submit();
assert.equal(calls.length, 0, "invalid email blocked in client as well as server");
email.validity.valid = true;
base = "https://attacker.example/wp-json/andy-core/v1/newsletter/";
submit();
assert.equal(calls.length, 0, "cross-origin endpoint must never receive email");
base = "https://site.example/wp-json/andy-core/v1/newsletter/";
submit();
assert.equal(calls.length, 1, "exactly one allowed submit");
assert.equal(calls[0].url, "https://site.example/wp-json/andy-core/v1/newsletter/subscribe");
assert.equal(calls[0].options.credentials, "omit");
assert.equal(calls[0].options.method, "POST");
const sent = JSON.parse(calls[0].options.body);
assert.equal(sent.marketing_consent, "1");
assert.equal(sent.email, "synthetic@example.test");
assert.equal(sent.consent_policy, "newsletter-v1");
assert.equal(sent.website, "");
(async () => {
  for (let i = 0; i < 12; i++) await Promise.resolve();
  assert.equal(resetCount, 1, "form may reset only on confirmed API response success");
  assert.match(status.textContent, /check your inbox/i);
  assert.equal(button.disabled, false);
  assert.ok(prevented >= 5);
  assert.match(source, /history\.replaceState/);
  assert.match(source, /button\.addEventListener\("click"/);
  assert.match(source, /url\.searchParams\.set\("rest_route"/);
  assert.match(source, /post\(url, \{ token: token \}\)/);
  console.log("NEWSLETTER_M1_FRONTEND_RUNTIME_PASS 18 assertions");
})().catch(err => { console.error(err); process.exitCode = 1; });
