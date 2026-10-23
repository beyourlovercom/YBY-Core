"use strict";

const assert = require("assert");
const fs = require("fs");
const path = require("path");
const vm = require("vm");

const source = fs.readFileSync(
  path.join(__dirname, "..", "public", "assets", "js", "yby-core-public.js"),
  "utf8"
);

function extract(pattern, label) {
  const match = source.match(pattern);
  assert(match, "Missing runtime " + label);
  return match[0];
}

const irrigationHelper = extract(
  /  function isIrrigationThankYouPage\(\) \{[\s\S]*?\n  \}/,
  "irrigation route guard"
);
const leadHandler = extract(
  /  window\.YBYThankYou\.trackGenerateLeadOnce = function \(\) \{[\s\S]*?\n  \};/,
  "thank-you conversion handler"
);

const CASE_ID = "YBY-IRR-20261023-ABC234";
const OTHER_ID = "YBY-IRR-20261023-DEF345";

function runCase(name, options) {
  const opts = options || {};
  const session = new Map(Object.entries(opts.session || {}));
  const emissions = [];
  const loc = {
    pathname: opts.pathname || "/lp/thank-you-irrigation-solution/"
  };
  const win = {
    location: loc,
    YBYLead: { getCaseId: () => opts.caseId === undefined ? CASE_ID : opts.caseId },
    YBYTracking: {
      generateLead(payload) {
        emissions.push(payload);
        return opts.trackingSuccess !== false;
      }
    },
    YBYThankYou: {}
  };
  const context = {
    window: win,
    isBottleThankYouPage: () => loc.pathname === "/lp/thank-you-glass-bottle-oem/",
    isValidCaseId: v => /^YBY-[A-Z0-9]+-\d{8}-[A-HJ-NP-Z2-9]{6}$/.test(v || ""),
    normalizeCaseId: v => String(v || "").toUpperCase(),
    getQueryParam: key => key === "case_id" ? (opts.urlCaseId || "") : "",
    getSessionItem: key => session.get(key) || "",
    setSessionItem: (key, value) => session.set(key, value)
  };
  vm.createContext(context);
  vm.runInContext(irrigationHelper + "\n" + leadHandler, context);
  const first = win.YBYThankYou.trackGenerateLeadOnce();
  const second = opts.checkRepeat ? win.YBYThankYou.trackGenerateLeadOnce() : undefined;
  return { name, first, second, session, emissions };
}

const tests = [
  ["direct URL is not a lead", { urlCaseId: CASE_ID }, false, 0],
  ["wrong confirmed Case ID", { urlCaseId: CASE_ID, session: { yby_confirmed_lead_case_id: OTHER_ID } }, false, 0],
  ["wrong URL Case ID", { urlCaseId: OTHER_ID, session: { yby_confirmed_lead_case_id: CASE_ID } }, false, 0],
  ["no Case ID", { caseId: "", urlCaseId: "", session: { yby_confirmed_lead_case_id: CASE_ID } }, false, 0],
  ["confirmed valid lead", { urlCaseId: CASE_ID, session: { yby_confirmed_lead_case_id: CASE_ID }, checkRepeat: true }, true, 1],
  ["pre-counted lead", { urlCaseId: CASE_ID, session: { yby_confirmed_lead_case_id: CASE_ID, ["yby_generate_lead_counted_" + CASE_ID]: "1" } }, false, 0],
  ["tracking failure leaves no count flag", { urlCaseId: CASE_ID, session: { yby_confirmed_lead_case_id: CASE_ID }, trackingSuccess: false }, false, 1],
  ["Bottle still requires preset", { pathname: "/lp/thank-you-glass-bottle-oem/", urlCaseId: CASE_ID, session: { yby_confirmed_lead_case_id: CASE_ID } }, false, 0],
  ["Bottle existing good flow", { pathname: "/lp/thank-you-glass-bottle-oem/", urlCaseId: CASE_ID, session: { yby_confirmed_lead_case_id: CASE_ID, yby_confirmed_lead_preset: "bottle_oem_inquiry" } }, true, 1],
  ["Other legacy thank-you unchanged", { pathname: "/lp/thank-you/", urlCaseId: CASE_ID }, true, 1]
];

tests.forEach(([name, options, expectedResult, expectedEvents]) => {
  const result = runCase(name, options);
  assert.strictEqual(result.first, expectedResult, name + ": return");
  assert.strictEqual(result.emissions.length, expectedEvents, name + ": emission count");
  if (options.checkRepeat) {
    assert.strictEqual(result.second, false, name + ": repeat return");
    assert.strictEqual(result.emissions.length, expectedEvents, name + ": repeat emissions");
  }
  if (options.trackingSuccess === false) {
    assert(!result.session.has("yby_generate_lead_counted_" + CASE_ID), name + ": false count flag");
  }
  console.log("PASS " + name);
});

console.log("PASS irrigation-confirmed-lead-guard-harness");
