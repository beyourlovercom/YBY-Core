const fs = require("fs");
const path = require("path");
const vm = require("vm");

const pluginRoot = path.resolve(__dirname, "..");
const publicScript = fs.readFileSync(path.join(pluginRoot, "public", "assets", "js", "yby-core-public.js"), "utf8");
const inquiryScript = fs.readFileSync(path.join(pluginRoot, "public", "js", "yby-inquiry-components.js"), "utf8");

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

class SessionStorageMock {
  constructor() {
    this.store = new Map();
  }

  getItem(key) {
    return this.store.has(key) ? this.store.get(key) : null;
  }

  setItem(key, value) {
    this.store.set(String(key), String(value));
  }

  removeItem(key) {
    this.store.delete(String(key));
  }

  key(index) {
    return Array.from(this.store.keys())[index] || null;
  }

  get length() {
    return this.store.size;
  }
}

class MockNode {
  constructor(attributes = {}) {
    this.attributes = Object.assign({}, attributes);
    this.textContent = attributes.textContent || "";
    this.value = attributes.value || "";
    this.hidden = !!attributes.hidden;
    this.disabled = !!attributes.disabled;
    this.checked = !!attributes.checked;
    this.childrenBySelector = {};
    this.listeners = {};
  }

  setAttribute(name, value) {
    this.attributes[name] = String(value);
    if (name === "href") {
      this.href = String(value);
    }
    if (name === "src") {
      this.src = String(value);
    }
  }

  getAttribute(name) {
    if (Object.prototype.hasOwnProperty.call(this.attributes, name)) {
      return this.attributes[name];
    }

    if (name === "href") {
      return this.href || "";
    }

    if (name === "src") {
      return this.src || "";
    }

    return "";
  }

  removeAttribute(name) {
    delete this.attributes[name];
  }

  addEventListener(name, handler) {
    this.listeners[name] = this.listeners[name] || [];
    this.listeners[name].push(handler);
  }

  querySelector(selector) {
    const matches = this.querySelectorAll(selector);
    return matches.length ? matches[0] : null;
  }

  querySelectorAll(selector) {
    return (this.childrenBySelector[selector] || []).slice();
  }

  closest() {
    return null;
  }

  focus() {}
}

class MockDocument {
  constructor() {
    this.readyState = "complete";
    this.listeners = {};
    this.selectorMap = {};
    this.body = new MockNode();
    this.title = "Harness Page";
  }

  setSelector(selector, nodes) {
    this.selectorMap[selector] = nodes.slice();
  }

  querySelector(selector) {
    if (selector.indexOf(",") > -1) {
      const selectors = selector.split(",").map((item) => item.trim());

      for (const singleSelector of selectors) {
        const matches = this.querySelectorAll(singleSelector);
        if (matches.length) {
          return matches[0];
        }
      }

      return null;
    }

    const matches = this.querySelectorAll(selector);
    return matches.length ? matches[0] : null;
  }

  querySelectorAll(selector) {
    return (this.selectorMap[selector] || []).slice();
  }

  addEventListener(name, handler) {
    this.listeners[name] = this.listeners[name] || [];
    this.listeners[name].push(handler);
  }

  dispatchEvent(name, event) {
    (this.listeners[name] || []).forEach((handler) => handler(event));
  }
}

function decodeWhatsAppText(url) {
  const value = String(url || "");
  const match = value.match(/[?&]text=([^&]+)/);
  return match ? decodeURIComponent(match[1]) : "";
}

function buildThankYouDocument() {
  const document = new MockDocument();
  const caseNode = new MockNode();
  const caseInput = new MockNode();
  const leadName = new MockNode();
  const whatsappLink = new MockNode();
  const catalogLink = new MockNode();
  const returnLink = new MockNode();
  const videoFrame = new MockNode();
  const videoNote = new MockNode({ hidden: false });

  document.setSelector("[data-yby-case-id]", [caseNode]);
  document.setSelector("[data-yby-case-id-input]", [caseInput]);
  document.setSelector("[data-yby-lead-name]", [leadName]);
  document.setSelector("[data-yby-whatsapp-link]", [whatsappLink]);
  document.setSelector("[data-yby-catalog-link]", [catalogLink]);
  document.setSelector("[data-yby-return-link]", [returnLink]);
  document.setSelector("[data-yby-video-frame]", [videoFrame]);
  document.setSelector("[data-yby-video-note]", [videoNote]);
  document.setSelector("[data-yby-project-form]", []);

  return {
    document,
    nodes: {
      caseNode,
      caseInput,
      leadName,
      whatsappLink,
      catalogLink,
      returnLink,
      videoFrame,
      videoNote
    }
  };
}

