# Email Branding

The Email provider includes presentation settings for branded notifications.

Configured values:

- Company Name
- Company Website
- Company Phone
- Company WhatsApp
- Footer Copyright
- Email Logo
- Email Reverse Logo

Defaults:

- Company Name: `YBY Irrigation`
- Company Website: `https://ybyirrigation.com/`
- Company Phone: empty
- Company WhatsApp: empty
- Footer Copyright: `© YBY Irrigation. All rights reserved.`

Behavior:

- email logo fields store URLs only
- blank email logo fields fall back to Brand settings when available
- branding fields are presentation-only and do not affect REST payloads
- branding values are sanitized before rendering

