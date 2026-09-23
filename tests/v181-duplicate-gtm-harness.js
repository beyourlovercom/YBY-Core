const fs = require("fs");
const vm = require("vm");
const path = require("path");
const source = fs.readFileSync(path.join(__dirname, "..", "public", "js", "yby-analytics-diagnostics.js"), "utf8");
function assert(ok, message) { if (!ok) { throw new Error("FAIL: " + message); } }
function inspect(scriptSrcs, iframeSrcs) {
  const window = { location: { origin: "https://example.com" }, AndyAnalyticsDiagnosticsConfig: { debug: false } };
  const document = {
    querySelectorAll(selector) {
      if (selector === "script[src]") return scriptSrcs.map(src => ({ src }));
      if (selector === "iframe[src]") return iframeSrcs.map(src => ({ src }));
      return [];
    }
  };
  window.window = window;
  const context = { window, document, URL, Set, Array, Object, String, RegExp };
  vm.createContext(context);
  vm.runInContext(source, context);
  return window.YBYAnalyticsDiagnostics.inspectGtm();
}
let r = inspect(["https://www.googletagmanager.com/gtm.js?id=GTM-ABC123"], []);
assert(r.status === "clean", "one loader should be clean");
assert(r.loader_count === 1, "one loader count");
assert(r.container_ids.join(",") === "GTM-ABC123", "container id");
assert(!r.has_duplicate_injection, "one loader not duplicate");
r = inspect([
  "https://www.googletagmanager.com/gtm.js?id=GTM-ABC123",
  "https://www.googletagmanager.com/gtm.js?id=GTM-ABC123"
], []);
assert(r.status === "duplicate", "duplicate loader status");
assert(r.duplicate_loader_ids.join(",") === "GTM-ABC123", "duplicate loader id");
r = inspect([
  "https://www.googletagmanager.com/gtm.js?id=GTM-ABC123",
  "https://www.googletagmanager.com/gtm.js?id=GTM-XYZ789"
], []);
assert(r.status === "multiple_containers", "multiple container status");
assert(r.multiple_containers, "multiple container flag");
r = inspect([], []);
assert(r.status === "not_detected", "no GTM status");
assert(r.cleanup_policy === "diagnose_only", "must never auto-remove tags");
console.log("PASS v181-duplicate-gtm-harness");
