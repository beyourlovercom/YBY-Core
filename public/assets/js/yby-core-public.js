(function () {
  "use strict";

  var root = window.YBYCoreData || {};
  var config = root.config || {};
  var leadSession = root.leadSession || {};
  var tracking = root.tracking || {};
  var thankYouUrl = root.thankYouUrl || "/lp/thank-you-irrigation-solution/";
  var readableChars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";

  function safeString(value, maxLength) {
    return String(value || "")
      .replace(/[<>]/g, "")
      .trim()
      .substring(0, maxLength || 120);
  }

  function formatDateYYYYMMDD(date) {
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, "0");
    var day = String(date.getDate()).padStart(2, "0");
    return "" + year + month + day;
  }

  function randomCode(length) {
    var output = "";
    var index;

    for (index = 0; index < length; index += 1) {
      output += readableChars.charAt(Math.floor(Math.random() * readableChars.length));
    }

    return output;
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

  function pushDataLayer(payload) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(payload);
  }

  window.YBYLead = window.YBYLead || {};
  window.YBYTracking = window.YBYTracking || {};

  window.YBYLead.createCaseId = function () {
    return "YBY-IRR-" + formatDateYYYYMMDD(new Date()) + "-" + randomCode(6);
  };

  window.YBYLead.saveLeadDisplayData = function (payload) {
    var safePayload = payload || {};
    var firstName = safeString(safePayload.first_name, 50);
    var caseId = safeString(safePayload.case_id, 40) || window.YBYLead.createCaseId();

    setSessionItem("yby_lead_first_name", firstName);
    setSessionItem("yby_case_id", caseId);

    return {
      first_name: firstName,
      case_id: caseId
    };
  };

  window.YBYLead.redirectToThankYou = function (payload) {
    var data = window.YBYLead.saveLeadDisplayData(payload);

    /*
     * No PII in URL:
     * Only Case ID is appended for continuity. Email and phone must stay out of the URL.
     */
    window.location.href = thankYouUrl + "?case_id=" + encodeURIComponent(data.case_id);
  };

  window.YBYTracking.push = function (eventName, payload) {
    if (!tracking.enabled) {
      return false;
    }

    var safePayload = payload && typeof payload === "object" ? payload : {};
    pushDataLayer(
      Object.assign(
        {
          event: safeString(eventName, 80)
        },
        safePayload
      )
    );

    return true;
  };

  window.YBYTracking.generateLead = function (payload) {
    return window.YBYTracking.push("generate_lead", payload);
  };

  window.YBYTracking.clickWhatsApp = function (payload) {
    return window.YBYTracking.push("click_whatsapp", payload);
  };

  window.YBYTracking.downloadCatalog = function (payload) {
    return window.YBYTracking.push("download_catalog", payload);
  };

  window.YBYLead.currentCaseId = getSessionItem("yby_case_id");
  window.YBYLead.currentFirstName = getSessionItem("yby_lead_first_name");
  window.YBYLead.caseIdRegex = leadSession.caseIdRegex || "^YBY-IRR-\\d{8}-[A-HJ-NP-Z2-9]{6}$";
  window.YBYTracking.events = tracking.events || [];
  window.YBYTracking.config = config;
})();
