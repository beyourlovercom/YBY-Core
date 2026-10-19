# Andy Core — Universal Inquiry / Per-Site Configuration Contract V1

Status: **architecture correction, independent PR; no production deployment**.

## Principle

**One shared Andy Core source + signed Stable release channel for every website.** Core owns renderer, input field registry, REST validation, attribution, responsive layout, source/thank-you pipeline and security. Each WordPress site owns its own field selection, contact requirements, page profile, text, CSS custom properties, JavaScript hooks and real product photo. Never create a per-country fork or a new plugin release just to change a brand's layout or require Email.

## What belongs in Core

- Presets are resolved using the existing `yby_inquiry_presets` filter or the site's `yby_inquiry_presets` WordPress option.
- Existing four generic contact modes: `email`, `whatsapp`, `email_or_whatsapp`, `none`.
- Email/WhatsApp required presentation and HTML attributes follow the **trusted preset**. Backend REST validation independently reads the same trusted preset, never a client-supplied requirement.
- Standard `split_visual` layout prevents form fields from overlapping the submit/privacy footer. CSS uses optional custom properties to let themes change dimensions without modifying shared Core files.
- Existing `[yby_inquiry_modal]` Shortcode supports `id`, `preset`, `layout`, `desktop_fields`, `mobile_fields`, `image`, `title`, `subtitle`, `brand_name`, `submit_label`, `class`, etc. It does not define any site's branded preset.

## Example: define a site preset OUTSIDE Andy Core

The following snippet is **illustrative, not installed by the shared plugin**. Put it in that website's reviewed child-theme code or an approved site-local configuration plugin. Never duplicate an entire Core plugin.

```php
add_filter( 'yby_inquiry_presets', static function ( $presets ) {
    $presets['site_wholesale_quote'] = array(
        'id'               => 'site_wholesale_quote',
        'label'            => 'Wholesale Quote',
        'enabled'          => true,
        'version'          => '1.0',
        'fields'           => array( 'name', 'company', 'email', 'whatsapp', 'country', 'product_interest', 'quantity', 'message' ),
        'mobile_fields'    => array( 'name', 'email', 'whatsapp', 'country', 'product_interest', 'quantity', 'message' ),
        'validation'       => array( 'contact_requirement' => 'email' ),
        'source_component' => 'inquiry_modal',
        'page_profiles'    => array( 'irrigation_wholesale' ),
    );
    return $presets;
} );
```

The site admin/editor then uses the existing WordPress Shortcode block or Bricks Shortcode element:

```text
[yby_inquiry_modal id="site-wholesale-quote" preset="site_wholesale_quote" layout="split_visual" class="site-wholesale-quote" image="https://EXAMPLE.com/real-product-image.webp" title="Request a Wholesale Quote" brand_name="SITE BRAND" mobile_fields="name,email,whatsapp,country,product_interest,quantity,message" submit_label="Request Pricing"]
```

Each CTA on that page points to its own modal using `data-yby-inquiry-trigger` and `data-yby-modal-open="site-wholesale-quote"`. The page profile and Thank You URL are set in the **site's own Page Profile UI**. The site's WP/Bricks CSS, not Andy Core, may style it:

```css
#site-wholesale-quote {
  --yby-inquiry-primary: #176345;
  --yby-inquiry-split-height: 750px;
  --yby-inquiry-split-form-gap: 8px;
}
```

Avoid `mobile_layout="compact"` for seven-field long forms until its scrollability passes mobile UAT; normal mobile responsive mode is safer. Prefer Core's CTA/modal metadata to new JavaScript. Site JS must only add presentation behaviors and must not bypass server validation or synthesize lead conversions.

## Boundaries and release gates

1. **Site copy/photo/CSS/CTA edits:** only that site's WordPress or its theme/site-local GitHub branch, with owner visual UAT.
2. **Shared functional bug or security change:** Core main after CI and exact-head approval, then one signed release on the shared Stable channel. Sites may install it at different times; there are no per-site Core forks.
3. **Required contact validation:** website preset declares `contact_requirement`; server enforces it. Do not mark Email with an asterisk using CSS/JS alone.
4. **No live leads for visual testing**. Test with draft/Dev, isolated synthetic harnesses, then separately authorized end-to-end lead tests.
5. **Production WWW, Germany and Ads remain untouched** by this refactor. Continue US #826 only after site-specific config and visual UAT.

## Migration from PR #166

- Keep its generic renderer Email-required support and generic REST preset-based enforcement.
- Remove built-in `irrigation_us_wholesale` and its forced legacy-options backfill.
- Remove branded `us-wholesale-modal-v2` selectors from shared stylesheet. Fix split-visual overlapping globally through the standard renderer's CSS.
- Replace the branded test harness with a generic test that injects a simulated independent website's preset via the documented WordPress filter. Assert frontend required attributes, backend four modes, profile scoping and legacy compatibility.
- Do **not** bundle this example snippet as executable code in Core. Whether to add a reusable, permission-controlled per-site Preset Editor in WP admin is a separate feature/UAT track, not a reason for a per-site plugin fork.
