"use strict";
/**
 * M3 email-link browser actions: pure VM/DOM mocks, ZERO browser/network/mail.
 * Complements disposable WP/MySQL REST state and token tests. No real URLs
 * or mailbox addresses; uses example.test origin and synthetic 64-char tokens.
 */
const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");
const source = fs.readFileSync("public/js/yby-newsletter.js", "utf8");
let checks = 0;
function check(condition, label) {
  assert.ok(condition, label);
  checks++;
}
function simulate(action, token, endpoint = "https://isolated.example.test/wp-json/andy-core/v1/newsletter/") {
  const handlers = new Map(), nodes = [], requests = [], navigations = [];
  const origin = "https://isolated.example.test";
  const listeners = {};
  const document = {
    addEventListener(key, fn) { listeners[key] = fn; },
    documentElement: { lang: "en-US" },
    querySelector() { return null; },
    createElement(tag) {
      const element = {
        tag, textContent: "", type: "", disabled: false, className: "",
        children: [], attributes: {},
        setAttribute(k, v) { this.attributes[k] = v; },
        appendChild(v) { this.children.push(v); },
        addEventListener(k, fn) { handlers.set(element, { key: k, fn }); }
      };
      nodes.push(element);
      return element;
    },
    body: { firstChild: null, insertBefore() {} }
  };
  const location = {
    origin, href: origin + "/?yby_newsletter_action=" + action + "&token=" + token,
    pathname: "/", search: "?yby_newsletter_action=" + action + "&token=" + token, hash: ""
  };
  const context = {
    URL, URLSearchParams, Promise, console,
    window: { YBYNewsletterEndpoint: endpoint },
    document, location,
    history: { replaceState(_a, _b, url) { navigations.push(url); } },
    fetch(url, options) {
      requests.push({ url, options });
      return Promise.resolve({
        ok: true, status: 200,
        json: () => Promise.resolve({ success: true, message: "Subscription confirmed." })
      });
    }
  };
  vm.runInNewContext(source, context);
  assert.equal(typeof listeners.DOMContentLoaded, "function");
  listeners.DOMContentLoaded();
  return { nodes, handlers, requests, navigations };
}
async function main() {
  const token = "a".repeat(64);
  const good = simulate("confirm", token);
  check(good.requests.length === 0, "Opening confirmation link never auto-sends HTTP");
  check(good.navigations.length === 1 && !good.navigations[0].includes(token),
    "Raw confirmation token removed from address bar");
  const button = good.nodes.find(n => n.tag === "button");
  check(Boolean(button) && button.textContent === "Confirm my subscription",
    "Visible human confirmation button rendered");
  check(Boolean(good.handlers.get(button)) && good.handlers.get(button).key === "click",
    "Confirmation requires explicit click handler");
  good.handlers.get(button).fn();
  check(good.requests.length === 1, "Exactly one mock HTTP request after click");
  check(good.requests[0].options.method === "POST" &&
    good.requests[0].options.credentials === "omit", "Token submission uses anonymous POST");
  check(good.requests[0].url ===
    "https://isolated.example.test/wp-json/andy-core/v1/newsletter/confirm",
    "Confirmation cannot reach external origin");
  check(JSON.parse(good.requests[0].options.body).token === token,
    "One-use token passed only to POST body");
  for (let i = 0; i < 8; i++) await Promise.resolve();
  check(button.disabled === true, "Confirm button disables after successful click");

  const unsub = simulate("unsubscribe", "b".repeat(64));
  check(unsub.requests.length === 0, "Unsubscribe link GET also has no mutation");
  const unsubButton = unsub.nodes.find(n => n.tag === "button");
  check(Boolean(unsubButton) && unsubButton.textContent === "Unsubscribe",
    "Unsubscribe is an explicit owner action");
  unsub.handlers.get(unsubButton).fn();
  check(unsub.requests.length === 1 &&
    unsub.requests[0].url.endsWith("/newsletter/unsubscribe"), "Unsubscribe POST only on click");

  const badToken = simulate("confirm", "not-valid");
  check(badToken.requests.length === 0 &&
    badToken.nodes.length === 0 && badToken.navigations.length === 0,
    "Malformed token creates no action or request");

  const malicious = simulate("confirm", token,
    "https://attacker.example.test/wp-json/andy-core/v1/newsletter/");
  check(malicious.requests.length === 0 &&
    malicious.nodes.length === 0, "Third-party REST endpoint blocked before any interaction");
  console.log("NEWSLETTER_M3_TOKEN_UI_NO_NETWORK_PASS " + checks +
    " checks; 0 real HTTP; 0 emails");
}
main().catch(err => { console.error(err); process.exitCode = 1; });