function buildInquiryDocument() {
  const document = new MockDocument();
  const form = new MockNode();
  const submit = new MockNode();

  form.childrenBySelector["[data-yby-inquiry-submit]"] = [submit];
  document.setSelector("[data-yby-inquiry-form]", [form]);

  return { document, form, submit };
}

function createRuntime(options = {}) {
  const documentBundle = options.documentBundle || buildThankYouDocument();
  const document = documentBundle.document;
  const sessionStorage = options.sessionStorage || new SessionStorageMock();
  const dataLayer = options.dataLayer || [];
  const location = {
    origin: "https://runtime.example.test",
    pathname: options.pathname || "/thank-you/",
    search: options.search || "",
    href: "https://runtime.example.test" + (options.pathname || "/thank-you/") + (options.search || "")
  };
  const window = {
    YBY_CORE_TEST_MODE: options.testMode === undefined ? true : !!options.testMode,
    YBYCoreConfig: Object.assign(
      {
        siteBrandName: "YBY Irrigation",
        caseIdBrandCode: "IRR",
        thankYouUrl: "/current-thank-you/",
        returnPageUrl: "/current-return/",
        catalogUrl: "https://brand.example.test/current-catalog.pdf",
        youtubeVideoId: "CurrentVideo123",
        whatsappNumber: "+8613798537439",
        whatsappMessageTemplate: "",
        defaultCountry: "Tanzania",
        defaultProductInterest: "irrigation system solution",
        enableTracking: true,
        enableCaseId: true,
        enableCrmWebhook: false
      },
      options.runtime || {}
    ),
    YBYCoreData: {
      tracking: Object.assign(
        {
          enabled: true,
          defaultProduct: "irrigation system solution",
          events: ["generate_lead", "thank_you_page_view"]
        },
        options.tracking || {}
      ),
      project: Object.assign(
        {
          productInterest: "irrigation system solution",
          trackingGroup: "project-group",
          ga4ContentGroup: "content-group",
          adsConversionGroup: "ads-group",
          catalogUrl: "/legacy-project-catalog.pdf",
          returnPageUrl: "/legacy-project-return/",
          thankYouUrl: "/legacy-project-thank-you/",
          youtubeVideoId: "LegacyProjectVideo",
          country: "Tanzania",
          crop: "Vegetables",
          farm_size: "1–5 ha",
          water_source: "Well",
          recommended_system: "Drip Irrigation System",
          estimated_range: "USD 800–3,500",
          source: "final_ctan",
          source_component: "inquiry_modal",
          source_preset: "irrigation_quick_inquiry",
          source_page: "Irrigation Inquiry Test"
        },
        options.project || {}
      ),
      pageProfile: Object.assign(
        {
          catalogUrl: "/legacy-page-catalog.pdf",
          returnPageUrl: "/legacy-page-return/",
          thankYouUrl: "/legacy-page-thank-you/",
          youtubeVideoId: "LegacyPageVideo",
        },
        options.pageProfile || {}
      ),
      pageProfileOverrides: Object.assign({}, options.pageProfileOverrides || {}),
      leadSession: Object.assign(
        {
          caseIdRegex: "^YBY-[A-Z0-9]+-\\d{8}-[A-HJ-NP-Z2-9]{6}$",
          thankYouUrl: "/session-thank-you/"
        },
        options.leadSession || {}
      )
    },
    YBYProject: null,
    YBYPageProfile: null,
    YBYContent: {},
    YBYTemplate: {},
    location,
    sessionStorage,
    dataLayer,
    document,
    console,
    fetch() {
      throw new Error("Network access is disabled in harness.");
    },
    setTimeout,
    clearTimeout,
    URLSearchParams,
    encodeURIComponent,
    decodeURIComponent,
    Math,
    Date,
    JSON
  };

  window.window = window;
  window.globalThis = window;
  window.YBYProject = window.YBYCoreData.project;
  window.YBYPageProfile = window.YBYCoreData.pageProfile;

  const context = vm.createContext({
    window,
    document,
    console,
    setTimeout,
    clearTimeout,
    URLSearchParams,
    encodeURIComponent,
    decodeURIComponent,
    Math,
    Date,
    JSON
  });

  vm.runInContext(publicScript, context, { filename: "yby-core-public.js" });

  return {
    context,
    window,
    document,
    sessionStorage,
    dataLayer,
    nodes: documentBundle.nodes || {}
  };
}

