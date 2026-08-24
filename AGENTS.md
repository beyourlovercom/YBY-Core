# Andy Core Development Authority

## Canonical workflow

GPT is the analysis, planning, diagnosis, and review authority. GitHub is the canonical source and history authority. Local Codex Luna on the Windows development machine is the primary implementation runtime.

Development and release order is mandatory:

1. GitHub/GPT analysis and task definition.
2. Implement with local Codex Luna on Windows.
3. Test locally first.
4. Complete Local UAT first.
5. Only after Local UAT passes, deploy the validated build to the Dev website.
6. Complete Dev UAT against the real hosting environment.
7. Only after Dev UAT passes, deploy the same validated release to the WWW production site.

Never use Dev or WWW as the default development environment.

## Windows local development baseline

- Local development root: `D:\ai`
- YBY Bottle site: `D:\ai\devybybottle`
- YBY Bottle URL: `https://localdev.ybybottle.com`
- YBY Irrigation site: `D:\ai\devybyirrigation`
- YBY Irrigation URL: `https://localdev.ybyirrigation.com`
- Canonical Andy Core Git repository: `D:\ai\_repos\YBY-Core`
- Primary implementation runtime: local Codex CLI / Luna on Windows.

Prefer local debugging and local evidence whenever the issue can be reproduced locally. Use Dev only for environment-specific validation such as hosting behavior, SSL, mail, Cron, payments, webhooks, CDN/R2, permissions, and server/cache behavior.

## Repository boundaries

Do not commit full WordPress site contents, databases, uploads, caches, migration backups, generated logs, secrets, credentials, or machine-local runtime data to this repository.

GitHub stores source code, tests, documentation, release metadata, and development rules. WordPress site copies are Local/Dev/UAT environments, not source-of-truth Git repositories.

## Local WordPress Runtime Sync
- Canonical source is always `D:\ai\_repos\YBY-Core`.
- Do not edit the restored WordPress plugin copy as source code.
- After source changes, sync runtime files with:
  `powershell -ExecutionPolicy Bypass -File D:\ai\_system\sync-yby-core-local.ps1`
- Local runtime target:
  `D:\ai\devybybottle\app\public\wp-content\plugins\yby-core`
- Run local browser/UAT only after sync returns `YBY_CORE_LOCAL_SYNC=PASS`.
- Never copy `.git`, `.github`, docs, tests, releases, or repository-only metadata into the runtime plugin directory.