"use strict";

const fs = require("fs");
const path = require("path");
const vm = require("vm");

const source = fs.readFileSync(
  path.join(__dirname, "..", "public", "js", "yby-inquiry-components.js"),
  "utf8"
);
const cssSource = fs.readFileSync(
  path.join(__dirname, "..", "public", "css", "yby-inquiry-components.css"),
  "utf8"
);

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

function field(id, value, mode) {
  const attributes = { "data-yby-field-id": id };
  const wrapper = mode
    ? { getAttribute: (name) => (name === "data-yby-responsive" ? mode : null), hidden: false, setAttribute() {} }
    : null;
  return {
    value: value || "",
    type: "text",
    tagName: "INPUT",
    disabled: false,
    checked: false,
    getAttribute(name) {
      return Object.prototype.hasOwnProperty.call(attributes, name) ? attributes[name] : null;
    },
    setAttribute(name, value) {
      attributes[name] = String(value);
    },
    removeAttribute(name) {
      delete attributes[name];
    },
    closest(selector) {
      return selector === "[data-yby-responsive]" ? wrapper : null;
    }
  };
}

function runtime(mobile, contactValue, productInterest) {
  const fields = [
    field("name", "Test Name", "desktop"),
    field("contact", contactValue, "mobile"),
    field("email", "desktop@example.test", "desktop"),
    field("whatsapp", "+255 700 000 000", "desktop"),
    field("message", "Details", "mobile")
  ];
  const form = {
    getAttribute(name) {
      return {
        "data-yby-contact-requirement": "email_or_whatsapp",
        "data-yby-preset": "irrigation_quick_inquiry",
        "data-yby-form-version": "1.0",
        "data-yby-product-interest-context": productInterest || ""
      }[name] || null;
    },
    querySelectorAll(selector) {
      return selector === "[data-yby-field]" ? fields : [];
    },
    closest() {
      return null;
    }
  };
  const document = {
    readyState: "loading",
    title: "Inquiry",
    addEventListener() {},
    querySelector() { return null; },
    querySelectorAll() { return []; }
  };
  const window = {
    YBYCoreConfig: {},
    YBYInquiry: {},
    location: { href: "https://example.test/inquiry", pathname: "/inquiry", search: "" },
    matchMedia() { return { matches: mobile }; },
    addEventListener() {},
    dataLayer: []
  };
  const context = vm.createContext({ window, document, console, URLSearchParams, Object, Array, String, Promise });
  vm.runInContext(source, context, { filename: "yby-inquiry-components.js" });
  return { form, fields, inquiry: window.YBYInquiry };
}

assert(cssSource.includes("@media (min-width: 860px)"), "Desktop breakpoint contract must exist.");
assert(cssSource.includes("@media (max-width: 859px)"), "Mobile breakpoint contract must exist.");
assert(cssSource.includes("max-height: min(50vh"), "Mobile dialog must stay near half viewport height.");
assert(cssSource.includes(".yby-inquiry-modal__media,"), "Mobile media hide contract must exist.");
assert(cssSource.includes("min-height: 68px"), "Mobile inquiry details must use compact textarea height.");
assert(cssSource.includes("min-height: 46px"), "Mobile submit must use compact height.");

const mobileEmail = runtime(true, "mobile@example.test");
const emailPayload = mobileEmail.inquiry.buildPayload(mobileEmail.form);
assert(emailPayload.email === "mobile@example.test", "Combined mobile contact must map email-looking values to email.");
assert(emailPayload.whatsapp === "", "Inactive desktop WhatsApp must not pollute the mobile payload.");
assert(emailPayload.fields === undefined, "Presentation-only combined contact must not become a custom field.");
assert(emailPayload.project_details === "Message: Details", "Mobile inquiry details must use the existing project details pipeline.");

const mobileWhatsApp = runtime(true, "+255 700 123 456", "UF-2026-018 | Toyota 8FD30");
const whatsappPayload = mobileWhatsApp.inquiry.buildPayload(mobileWhatsApp.form);
assert(whatsappPayload.whatsapp === "+255 700 123 456", "Combined mobile contact must map non-email values to WhatsApp.");
assert(whatsappPayload.email === "", "WhatsApp contact must not populate email.");
assert(whatsappPayload.product_interest === "UF-2026-018 | Toyota 8FD30", "Hidden mobile product context must survive into product_interest.");

const desktop = runtime(false, "");
const desktopPayload = desktop.inquiry.buildPayload(desktop.form);
assert(desktopPayload.email === "desktop@example.test", "Desktop fields must remain active on desktop.");
assert(desktopPayload.whatsapp === "+255 700 000 000", "Desktop WhatsApp must remain active on desktop.");

console.log("PASS inquiry-modal-default-runtime-harness");
