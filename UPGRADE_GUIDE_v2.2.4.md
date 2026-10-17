# Andy Core v2.2.4 Canary — controlled WordPress Update Now UAT

**Not yet approved to publish, deploy or install.** This is a future test plan only.

1. Confirm signed GitHub v2.2.4 **Prerelease** and full ZIP/metadata Ed25519 verification; confirm Stable latest remains separately managed.
2. Check exact target Dev site whitelist, plugin health, previous-version protected code rollback, database version 1.5.0 and the installed plugin version.
3. Preserve `dev.beyourlover.com` manual update policy (`canary`, auto updates OFF). Existing Dev has local source-only #138/#140 overlays; v2.2.4 signed package is meant to promote these exact fixes into a versioned artifact **after approval**.
4. Before ever publishing the Canary, isolate all other auto-ON Dev sites (e.g. Bottle Dev) from unplanned update exposure; restore their earlier setting after a separately authorized test.
5. In WordPress, click Andy Core → Updates → Check for Updates; go to Plugins → Andy Core → Update Now. **Owner** confirms the version, left-side native L2, Chinese L2/L3, permissions and no regressions.
6. Never test with real production orders/payments, real mail or data migration. Public Stable and Production site deployments require separate authorization.
