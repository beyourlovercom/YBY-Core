# Email Subject Template

The lead notification subject is configurable through the Email settings.

Default template:

`[YBY New Lead] {country} | {farm_size} | {crop} | {case_id}`

Supported placeholders:

- `{case_id}`
- `{name}`
- `{country}`
- `{crop}`
- `{farm_size}`
- `{water_source}`
- `{recommended_system}`
- `{project_id}`
- `{product_interest}`
- `{source_component}`

Rules:

- values are rendered as plain text only
- unsafe line breaks are stripped
- unknown placeholders are removed before send
- empty values collapse cleanly without breaking the subject line

