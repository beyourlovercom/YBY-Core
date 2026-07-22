# YBY Irrigation Runtime Profile

## Site Identity

- `site_brand_key`: `yby_irrigation`
- `site_brand_name`: `YBY Irrigation`
- `case_id_brand_code`: `IRR`
- canonical website: `https://ybyirrigation.com/`

## Inquiry Navigation

- Thank You URL: `/lp/thank-you-irrigation-solution/`
- Return URL: `/lp/irrigation-solution/`
- Current development Catalog URL: `https://dev.ybyirrigation.com/wp-content/uploads/2026/07/YBY-Irrigation-System-user-guide-V2.pdf`
- YouTube video ID: `k1l8IRfDx4s`

The development Catalog URL is environment-specific and must be reviewed before any production deployment.

## Approved WhatsApp Message Template

Exact approved template:

```text
Hello {brand_name}, I submitted an irrigation solution request.

My Case ID: {case_id}

Project Summary
Country: {country}
Crop: {crop}
Farm Size: {farm_size}
Water Source: {water_source}
Recommended System: {recommended_system}
Estimated Range: {estimated_range}

Please contact me to discuss the next steps.
```

- WordPress option key: `whatsapp_message_template`
- Public Runtime key: `whatsappMessageTemplate`
- Admin location: `YBY Core -> Inquiry Experience -> WhatsApp Message Template`

## Accepted Rendered Example

Accepted development example:

```text
Hello YBY Irrigation, I submitted an irrigation solution request.

My Case ID: YBY-IRR-20260721-98CLV9

Project Summary
Country: Tanzania

Please contact me to discuss the next steps.
```

Only `Country` appeared because the remaining Project Summary values were empty in that Thank You page context. Empty fields are intentionally omitted, and this is accepted behavior rather than a defect.

## Production Checklist

Before any production deployment, review:

- canonical production Catalog URL
- masked WhatsApp destination
- privacy policy
- Thank You URL
- Return URL
- YouTube ID
- recipient configuration
- SMTP delivery
- tracking container
- analytics blocking removal after QA
- production cache
- production backup
- production rollback package

Production deployment is not authorized by the M5B development acceptance.
