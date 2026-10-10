(function () {
  "use strict";
  /* Core-owned shared runtime. Never assumes a mailing provider is active. */
  function status(form, message) {
    var node = form.querySelector("[data-yby-subscribe-status]");
    if (node) node.textContent = message;
  }
  function endpoint(form, action) {
    var base = (form && form.getAttribute("data-yby-newsletter-endpoint")) ||
      window.YBYNewsletterEndpoint || "";
    if (!/^https?:\/\//.test(base)) return "";
    try {
      var url = new URL(base, location.href);
      if (url.origin !== location.origin) return "";
      if (/\/andy-core\/v1\/newsletter\/?$/.test(url.pathname)) {
        url.pathname = url.pathname.replace(/\/?$/, "/") + action;
        return url.href;
      }
      // WordPress supports REST even when pretty permalinks are disabled.
      var route = url.searchParams.get("rest_route");
      if (route === "/andy-core/v1/newsletter/" || route === "/andy-core/v1/newsletter") {
        url.searchParams.set("rest_route", "/andy-core/v1/newsletter/" + action);
        return url.href;
      }
      return "";
    } catch (e) { return ""; }
  }
  function post(url, body) {
    return fetch(url, {
      method: "POST", credentials: "omit", cache: "no-store",
      headers: { "Content-Type": "application/json", "Accept": "application/json" },
      body: JSON.stringify(body)
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (json) {
        return { ok: response.ok, message: String(json.message || ""), code: response.status };
      });
    });
  }
  function submit(event) {
    var form = event.target;
    if (!form || !form.matches("[data-yby-subscribe-contract]")) return false;
    event.preventDefault();
    if (form.getAttribute("data-yby-newsletter-enabled") !== "1") {
      status(form, "Subscribe submission is not configured yet; no mailing-list provider was called.");
      return true;
    }
    var consent = form.querySelector('input[name="marketing_consent"]');
    var emailField = form.querySelector('input[name="email"]');
    if (!emailField || !emailField.validity.valid || !consent || !consent.checked) {
      status(form, "Enter a valid email and check the optional marketing consent box.");
      return true;
    }
    var url = endpoint(form, "subscribe");
    if (!url) { status(form, "Newsletter is unavailable."); return true; }
    var submitButton = form.querySelector('button[type="submit"]');
    if (submitButton && submitButton.disabled) return true;
    if (submitButton) submitButton.disabled = true;
    status(form, "Submitting your subscription request…");
    post(url, {
      email: emailField.value,
      marketing_consent: "1",
      consent_policy: "newsletter-v1",
      source: form.closest("[data-yby-global-popup]") ? "popup" : "shortcode",
      source_page: location.pathname.slice(0, 255),
      locale: document.documentElement.lang.slice(0, 20),
      website: (form.querySelector('input[name="website"]') || {}).value || ""
    }).then(function (result) {
      status(form, result.message || (result.ok ? "Please check your inbox." : "Newsletter is unavailable."));
      if (result.ok) form.reset();
    }).catch(function () {
      status(form, "Newsletter is unavailable. Please try later.");
    }).then(function () {
      if (submitButton) submitButton.disabled = false;
    });
    return true;
  }
  document.addEventListener("submit", function (event) {
    if (submit(event)) event.stopImmediatePropagation();
  }, true);

  /* Email links do not execute confirmation or unsubscribe by visiting GET.
   * A deliberate click POSTs the single-use token. No auto-confirm via scanners.
   * Remove token from address bar after capturing it in closure memory.
   */
  document.addEventListener("DOMContentLoaded", function () {
    // Universal site landing: works even when no /newsletter/ Page exists.
    var params = new URLSearchParams(location.search);
    var action = params.get("yby_newsletter_action");
    var token = params.get("token");
    if ((action !== "confirm" && action !== "unsubscribe") ||
        !/^[a-f0-9]{64}$/.test(token || "")) return;
    var form = document.querySelector("[data-yby-subscribe-contract]");
    var url = endpoint(form, action);
    if (!url) return;
    params.delete("token"); params.delete("yby_newsletter_action");
    var cleaned = location.pathname + (params.toString() ? "?" + params.toString() : "") + location.hash;
    history.replaceState(null, "", cleaned);
    var box = document.createElement("div");
    box.className = "yby-newsletter-token-action";
    var button = document.createElement("button");
    button.type = "button";
    button.textContent = action === "confirm" ? "Confirm my subscription" : "Unsubscribe";
    var info = document.createElement("p");
    info.setAttribute("role", "status");
    info.setAttribute("aria-live", "polite");
    box.appendChild(button); box.appendChild(info);
    // A dedicated accessible action surface even on a generic homepage.
    box.setAttribute("role", "region");
    box.setAttribute("aria-label", "Newsletter " + action);
    if (form && form.parentNode) form.parentNode.insertBefore(box, form);
    else document.body.insertBefore(box, document.body.firstChild);
    button.addEventListener("click", function () {
      button.disabled = true;
      info.textContent = "Processing…";
      post(url, { token: token }).then(function (res) {
        info.textContent = res.message || (res.ok ? "Request completed." : "Request failed.");
        if (!res.ok) button.disabled = false;
      }).catch(function () {
        info.textContent = "Request failed. Please try again.";
        button.disabled = false;
      });
    });
  });
  window.YBYNewsletter = { submit: submit };
}());
