"use strict";

const fs = require("fs");
const path = require("path");

const source = fs.readFileSync(
  path.join(__dirname, "..", "public", "assets", "js", "yby-google-one-tap.js"),
  "utf8"
);

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

function classList() {
  const values = new Set();

  return {
    add(value) {
      values.add(value);
    },
    contains(value) {
      return values.has(value);
    },
    toggle(value, force) {
      if (force) {
        values.add(value);
      } else {
        values.delete(value);
      }
    }
  };
}

function createRuntime(authResult, sessionResult = { success: true, authenticated: true }) {
  const events = [];
  const requests = [];
  const controls = [{ hidden: false }, { hidden: false }];
  const elements = {};
  const counters = {
    gisLoads: 0,
    initialize: 0,
    prompt: 0,
    cancel: 0,
    assign: 0,
    replace: 0,
    reload: 0
  };
  const endpointCounts = {
    challenge: 0,
    auth: 0,
    session: 0
  };
  let initializedConfig = null;

  function createElement(tag) {
    const listeners = {};

    return {
      tagName: String(tag).toUpperCase(),
      classList: classList(),
      hidden: false,
      id: "",
      src: "",
      textContent: "",
      addEventListener(name, callback) {
        listeners[name] = callback;
      },
      setAttribute() {},
      _listeners: listeners
    };
  }

  const document = {
    documentElement: { classList: classList() },
    body: {
      appendChild(node) {
        if (node.id) {
          elements[node.id] = node;
        }
      }
    },
    head: {
      appendChild(script) {
        counters.gisLoads += 1;
        window.google = {
          accounts: {
            id: {
              initialize(options) {
                counters.initialize += 1;
                initializedConfig = options;
              },
              prompt() {
                counters.prompt += 1;
                Promise.resolve().then(() => initializedConfig.callback({ credential: "signed-google-token" }));
              },
              cancel() {
                counters.cancel += 1;
              }
            }
          }
        };
        Promise.resolve().then(() => script._listeners.load());
      }
    },
    createElement,
    getElementById(id) {
      return elements[id] || null;
    },
    querySelector(selector) {
      if (selector === 'script[src="https://accounts.google.com/gsi/client"]') {
        return null;
      }

      return null;
    },
    querySelectorAll(selector) {
      return selector === ".yby-social-login, .yby-social-login-login-page" ? controls : [];
    },
    dispatchEvent(event) {
      events.push({ name: event.type, detail: event.detail });
    }
  };

  const storage = {};
  const window = {
    YBYGoogleOneTapConfig: {
      clientId: "test.apps.googleusercontent.com",
      challengeEndpoint: "https://example.test/wp-json/yby/v1/auth/google/onetap/challenge",
      authEndpoint: "https://example.test/wp-json/yby/v1/auth/google/onetap",
      sessionEndpoint: "https://example.test/wp-json/yby/v1/auth/google/onetap/session",
      requestHeader: "X-Andy-Core-One-Tap",
      sessionHeader: "X-Andy-Core-One-Tap-Session"
    },
    crypto: {
      getRandomValues(bytes) {
        for (let index = 0; index < bytes.length; index += 1) {
          bytes[index] = index + 1;
        }
        return bytes;
      }
    },
    sessionStorage: {
      getItem(key) {
        return storage[key] || null;
      },
      setItem(key, value) {
        storage[key] = value;
      }
    },
    location: {
      assign() {
        counters.assign += 1;
      },
      replace() {
        counters.replace += 1;
      },
      reload() {
        counters.reload += 1;
      }
    },
    fetch(url, options) {
      requests.push({ url, options });
      let data;

      if (url.includes("/challenge")) {
        endpointCounts.challenge += 1;
        data = { success: true, nonce: "fresh-one-tap-challenge" };
      } else if (url.endsWith("/session")) {
        endpointCounts.session += 1;
        data = sessionResult;
      } else if (url.endsWith("/onetap")) {
        endpointCounts.auth += 1;
        data = authResult;
      } else {
        throw new Error("Unexpected endpoint: " + url);
      }

      return Promise.resolve({
        json() {
          return Promise.resolve(data);
        }
      });
    }
  };

  class CustomEvent {
    constructor(type, options) {
      this.type = type;
      this.detail = options.detail;
    }
  }

  const execute = new Function("window", "document", "CustomEvent", "Uint8Array", "Promise", source);
  execute(window, document, CustomEvent, Uint8Array, Promise);

  return {
    counters,
    controls,
    document,
    endpointCounts,
    events,
    requests,
    status() {
      return elements["yby-google-one-tap-status"] || null;
    },
    initializedConfig() {
      return initializedConfig;
    }
  };
}

async function settle() {
  await new Promise((resolve) => setImmediate(resolve));
  await new Promise((resolve) => setImmediate(resolve));
}

