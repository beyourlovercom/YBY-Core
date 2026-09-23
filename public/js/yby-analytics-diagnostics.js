(function (window, document) {
  "use strict";

  function safeUrl(value) {
    try {
      return new URL(String(value || ""), window.location && window.location.origin ? window.location.origin : "https://example.invalid");
    } catch (error) {
      return null;
    }
  }

  function getContainerId(src, expectedPath) {
    var url = safeUrl(src);
    var id;
    if (!url || url.hostname !== "www.googletagmanager.com" || url.pathname !== expectedPath) {
      return "";
    }
    id = String(url.searchParams.get("id") || "").toUpperCase();
    return /^GTM-[A-Z0-9]+$/.test(id) ? id : "";
  }

  function countIds(values) {
    var counts = {};
    values.forEach(function (id) { counts[id] = (counts[id] || 0) + 1; });
    return counts;
  }

  function duplicateIds(counts) {
    return Object.keys(counts).filter(function (id) { return counts[id] > 1; }).sort();
  }

  function inspectGtm() {
    var scripts = Array.prototype.slice.call(document.querySelectorAll("script[src]"));
    var iframes = Array.prototype.slice.call(document.querySelectorAll("iframe[src]"));
    var loaderIds = scripts.map(function (node) { return getContainerId(node.src, "/gtm.js"); }).filter(Boolean);
    var iframeIds = iframes.map(function (node) { return getContainerId(node.src, "/ns.html"); }).filter(Boolean);
    var loaderCounts = countIds(loaderIds);
    var iframeCounts = countIds(iframeIds);
    var containerIds = Array.from(new Set(loaderIds.concat(iframeIds))).sort();
    var duplicateLoaderIds = duplicateIds(loaderCounts);
    var duplicateIframeIds = duplicateIds(iframeCounts);
    var hasDuplicateInjection = duplicateLoaderIds.length > 0 || duplicateIframeIds.length > 0;
    var multipleContainers = containerIds.length > 1;
    var status = "clean";

    if (!containerIds.length) { status = "not_detected"; }
    else if (hasDuplicateInjection) { status = "duplicate"; }
    else if (multipleContainers) { status = "multiple_containers"; }

    return {
      status: status,
      loader_count: loaderIds.length,
      iframe_count: iframeIds.length,
      container_ids: containerIds,
      duplicate_loader_ids: duplicateLoaderIds,
      duplicate_iframe_ids: duplicateIframeIds,
      multiple_containers: multipleContainers,
      has_duplicate_injection: hasDuplicateInjection,
      cleanup_policy: "diagnose_only"
    };
  }

  window.YBYAnalyticsDiagnostics = window.YBYAnalyticsDiagnostics || {};
  window.YBYAnalyticsDiagnostics.inspectGtm = inspectGtm;
  window.YBYAnalyticsDiagnostics.cleanupPolicy = "diagnose_only";

  if (window.AndyAnalyticsDiagnosticsConfig && window.AndyAnalyticsDiagnosticsConfig.debug) {
    window.YBYAnalyticsDiagnostics.lastGtmInspection = inspectGtm();
  }
})(window, document);
