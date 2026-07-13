# Production Verification

Before WordPress production verification, confirm:

- PHP lint passes for every plugin PHP file
- the ZIP root is `yby-core/`
- the ZIP contains only governed plugin files
- the REST lead response is unchanged
- Notification Center recipient and branding settings are available in admin
- email subject template rendering works
- email branding fallback uses Brand settings where appropriate