function runInquiryRuntime() {
  const bundle = buildInquiryDocument();
  const windowListeners = {};
  const window = {
    YBYCoreConfig: {
      enableTracking: true
    },
    YBYInquiry: {},
    YBYLead: {
      submit() {
        throw new Error("submit should not run in inquiry exclusion test");
      },
      redirectToThankYou() {
        throw new Error("redirect should not run in inquiry exclusion test");
      }
    },
    dataLayer: [],
    document: bundle.document,
    location: {
      search: "",
      href: "https://runtime.example.test/inquiry/",
      pathname: "/inquiry/"
    },
    console,
    URLSearchParams,
    scrollY: 0,
    addEventListener(type, listener) {
      windowListeners[type] = listener;
    },
    removeEventListener(type) {
      delete windowListeners[type];
    }
  };

  window.window = window;
  window.globalThis = window;

  const context = vm.createContext({
    window,
    document: bundle.document,
    console,
    URLSearchParams
  });

  vm.runInContext(inquiryScript, context, { filename: "yby-inquiry-components.js" });

  return window;
}

function testRuntimeCatalogWins() {
  const runtime = createRuntime();
  assert(runtime.nodes.catalogLink.getAttribute("href") === "https://brand.example.test/current-catalog.pdf", "Runtime catalog must win over stale compatibility values.");
}

function testTestHelpersHiddenByDefault() {
  const runtime = createRuntime({
    testMode: false
  });

  assert(!Object.prototype.hasOwnProperty.call(runtime.window.YBYThankYou, "__testOnly"), "Test-only helpers must not be exposed in normal public runtime.");
}

function testTestHelpersEnabledExplicitly() {
  const runtime = createRuntime({
    testMode: true
  });

  assert(!!runtime.window.YBYThankYou.__testOnly, "Test-only helpers must be exposed when test mode is enabled explicitly.");
  assert(typeof runtime.window.YBYThankYou.__testOnly.getRuntimeWhatsAppTemplate === "function", "Test-only helper object must include runtime template access.");
}

function testRuntimeReturnWins() {
  const runtime = createRuntime();
  assert(runtime.nodes.returnLink.getAttribute("href") === "/current-return/", "Runtime return URL must win.");
}

function testRuntimeYouTubeWins() {
  const runtime = createRuntime();
  assert(runtime.nodes.videoFrame.getAttribute("src").indexOf("CurrentVideo123") > -1, "Runtime YouTube ID must win.");
  assert(runtime.nodes.videoFrame.getAttribute("src").indexOf("LegacyProjectVideo") === -1, "Stale project YouTube ID must not win.");
  assert(runtime.nodes.videoFrame.getAttribute("src").indexOf("LegacyPageVideo") === -1, "Stale page YouTube ID must not win.");
}

function testRuntimeThankYouWins() {
  const runtime = createRuntime({
    search: "?case_id=YBY-IRR-20260721-ABC234"
  });
  assert(runtime.window.YBYLead.buildThankYouUrl("YBY-IRR-20260721-ABC234") === "/current-thank-you/?case_id=YBY-IRR-20260721-ABC234", "Global Runtime must win when no explicit page override exists.");
}

