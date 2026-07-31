"use strict";

const fs = require("fs");
const path = require("path");
const vm = require("vm");

const source = fs.readFileSync(
  path.join(__dirname, "..", "public", "js", "yby-lead-sdk.js"),
  "utf8"
);

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

function createRuntime() {
  const requests = [];
  const session = new Map();
  const dataLayer = [];
  const window = {
    YBYCoreConfig: {
      caseIdBrandCode: "BCB",
      thankYouUrl: "/thank-you/"
    },
    dataLayer,
    location: {
      href: "https://example.test/inquiry/"
    },
    sessionStorage: {
      getItem(key) {
        return session.has(key) ? session.get(key) : null;
      },
      setItem(key, value) {
        session.set(String(key), String(value));
      }
    },
    fetch(url, options) {
      requests.push({
        url,
        options,
        body: JSON.parse(options.body)
      });

      return Promise.resolve({
        ok: true,
        status: 200,
        text() {
          return Promise.resolve(
            JSON.stringify({
              success: true,
              data: {
                lead_id: requests.length,
                case_id: "YBY-BCB-20260731-ABC123",
                status: "new"
              }
            })
          );
        }
      });
    }
  };

  const context = vm.createContext({
    window,
    document: {},
    console,
    Date,
    JSON,
    Math,
    Promise,
    String,
    Object,
    Array,
    encodeURIComponent
  });

  vm.runInContext(source, context, { filename: "yby-lead-sdk.js" });

  return {
    dataLayer,
    requests,
    sdk: window.YBYLead
  };
}

async function submit(runtime, overrides) {
  const payload = Object.assign(
    {
      name: "Internal SDK Test",
      email: "sdk-test@example.test",
      source_preset: "bottle_oem_inquiry"
    },
    overrides
  );

  await runtime.sdk.submit(payload);
  return runtime.requests[runtime.requests.length - 1].body;
}

(async function run() {
  const runtime = createRuntime();
  const fields = {
    estimated_quantity: "12,000-29,999",
    project_path: "new_custom_mold"
  };
  const projectSummary = {
    country: "Must remain governed"
  };

  const bottle = await submit(runtime, {
    page_profile: "Bottle_OEM",
    fields,
    project_summary: projectSummary,
    project_summary_confirmed_fields: ["country"]
  });

  assert(bottle.page_profile === "bottle_oem", "page_profile must survive validation and normalization.");
  assert(bottle.source_preset === "bottle_oem_inquiry", "source_preset must remain unchanged.");
  assert(
    JSON.stringify(bottle.fields) === JSON.stringify(fields),
    "Structured fields must remain unchanged."
  );
  assert(
    bottle.project_summary.country === projectSummary.country,
    "Confirmed project_summary values must remain unchanged."
  );
  assert(
    !Object.prototype.hasOwnProperty.call(bottle.project_summary, "page_profile"),
    "page_profile must not enter project_summary."
  );
  assert(
    !Object.prototype.hasOwnProperty.call(bottle.fields, "page_profile"),
    "page_profile must not enter fields."
  );
  assert(runtime.dataLayer.length === 0, "page_profile transport must not create tracking events.");

  const sanitized = await submit(runtime, {
    page_profile: "<Bottle OEM profile!>"
  });
  assert(
    sanitized.page_profile === "bottleoemprofile",
    "Markup, spaces, and unsupported characters must be removed."
  );

  const missing = await submit(runtime, {
    source_preset: "bottle_wholesale_inquiry"
  });
  assert(
    !Object.prototype.hasOwnProperty.call(missing, "page_profile"),
    "Missing page_profile must remain absent."
  );
  assert(
    missing.source_preset === "bottle_wholesale_inquiry",
    "Unrestricted presets must remain compatible."
  );

  const irrigation = await submit(runtime, {
    source_preset: "irrigation_quick_inquiry",
    page_profile: "Irrigation"
  });
  assert(irrigation.page_profile === "irrigation", "Existing irrigation profiles must remain compatible.");
  assert(
    runtime.requests.every((request) => request.url === "/wp-json/yby/v1/leads"),
    "Every request must use the canonical Lead REST endpoint."
  );
  assert(
    runtime.requests[0].body.page_profile === "bottle_oem",
    "The fetch JSON body must contain the sanitized page_profile."
  );

  console.log("PASS lead-sdk-page-profile-harness");
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
