# Architecture

Notification Center introduces a dispatch layer between lead runtime and delivery providers.

Flow:

1. Lead Runtime resolves a canonical lead payload.
2. `YBY_Notification_Manager` receives the payload.
3. The manager dispatches to the active provider.
4. The Email provider composes and sends the notification.
5. Subject rendering, recipient validation, and branding are handled inside the Email provider layer.

Design rules:

- REST contract stays unchanged.
- Lead runtime does not know provider internals.
- Future providers can be registered without rewriting lead intake.
- Provider failures return safe, non-sensitive status codes only.