function testExplicitPageThankYouOverrideWins() {
  const runtime = createRuntime({
    pageProfileOverrides: {
      thankYouUrl: "/lp/thank-you-glass-bottle-oem/"
    }
  });
  const url = runtime.window.YBYLead.buildThankYouUrl("YBY-IRR-20260721-ABC234", {
    thankYouUrl: "https://evil.example.test/",
    redirect_url: "https://evil.example.test/",
    name: "Private Name",
    email: "private@example.test",
    whatsapp: "+8613800000000",
    company: "Private Company"
  });

  assert(url === "/lp/thank-you-glass-bottle-oem/?case_id=YBY-IRR-20260721-ABC234", "Explicit page Thank You override must win over global runtime.");
  assert(url.indexOf("evil.example.test") === -1, "Client payload must not choose the Thank You target.");
  ["Private Name", "private@example.test", "+8613800000000", "Private Company"].forEach((value) => {
    assert(url.indexOf(value) === -1, "Thank You URL must not expose PII.");
  });
  assert(url.split("?")[1] === "case_id=YBY-IRR-20260721-ABC234", "Only case_id may be appended to the Thank You URL.");
}

function testMergedPageProfileCannotOverrideGlobal() {
  const runtime = createRuntime({
    pageProfile: {
      thankYouUrl: "/merged-page-thank-you/"
    },
    pageProfileOverrides: {}
  });

  assert(runtime.window.YBYLead.buildThankYouUrl("YBY-IRR-20260721-ABC234") === "/current-thank-you/?case_id=YBY-IRR-20260721-ABC234", "Merged page profile must not override global runtime without an explicit override.");
}

function testLegacyFallbackRemains() {
  const runtimeProject = createRuntime({
    runtime: {
      catalogUrl: "",
      returnPageUrl: "",
      thankYouUrl: "",
      youtubeVideoId: ""
    }
  });

  assert(runtimeProject.nodes.catalogLink.getAttribute("href") === "/legacy-project-catalog.pdf", "Project catalog fallback must remain.");
  assert(runtimeProject.nodes.returnLink.getAttribute("href") === "/legacy-project-return/", "Project return fallback must remain.");
  assert(runtimeProject.nodes.videoFrame.getAttribute("src").indexOf("LegacyProjectVideo") > -1, "Project YouTube fallback must remain.");
  assert(runtimeProject.window.YBYLead.buildThankYouUrl("YBY-IRR-20260721-ABC234") === "/legacy-project-thank-you/?case_id=YBY-IRR-20260721-ABC234", "Project Thank You fallback must remain.");

  const runtimePage = createRuntime({
    runtime: {
      catalogUrl: "",
      returnPageUrl: "",
      thankYouUrl: "",
      youtubeVideoId: ""
    },
    project: {
      catalogUrl: "",
      returnPageUrl: "",
      thankYouUrl: "",
      youtubeVideoId: ""
    }
  });

  assert(runtimePage.nodes.catalogLink.getAttribute("href") === "/legacy-page-catalog.pdf", "Page catalog fallback must remain when project is empty.");
  assert(runtimePage.nodes.returnLink.getAttribute("href") === "/legacy-page-return/", "Page return fallback must remain when project is empty.");
  assert(runtimePage.nodes.videoFrame.getAttribute("src").indexOf("LegacyPageVideo") > -1, "Page YouTube fallback must remain when project is empty.");
  assert(runtimePage.window.YBYLead.buildThankYouUrl("YBY-IRR-20260721-ABC234") === "/legacy-page-thank-you/?case_id=YBY-IRR-20260721-ABC234", "Page Thank You fallback must remain when project is empty.");
}

function testLegacyEscapedNewlines() {
  const runtime = createRuntime({
    search: "?case_id=YBY-IRR-20260721-ABC234",
    runtime: {
      whatsappMessageTemplate: "Hello YBY\\\\n\\\\nMy Case ID: {case_id}\\\\nCountry: Tanzania"
    }
  });
  const message = decodeWhatsAppText(runtime.window.YBYThankYou.buildWhatsAppUrl());

  assert(message.indexOf("Hello YBY") === 0, "Legacy escaped message must remain readable.");
  assert(message.indexOf("\\n") === -1, "Legacy escaped newlines must become real line breaks.");
  assert(message.split("\n").length >= 3, "Legacy escaped newlines must preserve paragraph breaks.");
  assert(message.indexOf("My Case ID: YBY-IRR-") > -1, "Legacy escaped message must interpolate case ID.");
}

