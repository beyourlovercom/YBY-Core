(function () {
  "use strict";

  function revealAfterFirstScreen(dock) {
    if (!dock || !dock.classList.contains("yby-global-inquiry-dock--mode-dual")) return;
    if (!dock.classList.contains("yby-global-inquiry-dock--timing-after_first_screen")) return;
    if (dock.classList.contains("yby-global-inquiry-dock--admin-preview")) return;
    var revealed = false;
    function reveal() {
      if (revealed || window.scrollY <= window.innerHeight) return;
      revealed = true;
      dock.classList.add("is-visible");
      window.removeEventListener("scroll", reveal);
    }
    dock.classList.add("yby-global-inquiry-dock--deferred");
    window.addEventListener("scroll", reveal, { passive: true });
    reveal();
  }

  function hydrateWhatsAppLinks(root) {
    if (!window.YBYThankYou || typeof window.YBYThankYou.buildWhatsAppUrl !== "function") return;
    (root || document).querySelectorAll("[data-yby-whatsapp-link]").forEach(function (link) {
      link.setAttribute("href", window.YBYThankYou.buildWhatsAppUrl());
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".yby-global-inquiry-dock").forEach(revealAfterFirstScreen);
    hydrateWhatsAppLinks(document);
  });
}());
