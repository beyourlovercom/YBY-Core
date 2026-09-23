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
  var whatsAppSummaryStorageKey = "yby_whatsapp_project_summary";
  var whatsAppSummaryFields = [
    { key: "country", label: "Country", maxLength: 120 },
    { key: "crop", label: "Crop", maxLength: 180 },
    { key: "farm_size", label: "Farm Size", maxLength: 180 },
    { key: "water_source", label: "Water Source", maxLength: 180 },
    { key: "recommended_system", label: "Recommended System", maxLength: 180 },
    { key: "estimated_range", label: "Estimated Range", maxLength: 180 }
  ];
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

  function getSessionJson(key) {
    var value = getSessionItem(key);

    if (!value) {
      return {};
    }

    try {
      value = JSON.parse(value);
    } catch (error) {
      return {};
    }

    return value && typeof value === "object" && !Array.isArray(value) ? value : {};
  }

  function getConfirmedWhatsAppProjectSummary(summary, confirmedFields) {
    var input = summary && typeof summary === "object" && !Array.isArray(summary) ? summary : {};
    var confirmed = Array.isArray(confirmedFields) ? confirmedFields : [];
    var confirmedLookup = {};
    var output = {};

    confirmed.forEach(function (key) {
      var normalizedKey = safeString(key, 80).toLowerCase();

      if (normalizedKey) {
        confirmedLookup[normalizedKey] = true;
      }
    });

    whatsAppSummaryFields.forEach(function (field) {
      if (confirmedLookup[field.key]) {
        output[field.key] = safeString(input[field.key], field.maxLength);
      }
    });

    return output;
  }

  function saveConfirmedWhatsAppProjectSummary(caseId, summary, confirmedFields) {
    var normalizedCaseId = normalizeCaseId(caseId);
    var payload = {
      case_id: normalizedCaseId,
      project_summary: getConfirmedWhatsAppProjectSummary(summary, confirmedFields)
    };

    setSessionItem(whatsAppSummaryStorageKey, JSON.stringify(payload));
    return payload.project_summary;
  }

  function getConfirmedWhatsAppProjectSummaryForCase(caseId) {
    var stored = getSessionJson(whatsAppSummaryStorageKey);
    var normalizedCaseId = normalizeCaseId(caseId);

    if (!normalizedCaseId || normalizeCaseId(stored.case_id) !== normalizedCaseId) {
      return {};
    }

    return getConfirmedWhatsAppProjectSummary(
      stored.project_summary,
      whatsAppSummaryFields.map(function (field) {
        return field.key;
      })
    );
  }

  function pushDataLayer(payload) {
    var analytics = tracking && tracking.analytics && typeof tracking.analytics === "object" ? tracking.analytics : {};
    var layerName = analytics.runtime_ready && typeof analytics.data_layer_name === "string" && /^[A-Za-z_$][A-Za-z0-9_$]{0,63}$/.test(analytics.data_layer_name)
      ? analytics.data_layer_name
      : "dataLayer";

    window[layerName] = window[layerName] || [];
    window[layerName].push(payload);
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
    var pattern = leadSession.caseIdRegex || "^YBY-[A-Z0-9]+-\\d{8}-[A-HJ-NP-Z2-9]{6}$";

    if (!normalized) {
      return false;
    }

    try {
      return new RegExp(pattern).test(normalized);
    } catch (error) {
      return /^YBY-[A-Z0-9]+-\d{8}-[A-HJ-NP-Z2-9]{6}$/.test(normalized);
    }
  }

  function getCaseIdBrandCode() {
    var candidate = safeString(runtime.caseIdBrandCode || leadSession.caseIdBrandCode || "CORE", 8)
      .toUpperCase()
      .replace(/[^A-Z0-9]/g, "");

    return candidate.length >= 2 ? candidate : "CORE";
  }

  function isThankYouPage() {
    return !!document.querySelector(
      [
        "[data-yby-lead-name]",
        "[data-yby-case-id]",
        "[data-yby-case-id-input]",
        "[data-yby-project-form]"
      ].join(", ")
    );
  }

  function getBricksLeadForm(elementId) {
    var normalizedId = safeString(elementId, 80).replace(/[^A-Za-z0-9_-]/g, "");
    var form;

    if (!normalizedId) {
      return null;
    }

    form = document.querySelector('[data-element-id="' + normalizedId + '"]');
    if (!form || typeof form.querySelector !== "function") {
      return null;
    }

    if (!form.querySelector("input[type='email']") || !form.querySelector("textarea")) {
      return null;
    }

    return form;
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

  function getBrandRuntimeString(key, fallback, maxLength) {
    var runtimeValue = safeString(runtime[key], maxLength || 240);

    if (runtimeValue) {
      return runtimeValue;
    }

    return getCanonicalString(key, fallback, maxLength);
  }

  function getProductInterest() {
    return getCanonicalString("productInterest", runtime.defaultProductInterest || tracking.defaultProduct || "", 80);
  }

  function sanitizeMultilineText(value, maxLength) {
    return String(value || "")
      .replace(/\\r\\n/g, "\n")
      .replace(/\\n/g, "\n")
      .replace(/\\r/g, "\n")
      .replace(/\r\n?/g, "\n")
      .replace(/<[^>]*>/g, "")
      .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/g, "")
      .replace(/[ \t]+\n/g, "\n")
      .trim()
      .substring(0, maxLength || 4000);
  }

  function normalizeWhatsAppMessage(value) {
    return sanitizeMultilineText(value, 4000)
      .replace(/\n{3,}/g, "\n\n")
      .trim();
  }

  function countExactOccurrences(haystack, needle) {
    var value = String(haystack || "");
    var token = String(needle || "");
    var count = 0;
    var offset = 0;

    if (!token) {
      return 0;
    }

    while ((offset = value.indexOf(token, offset)) !== -1) {
      count += 1;
      offset += token.length;
    }

    return count;
  }

  function appendWhatsAppField(lines, label, value) {
    var normalized = normalizeWhatsAppMessage(value);

    if (normalized) {
      lines.push(label + ": " + normalized.replace(/\n+/g, " "));
    }
  }

  function collapseExcessBlankLines(message) {
    return normalizeWhatsAppMessage(message);
  }

  function stripInternalWhatsAppLines(message) {
    return normalizeWhatsAppMessage(
      String(message || "")
        .split("\n")
        .filter(function (line) {
          var normalized = normalizeWhatsAppMessage(line).toLowerCase();

          if (!normalized) {
            return true;
          }

          return !/^(source|source_component|source_preset|source_page|tracking_group|ga4_content_group|ads_conversion_group|crm_pipeline|utm_source|utm_medium|utm_campaign|utm_term|gclid|fbclid|project id|profile id)\s*:/.test(normalized);
        })
        .join("\n")
    );
  }

  function replaceWhatsAppTemplateTokens(message, tokens) {
    var normalizedTemplate = normalizeWhatsAppMessage(message);
    var lines = normalizedTemplate ? normalizedTemplate.split("\n") : [];

    lines = lines.map(function (line) {
      var output = String(line || "");
      var removeLine = false;

      Object.keys(tokens).forEach(function (tokenKey) {
        var rawValue = tokens[tokenKey];
        var tokenPattern = new RegExp("\\{" + tokenKey + "\\}", "ig");
        var lineHasToken = tokenPattern.test(output);

        tokenPattern.lastIndex = 0;

        if (!lineHasToken) {
          return;
        }

        if (!rawValue) {
          removeLine = true;
          output = output.replace(tokenPattern, "");
          return;
        }

        output = output.replace(tokenPattern, rawValue);
      });

      output = sanitizeMultilineText(output, 4000).replace(/[ \t]{2,}/g, " ").trim();

      if (removeLine) {
        return "";
      }

      return output;
    });

    return stripInternalWhatsAppLines(collapseExcessBlankLines(lines.join("\n")));
  }

  function buildWhatsAppCaseId(caseId) {
    var normalized = normalizeCaseId(caseId || window.YBYLead.getCaseId());

    return normalized || "Pending";
  }

  function ensureWhatsAppCaseId(message, caseId) {
    var normalizedMessage = normalizeWhatsAppMessage(message);
    var normalizedCaseId = buildWhatsAppCaseId(caseId);
    var occurrenceCount = countExactOccurrences(normalizedMessage, normalizedCaseId);
    var parts;

    if (occurrenceCount > 1) {
      parts = normalizedMessage.split(normalizedCaseId);
      normalizedMessage = parts[0] + normalizedCaseId + parts.slice(1).join("");
      occurrenceCount = 1;
    }

    if (occurrenceCount === 0) {
      normalizedMessage = normalizedMessage
        ? normalizedMessage + "\n\nCase ID: " + normalizedCaseId + "."
        : "Case ID: " + normalizedCaseId + ".";
    }

    return stripInternalWhatsAppLines(normalizeWhatsAppMessage(normalizedMessage));
  }

  function getWhatsAppSummaryFields() {
    var summary = getConfirmedWhatsAppProjectSummaryForCase(window.YBYLead.getCaseId());

    return whatsAppSummaryFields.map(function (field) {
      return {
        label: field.label,
        value: summary[field.key] || ""
      };
    });
  }

  function getRuntimeWhatsAppTemplate() {
    var template = normalizeWhatsAppMessage(runtime.whatsappMessageTemplate || "");
    var brandName = getWhatsAppBrandName().toLowerCase();

    if (brandName.indexOf("bottle") !== -1 && /\b(irrigation|farm|crop|water source)\b/i.test(template)) {
      return "";
    }

    return template;
  }

  function getWhatsAppBrandName() {
    return getBrandRuntimeString("siteBrandName", "YBY", 120) || "YBY";
  }

  function buildWhatsAppTokens(caseId) {
    var fields = getWhatsAppSummaryFields();
    var tokenValues = {
      case_id: buildWhatsAppCaseId(caseId),
      brand_name: getWhatsAppBrandName()
    };

    fields.forEach(function (field) {
      tokenValues[field.label.toLowerCase().replace(/\s+/g, "_")] = normalizeWhatsAppMessage(field.value).replace(/\n+/g, " ");
    });

    return tokenValues;
  }

  function buildDefaultWhatsAppMessage(caseId) {
    var tokens = buildWhatsAppTokens(caseId);
    var lines = [
      "Hello " + tokens.brand_name + ", I submitted a website inquiry.",
      "",
      "My Case ID: " + tokens.case_id,
      ""
    ];
    var summaryLines = [];

    getWhatsAppSummaryFields().forEach(function (field) {
      appendWhatsAppField(summaryLines, field.label, field.value);
    });

    if (summaryLines.length) {
      lines.push("Project Summary");
      lines = lines.concat(summaryLines);
      lines.push("");
    }

    lines.push("Please contact me about the next steps.");

    return normalizeWhatsAppMessage(lines.join("\n"));
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
    return "YBY-" + getCaseIdBrandCode() + "-" + formatDateYYYYMMDD(new Date()) + "-" + randomCode(6);
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

    saveConfirmedWhatsAppProjectSummary(
      caseId,
      safeInput.project_summary,
      safeInput.project_summary_confirmed_fields
    );

    return {
      first_name: firstName,
      case_id: caseId
    };
  };

  window.YBYLead.buildThankYouUrl = function (caseId) {
    var safeCaseId = buildWhatsAppCaseId(caseId);
    var thankYouUrl = safeString(pageProfile.thankYouUrl, 240) || getBrandRuntimeString("thankYouUrl", leadSession.thankYouUrl || "/", 240) || "/";
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

  window.YBYLead.submit = window.YBYLead.submit || function (payload) {
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

    var analyticsRuntime = tracking && tracking.analytics && typeof tracking.analytics === "object" ? tracking.analytics : {};
    if (analyticsRuntime.enabled && analyticsRuntime.runtime_ready === false) {
      return false;
    }

    var eventPayload = sanitizeTrackingPayload(payload);
    var analyticsSiteProfile = tracking && tracking.siteProfile && typeof tracking.siteProfile === "object" ? tracking.siteProfile : {};
    pushDataLayer(
      Object.assign(
        {
          event: safeString(eventName, 80),
          page_location_clean: cleanLocation(),
          product_interest: getProductInterest(),
          tracking_group: safeString(analyticsSiteProfile.tracking_group || getCanonicalString("trackingGroup", "", 80), 80),
          ga4_content_group: safeString(analyticsSiteProfile.ga4_content_group || getCanonicalString("ga4ContentGroup", "", 80), 80),
          ads_conversion_group: safeString(analyticsSiteProfile.ads_conversion_group || getCanonicalString("adsConversionGroup", "", 80), 80),
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

  window.YBYTracking.bindGlobalConversionActions = function () {
    if (window.YBYTracking.globalConversionActionsBound) {
      return;
    }

    window.YBYTracking.globalConversionActionsBound = true;

    document.addEventListener("bricks/form/success", function (event) {
      var detail = event && event.detail ? event.detail : {};
      var elementId = safeString(detail.elementId, 80);
      var form;

      if (isThankYouPage()) {
        return;
      }

      form = getBricksLeadForm(elementId);
      if (!form) {
        return;
      }

      window.YBYTracking.generateLead({
        page_type: "bricks_form",
        form_id: elementId,
        lead_type: "lead_form",
        event_id: "BRICKS-" + (elementId || "FORM") + "-" + Date.now()
      });
    });

    document.addEventListener("click", function (event) {
      var target = event && event.target;
      var link = target && typeof target.closest === "function" ? target.closest("a") : null;
      var href;

      if (!link || isThankYouPage()) {
        return;
      }

      href = String(link.getAttribute("href") || "").toLowerCase();
      if (href.indexOf("wa.me") === -1 && href.indexOf("whatsapp") === -1) {
        return;
      }

      window.YBYTracking.clickWhatsApp({
        page_type: getCanonicalString("pageType", "website", 80) || "website",
        click_url: "whatsapp",
        click_text: safeString(link.textContent, 80),
        lead_type: "whatsapp"
      });
    });
  };

  window.YBYTracking.bindGlobalConversionActions();

  window.YBYThankYou.init = function () {
    if (!isThankYouPage()) {
      return;
    }

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
    var caseId = buildWhatsAppCaseId(window.YBYLead.getCaseId());
    var number = getBrandRuntimeString("whatsappNumber", "", 32).replace(/[^\d]/g, "");
    var customMessage = getRuntimeWhatsAppTemplate();
    var tokens = buildWhatsAppTokens(caseId);
    var message = customMessage ? replaceWhatsAppTemplateTokens(customMessage, tokens) : buildDefaultWhatsAppMessage(caseId);

    message = ensureWhatsAppCaseId(message, caseId);

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
      node.setAttribute("href", getBrandRuntimeString("catalogUrl", "#", 240) || "#");
    });

    returnLinks.forEach(function (node) {
      node.setAttribute("href", getBrandRuntimeString("returnPageUrl", "/", 240) || "/");
    });

    if (videoFrame) {
      if (getBrandRuntimeString("youtubeVideoId", "", 120)) {
        videoFrame.setAttribute(
          "src",
          "https://www.youtube.com/embed/" +
            encodeURIComponent(getBrandRuntimeString("youtubeVideoId", "", 120)) +
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

  if (window.YBY_CORE_TEST_MODE === true) {
    window.YBYThankYou.__testOnly = {
      getBrandRuntimeString: getBrandRuntimeString,
      normalizeWhatsAppMessage: normalizeWhatsAppMessage,
      replaceWhatsAppTemplateTokens: replaceWhatsAppTemplateTokens,
      ensureWhatsAppCaseId: ensureWhatsAppCaseId,
      countExactOccurrences: countExactOccurrences,
      buildDefaultWhatsAppMessage: buildDefaultWhatsAppMessage,
      buildWhatsAppTokens: buildWhatsAppTokens,
      getWhatsAppSummaryFields: getWhatsAppSummaryFields,
      getRuntimeWhatsAppTemplate: getRuntimeWhatsAppTemplate,
      getConfirmedWhatsAppProjectSummary: getConfirmedWhatsAppProjectSummary,
      getConfirmedWhatsAppProjectSummaryForCase: getConfirmedWhatsAppProjectSummaryForCase
    };
  }

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
  window.YBYLead.caseIdRegex = leadSession.caseIdRegex || "^YBY-[A-Z0-9]+-\\d{8}-[A-HJ-NP-Z2-9]{6}$";
  window.YBYTracking.events = tracking.events || [];

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      window.YBYThankYou.init();
    });
  } else {
    window.YBYThankYou.init();
  }
})();
