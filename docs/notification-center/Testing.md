# Testing

Verify the following behaviors before release:

- Primary recipient is sent to the configured address
- CC recipients are attached only for valid email addresses
- BCC recipients are attached only for valid email addresses
- duplicate recipients are removed across To, CC, and BCC where practical
- invalid email addresses are ignored
- reply-to policy still works
- HTML email layout remains intact
- plain-text AltBody remains available
- REST response fields stay unchanged
- subject templates render expected placeholders
- branding fallback uses Brand settings when Email-specific logo fields are blank

Full governance and QA details are documented in the repository notification center docs.

