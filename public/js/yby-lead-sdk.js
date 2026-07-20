(function () {
  "use strict";

  var readableChars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  var debugLabel = "[YBY Lead SDK]";

  function getRuntime() {
    return window.YBYCoreConfig || {};
  }

  function log(message, data) {
    if (!window.YBYLead || !window.YBYLead.debug) {
      return;
    }

    if (typeof data === "undefined") {
      console.log(debugLabel, message);
      return;
    }

    console.log(debugLabel, message, data);
  }

  function safeString(value, maxLength) {
    return String(value || "")
      .replace(/[<>]/g, "")
      .trim()
      .substring(0, maxLength || 120);
  }

  function sanitizeFieldKey(key) {
    return String(key || "")
      .toLowerCase()
      .replace(/[^a-z0-9_]/g, "")
      .substring(0, 80);
  }

  function sanitizeFieldValue(value, maxLength) {
    if (typeof value === "boolean") {
      return value ? "1" : "";
    }

    if (typeof value === "number") {
      return safeString(value, maxLength || 3000);
    }

    if (typeof value === "string") {
      return safeString(value, maxLength || 3000);
    }

    return "";
  }

  function sanitizeStructuredFields(fields) {
    var output = {};
    var count = 0;

    if (!fields || typeof fields !== "object" || Array.isArray(fields)) {
      return output;
    }

    Object.keys(fields).forEach(function (key) {
      var safeKey;
      var safeValue;

      if (count >= 50 || !Object.prototype.hasOwnProperty.call(fields, key)) {
        return;
      }

      if (Array.isArray(fields[key]) || (fields[key] && typeof fields[key] === "object")) {
        return;
      }

      safeKey = sanitizeFieldKey(key);
      safeValue = sanitizeFieldValue(fields[key], 3000);

      if (!safeKey || !safeValue) {
        return;
      }

      output[safeKey] = safeValue;
      count += 1;
    });

    return output;
  }

  function normalizeCaseId(caseId) {
    return safeString(caseId, 40).toUpperCase().replace(/[^A-Z0-9-]/g, "");
  }

  function looksLikeEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(safeString(value, 150));
  }

  function looksLikeWhatsApp(value) {
    return /^[+()\d\s-]{6,}$/.test(safeString(value, 50));
  }

  function randomCode(length) {
    var output = "";
    var index;

    for (index = 0; index < length; index += 1) {
      output += readableChars.charAt(Math.floor(Math.random() * readableChars.length));
    }

    return output;
  }

  function formatDateYYYYMMDD(date) {
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, "0");
    var day = String(date.getDate()).padStart(2, "0");
    return "" + year + month + day;
  }

  function getSessionItem(key) {
    try {
      return window.sessionStorage.getItem(key) || "";
    } catch (error) {
      return "";
    }
  }

  function setSessionItem(key, value) {
    try {
      window.sessionStorage.setItem(key, value);
    } catch (error) {}
  }

  function validatePayload(payload) {
    var input = payload && typeof payload === "object" ? payload : {};
    var output = {};
    var maxLengths = {
      brand: 50,
      website: 255,
      name: 100,
      email: 150,
      whatsapp: 50,
      buyer_type: 50,
      company: 150,
      country: 100,
      product_interest: 500,
      quantity: 100,
      project_details: 3000,
      page: 240,
      source_url: 1000,
      source_component: 100,
      source_preset: 100,
      source_page: 255,
      form_version: 30,
      utm_source: 100,
      utm_medium: 100,
      utm_campaign: 150,
      utm_term: 150,
      gclid: 255,
      fbclid: 255
    };
    var allowed = [
      "name",
      "first_name",
      "email",
      "contact",
      "country",
      "whatsapp",
      "phone",
      "buyer_type",
      "product_interest",
      "project_details",
      "case_id",
      "company",
      "brand",
      "website",
      "page",
      "source_url",
      "source_component",
      "source_preset",
      "source_page",
      "form_version",
      "quantity",
      "crop",
      "farm_size",
      "water_source",
      "message",
      "utm_source",
      "utm_medium",
      "utm_campaign",
      "utm_term",
      "gclid",
      "fbclid"
    ];

    allowed.forEach(function (key) {
      if (typeof input[key] !== "undefined") {
        output[key] = safeString(input[key], maxLengths[key] || 180);
      }
    });

    if (typeof input.fields !== "undefined") {
      output.fields = sanitizeStructuredFields(input.fields);
    }

    return output;
  }

  function appendDetailLine(lines, label, value) {
    var normalized = safeString(value, 1000);

    if (normalized) {
      lines.push(label + ": " + normalized);
    }
  }

  function buildProjectDetails(input) {
    var lines = [];

    appendDetailLine(lines, "Crop", input.crop);
    appendDetailLine(lines, "Farm Size", input.farm_size);
    appendDetailLine(lines, "Water Source", input.water_source);
    appendDetailLine(lines, "Message", input.message);

    return safeString(lines.join("\n"), 3000);
  }

  function normalizeLeadPayload(payload) {
    var input = validatePayload(payload);
    var contactCandidates = [input.email, input.whatsapp, input.phone, input.contact];
    var output = {
      name: input.name || input.first_name || "",
      email: looksLikeEmail(input.email) ? input.email : "",
      whatsapp: looksLikeEmail(input.whatsapp) ? "" : input.whatsapp || input.phone || "",
      country: input.country || "",
      product_interest: input.product_interest || input.crop || "",
      project_details: input.project_details || buildProjectDetails(input),
      case_id: normalizeCaseId(input.case_id || "")
    };

    contactCandidates.forEach(function (value) {
      if (!output.email && looksLikeEmail(value)) {
        output.email = safeString(value, 150);
      }

      if (!output.whatsapp && looksLikeWhatsApp(value) && !looksLikeEmail(value)) {
        output.whatsapp = safeString(value, 50);
      }
    });

    [
      "brand",
      "website",
      "page",
      "source_url",
      "buyer_type",
      "company",
      "source_component",
      "source_preset",
      "source_page",
      "form_version",
      "quantity",
      "utm_source",
      "utm_medium",
      "utm_campaign",
      "utm_term",
      "gclid",
      "fbclid"
    ].forEach(function (key) {
      if (input[key]) {
        output[key] = input[key];
      }
    });

    if (!output.case_id) {
      delete output.case_id;
    }

    if (input.fields && Object.keys(input.fields).length) {
      output.fields = input.fields;
    }

    return output;
  }

  function validateRequired(payload) {
    var errors = {};
    var hasEmail = !!payload.email;
    var hasWhatsApp = !!payload.whatsapp;

    if (!payload.name) {
      errors.name = "Name is required";
    }

    if (!hasEmail && !hasWhatsApp) {
      errors.email = "Email or WhatsApp is required";
      errors.whatsapp = "Email or WhatsApp is required";
    } else if (hasEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(payload.email)) {
      errors.email = "Invalid email";
    }

    if (hasWhatsApp && !looksLikeWhatsApp(payload.whatsapp)) {
      errors.whatsapp = "Invalid WhatsApp";
    }

    return errors;
  }

  function parseJsonResponse(response) {
    return response.text().then(function (text) {
      var data = {};

      if (text) {
        try {
          data = JSON.parse(text);
        } catch (error) {
          data = {};
        }
      }

      return {
        ok: !!response.ok,
        status: response.status || 0,
        data: data && typeof data === "object" ? data : {}
      };
    });
  }

  function createSdk() {
    var sdk = window.YBYLead || {};
    var debugEnabled = !!sdk.debug;

    Object.defineProperty(sdk, "debug", {
      configurable: true,
      enumerable: true,
      get: function () {
        return debugEnabled;
      },
      set: function (value) {
        debugEnabled = !!value;
        if (debugEnabled) {
          log("initialized");
        }
      }
    });

    sdk.createCaseId = function () {
      var today = formatDateYYYYMMDD(new Date());
      return "YBY-IRR-" + today + "-" + randomCode(6);
    };

    sdk.create = function () {
      return sdk.createCaseId();
    };

    sdk.get = function () {
      return {
        case_id: sdk.getCaseId(),
        first_name: sdk.getFirstName()
      };
    };

    sdk.getCaseId = function () {
      return normalizeCaseId(sdk.currentCaseId || getSessionItem("yby_case_id"));
    };

    sdk.setCaseId = function (caseId) {
      var normalized = normalizeCaseId(caseId);
      if (normalized) {
        sdk.currentCaseId = normalized;
        setSessionItem("yby_case_id", normalized);
      }

      return normalized;
    };

    sdk.getFirstName = function () {
      return safeString(sdk.currentFirstName || getSessionItem("yby_lead_first_name"), 50);
    };

    sdk.setFirstName = function (firstName) {
      var normalized = safeString(firstName, 50);
      sdk.currentFirstName = normalized;
      if (normalized) {
        setSessionItem("yby_lead_first_name", normalized);
      }

      return normalized;
    };

    sdk.saveLeadDisplayData = function (payload) {
      var safeInput = normalizeLeadPayload(payload);
      var firstName = sdk.setFirstName(safeInput.first_name || safeInput.name || "");
      var caseId = sdk.setCaseId(safeInput.case_id || sdk.createCaseId());

      if (!caseId) {
        caseId = sdk.setCaseId(sdk.createCaseId());
      }

      return {
        first_name: firstName || safeInput.name || "",
        case_id: caseId,
        email: safeInput.email || "",
        country: safeInput.country || "",
        whatsapp: safeInput.whatsapp || "",
        phone: safeInput.phone || "",
        product_interest: safeInput.product_interest || "",
        company: safeInput.company || ""
      };
    };

    sdk.buildThankYouUrl = function (caseId) {
      var runtime = getRuntime();
      var safeCaseId = normalizeCaseId(caseId || sdk.getCaseId() || sdk.createCaseId());
      var baseUrl = runtime.thankYouUrl || runtime.returnPageUrl || "/thank-you/";
      var separator = baseUrl.indexOf("?") === -1 ? "?" : "&";
      return baseUrl + separator + "case_id=" + encodeURIComponent(safeCaseId);
    };

    sdk.redirectToThankYou = function (payload) {
      var dataPayload = sdk.saveLeadDisplayData(payload);

      window.location.href = sdk.buildThankYouUrl(dataPayload.case_id);

      return dataPayload;
    };

    sdk.submit = function (payload) {
      var requestPayload = normalizeLeadPayload(payload);
      var errors = validateRequired(requestPayload);

      log("submit called", requestPayload);

      if (Object.keys(errors).length) {
        return Promise.reject({
          success: false,
          message: "Validation failed",
          errors: errors
        });
      }

      return window
        .fetch("/wp-json/yby/v1/leads", {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json"
          },
          body: JSON.stringify(requestPayload)
        })
        .then(parseJsonResponse)
        .then(function (result) {
          var responseData = result.data || {};

          if (!result.ok || !responseData.success) {
            throw responseData;
          }

          if (responseData.data && responseData.data.case_id) {
            requestPayload.case_id = responseData.data.case_id;
            sdk.setCaseId(responseData.data.case_id);
          }

          sdk.saveLeadDisplayData(requestPayload);

          if (responseData.data && responseData.data.lead_id) {
            setSessionItem("yby_lead_id", responseData.data.lead_id);
          }

          try {
            setSessionItem(
              "yby_lead_data",
              JSON.stringify({
                lead_id: responseData.data ? responseData.data.lead_id || "" : "",
                case_id: responseData.data ? responseData.data.case_id || "" : "",
                name: requestPayload.name || "",
                email: requestPayload.email || "",
                country: requestPayload.country || "",
                product_interest: requestPayload.product_interest || ""
              })
            );
          } catch (error) {}

          return responseData;
        });
    };

    sdk.currentCaseId = sdk.getCaseId();
    sdk.currentFirstName = sdk.getFirstName();

    if (debugEnabled) {
      log("initialized");
    }

    return sdk;
  }

  window.YBYLead = createSdk();
})();
