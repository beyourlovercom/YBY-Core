(function () {
  "use strict";

  /*
   * Stable public runtime API for v1.x:
   * window.YBYCoreConfig
   * window.YBYProject
   * window.YBYContent
   * window.YBYTemplate
   * window.YBYLead
   * window.YBYTracking
   * window.YBYThankYou
   *
   * Signatures are frozen for the v1.x compatibility line.
   */

  var runtime = window.YBYCoreConfig || {};
  var data = window.YBYCoreData || {};
  var leadSession = data.leadSession || {};
  var tracking = data.tracking || {};
  var project = window.YBYProject || data.project || {};
  var pageProfile = window.YBYPageProfile || data.pageProfile || {};
  var content = window.YBYContent || data.content || {};
  var template = window.YBYTemplate || data.template || {};
  var readableChars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  var trackingBlockedKeys = {
    name: true,
    first_name: true,
    company: true,
    contact: true,
    message: true,
    email: true,
    whatsapp: true,
    phone: true,
    ip: true,
    user_agent: true,
    payload: true,
    form_data: true,
    rest_payload: true
  };

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

  function cleanLocation() {
    return window.location.origin + window.location.pathname;
  }

  function normalizeCaseId(caseId) {
    return safeString(caseId, 40).toUpperCase().replace(/[^A-Z0-9-]/g, "");
  }

  function caseIdAvailable(caseId) {
    return caseId ? "yes" : "no";
  }

  function isValidCaseId(caseId) {
    var normalized = normalizeCaseId(caseId);
    var pattern = leadSession.caseIdRegex || "^YBY-IRR-\\d{8}-[A-HJ-NP-Z2-9]{6}$";

    if (!normalized) {
      return false;
    }

    try {
      return new RegExp(pattern).test(normalized);
    } catch (error) {
      return /^YBY-IRR-\d{8}-[A-HJ-NP-Z2-9]{6}$/.test(normalized);
    }
  }

  function sanitizeTrackingPayload(payload) {
    var input = payload && typeof payload === "object" ? payload : {};
    var output = {};

    Object.keys(input).forEach(function (key) {
      if (trackingBlockedKeys[key]) {
        return;
      }

      output[key] = safeString(input[key], 240);
    });

    return output;
  }

  function getCanonicalString(keys, fallback, maxLength) {
    var list = Array.isArray(keys) ? keys : [keys];
    var index;
    var value = "";

    for (index = 0; index < list.length; index += 1) {
      value = safeString(project[list[index]], maxLength || 180);
      if (value) {
        return value;
      }
    }

    for (index = 0; index < list.length; index += 1) {
      value = safeString(pageProfile[list[index]], maxLength || 180);
      if (value) {
        return value;
      }
    }

    value = safeString(fallback, maxLength || 180);
    if (value) {
      return value;
    }

    return "";
  }

  function getProductInterest() {
    return getCanonicalString(
      "productInterest",
      runtime.defaultProductInterest || tracking.defaultProduct || "irrigation system solution",
      80
    );
  }

  function appendQueryParam(url, key, value) {
    var joiner = url.indexOf("?") === -1 ? "?" : "&";
    return url + joiner + encodeURIComponent(key) + "=" + encodeURIComponent(value);
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

  function getQueryParam(name) {
    try {
      return new URLSearchParams(window.location.search || "").get(name) || "";
    } catch (error) {
      return "";
    }
  }

  window.YBYCoreConfig = runtime;
  window.YBYProject = Object.assign(
    {
      projectId: "",
      projectName: "",
      projectType: "",
      projectStatus: "",
      country: "",
      language: "",
      region: "",
      targetMarket: "",
      productInterest: "",
      productCategory: "",
      productLine: "",
      catalogUrl: "",
      youtubeVideoId: "",
      caseStudyUrl: "",
      landingUrl: "",
      thankYouUrl: "",
      returnPageUrl: "",
      whatsappMessage: "",
      emailSubject: "",
      emailIntro: "",
      trackingGroup: "",
      ga4ContentGroup: "",
      adsConversionGroup: "",
      crmPipeline: "",
      crmOwner: "",
      leadPriority: ""
    },
    project
  );
  window.YBYPageProfile = Object.assign(
    {
      profileId: "",
      pageType: "",
      productInterest: "",
      country: "",
      catalogUrl: "",
      youtubeVideoId: "",
      thankYouUrl: "",
      returnPageUrl: "",
      whatsappMessage: "",
      crmPipeline: "",
      trackingGroup: ""
    },
    pageProfile
  );
  window.YBYContent = Object.assign(
    {
      projectId: "",
      profileId: "",
      sections: {}
    },
    content
  );
  window.YBYTemplate = Object.assign(
    {
      templateId: "",
      templateName: "",
      version: "",
      status: "",
      pageMap: {
        landingPage: "",
        thankYouPage: "",
        futurePages: []
      },
      contentMap: {
        hero: "",
        faq: "",
        caseStudies: "",
        products: "",
        cta: "",
        footer: ""
      },
      trackingMap: {
        trackingGroup: "",
        ga4ContentGroup: "",
        adsConversionGroup: ""
      },
      assetMap: {
        catalog: "",
        video: "",
        downloads: [],
        images: []
      },
      integrationMap: {
        crm: "",
        email: "",
        erp: "",
        futureAi: ""
      }
    },
    template
  );
  project = window.YBYProject;
  pageProfile = window.YBYPageProfile;
  content = window.YBYContent;
  template = window.YBYTemplate;
  window.YBYLead = window.YBYLead || {};
  window.YBYTracking = window.YBYTracking || {};
  window.YBYThankYou = window.YBYThankYou || {};

  window.YBYContent.getSection = function (sectionId) {
    var sections = window.YBYContent.sections || {};
    var key = safeString(sectionId, 80).toLowerCase().replace(/\s+/g, "_");
    return sections[key] || null;
  };

  window.YBYContent.getField = function (sectionId, fieldId) {
    var section = window.YBYContent.getSection(sectionId);
    var fields;
    var key;

    if (!section || !section.fields) {
      return null;
    }

    fields = section.fields;
    key = safeString(fieldId, 80).toLowerCase().replace(/\s+/g, "_");
    return fields[key] || null;
  };

  window.YBYContent.isEnabled = function (sectionId) {
    var section = window.YBYContent.getSection(sectionId);
    return !!(section && section.enabled);
  };

  window.YBYContent.getOrderedSections = function () {
    var sections = window.YBYContent.sections || {};

    return Object.keys(sections)
      .map(function (key) {
        return sections[key];
      })
      .filter(function (section) {
        return !!section;
      })
      .sort(function (left, right) {
        return (left.order || 0) - (right.order || 0);
      });
  };

  window.YBYTemplate.getTemplate = function () {
    return window.YBYTemplate;
  };

  window.YBYTemplate.getPageMap = function () {
    return window.YBYTemplate.pageMap || {};
  };

  window.YBYTemplate.getContentMap = function () {
    return window.YBYTemplate.contentMap || {};
  };

  window.YBYTemplate.getTrackingMap = function () {
    return window.YBYTemplate.trackingMap || {};
  };

  window.YBYTemplate.getAssetMap = function () {
    return window.YBYTemplate.assetMap || {};
  };

  window.YBYTemplate.getIntegrationMap = function () {
    return window.YBYTemplate.integrationMap || {};
  };

  window.YBYLead.createCaseId = function () {
    return "YBY-IRR-" + formatDateYYYYMMDD(new Date()) + "-" + randomCode(6);
  };

  window.YBYLead.getCaseId = function () {
    return normalizeCaseId(window.YBYLead.currentCaseId || getSessionItem("yby_case_id"));
  };

  window.YBYLead.setCaseId = function (caseId) {
    var normalized = normalizeCaseId(caseId);

    if (isValidCaseId(normalized)) {
      setSessionItem("yby_case_id", normalized);
      window.YBYLead.currentCaseId = normalized;
      return normalized;
    }

    return "";
  };

  window.YBYLead.getFirstName = function () {
    return safeString(window.YBYLead.currentFirstName || getSessionItem("yby_lead_first_name"), 50);
  };

  window.YBYLead.setFirstName = function (firstName) {
    var normalized = safeString(firstName, 50);
    setSessionItem("yby_lead_first_name", normalized);
    window.YBYLead.currentFirstName = normalized;
    return normalized;
  };

  window.YBYLead.saveLeadDisplayData = function (payload) {
    var safeInput = payload && typeof payload === "object" ? payload : {};
    var firstName = window.YBYLead.setFirstName(safeInput.first_name || safeInput.name || "");
    var caseId = window.YBYLead.setCaseId(safeInput.case_id || window.YBYLead.createCaseId());

    if (!caseId) {
      caseId = window.YBYLead.setCaseId(window.YBYLead.createCaseId());
    }

    return {
      first_name: firstName,
      case_id: caseId
    };
  };

  window.YBYLead.buildThankYouUrl = function (caseId) {
    var safeCaseId = normalizeCaseId(caseId || window.YBYLead.getCaseId() || window.YBYLead.createCaseId());
    var thankYouUrl =
      getCanonicalString("thankYouUrl", runtime.thankYouUrl || leadSession.thankYouUrl || "/lp/thank-you-irrigation-solution/", 240);
    return appendQueryParam(thankYouUrl, "case_id", safeCaseId);
  };

  window.YBYLead.redirectToThankYou = function (payload) {
    var dataPayload = window.YBYLead.saveLeadDisplayData(payload);

    /*
     * No PII in URL:
     * Only case_id is allowed in the Thank You URL.
     * Email and phone must stay out of the URL and out of UTM parameters.
     */
    window.location.href = window.YBYLead.buildThankYouUrl(dataPayload.case_id);
  };

  window.YBYLead.submit = function (payload) {
    var requestPayload = payload && typeof payload === "object" ? payload : {};

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
        var message = safeString(responseData.message || "", 240);

        if (!result.ok || !responseData.success) {
          throw new Error(message || "We could not send your request right now.");
        }

        return responseData;
      });
  };

  window.YBYTracking.push = function (eventName, payload) {
    if (!runtime.enableTracking && !tracking.enabled) {
      return false;
    }

    var eventPayload = sanitizeTrackingPayload(payload);
    pushDataLayer(
      Object.assign(
        {
          event: safeString(eventName, 80),
          page_location_clean: cleanLocation(),
          product_interest: getProductInterest(),
          tracking_group: getCanonicalString("trackingGroup", "", 80),
          ga4_content_group: getCanonicalString("ga4ContentGroup", "", 80),
          ads_conversion_group: getCanonicalString("adsConversionGroup", "", 80),
          case_id_available: caseIdAvailable(window.YBYLead.getCaseId())
        },
        eventPayload
      )
    );

    return true;
  };

  window.YBYTracking.thankYouPageView = function (payload) {
    return window.YBYTracking.push("thank_you_page_view", payload);
  };

  window.YBYTracking.generateLead = function (payload) {
    return window.YBYTracking.push("generate_lead", payload);
  };

  window.YBYTracking.clickWhatsApp = function (payload) {
    return window.YBYTracking.push("click_whatsapp", payload);
  };

  window.YBYTracking.clickWhatsAppAfterLead = function (payload) {
    return window.YBYTracking.push("click_whatsapp_after_lead", payload);
  };

  window.YBYTracking.downloadCatalog = function (payload) {
    return window.YBYTracking.push("download_catalog", payload);
  };

  window.YBYTracking.submitProjectDetails = function (payload) {
    return window.YBYTracking.push("submit_project_details", payload);
  };

  window.YBYTracking.returnToLp = function (payload) {
    return window.YBYTracking.push("return_to_lp", payload);
  };

  window.YBYTracking.viewCaseStudy = function (payload) {
    return window.YBYTracking.push("view_case_study", payload);
  };

  window.YBYThankYou.init = function () {
    window.YBYThankYou.hydrateCaseIdFromUrl();
    window.YBYThankYou.renderLeadName();
    window.YBYThankYou.renderCaseId();
    window.YBYThankYou.applyConfigLinks();
    window.YBYThankYou.bindActions();
    window.YBYThankYou.trackPageView();
    window.YBYThankYou.trackGenerateLeadOnce();
  };

  window.YBYThankYou.hydrateCaseIdFromUrl = function () {
    var urlCaseId = getQueryParam("case_id");
    return window.YBYLead.setCaseId(urlCaseId);
  };

  window.YBYThankYou.renderLeadName = function () {
    var name = window.YBYLead.getFirstName() || "Customer";
    var nodes = document.querySelectorAll("[data-yby-lead-name]");
    nodes.forEach(function (node) {
      node.textContent = name;
    });
  };

  window.YBYThankYou.renderCaseId = function () {
    var caseId = window.YBYLead.getCaseId();
    var textNodes = document.querySelectorAll("[data-yby-case-id]");
    var inputNodes = document.querySelectorAll("[data-yby-case-id-input]");

    textNodes.forEach(function (node) {
      node.textContent = caseId || "Pending";
    });

    inputNodes.forEach(function (node) {
      node.value = caseId;
    });
  };

  window.YBYThankYou.buildWhatsAppUrl = function () {
    var caseId = window.YBYLead.getCaseId() || "Pending";
    var number = safeString(runtime.whatsappNumber, 32).replace(/[^\d]/g, "");
    var customMessage = getCanonicalString("whatsappMessage", "", 240);
    var message = customMessage || "Hello YBY, I submitted an irrigation request. My Case ID is " + caseId + ".";
    return "https://wa.me/" + number + "?text=" + encodeURIComponent(message);
  };

  window.YBYThankYou.applyConfigLinks = function () {
    var whatsappLinks = document.querySelectorAll("[data-yby-whatsapp-link]");
    var catalogLinks = document.querySelectorAll("[data-yby-catalog-link]");
    var returnLinks = document.querySelectorAll("[data-yby-return-link]");
    var videoFrame = document.querySelector("[data-yby-video-frame]");
    var videoNote = document.querySelector("[data-yby-video-note]");

    whatsappLinks.forEach(function (node) {
      node.setAttribute("href", window.YBYThankYou.buildWhatsAppUrl());
    });

    catalogLinks.forEach(function (node) {
      node.setAttribute("href", getCanonicalString("catalogUrl", runtime.catalogUrl || "#", 240));
    });

    returnLinks.forEach(function (node) {
      node.setAttribute("href", getCanonicalString("returnPageUrl", runtime.returnPageUrl || "/lp/irrigation-solution/", 240));
    });

    if (videoFrame) {
      if (getCanonicalString("youtubeVideoId", runtime.youtubeVideoId || "", 120)) {
        videoFrame.setAttribute(
          "src",
          "https://www.youtube.com/embed/" +
            encodeURIComponent(getCanonicalString("youtubeVideoId", runtime.youtubeVideoId || "", 120)) +
            "?enablejsapi=1"
        );
        if (videoNote) {
          videoNote.hidden = true;
        }
      } else if (videoNote) {
        videoNote.hidden = false;
      }
    }
  };

  window.YBYThankYou.trackPageView = function () {
    window.YBYTracking.thankYouPageView({
      page_type: "thank_you_page"
    });
  };

  window.YBYThankYou.trackGenerateLeadOnce = function () {
    var caseId = window.YBYLead.getCaseId();
    var key = caseId ? "yby_generate_lead_counted_" + caseId : "yby_generate_lead_counted";

    if (getSessionItem(key)) {
      return;
    }

    window.YBYTracking.generateLead({
      page_type: "thank_you_page",
      event_id: caseId || "YBY-LEAD-" + Date.now()
    });

    setSessionItem(key, "1");
  };

  window.YBYThankYou.bindActions = function () {
    document.addEventListener("click", function (event) {
      var link = event.target.closest("a");
      if (!link) {
        return;
      }

      var href = link.getAttribute("href") || "";
      var eventName = link.getAttribute("data-yby-event") || "";
      var payload = {
        page_type: "thank_you_page",
        event_id: window.YBYLead.getCaseId() || undefined
      };

      if (href.indexOf("wa.me") > -1 || href.indexOf("whatsapp") > -1) {
        window.YBYTracking.clickWhatsAppAfterLead(payload);
      }

      if (eventName === "download_catalog" || href.indexOf("Catalog") > -1 || href.indexOf(".pdf") > -1) {
        window.YBYTracking.downloadCatalog(payload);
      }

      if (eventName === "return_to_lp") {
        window.YBYTracking.returnToLp(payload);
      }

      if (eventName === "view_case_study") {
        window.YBYTracking.viewCaseStudy(payload);
      }
    });

    document.addEventListener("submit", function (event) {
      var form = event.target.closest("[data-yby-project-form]");
      var messageNode;

      if (!form) {
        return;
      }

      window.YBYTracking.submitProjectDetails({
        page_type: "thank_you_page",
        event_id: window.YBYLead.getCaseId() || undefined
      });

      messageNode = document.querySelector("[data-yby-form-message]");

      if (messageNode && !runtime.enableCrmWebhook) {
        messageNode.textContent = "Project details were captured locally for future CRM integration.";
      }
    });
  };

  window.YBYLead.currentCaseId = window.YBYLead.getCaseId();
  window.YBYLead.currentFirstName = window.YBYLead.getFirstName();
  window.YBYLead.caseIdRegex = leadSession.caseIdRegex || "^YBY-IRR-\\d{8}-[A-HJ-NP-Z2-9]{6}$";
  window.YBYTracking.events = tracking.events || [];

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      window.YBYThankYou.init();
    });
  } else {
    window.YBYThankYou.init();
  }
})();