async function run() {
  const success = createRuntime({ success: true, code: "success" });
  await settle();
  const config = success.initializedConfig();

  assert(success.counters.gisLoads === 1, "GIS must load exactly once.");
  assert(success.counters.initialize === 1, "Google initialize must run exactly once.");
  assert(success.counters.prompt === 1, "Google prompt must run exactly once.");
  assert(config.client_id === "test.apps.googleusercontent.com", "Configured Client ID must be used.");
  assert(config.nonce === "fresh-one-tap-challenge", "Fresh challenge must become the Google nonce.");
  assert(config.context === "signin", "One Tap context must be signin.");
  assert(config.auto_select === false, "One Tap automatic account selection must remain disabled.");
  assert(config.cancel_on_tap_outside === true, "Tap-outside cancellation must remain enabled.");
  assert(!Object.prototype.hasOwnProperty.call(config, "login_uri"), "One Tap must not use login_uri.");
  assert(success.requests.length === 3, "Success must perform challenge, auth, and cookie confirmation requests.");
  assert(success.endpointCounts.challenge === 1, "Success must issue exactly one login challenge.");
  assert(success.endpointCounts.auth === 1, "Success must call authentication exactly once.");
  assert(success.endpointCounts.session === 1, "Success must call the dedicated session endpoint exactly once.");
  assert(success.requests.every((request) => request.options.credentials === "same-origin"), "Every request must use same-origin credentials.");
  assert(success.requests.every((request) => request.options.cache === "no-store"), "Every request must bypass response caches.");
  assert(success.counters.assign === 0 && success.counters.replace === 0 && success.counters.reload === 0, "Success must not redirect or reload.");
  assert(success.counters.cancel === 1, "Success must close the One Tap prompt.");
  assert(success.document.documentElement.classList.contains("yby-social-login-authenticated"), "Success must set the non-PII document state.");
  assert(success.controls.every((control) => control.hidden), "Success must hide active Social Login controls.");
  assert(success.status().textContent === "Signed in successfully.", "Success state message is incorrect.");
  assert(success.events.length === 1 && success.events[0].name === "yby:social-login:success", "Success event must dispatch once.");
  assert(JSON.stringify(success.events[0].detail) === '{"provider":"google"}', "Success event detail must contain provider only.");

  const failure = createRuntime({
    success: false,
    code: "role_not_allowed",
    message: "Google login is not available for this account. Please use the existing WordPress login."
  });
  await settle();
  assert(failure.counters.gisLoads === 1 && failure.counters.initialize === 1 && failure.counters.prompt === 1, "Failure flow must initialize and prompt only once.");
  assert(failure.counters.cancel === 1, "Failure must close the prompt.");
  assert(failure.requests.length === 2, "Failure must not attempt cookie confirmation or retry.");
  assert(failure.endpointCounts.challenge === 1 && failure.endpointCounts.auth === 1 && failure.endpointCounts.session === 0, "Authentication failure must not call session confirmation.");
  assert(failure.counters.assign === 0 && failure.counters.replace === 0 && failure.counters.reload === 0, "Failure must remain on the current page.");
  assert(failure.events.length === 1 && failure.events[0].name === "yby:social-login:error", "Error event must dispatch once.");
  assert(JSON.stringify(failure.events[0].detail) === '{"provider":"google"}', "Error event detail must contain provider only.");
  assert(failure.status().textContent.includes("Google login is not available"), "Failure must show the safe server message.");

  const sessionFailure = createRuntime(
    { success: true, code: "success" },
    {
      success: false,
      authenticated: false,
      code: "login_failed",
      message: "The account was verified, but sign-in could not be completed."
    }
  );
  await settle();
  assert(sessionFailure.endpointCounts.challenge === 1, "Session confirmation must not request a second challenge.");
  assert(sessionFailure.endpointCounts.auth === 1 && sessionFailure.endpointCounts.session === 1, "Session failure must call authentication and dedicated confirmation once.");
  assert(sessionFailure.requests.length === 3, "Session failure must not retry any endpoint.");
  assert(sessionFailure.events.length === 1 && sessionFailure.events[0].name === "yby:social-login:error", "Session failure must call completeError.");
  assert(sessionFailure.status().textContent === "The account was verified, but sign-in could not be completed.", "Session failure message is incorrect.");
  assert(sessionFailure.counters.assign === 0 && sessionFailure.counters.replace === 0 && sessionFailure.counters.reload === 0, "Session failure must not redirect or reload.");

  assert(!source.includes("location.assign"), "Runtime must not call location.assign.");
  assert(!source.includes("location.replace"), "Runtime must not call location.replace.");
  assert(!source.includes("location.reload"), "Runtime must not reload the page.");
  assert(!source.includes("use_fedcm_for_prompt"), "Deprecated FedCM prompt configuration must remain absent.");
  assert(!source.includes("login_hint"), "One Tap must not configure login_hint.");

  [
    "gis_load_once",
    "initialize_once",
    "prompt_once",
    "auto_select_false",
    "no_login_uri",
    "same_origin_credentials",
    "dedicated_session_confirmation",
    "challenge_not_reused_for_confirmation",
    "same_page_success",
    "success_event_no_pii",
    "same_page_failure",
    "error_event_no_pii",
    "no_automatic_retry"
  ].forEach((name) => process.stdout.write(name + ":PASS\n"));
}

run().catch((error) => {
  process.stderr.write(error.stack + "\n");
  process.exitCode = 1;
});
