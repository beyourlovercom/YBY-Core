(function () {
  "use strict";
  var openPopup = null, delegatedInquiryModal = null, lastTrigger = null, context = {};
  function focusable(root) { return root.querySelectorAll("a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex='-1'])"); }
  function inquiryIsOpen() { return delegatedInquiryModal && !delegatedInquiryModal.hidden && delegatedInquiryModal.getAttribute("aria-hidden") !== "true" && delegatedInquiryModal.classList.contains("is-open"); }
  function closeDelegatedInquiry(reason) {
    if (!delegatedInquiryModal) return;
    if (inquiryIsOpen() && window.YBYInquiry && typeof window.YBYInquiry.close === "function") {
      window.YBYInquiry.close(delegatedInquiryModal, reason || "global_popup");
    }
    delegatedInquiryModal = null;
  }
  function close() {
    if (!openPopup) { closeDelegatedInquiry("global_popup"); return; }
    openPopup.hidden = true; openPopup.setAttribute("aria-hidden", "true"); document.body.classList.remove("yby-popup-open"); if (lastTrigger && lastTrigger.focus) lastTrigger.focus(); openPopup = null;
  }
  function open(type, trigger) {
    if (type === "inquiry") {
      if (!window.YBYInquiry || typeof window.YBYInquiry.open !== "function") return false;
      close();
      var sourcePage = trigger && trigger.getAttribute("data-yby-source-page");
      sourcePage = sourcePage || location.pathname;
      var inquiryModal = window.YBYInquiry.open({ modalId: trigger && trigger.getAttribute("data-yby-modal-open"), source: trigger && trigger.getAttribute("data-yby-source"), sourcePage: sourcePage, profile: trigger && trigger.getAttribute("data-yby-page-profile"), trigger: trigger }, trigger);
      delegatedInquiryModal = inquiryModal || null;
      return !!inquiryModal;
    }
    var node = document.querySelector('[data-yby-global-popup="' + type + '"]') || document.getElementById("yby-global-" + type + "-popup");
    if (!node) return false;
    close(); closeDelegatedInquiry("global_popup"); openPopup = node; lastTrigger = trigger || document.activeElement;
    context = { page_profile: trigger && trigger.getAttribute("data-yby-page-profile") || "", source: trigger && trigger.getAttribute("data-yby-source") || "", source_page: trigger && trigger.getAttribute("data-yby-source-page") || location.pathname, trigger_source: trigger && (trigger.getAttribute("data-yby-trigger-source") || trigger.getAttribute("data-yby-source")) || "cta", product: trigger && trigger.getAttribute("data-yby-product") || "", campaign: trigger && trigger.getAttribute("data-yby-campaign") || "", country: trigger && trigger.getAttribute("data-yby-country") || "" };
    node.dataset.ybyPopupContext = JSON.stringify(context); node.hidden = false; node.setAttribute("aria-hidden", "false"); document.body.classList.add("yby-popup-open"); var first = focusable(node); if (first.length) first[0].focus(); return true;
  }
  document.addEventListener("click", function (event) { var trigger = event.target.closest("[data-yby-popup-open]"); if (trigger && open(trigger.getAttribute("data-yby-popup-open"), trigger)) event.preventDefault(); if (event.target.closest("[data-yby-popup-close]") && openPopup) close(); });
  document.addEventListener("keydown", function (event) {
    if (!openPopup) return;
    if (event.key === "Escape") { event.preventDefault(); close(); return; }
    if (event.key !== "Tab") return;
    var dialog = openPopup.querySelector(".yby-global-popup__dialog");
    var items = dialog ? focusable(dialog) : [];
    if (!items.length) { event.preventDefault(); if (dialog) dialog.focus(); return; }
    if (event.shiftKey && document.activeElement === items[0]) { event.preventDefault(); items[items.length - 1].focus(); }
    else if (!event.shiftKey && document.activeElement === items[items.length - 1]) { event.preventDefault(); items[0].focus(); }
  });
  document.addEventListener("submit", function (event) { if (event.target.matches("[data-yby-subscribe-contract]")) { event.preventDefault(); event.target.querySelector("[data-yby-subscribe-status]").textContent = "Subscribe submission is not configured yet; no mailing-list provider was called."; } });
  function previewFromNavigation() { var query = new URLSearchParams(location.search).get("yby_popup_preview"); var hash = location.hash.replace(/^#/, ""); var requested = query || (hash.indexOf("yby-popup=") === 0 ? hash.split("=")[1] : hash === "yby-global-inquiry-popup" ? "inquiry" : hash === "yby-global-subscribe-popup" ? "subscribe" : ""); if (requested !== "inquiry" && requested !== "subscribe") return; var trigger = document.createElement("button"); trigger.type = "button"; trigger.setAttribute("data-yby-source", "admin_preview"); trigger.setAttribute("data-yby-source-page", location.pathname); if (requested === "inquiry") trigger.setAttribute("data-yby-modal-open", "yby-global-inquiry-popup"); open(requested, trigger); }
  document.addEventListener("DOMContentLoaded", previewFromNavigation);
  window.YBYPopup = { open: open, close: close, getContext: function () { return Object.assign({}, context); } };
}());
