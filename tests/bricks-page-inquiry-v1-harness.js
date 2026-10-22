/* Synthetic page-scoped regression only: no WordPress, leads or network. */
"use strict";
const assert = require("assert");
const fs = require("fs");
const path = require("path");
const vm = require("vm");
const script = fs.readFileSync(path.join(__dirname, "../public/js/yby-bricks-page-inquiry.js"), "utf8");

function item(attrs) {
  return {
    attrs: Object.assign({}, attrs),
    classes: [],
    styles: {},
    hasAttribute(k) { return Object.prototype.hasOwnProperty.call(this.attrs, k); },
    getAttribute(k) { return this.attrs[k] || null; },
    setAttribute(k, v) { this.attrs[k] = v; },
    classList: { add() {} },
    style: { setProperty() {} }
  };
}
function run(options) {
  const marker = item({
    "data-yby-modal-id": "page-quote-215",
    "data-yby-cta-routing": options.routing || "page_all",
    "data-yby-modal-width": "960",
    "data-yby-modal-accent": "#2e7d32",
    "data-yby-mobile-image": options.mobileImage || "hide"
  });
  const modal = item();
  modal.matches = selector => selector === "[data-yby-inquiry-modal]";
  modal.classList.add = name => modal.classes.push(name);
  modal.style.setProperty = (k,v) => { modal.styles[k] = v; };
  const defaultCta = item();
  const existingCta = item({ "data-yby-modal-open": "other-modal" });
  const hrefCta = item();
  const document = {
    readyState: "complete",
    querySelectorAll(selector) {
      if (selector === "[data-yby-inquiry-page-module]") { return options.noModule ? [] : [marker]; }
      if (selector === '[data-yby-inquiry-trigger], a[href="#yby-inquiry"]') {
        return [defaultCta, existingCta, hrefCta];
      }
      return [];
    },
    getElementById(id) { return id === "page-quote-215" ? modal : null; }
  };
  vm.runInNewContext(script, { document });
  return { marker, modal, defaultCta, existingCta, hrefCta };
}
const standard = run({});
assert.equal(standard.defaultCta.getAttribute("data-yby-modal-open"), "page-quote-215");
assert.equal(standard.hrefCta.getAttribute("data-yby-modal-open"), "page-quote-215");
assert.equal(standard.existingCta.getAttribute("data-yby-modal-open"), "other-modal");
assert(standard.modal.classes.includes("yby-page-inquiry-owned"));
assert.equal(standard.modal.styles["--yby-inquiry-max-width"], "960px");
assert.equal(standard.modal.styles["--yby-inquiry-primary"], "#2e7d32");
assert(!standard.modal.classes.includes("yby-page-inquiry-mobile-image"));
assert.equal(run({ routing: "explicit" }).defaultCta.getAttribute("data-yby-modal-open"), null);
assert.equal(run({ noModule: true }).defaultCta.getAttribute("data-yby-modal-open"), null);
assert(run({ mobileImage: "show" }).modal.classes.includes("yby-page-inquiry-mobile-image"));
console.log("BRICKS_PAGE_INQUIRY_V1_ROUTING_PASS tests=10 no_leads=1 cross_page_mutation=0");

// Native Bricks mobile layout must not inherit oversized inputs or force an
// 88dvh sheet for a two-field form. Keep global popups out of this rule.
const nativeCss = fs.readFileSync(path.join(__dirname, "../public/css/yby-bricks-page-inquiry.css"), "utf8");
const compactBlock = nativeCss.split("/* Page-owned compact mobile fields:")[1];
assert(compactBlock, "scoped native mobile layout contract missing");
assert(compactBlock.includes(".yby-inquiry-modal.yby-page-inquiry-owned"), "must stay native-only");
assert(compactBlock.includes("height: auto !important"), "short forms must not fill 88dvh");
assert(compactBlock.includes("max-height: min(88dvh"), "long forms must cap screen height");
assert(compactBlock.includes("grid-auto-rows: max-content !important"), "grid rows must not stretch");
assert(compactBlock.includes("height: 44px !important"), "mobile input touch size contract");
assert(compactBlock.includes("min-height: 44px !important"), "mobile input min contract");
assert(compactBlock.includes(".yby-inquiry-field__error:empty"), "empty errors must not reserve space");
assert(compactBlock.includes(".yby-inquiry-form__status:empty"), "empty status must not reserve space");
console.log("BRICKS_M14_COMPACT_CSS_PASS scoped=native_only input=44px natural_height=1 scroll=existing");