function testWhatsAppTemplateInterpolation() {
  const runtime = createRuntime({
    search: "?case_id=YBY-IRR-20260721-ABC234",
    runtime: {
      whatsappMessageTemplate: "Hello {brand_name}, my reference is {case_id}."
    }
  });
  const message = decodeWhatsAppText(runtime.window.YBYThankYou.buildWhatsAppUrl());

  assert(message === "Hello YBY Irrigation, my reference is YBY-IRR-20260721-ABC234.", "Custom WhatsApp tokens must interpolate.");
}

function testMandatoryCaseIdAppend() {
  const runtime = createRuntime({
    search: "?case_id=YBY-IRR-20260721-ABC234",
    runtime: {
      whatsappMessageTemplate: "Please contact me about this project."
    }
  });
  const message = decodeWhatsAppText(runtime.window.YBYThankYou.buildWhatsAppUrl());

  assert(message.indexOf("Please contact me about this project.\n\nCase ID: YBY-IRR-20260721-ABC234.") > -1, "Case ID must be appended when absent.");
  assert(runtime.window.YBYThankYou.__testOnly.countExactOccurrences(message, "YBY-IRR-20260721-ABC234") === 1, "Case ID must appear exactly once.");
}

function testDefaultIrrigationMessage() {
  const runtime = createRuntime({
    search: "?case_id=YBY-IRR-20260721-ABC234"
  });
  const message = decodeWhatsAppText(runtime.window.YBYThankYou.buildWhatsAppUrl());

  assert(message.indexOf("Hello YBY Irrigation, I submitted a website inquiry.") === 0, "Default message must use the neutral website inquiry copy.");
  assert(message.indexOf("Project Summary") > -1, "Default irrigation message must include project summary.");
  assert(message.indexOf("Country: Tanzania") > -1, "Default irrigation message must include country.");
  assert(message.indexOf("Crop: Vegetables") > -1, "Default irrigation message must include crop.");
  assert(message.indexOf("Farm Size: 1–5 ha") > -1, "Default irrigation message must include farm size.");
  assert(message.indexOf("Water Source: Well") > -1, "Default irrigation message must include water source.");
  assert(message.indexOf("Recommended System: Drip Irrigation System") > -1, "Default irrigation message must include recommended system.");
  assert(message.indexOf("Estimated Range: USD 800–3,500") > -1, "Default irrigation message must include estimated range.");
  assert(/\nSource:/i.test(message) === false, "Default irrigation message must not expose internal source.");
  assert(message.indexOf("final_ctan") === -1, "Default irrigation message must not expose internal source tokens.");
  assert(message.indexOf("\\n") === -1, "Default irrigation message must use real line breaks.");
}

function testRuntimeTemplateWins() {
  const runtime = createRuntime({
    search: "?case_id=YBY-IRR-20260721-ABC234",
    runtime: {
      whatsappMessageTemplate: "Hello {brand_name}, runtime ref {case_id}."
    },
    project: {
      whatsappMessage: "legacy project template"
    },
    pageProfile: {
      whatsappMessage: "legacy page template"
    }
  });
  const message = decodeWhatsAppText(runtime.window.YBYThankYou.buildWhatsAppUrl());

  assert(message === "Hello YBY Irrigation, runtime ref YBY-IRR-20260721-ABC234.", "Runtime WhatsApp template must govern the final customer-facing message.");
  assert(message.indexOf("legacy project template") === -1, "Legacy project WhatsApp template must not be used.");
  assert(message.indexOf("legacy page template") === -1, "Legacy page WhatsApp template must not be used.");
}

function testOptionalTokenLineRemovalPreservesParagraphs() {
  const runtime = createRuntime({
    search: "?case_id=YBY-IRR-20260721-ABC234",
    runtime: {
      whatsappMessageTemplate: "Hello {brand_name}\n\nWater Source: {water_source}\nEstimated Range: {estimated_range}\n\nCase ID: {case_id}"
    },
    project: {
      water_source: "",
      estimated_range: ""
    }
  });
  const message = decodeWhatsAppText(runtime.window.YBYThankYou.buildWhatsAppUrl());

  assert(message === "Hello YBY Irrigation\n\nCase ID: YBY-IRR-20260721-ABC234", "Empty token lines must be removed without collapsing intentional paragraph breaks.");
}

