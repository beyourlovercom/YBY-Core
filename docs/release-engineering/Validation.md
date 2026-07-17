# Validation

## Mandatory checks before release approval

- repository lineage audit complete
- governed baseline confirmed
- plugin version consistency confirmed
- public runtime compatibility confirmed
- PHP lint run against every plugin PHP file
- `git diff --check` clean
- no debug statements
- no unintended Google Tag or GTM injection
- no leaked recipient email in frontend payloads
- ZIP root structure verified
- ZIP main plugin file path verified
- ZIP internal paths use `/` separators only
- SHA256 generated

## ZIP internal path separator check

Before declaring a release package `release-ready`, `validated`, or `safe to upload`, the package must pass:

- ZIP root directory validation
- Main plugin file validation
- Internal path separator validation

Validation command:

```bash
unzip -l yby-core-vX.X.X.zip
```

Expected output example:

```text
yby-core/yby-core.php
yby-core/inc/
yby-core/public/
```

Invalid output:

```text
yby-core\yby-core.php
```

## Required lead intake behavior

- `POST /wp-json/yby/v1/leads`
- successful new lead returns `mail_sent: true`
- successful duplicate returns `mail_sent: false`
- failures expose only safe public error codes and messages
- no recipient email, stack trace, server path, or SMTP secret is exposed
