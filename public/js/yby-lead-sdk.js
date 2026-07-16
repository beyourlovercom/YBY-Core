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

  function normalizeCaseId(caseId) {
    return safeString(caseId, 40).toUpperCase().replace(/[^A-Z0-9-]/g, "");
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
      project_details: 3000,
      page: 240,
      utm_source: 500,
      utm_medium: 500,
      utm_campaign: 500,
      utm_term: 500,
      gclid: 500,
      fbclid: 500
    };
    var allowed = [
      "name",
      "first_name",
      "email",
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

    return output;
  }

  function validateRequired(payload) {
    var errors = {};

    if (!payload.name) {
      errors.name = "Name is required";
    }

    if (!payload.email) {
      errors.email = "Email is required";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(payload.email)) {
      errors.email = "Invalid email";
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
      var safeInput = validatePayload(payload);
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
      var requestPayload = validatePayload(payload);
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
