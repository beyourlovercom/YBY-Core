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
- SHA256 generated

## Required lead intake behavior

- `POST /wp-json/yby/v1/leads`
- successful new lead returns `mail_sent: true`
- successful duplicate returns `mail_sent: false`
- failures expose only safe public error codes and messages
- no recipient email, stack trace, server path, or SMTP secret is exposed
