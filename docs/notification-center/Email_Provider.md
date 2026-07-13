# Email Provider

The Email provider is the first Notification Center implementation.

Responsibilities:

- build the HTML notification
- build the plain-text AltBody
- apply `To`, `CC`, `BCC`, and `Reply-To`
- ignore invalid email addresses
- render the subject from a governed template
- apply email branding from the Email settings, with Brand settings as fallback
- de-duplicate recipients across To, CC, and BCC where practical
- return only `mail_sent` and `mail_error_code` internally

Configured settings:

- Primary Recipient Email
- CC Recipient Emails
- BCC Recipient Emails
- Reply-To Policy
- Lead Email Subject Template
- Email Branding settings

Default behavior:

- primary recipient: `sale@yby-irrigation.com`
- CC: `yishitongshop@gmail.com`
- BCC: empty
- reply-to policy: `auto`
- subject template: `[YBY New Lead] {country} | {farm_size} | {crop} | {case_id}`
