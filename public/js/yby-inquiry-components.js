(function () {
  "use strict";

  var runtime = window.YBYCoreConfig || {};
  var inquiry = window.YBYInquiry || {};
  var activeModal = null;
  var activeTrigger = null;
  var listenersBound = false;
  var stickyListenersBound = false;
  var CORE_FIELD_IDS = {
    name: true,
    company: true,
    email: true,
    whatsapp: true,
    country: true,
    product_interest: true,
    quantity: true,
    message: true
  };

  var FOCUSABLE_SELECTOR = [
    "a[href]",
    "button:not([disabled])",
    "input:not([disabled]):not([type='hidden'])",
    "select:not([disabled])",
    "textarea:not([disabled])",
    "[tabindex]:not([tabindex='-1'])"
  ].join(", ");

  function safeString(value, maxLength) {
    return String(value || "")
      .replace(/[<>]/g, "")
      .trim()
      .substring(0, maxLength || 240);
  }

  function getModalById(modalId) {
    var safeId = safeString(modalId, 120);

    if (!safeId) {
      return null;
    }

    return document.getElementById(safeId);
  }

  function getDialog(modal) {
    return modal ? modal.querySelector(".yby-inquiry-modal__dialog") : null;
  }

  function getForm(modal) {
    return modal ? modal.querySelector("[data-yby-inquiry-form]") : null;
  }

  function getDefaultModal() {
    return document.querySelector("[data-yby-inquiry-modal]");
  }

  function getClosestProfile(trigger) {
    var root = trigger ? trigger.closest("[data-yby-page-profile]") : null;

    return safeString(
      (trigger ? trigger.getAttribute("data-yby-page-profile") : "") ||
        (root ? root.getAttribute("data-yby-page-profile") : ""),
      100
    );
  }

  function resolveOpenRequest(modalOrOptions, trigger) {
    var options = {};
    var modalId = "";
    var modal;

    if (modalOrOptions && typeof modalOrOptions === "object" && !modalOrOptions.nodeType) {
      options = modalOrOptions;
    } else {
      options.modalId = modalOrOptions;
      options.trigger = trigger;
    }

    trigger = options.trigger || trigger || null;
    modalId = safeString(
      options.modalId ||
        (trigger ? trigger.getAttribute("data-yby-modal-open") : "") ||
        (trigger ? trigger.getAttribute("data-yby-inquiry-target") : ""),
      120
    );
    modal = modalId ? getModalById(modalId) : getDefaultModal();

    return {
      modal: modal,
      trigger: trigger,
      source: safeString(options.source || (trigger ? trigger.getAttribute("data-yby-source") : ""), 120),
      profile: safeString(options.profile || getClosestProfile(trigger), 100)
    };
  }

  function applyTriggerContext(form, request) {
    if (!form || !request) {
      return;
    }

    if (request.source) {
      form.setAttribute("data-yby-trigger-source", request.source);
    } else {
      form.removeAttribute("data-yby-trigger-source");
    }

    if (request.profile) {
      form.setAttribute("data-yby-page-profile", request.profile);
    } else {
      form.removeAttribute("data-yby-page-profile");
    }
  }

  function getFocusableElements(modal) {
    return Array.prototype.slice
      .call((modal || document).querySelectorAll(FOCUSABLE_SELECTOR))
      .filter(function (element) {
        if (!element) {
          return false;
        }

        if (element.disabled || element.hidden) {
          return false;
        }

        if (element.getAttribute("aria-hidden") === "true") {
          return false;
        }

        return element.offsetParent !== null || element === document.activeElement;
      });
  }

  function isSdkAvailable() {
    return !!(
      window.YBYLead &&
      typeof window.YBYLead.submit === "function" &&
      typeof window.YBYLead.redirectToThankYou === "function"
    );
  }

  function setFormGeneralError(form, message, errorType) {
    var errorNode = form ? form.querySelector("[data-yby-inquiry-error]") : null;

    if (!errorNode) {
      return;
    }

    if (message) {
      errorNode.textContent = safeString(message, 240);
      errorNode.hidden = false;
      errorNode.setAttribute("data-yby-error-type", safeString(errorType || "general", 80));
      return;
    }

    errorNode.textContent = "";
    errorNode.hidden = true;
    errorNode.removeAttribute("data-yby-error-type");
  }

  function setFormStatus(form, message) {
    var statusNode = form ? form.querySelector("[data-yby-inquiry-status]") : null;

    if (!statusNode) {
      return;
    }

    statusNode.textContent = safeString(message, 240);
  }

  function setFieldError(field, message) {
    var fieldId = field ? safeString(field.getAttribute("data-yby-field-id"), 80) : "";
    var form = field ? field.form : null;
    var errorNode = form && fieldId ? form.querySelector('[data-yby-field-error="' + fieldId + '"]') : null;

    if (!field) {
      return;
    }

    if (message) {
      field.setAttribute("aria-invalid", "true");
      if (errorNode) {
        errorNode.textContent = safeString(message, 200);
      }
      return;
    }

    field.removeAttribute("aria-invalid");
    if (errorNode) {
      errorNode.textContent = "";
    }
  }

  function getFieldsByName(form) {
    var fields = {};

    Array.prototype.slice.call(form.querySelectorAll("[data-yby-field]")).forEach(function (field) {
      var fieldId = safeString(field.getAttribute("data-yby-field-id"), 80);

      if (fieldId) {
        fields[fieldId] = field;
      }
    });

    return fields;
  }

  function normalizeWhatsApp(value) {
    return safeString(value, 50);
  }

  function looksLikeEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(safeString(value, 150));
  }

  function looksLikeWhatsApp(value) {
    return /^[+()\d\s-]{6,}$/.test(normalizeWhatsApp(value));
  }

  function getQueryParam(name) {
    try {
      return new URLSearchParams(window.location.search || "").get(name) || "";
    } catch (error) {
      return "";
    }
  }

  function stripHash(url) {
    return safeString(String(url || "").split("#")[0], 1000);
  }

  function buildProjectDetails(payload) {
    var lines = [];

    if (payload.farm_size) {
      lines.push("Farm Size: " + payload.farm_size);
    }

    if (payload.customization) {
      lines.push("Customization Requirements: " + payload.customization);
    }

    if (payload.message) {
      lines.push("Message: " + payload.message);
    }

    return safeString(lines.join("\n"), 3000);
  }

  function getSourcePage(form, modal) {
    return safeString(
      form.getAttribute("data-yby-source-page") ||
        (modal ? modal.getAttribute("data-yby-source-page") : "") ||
        document.title ||
        window.location.pathname,
      255
    );
  }

  function getTrackingPayload(form, extra) {
    var modal = form.closest("[data-yby-inquiry-modal]");
    var base = {
      source_component: safeString(modal ? modal.getAttribute("data-yby-source-component") : "inquiry_modal", 80),
      source_preset: safeString(form.getAttribute("data-yby-preset"), 80),
      source_page: getSourcePage(form, modal),
      modal_id: safeString(modal ? modal.id : "", 120),
      form_version: safeString(form.getAttribute("data-yby-form-version"), 80),
      trigger_source: safeString(form.getAttribute("data-yby-trigger-source"), 120),
      page_profile: safeString(form.getAttribute("data-yby-page-profile"), 100)
    };

    if (extra && typeof extra === "object") {
      Object.keys(extra).forEach(function (key) {
        base[key] = safeString(extra[key], key === "case_id" ? 80 : 180);
      });
    }

    return base;
  }

  function pushTracking(form, eventName, extra) {
    var payload = getTrackingPayload(form, extra || {});

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(
      Object.assign(
        {
          event: safeString(eventName, 80)
        },
        payload
      )
    );
  }

  function enableFormIfReady(form) {
    var submitButton = form.querySelector("[data-yby-inquiry-submit]");

    if (!submitButton) {
      return;
    }

    if (isSdkAvailable()) {
      submitButton.disabled = false;
      setFormGeneralError(form, "", "");
      setFormStatus(form, "");
      return;
    }

    submitButton.disabled = true;
    setFormGeneralError(form, "Inquiry form is temporarily unavailable. Please try again later.", "sdk_unavailable");
  }

  function lockBodyScroll() {
    document.body.classList.add("yby-inquiry-modal-open");
  }

  function unlockBodyScrollIfNeeded() {
    if (!document.querySelector(".yby-inquiry-modal.is-open")) {
      document.body.classList.remove("yby-inquiry-modal-open");
    }
  }

  function markStartIfNeeded(form) {
    if (!form || form.getAttribute("data-yby-started") === "true") {
      return;
    }

    form.setAttribute("data-yby-started", "true");
    pushTracking(form, "yby_inquiry_start");
  }

  function setSubmitting(form, isSubmitting) {
    var submitButton = form.querySelector("[data-yby-inquiry-submit]");

    if (isSubmitting) {
      form.setAttribute("aria-busy", "true");
      form.setAttribute("data-yby-submitting", "true");
      if (submitButton) {
        submitButton.disabled = true;
      }
      return;
    }

    form.removeAttribute("aria-busy");
    form.setAttribute("data-yby-submitting", "false");
    enableFormIfReady(form);
  }

  function validateForm(form) {
    var fields = getFieldsByName(form);
    var errors = {};
    var contactRequirement = safeString(form.getAttribute("data-yby-contact-requirement"), 80) || "none";
    var firstInvalid = null;

    Object.keys(fields).forEach(function (fieldId) {
      setFieldError(fields[fieldId], "");
    });

    setFormGeneralError(form, "", "");

    Object.keys(fields).forEach(function (fieldId) {
      var field = fields[fieldId];
      var type = safeString(field.type || field.tagName.toLowerCase(), 40);
      var value = type === "checkbox" ? (field.checked ? "1" : "") : safeString(field.value, 3000);

      if (field.required) {
        if (type === "checkbox" && !field.checked) {
          errors[fieldId] = "This field is required.";
        } else if (!value) {
          errors[fieldId] = "This field is required.";
        }
      }

      if (!errors[fieldId] && fieldId === "email" && value && !looksLikeEmail(value)) {
        errors[fieldId] = "Please enter a valid email address.";
      }

      if (!errors[fieldId] && fieldId === "whatsapp" && value && !looksLikeWhatsApp(value)) {
        errors[fieldId] = "Please enter a valid WhatsApp number.";
      }
    });

    if (contactRequirement === "email") {
      if (!fields.email || !looksLikeEmail(fields.email.value || "")) {
        errors.email = fields.email && safeString(fields.email.value, 150) ? "Please enter a valid email address." : "Email is required.";
      }
    } else if (contactRequirement === "whatsapp") {
      if (!fields.whatsapp || !looksLikeWhatsApp(fields.whatsapp.value || "")) {
        errors.whatsapp = fields.whatsapp && safeString(fields.whatsapp.value, 50) ? "Please enter a valid WhatsApp number." : "WhatsApp is required.";
      }
    } else if (contactRequirement === "email_or_whatsapp") {
      var emailValue = fields.email ? safeString(fields.email.value, 150) : "";
      var whatsappValue = fields.whatsapp ? safeString(fields.whatsapp.value, 50) : "";
      var emailValid = emailValue && looksLikeEmail(emailValue);
      var whatsappValid = whatsappValue && looksLikeWhatsApp(whatsappValue);

      if (!emailValid && !whatsappValid) {
        errors.email = emailValue ? "Please enter a valid email address." : "Email or WhatsApp is required.";
        errors.whatsapp = whatsappValue ? "Please enter a valid WhatsApp number." : "Email or WhatsApp is required.";
      }
    }

    Object.keys(errors).forEach(function (fieldId) {
      if (!fields[fieldId]) {
        return;
      }

      setFieldError(fields[fieldId], errors[fieldId]);

      if (!firstInvalid) {
        firstInvalid = fields[fieldId];
      }
    });

    if (firstInvalid) {
      firstInvalid.focus();
    }

    return {
      valid: Object.keys(errors).length === 0,
      errors: errors,
      firstInvalid: firstInvalid
    };
  }

  function buildPayload(form) {
    var fields = getFieldsByName(form);
    var payload = {};
    var customFields = {};
    var modal = form.closest("[data-yby-inquiry-modal]");

    Object.keys(fields).forEach(function (fieldId) {
      var field = fields[fieldId];
      var type = safeString(field.type || field.tagName.toLowerCase(), 40);
      var value = type === "checkbox" ? (field.checked ? "1" : "") : safeString(field.value, 3000);

      if (value) {
        payload[fieldId] = value;

        if (!CORE_FIELD_IDS[fieldId]) {
          customFields[fieldId] = value;
        }
      }
    });

    payload.name = payload.name || "";
    payload.company = payload.company || "";
    payload.email = payload.email || "";
    payload.whatsapp = payload.whatsapp || "";
    payload.country = payload.country || "";
    payload.product_interest = payload.product_interest || "";
    payload.quantity = payload.quantity || "";
    payload.project_details = buildProjectDetails(payload);
    payload.page = safeString(document.title || window.location.pathname, 240);
    payload.source_url = stripHash(window.location.href);
    payload.source_component = safeString(modal ? modal.getAttribute("data-yby-source-component") : "inquiry_modal", 100);
    payload.source_preset = safeString(form.getAttribute("data-yby-preset"), 100);
    payload.source_page = getSourcePage(form, modal);
    payload.form_version = safeString(form.getAttribute("data-yby-form-version"), 30);
    payload.trigger_source = safeString(form.getAttribute("data-yby-trigger-source"), 120);
    payload.page_profile = safeString(form.getAttribute("data-yby-page-profile"), 100);
    payload.utm_source = safeString(getQueryParam("utm_source"), 100);
    payload.utm_medium = safeString(getQueryParam("utm_medium"), 100);
    payload.utm_campaign = safeString(getQueryParam("utm_campaign"), 150);
    payload.utm_term = safeString(getQueryParam("utm_term"), 150);
    payload.gclid = safeString(getQueryParam("gclid"), 255);
    payload.fbclid = safeString(getQueryParam("fbclid"), 255);

    if (Object.keys(customFields).length) {
      payload.fields = customFields;
    }

    return payload;
  }

  function close(modalOrId, reason) {
    var modal = typeof modalOrId === "string" ? getModalById(modalOrId) : modalOrId;

    if (!modal) {
      return;
    }

    modal.hidden = true;
    modal.setAttribute("aria-hidden", "true");
    modal.classList.remove("is-open");

    if (activeModal === modal) {
      activeModal = null;
    }

    unlockBodyScrollIfNeeded();

    if (activeTrigger && typeof activeTrigger.focus === "function") {
      activeTrigger.focus();
    }

    var form = getForm(modal);
    if (form) {
      pushTracking(form, "yby_inquiry_close", {
        close_reason: safeString(reason || "programmatic", 80)
      });
    }
  }

  function open(modalOrOptions, trigger) {
    var request = resolveOpenRequest(modalOrOptions, trigger);
    var modal = request.modal;
    var dialog;
    var form;
    var firstField;

    if (!modal) {
      return;
    }

    dialog = getDialog(modal);

    if (!dialog) {
      return;
    }

    if (activeModal && activeModal !== modal) {
      close(activeModal, "programmatic");
    }

    activeModal = modal;
    activeTrigger = request.trigger || document.activeElement;
    modal.hidden = false;
    modal.setAttribute("aria-hidden", "false");
    modal.classList.add("is-open");
    lockBodyScroll();

    form = getForm(modal);
    if (form) {
      form.setAttribute("data-yby-started", "false");
      applyTriggerContext(form, request);
    }

    firstField = modal.querySelector("[data-yby-field]:not([type='hidden']):not([disabled])");

    if (firstField) {
      firstField.focus();
    } else {
      dialog.focus();
    }

    enableFormIfReady(form);
    pushTracking(form, "yby_inquiry_open", {
      trigger_source: request.source,
      page_profile: request.profile
    });

    return modal;
  }

  function submitForm(form) {
    var payload;
    var validation;

    if (!form || form.getAttribute("data-yby-submitting") === "true") {
      return Promise.resolve(null);
    }

    if (!isSdkAvailable()) {
      setFormGeneralError(form, "Inquiry form is temporarily unavailable. Please try again later.", "sdk_unavailable");
      return Promise.resolve(null);
    }

    markStartIfNeeded(form);
    validation = validateForm(form);

    if (!validation.valid) {
      setFormGeneralError(form, "Please review the highlighted fields and try again.", "validation");
      return Promise.resolve(null);
    }

    payload = buildPayload(form);
    setFormGeneralError(form, "", "");
    setFormStatus(form, "Submitting your inquiry...");
    setSubmitting(form, true);
    pushTracking(form, "yby_inquiry_submit");

    return window.YBYLead.submit(payload)
      .then(function (response) {
        var caseId = response && response.data ? safeString(response.data.case_id, 80) : "";

        if (!caseId) {
          throw {
            message: "We could not confirm your inquiry case ID. Please try again.",
            error_type: "missing_case_id"
          };
        }

        pushTracking(form, "yby_inquiry_success", {
          case_id: caseId
        });

        setFormStatus(form, "Inquiry submitted successfully.");

        window.YBYLead.redirectToThankYou(
          Object.assign({}, payload, {
            case_id: caseId
          })
        );

        return response;
      })
      .catch(function (error) {
        var message = error && error.message ? error.message : "We could not submit your inquiry right now. Please try again.";
        var errorType = error && error.error_type ? error.error_type : "submit_error";

        setSubmitting(form, false);
        setFormStatus(form, "");
        setFormGeneralError(form, message, errorType);
        pushTracking(form, "yby_inquiry_error", {
          error_type: errorType
        });

        return null;
      });
  }

  function handleDocumentClick(event) {
    var openTrigger = event.target.closest("[data-yby-modal-open], [data-yby-inquiry-trigger], [data-yby-quote-trigger]");
    var closeTrigger = event.target.closest("[data-yby-modal-close]");

    if (openTrigger) {
      if (open(
        {
          modalId: openTrigger.getAttribute("data-yby-modal-open") || openTrigger.getAttribute("data-yby-inquiry-target") || "",
          source: openTrigger.getAttribute("data-yby-source") || "",
          profile: getClosestProfile(openTrigger),
          trigger: openTrigger
        },
        openTrigger
      )) {
        event.preventDefault();
      }
      return;
    }

    if (closeTrigger && activeModal && activeModal.contains(closeTrigger)) {
      event.preventDefault();
      close(activeModal, closeTrigger.classList.contains("yby-inquiry-modal__backdrop") ? "backdrop" : "close_button");
    }
  }

  function handleDocumentKeydown(event) {
    var focusables;
    var first;
    var last;

    if (!activeModal) {
      return;
    }

    if (event.key === "Escape") {
      event.preventDefault();
      close(activeModal, "escape");
      return;
    }

    if (event.key !== "Tab") {
      return;
    }

    focusables = getFocusableElements(activeModal);

    if (!focusables.length) {
      return;
    }

    first = focusables[0];
    last = focusables[focusables.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function handleFormInteraction(event) {
    var form = event.target.closest("[data-yby-inquiry-form]");
    var field = event.target.closest("[data-yby-field]");

    if (!form || !field) {
      return;
    }

    markStartIfNeeded(form);

    if (field.getAttribute("aria-invalid") === "true") {
      validateForm(form);
    }
  }

  function handleFormSubmit(event) {
    var form = event.target.closest("[data-yby-inquiry-form]");

    if (!form) {
      return;
    }

    event.preventDefault();
    submitForm(form);
  }

  function initForms(root) {
    Array.prototype.slice.call((root || document).querySelectorAll("[data-yby-inquiry-form]")).forEach(function (form) {
      form.setAttribute("data-yby-submitting", "false");
      form.removeAttribute("aria-busy");
      enableFormIfReady(form);
    });
  }

  function updateStickyCtas() {
    var shouldShow = window.scrollY > 180 && !document.querySelector(".yby-inquiry-modal.is-open");

    Array.prototype.slice.call(document.querySelectorAll("[data-yby-sticky-cta]")).forEach(function (cta) {
      cta.classList.toggle("is-visible", shouldShow);
    });
  }

  function initStickyCtas() {
    if (!stickyListenersBound) {
      window.addEventListener("scroll", updateStickyCtas, { passive: true });
      window.addEventListener("resize", updateStickyCtas);
      stickyListenersBound = true;
    }

    updateStickyCtas();
  }

  function init() {
    if (!listenersBound) {
      document.addEventListener("click", handleDocumentClick);
      document.addEventListener("keydown", handleDocumentKeydown);
      document.addEventListener("focusin", handleFormInteraction);
      document.addEventListener("input", handleFormInteraction);
      document.addEventListener("change", handleFormInteraction);
      document.addEventListener("submit", handleFormSubmit);
      listenersBound = true;
    }

    initForms(document);
    initStickyCtas();

    return inquiry;
  }

  inquiry = Object.assign(inquiry, {
    init: init,
    open: open,
    close: close,
    validateForm: validateForm,
    buildPayload: buildPayload,
    submitForm: submitForm
  });

  window.YBYInquiry = inquiry;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