function testSummaryAliasResolution() {
  const runtime = createRuntime({
    search: "?case_id=YBY-IRR-20260721-ABC234",
    project: {
      farmSize: "Camel Farm Size",
      farm_size: "Snake Farm Size",
      waterSource: "River",
      water_source: "Canal",
      recommendedSystem: "Pivot",
      recommended_system: "Drip",
      estimatedRange: "USD 999",
      estimated_range: "USD 111"
    }
  });
  const message = decodeWhatsAppText(runtime.window.YBYThankYou.buildWhatsAppUrl());

  assert(message.indexOf("Farm Size: Camel Farm Size") > -1, "Camel-case farm size should resolve first when present.");
  assert(message.indexOf("Farm Size: Snake Farm Size") === -1, "Summary should not duplicate farm size aliases.");
  assert(message.indexOf("Water Source: River") > -1, "Camel-case water source should resolve first when present.");
  assert(message.indexOf("Recommended System: Pivot") > -1, "Camel-case recommended system should resolve first when present.");
  assert(message.indexOf("Estimated Range: USD 999") > -1, "Camel-case estimated range should resolve first when present.");
}

function testEmptyFieldSuppression() {
  const runtime = createRuntime({
    search: "?case_id=YBY-IRR-20260721-ABC234",
    project: {
      water_source: "",
      estimated_range: ""
    }
  });
  const message = decodeWhatsAppText(runtime.window.YBYThankYou.buildWhatsAppUrl());

  assert(message.indexOf("Water Source:") === -1, "Empty Water Source field must be suppressed.");
  assert(message.indexOf("Estimated Range:") === -1, "Empty Estimated Range field must be suppressed.");
}

function testInternalSourceExclusion() {
  const runtime = createRuntime({
    search: "?case_id=YBY-IRR-20260721-ABC234",
    project: {
      source: "final_ctan",
      source_component: "inquiry_modal",
      source_preset: "irrigation_quick_inquiry",
      source_page: "Irrigation Inquiry Test"
    }
  });
  const message = decodeWhatsAppText(runtime.window.YBYThankYou.buildWhatsAppUrl());

  assert(/\nSource:/i.test(message) === false, "Customer WhatsApp message must not expose Source label.");
  assert(message.indexOf("final_ctan") === -1, "Customer WhatsApp message must not expose final_ctan.");
  assert(message.indexOf("inquiry_modal") === -1, "Customer WhatsApp message must not expose source_component.");
  assert(message.indexOf("irrigation_quick_inquiry") === -1, "Customer WhatsApp message must not expose source_preset.");
}

function testBottleDefaultMessage() {
  const runtime = createRuntime({
    search: "?case_id=YBY-BCB-20260721-ABC234",
    runtime: {
      siteBrandName: "BC Glass Bottles",
      caseIdBrandCode: "BCB",
      defaultProductInterest: "glass bottle wholesale"
    },
    project: {
      productInterest: "glass bottle wholesale",
      crop: "",
      farm_size: "",
      water_source: "",
      recommended_system: "",
      estimated_range: ""
    }
  });
  const message = decodeWhatsAppText(runtime.window.YBYThankYou.buildWhatsAppUrl());

  assert(message.indexOf("Hello BC Glass Bottles") === 0, "Bottle default message must use Bottle brand.");
  assert(message.indexOf("YBY-BCB-20260721-ABC234") > -1, "Bottle default message must include Bottle case ID.");
  assert(message.indexOf("YBY Irrigation") === -1, "Bottle default message must not leak Irrigation identity.");
  assert(message.toLowerCase().indexOf("irrigation solution") === -1, "Bottle default message must not hardcode irrigation wording.");
  assert(message.indexOf("I submitted a website inquiry.") > -1, "Bottle default message must use neutral shared copy.");
}

