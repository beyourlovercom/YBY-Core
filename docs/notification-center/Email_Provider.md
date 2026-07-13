# Email Provider

The Email provider is the first Notification Center implementation.

Responsibilities:

- build the HTML notification
- build the plain-text AltBody
- apply `To`, `CC`, `BCC`, and `Reply-To`
- ignore invalid email addresses
- return only `mail_sent` and `mail_error_code` internally

Configured settings:

- Primary Recipient Email
- CC Recipient Emails
- BCC Recipient Emails
- Reply-To Policy

Default behavior:

- primary recipient: `sale@yby-irrigation.com`
- CC: `yishitongshop@gmail.com`
- BCC: empty
- reply-to policy: `auto`
