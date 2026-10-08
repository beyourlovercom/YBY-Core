# YBY Core — Site-Specific Development Environments (V2)

## Purpose
Andy Core is shared by several websites. **Source authority is always GitHub**, but runtime integration/UAT differs by site. This document supersedes the previous claim that every site must be Local-first.

## Environment matrix

| Consumer site | Development/integration and Owner UAT | Optional/earlier verification | Production |
| --- | --- | --- | --- |
| **BYL Website V3** | **`https://dev.beyourlover.com` (Dev-first)** | `https://localdev.beyourlover.com` for isolation/high-risk experiments | Existing `www.beyourlover.com` untouched; eventual full Dev-to-WWW cutover needs Owner authorization |
| YBY Bottle | Established Windows Local-first process | `D:\ai\devybybottle` → `https://localdev.ybybottle.com` | Separate explicit release gate |
| YBY Irrigation | Established Windows Local-first process | `D:\ai\devybyirrigation` → `https://localdev.ybyirrigation.com` | Separate explicit release gate |

For shared Core changes run scoped unit/compatibility checks before site integration. A successful Local test in Bottle does not constitute BYL Dev Owner UAT PASS; BYL Dev-first also does not revoke Local-first on the other sites.

## Canonical source and local sync
- Repo: `beyourlovercom/YBY-Core` (`main`), Windows checkout `D:\ai\_repos\YBY-Core`.
- Never edit the installed plugin runtime copy as source; use a Git branch/worktree and deploy reproducible package/files.
- For the **Bottle LocalWP** runtime:
  `powershell -ExecutionPolicy Bypass -File D:\ai\_system\sync-yby-core-local.ps1`
- Local runtime destination: `D:\ai\devybybottle\app\public\wp-content\plugins\yby-core`.
- After `YBY_CORE_LOCAL_SYNC=PASS`, run local browser/UAT checks as relevant to that work package.
- For BYL, use the BYL Website V3 `AGENTS.md` and Dev integration gate instead of treating the Bottle-specific sync as a prerequisite.

## Secrets and access
Never store or publish real credentials in Git, docs, source, screenshots or logs. Local-only WordPress REST credentials may live in a protected local `.env.ybyirrigation.local` containing `WP_URL`, `WP_USER`, `WP_APP_PASSWORD`. Load them only for relevant authorized Irrigation integration tests; never print them or copy them into BYL/Dev/Production.

## Production and rollback
Every site requires its own explicit production release authorization. BYL will complete a separate whole-site cutover, not deploy each new Andy Core/Commerce feature individually onto the legacy WWW. Preserve exact SHA, dependency compatibility, backup and read-only smoke evidence.