function testSessionStorageHydrationAndTracking() {
  const runtime = createRuntime({
    search: "?case_id=YBY-IRR-20260721-ABC234"
  });
  const caseId = runtime.window.YBYLead.getCaseId();

  assert(caseId === "YBY-IRR-20260721-ABC234", "Thank You init must hydrate exact case ID from URL.");
  assert(runtime.sessionStorage.getItem("yby_case_id") === "YBY-IRR-20260721-ABC234", "Thank You init must persist exact case ID to sessionStorage.");
  assert(runtime.nodes.caseNode.textContent === "YBY-IRR-20260721-ABC234", "Rendered case ID node must contain exact case ID.");
  assert(runtime.nodes.caseInput.value === "YBY-IRR-20260721-ABC234", "Rendered case ID input must contain exact case ID.");
  assert(runtime.dataLayer.length === 2, "First Thank You init must push one page_view and one generate_lead.");
  assert(runtime.dataLayer[0].event === "thank_you_page_view", "First Thank You event must be thank_you_page_view.");
  assert(runtime.dataLayer[1].event === "generate_lead", "Second Thank You event must be generate_lead.");
  assert(runtime.dataLayer[1].event_id === "YBY-IRR-20260721-ABC234", "generate_lead must reference the exact case ID.");
  assert(runtime.sessionStorage.getItem("yby_generate_lead_counted_YBY-IRR-20260721-ABC234") === "1", "Generate Lead same-session dedupe key must be stored.");

  runtime.window.YBYThankYou.init();

  assert(runtime.dataLayer.length === 3, "Second Thank You init may add one page_view only.");
  assert(runtime.dataLayer[2].event === "thank_you_page_view", "Second init must add only thank_you_page_view.");
}

function testTrackingPiiExclusion() {
  const runtime = createRuntime({
    search: "?case_id=YBY-IRR-20260721-ABC234"
  });
  const blockedKeys = ["name", "email", "whatsapp", "phone", "company", "message", "project_details", "payload", "form_data", "rest_payload"];

  runtime.dataLayer.forEach((entry) => {
    blockedKeys.forEach((key) => {
      assert(!Object.prototype.hasOwnProperty.call(entry, key), "Tracking payload must not include PII key: " + key);
    });
  });
}

function testNonThankYouExclusion() {
  const bundle = { document: new MockDocument(), nodes: {} };
  const runtime = createRuntime({
    documentBundle: bundle,
    pathname: "/plain-page/",
    search: "?case_id=YBY-IRR-20260721-ABC234"
  });

  assert(runtime.dataLayer.length === 0, "Non-Thank You pages must not emit Thank You tracking.");
}

function testInquiryPageExclusion() {
  const runtime = runInquiryRuntime();

  assert(runtime.dataLayer.length === 0, "Inquiry runtime must not emit Thank You tracking events on init.");
}

const tests = [
  ["test_helpers_hidden", testTestHelpersHiddenByDefault],
  ["test_helpers_enabled", testTestHelpersEnabledExplicitly],
  ["runtime_catalog", testRuntimeCatalogWins],
  ["runtime_return", testRuntimeReturnWins],
  ["runtime_youtube", testRuntimeYouTubeWins],
  ["runtime_thank_you", testRuntimeThankYouWins],
  ["explicit_page_thank_you", testExplicitPageThankYouOverrideWins],
  ["merged_page_profile_boundary", testMergedPageProfileCannotOverrideGlobal],
  ["legacy_fallback", testLegacyFallbackRemains],
  ["runtime_template", testRuntimeTemplateWins],
  ["legacy_newlines", testLegacyEscapedNewlines],
  ["whatsapp_tokens", testWhatsAppTemplateInterpolation],
  ["empty_token_lines", testOptionalTokenLineRemovalPreservesParagraphs],
  ["mandatory_case_id", testMandatoryCaseIdAppend],
  ["default_irrigation", testDefaultIrrigationMessage],
  ["summary_aliases", testSummaryAliasResolution],
  ["empty_field_suppression", testEmptyFieldSuppression],
  ["internal_source_exclusion", testInternalSourceExclusion],
  ["default_bottle", testBottleDefaultMessage],
  ["session_tracking", testSessionStorageHydrationAndTracking],
  ["tracking_pii", testTrackingPiiExclusion],
  ["non_thank_you", testNonThankYouExclusion],
  ["inquiry_exclusion", testInquiryPageExclusion]
];

tests.forEach(([name, test]) => {
  test();
  process.stdout.write(name + ":PASS\n");
});
