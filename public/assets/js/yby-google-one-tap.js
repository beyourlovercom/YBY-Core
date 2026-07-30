(function (window, document) {
  "use strict";

  var config = window.YBYGoogleOneTapConfig || {};
  var scriptPromise = null;
  var initialized = false;
  var prompted = false;
  var finished = false;
  var sessionKey = "yby_google_one_tap_session";

  if (
    window.__YBYGoogleOneTapStarted === true ||
    !config.clientId ||
    !config.challengeEndpoint ||
    !config.authEndpoint ||
    !config.sessionEndpoint ||
    !config.requestHeader ||
    !config.sessionHeader
  ) {
    return;
  }

  window.__YBYGoogleOneTapStarted = true;

  function randomSessionId() {
    var bytes = new Uint8Array(24);

    if (!window.crypto || typeof window.crypto.getRandomValues !== "function") {
      return "";
    }

    window.crypto.getRandomValues(bytes);

    return Array.prototype.map.call(bytes, function (value) {
      return value.toString(16).padStart(2, "0");
    }).join("");
  }

  function getSessionId() {
    var value = "";

    try {
      value = window.sessionStorage.getItem(sessionKey) || "";
    } catch (error) {
      value = "";
    }

    if (/^[A-Za-z0-9_-]{16,128}$/.test(value)) {
      return value;
    }

    value = randomSessionId();

    if (value) {
      try {
        window.sessionStorage.setItem(sessionKey, value);
      } catch (error) {
        // The in-memory value still limits this page request.
      }
    }

    return value;
  }

  var sessionId = getSessionId();

  if (!sessionId) {
    return;
  }

  function requestHeaders() {
    var headers = {
      "Content-Type": "application/json"
    };

    headers[config.requestHeader] = "1";
    headers[config.sessionHeader] = sessionId;

    return headers;
  }

  function postJson(url, body) {
    return window.fetch(url, {
      method: "POST",
      credentials: "same-origin",
      cache: "no-store",
      headers: requestHeaders(),
      body: JSON.stringify(body)
    }).then(function (response) {
      return response.json().catch(function () {
        return {
          success: false,
          code: "invalid_request",
          message: "The Google login request was invalid."
        };
      });
    });
  }

  function loadGoogleIdentityServices() {
    if (window.google && window.google.accounts && window.google.accounts.id) {
      return Promise.resolve();
    }

    if (scriptPromise) {
      return scriptPromise;
    }

    scriptPromise = new Promise(function (resolve, reject) {
      var existing = document.querySelector('script[src="https://accounts.google.com/gsi/client"]');
      var script = existing || document.createElement("script");

      script.addEventListener("load", resolve, { once: true });
      script.addEventListener("error", reject, { once: true });

      if (!existing) {
        script.src = "https://accounts.google.com/gsi/client";
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
      }
    });

    return scriptPromise;
  }

  function cancelPrompt() {
    if (window.google && window.google.accounts && window.google.accounts.id) {
      window.google.accounts.id.cancel();
    }
  }

  function showMessage(message, success) {
    var node = document.getElementById("yby-google-one-tap-status");

    if (!node) {
      node = document.createElement("div");
      node.id = "yby-google-one-tap-status";
      node.className = "yby-google-one-tap-status";
      node.setAttribute("role", "status");
      node.setAttribute("aria-live", "polite");
      document.body.appendChild(node);
    }

    node.textContent = String(message || "");
    node.classList.toggle("is-success", success === true);
    node.classList.toggle("is-error", success !== true);
  }

  function dispatch(name) {
    document.dispatchEvent(new CustomEvent(name, {
      detail: {
        provider: "google"
      }
    }));
  }

  function completeSuccess() {
    if (finished) {
      return;
    }

    finished = true;
    cancelPrompt();
    document.documentElement.classList.add("yby-social-login-authenticated");
    Array.prototype.forEach.call(document.querySelectorAll(".yby-social-login, .yby-social-login-login-page"), function (node) {
      node.hidden = true;
    });
    showMessage("Signed in successfully.", true);
    dispatch("yby:social-login:success");
  }

  function completeError(message) {
    if (finished) {
      return;
    }

    finished = true;
    cancelPrompt();
    showMessage(message || "Google sign-in could not be completed.", false);
    dispatch("yby:social-login:error");
  }

  function confirmSession() {
    return postJson(config.sessionEndpoint, {}).then(function (result) {
      return result && result.success === true && result.authenticated === true;
    });
  }

  function authenticateCredential(response, challenge) {
    var credential = response && typeof response.credential === "string" ? response.credential : "";

    if (!credential) {
      completeError("Google identity verification failed.");
      return;
    }

    postJson(config.authEndpoint, {
      credential: credential,
      challenge: challenge
    }).then(function (result) {
      if (!result || result.success !== true || result.code !== "success") {
        completeError(result && result.message);
        return;
      }

      confirmSession().then(function (confirmed) {
        if (confirmed) {
          completeSuccess();
        } else {
          completeError("The account was verified, but sign-in could not be completed.");
        }
      }).catch(function () {
        completeError("The account was verified, but sign-in could not be completed.");
      });
    }).catch(function () {
      completeError("Google sign-in could not be completed.");
    });
  }

  postJson(config.challengeEndpoint, {}).then(function (challengeResult) {
    if (!challengeResult || challengeResult.success !== true || !challengeResult.nonce || finished) {
      return;
    }

    return loadGoogleIdentityServices().then(function () {
      if (initialized || finished || !window.google || !window.google.accounts || !window.google.accounts.id) {
        return;
      }

      initialized = true;
      window.google.accounts.id.initialize({
        client_id: config.clientId,
        callback: function (response) {
          authenticateCredential(response, challengeResult.nonce);
        },
        nonce: challengeResult.nonce,
        context: "signin",
        auto_select: false,
        cancel_on_tap_outside: true
      });

      if (!prompted) {
        prompted = true;
        window.google.accounts.id.prompt();
      }
    });
  }).catch(function () {
    // One Tap availability failures remain silent on ordinary pages.
  });
}(window, document));
