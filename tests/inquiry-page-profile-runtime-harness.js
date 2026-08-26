"use strict";

const fs = require("fs");
const path = require("path");
const vm = require("vm");

const source = fs.readFileSync(
  path.join(__dirname, "..", "public", "js", "yby-inquiry-components.js"),
  "utf8"
);

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

function createElement(attributes, properties) {
  const values = Object.assign({}, attributes || {});

  return Object.assign(
    {
      checked: false,
      value: "",
      type: "text",
      tagName: "INPUT",
      getAttribute(name) {
        return Object.prototype.hasOwnProperty.call(values, name) ? values[name] : null;
      },
      setAttribute(name, value) {
        values[name] = String(value);
      },
      removeAttribute(name) {
        delete values[name];
      }
    },
    properties || {}
  );
}

function createRuntime(formProfile) {
  const fields = [
    createElement({ "data-yby-field-id": "name" }, { value: "PRELAUNCH CORE UAT" }),
    createElement({ "data-yby-field-id": "project_path" }, { value: "new_custom_mold", tagName: "SELECT", type: "select-one" }),
    createElement({ "data-yby-field-id": "selected_components" }, { value: "Bottle, closure, decoration", tagName: "TEXTAREA", type: "textarea" })
  ];
  const formAttributes = {
    "data-yby-preset": "bottle_oem_inquiry",
    "data-yby-source-page": "glass-bottle-oem",
    "data-yby-form-version": "1.0"
  };

  if (formProfile !== undefined) {
    formAttributes["data-yby-page-profile"] = formProfile;
  }

  const modal = createElement(
    { "data-yby-source-component": "inquiry_modal" },
    { id: "bottle-oem-inquiry" }
  );
  const form = createElement(formAttributes, {
    querySelectorAll(selector) {
      return selector === "[data-yby-field]" ? fields : [];
    },
    closest(selector) {
      return selector === "[data-yby-inquiry-modal]" ? modal : null;
    }
  });
  const document = {
    readyState: "loading",
    title: "Glass Bottle OEM",
    addEventListener() {},
    querySelector() {
      return null;
    },
    querySelectorAll() {
      return [];
    }
  };
  const window = {
    YBYCoreConfig: {},
    YBYPageProfile: { profileId: "bottle_oem" },
    YBYInquiry: {},
    location: {
      href: "https://ybybottle.com/lp/glass-bottle-oem/?utm_source=internal_qa&utm_medium=core_e2e_uat&utm_campaign=bottle_oem_final_acceptance#yby-inquiry",
      pathname: "/lp/glass-bottle-oem/",
      search: "?utm_source=internal_qa&utm_medium=core_e2e_uat&utm_campaign=bottle_oem_final_acceptance"
    },
    addEventListener() {},
    dataLayer: []
  };
  const context = vm.createContext({
    window,
    document,
    console,
    URLSearchParams,
    Object,
    Array,
    String,
    Promise
  });

  vm.runInContext(source, context, { filename: "yby-inquiry-components.js" });

  return { form, window };
}

const fallbackRuntime = createRuntime();
const payload = fallbackRuntime.window.YBYInquiry.buildPayload(fallbackRuntime.form);

assert(payload.page_profile === "bottle_oem", "Runtime page profile must fill a missing form profile.");
assert(payload.source_preset === "bottle_oem_inquiry", "Preset must remain in the payload.");
assert(payload.source_page === "glass-bottle-oem", "Source page must remain in the payload.");
assert(payload.utm_source === "internal_qa", "UTM source must remain in the payload.");
assert(payload.fields.project_path === "new_custom_mold", "Project path must remain a structured field.");
assert(payload.fields.selected_components === "Bottle, closure, decoration", "Selected components must remain structured fields.");

const explicitRuntime = createRuntime("explicit_profile");
assert(
  explicitRuntime.window.YBYInquiry.buildPayload(explicitRuntime.form).page_profile === "explicit_profile",
  "An explicit form profile must override the Runtime page profile."
);

assert(source.includes('sourcePage: safeString(') && source.includes('form.setAttribute("data-yby-source-page", request.sourcePage)'), "Inquiry open context must preserve sourcePage onto the form.");

console.log("PASS inquiry-page-profile-runtime-harness");
