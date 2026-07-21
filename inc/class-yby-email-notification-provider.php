<?php
/**
 * Email notification provider.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email provider for lead notifications.
 */
class YBY_Email_Notification_Provider {

	/**
	 * Temporary AltBody value.
	 *
	 * @var string
	 */
	protected $current_alt_body = '';

	/**
	 * Send a lead notification email.
	 *
	 * @param array<string, mixed> $lead Lead payload.
	 * @return array<string, mixed>
	 */
	public function sendLeadNotification( $lead ) {
		$recipient = YBY_Config::get_lead_notification_primary_recipient_email();
		$subject   = $this->build_subject( $lead );
		$html      = $this->build_html( $lead );
		$headers   = $this->build_headers( $lead );

		$this->current_alt_body = $this->build_plain_text( $lead );
		add_action( 'phpmailer_init', array( $this, 'apply_alt_body' ) );

		try {
			$sent = (bool) wp_mail( $recipient, $subject, $html, $headers );

			return array(
				'mail_sent'       => $sent,
				'mail_error_code' => $sent ? '' : 'mail_send_failed',
			);
		} finally {
			remove_action( 'phpmailer_init', array( $this, 'apply_alt_body' ) );
			$this->current_alt_body = '';
		}
	}

	/**
	 * Build the email subject.
	 *
	 * @param array<string, mixed> $lead Lead payload.
	 * @return string
	 */
	public function build_subject( $lead ) {
		$renderer = new YBY_Email_Subject_Renderer();

		return $renderer->render( YBY_Brand_Profile::get_subject_template(), $lead );
	}

	/**
	 * Build headers.
	 *
	 * @param array<string, mixed> $lead Lead payload.
	 * @return array<int, string>
	 */
	public function build_headers( $lead ) {
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		$reply_to = $this->resolve_reply_to( $lead );

		if ( '' !== $reply_to ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}

		foreach ( $this->build_cc_recipients() as $email ) {
			$headers[] = 'Cc: ' . $email;
		}

		foreach ( $this->build_bcc_recipients() as $email ) {
			$headers[] = 'Bcc: ' . $email;
		}

		return $this->dedupe_headers( $headers );
	}

	/**
	 * Build the HTML email body.
	 *
	 * @param array<string, mixed> $lead Lead payload.
	 * @return string
	 */
	public function build_html( $lead ) {
		$brand            = $this->get_brand_values();
		$title            = $brand['title'];
		$header_logo_html = $this->build_logo_markup( $brand['logo'], $brand['name'], $brand['primary_text'], '180px' );
		$footer_logo      = '' !== $brand['reverse_logo'] ? $brand['reverse_logo'] : $brand['logo'];
		$footer_logo_html = $this->build_logo_markup( $footer_logo, $brand['name'], $brand['text'], '160px' );
		$footer_lines     = array_filter(
			array(
				$brand['name'],
				$brand['website'],
				$brand['phone'],
				$brand['whatsapp'],
				$brand['support_email'],
				$brand['copyright'],
			)
		);

		$quick_view_rows = $this->build_rows(
			array(
				'Customer'         => $this->lead_value( $lead, 'name' ),
				'Company'          => $this->lead_value( $lead, 'company' ),
				'Country'          => $this->lead_value( $lead, 'country' ),
				'Email'            => $this->lead_value( $lead, 'email' ),
				'WhatsApp'         => $this->lead_value( $lead, 'whatsapp' ),
				'Product Interest' => $this->lead_value( $lead, 'product_interest' ),
				'Quantity'         => $this->lead_value( $lead, 'quantity' ),
				'Project Details'  => $this->lead_value( $lead, 'project_details' ),
			),
			$this->cell_label_style( $brand ),
			$this->cell_value_style( $brand )
		);

		$source_rows = $this->build_rows(
			array(
				'Source Component' => $this->lead_value( $lead, 'source_component' ),
				'Source Preset'    => $this->lead_value( $lead, 'source_preset' ),
				'Source Page'      => $this->lead_value( $lead, 'source_page' ),
				'Form Version'     => $this->lead_value( $lead, 'form_version' ),
				'Case ID'          => $this->lead_value( $lead, 'case_id' ),
			),
			$this->cell_label_style( $brand ),
			$this->cell_value_style( $brand )
		);

		$technical_rows = $this->build_rows(
			array(
				'Landing Page URL'   => $this->lead_value( $lead, 'landing_page_url' ),
				'UTM Source'         => $this->lead_value( $lead, 'utm_source' ),
				'UTM Medium'         => $this->lead_value( $lead, 'utm_medium' ),
				'UTM Campaign'       => $this->lead_value( $lead, 'utm_campaign' ),
				'UTM Term'           => $this->lead_value( $lead, 'utm_term' ),
				'GCLID'              => $this->lead_value( $lead, 'gclid' ),
				'FBCLID'             => $this->lead_value( $lead, 'fbclid' ),
				'Submitted At (UTC)' => gmdate( 'Y-m-d H:i:s' ),
			),
			$this->cell_label_style( $brand ),
			$this->cell_value_style( $brand )
		);

		$custom_rows = $this->build_custom_field_rows( $lead, $brand );

		return '<!doctype html><html><body style="margin:0;padding:0;background:' . esc_attr( $brand['surface'] ) . ';">'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:' . esc_attr( $brand['surface'] ) . ';"><tr><td align="center" style="padding:24px 12px;">'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:640px;background:' . esc_attr( $brand['surface'] ) . ';border-collapse:collapse;border:1px solid ' . esc_attr( $brand['border'] ) . ';">'
			. '<tr><td style="padding:24px 28px;background:' . esc_attr( $brand['primary'] ) . ';color:' . esc_attr( $brand['primary_text'] ) . ';">'
			. '<div style="margin:0 0 10px;">' . $header_logo_html . '</div>'
			. '<p style="margin:0;font:700 12px Arial,Helvetica,sans-serif;letter-spacing:0.08em;text-transform:uppercase;color:' . esc_attr( $brand['primary_text'] ) . ';">' . esc_html( $brand['name'] ) . '</p>'
			. '<p style="margin:8px 0 0;font:400 13px Arial,Helvetica,sans-serif;line-height:1.6;color:' . esc_attr( $brand['primary_text'] ) . ';">' . esc_html( $brand['website'] ) . '</p>'
			. '</td></tr>'
			. '<tr><td style="padding:24px 28px;background:' . esc_attr( $brand['surface'] ) . ';">'
			. '<h1 style="' . esc_attr( $this->title_style( $brand ) ) . '">' . esc_html( $title ) . '</h1>'
			. '<h2 style="' . esc_attr( $this->section_head_style( $brand ) ) . '">Lead Details</h2>'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">' . $quick_view_rows . '</table>'
			. '<h2 style="' . esc_attr( $this->section_head_style( $brand ) ) . '">Inquiry Source</h2>'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">' . $source_rows . '</table>'
			. '<h2 style="' . esc_attr( $this->section_head_style( $brand ) ) . '">Quick Actions</h2>'
			. $this->build_quick_actions( $lead, $brand )
			. '<h2 style="' . esc_attr( $this->section_head_style( $brand ) ) . '">Custom Fields</h2>'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">' . $custom_rows . '</table>'
			. '<h2 style="' . esc_attr( $this->section_head_style( $brand ) ) . '">Technical &amp; Attribution Details</h2>'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">' . $technical_rows . '</table>'
			. '<p style="margin:0;font:400 12px Arial,Helvetica,sans-serif;line-height:1.6;color:' . esc_attr( $brand['muted'] ) . ';">This email was generated by YBY Core v' . esc_html( YBY_CORE_VERSION ) . '.</p>'
			. '</td></tr>'
			. '<tr><td style="padding:20px 28px;background:' . esc_attr( $brand['surface'] ) . ';border-top:1px solid ' . esc_attr( $brand['border'] ) . ';">'
			. '<div style="margin-bottom:10px;">' . $footer_logo_html . '</div>'
			. '<p style="' . esc_attr( $this->footer_line_style( $brand ) ) . '">' . esc_html( implode( ' | ', $footer_lines ) ) . '</p>'
			. '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}

	/**
	 * Build the plain text version.
	 *
	 * @param array<string, mixed> $lead Lead payload.
	 * @return string
	 */
	public function build_plain_text( $lead ) {
		$brand = $this->get_brand_values();
		$lines = array(
			$brand['title'],
			'Case ID: ' . $this->lead_value( $lead, 'case_id' ),
			'',
			'Lead Details',
			'Customer: ' . $this->lead_value( $lead, 'name' ),
			'Company: ' . $this->lead_value( $lead, 'company' ),
			'Country: ' . $this->lead_value( $lead, 'country' ),
			'Email: ' . $this->lead_value( $lead, 'email' ),
			'WhatsApp: ' . $this->lead_value( $lead, 'whatsapp' ),
			'Product Interest: ' . $this->lead_value( $lead, 'product_interest' ),
			'Quantity: ' . $this->lead_value( $lead, 'quantity' ),
			'Project Details: ' . $this->lead_value( $lead, 'project_details' ),
			'',
			'Inquiry Source',
			'Source Component: ' . $this->lead_value( $lead, 'source_component' ),
			'Source Preset: ' . $this->lead_value( $lead, 'source_preset' ),
			'Source Page: ' . $this->lead_value( $lead, 'source_page' ),
			'Form Version: ' . $this->lead_value( $lead, 'form_version' ),
			'',
			'Custom Fields',
		);

		foreach ( $this->get_custom_field_items( $lead ) as $label => $value ) {
			$lines[] = $label . ': ' . $value;
		}

		if ( 'Custom Fields' === end( $lines ) ) {
			$lines[] = 'Custom Fields: -';
		}

		$lines = array_merge(
			$lines,
			array(
				'',
				'Technical & Attribution Details',
				'Landing Page URL: ' . $this->lead_value( $lead, 'landing_page_url' ),
				'UTM Source: ' . $this->lead_value( $lead, 'utm_source' ),
				'UTM Medium: ' . $this->lead_value( $lead, 'utm_medium' ),
				'UTM Campaign: ' . $this->lead_value( $lead, 'utm_campaign' ),
				'UTM Term: ' . $this->lead_value( $lead, 'utm_term' ),
				'GCLID: ' . $this->lead_value( $lead, 'gclid' ),
				'FBCLID: ' . $this->lead_value( $lead, 'fbclid' ),
			)
		);

		return implode( "\n", array_map( 'sanitize_text_field', $lines ) );
	}

	/**
	 * Apply the temporary AltBody for the current wp_mail send.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
	 * @return void
	 */
	public function apply_alt_body( $phpmailer ) {
		$phpmailer->AltBody = $this->current_alt_body;
	}

	/**
	 * Resolve the reply-to address according to policy.
	 *
	 * @param array<string, mixed> $lead Lead payload.
	 * @return string
	 */
	protected function resolve_reply_to( $lead ) {
		$policy = YBY_Config::get_lead_notification_reply_to_policy();
		$email  = $this->lead_value( $lead, 'contact_email' );

		if ( 'disabled' === $policy ) {
			return '';
		}

		if ( is_email( $email ) ) {
			return sanitize_email( $email );
		}

		return '';
	}

	/**
	 * Normalize a comma-separated email string.
	 *
	 * @param string $value Raw emails.
	 * @return array<int, string>
	 */
	protected function normalize_email_list( $value ) {
		$emails = array();
		$pieces = preg_split( '/[\s,]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
		$pieces = is_array( $pieces ) ? $pieces : array();

		foreach ( $pieces as $piece ) {
			$email = sanitize_email( $piece );

			if ( is_email( $email ) ) {
				$emails[ strtolower( $email ) ] = $email;
			}
		}

		return array_values( $emails );
	}

	/**
	 * Build CC recipients with duplicate protection.
	 *
	 * @return array<int, string>
	 */
	protected function build_cc_recipients() {
		$to      = $this->normalize_email_list( YBY_Config::get_lead_notification_primary_recipient_email() );
		$cc      = $this->normalize_email_list( YBY_Config::get_lead_notification_cc_recipient_emails() );
		$blocked = array_fill_keys( array_map( 'strtolower', $to ), true );
		$result  = array();

		foreach ( $cc as $email ) {
			if ( isset( $blocked[ strtolower( $email ) ] ) ) {
				continue;
			}

			$blocked[ strtolower( $email ) ] = true;
			$result[]                        = $email;
		}

		return $result;
	}

	/**
	 * Build BCC recipients with duplicate protection.
	 *
	 * @return array<int, string>
	 */
	protected function build_bcc_recipients() {
		$to      = $this->normalize_email_list( YBY_Config::get_lead_notification_primary_recipient_email() );
		$cc      = $this->build_cc_recipients();
		$bcc     = $this->normalize_email_list( YBY_Config::get_lead_notification_bcc_recipient_emails() );
		$blocked = array_fill_keys( array_map( 'strtolower', array_merge( $to, $cc ) ), true );
		$result  = array();

		foreach ( $bcc as $email ) {
			if ( isset( $blocked[ strtolower( $email ) ] ) ) {
				continue;
			}

			$blocked[ strtolower( $email ) ] = true;
			$result[]                        = $email;
		}

		return $result;
	}

	/**
	 * Deduplicate address headers.
	 *
	 * @param array<int, string> $headers Headers.
	 * @return array<int, string>
	 */
	protected function dedupe_headers( $headers ) {
		$seen    = array();
		$deduped = array();

		foreach ( $headers as $header ) {
			$key = strtolower( trim( (string) $header ) );

			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$deduped[]    = $header;
		}

		return $deduped;
	}

	/**
	 * Build two-column detail rows.
	 *
	 * @param array<string, string> $items Display items.
	 * @param string                $label_style Label style.
	 * @param string                $value_style Value style.
	 * @return string
	 */
	protected function build_rows( $items, $label_style, $value_style ) {
		$output = '';

		foreach ( $items as $label => $value ) {
			if ( '' === trim( (string) $value ) ) {
				$value = '-';
			}

			$output .= '<tr>'
				. '<td style="' . esc_attr( $label_style ) . '">' . esc_html( $label ) . '</td>'
				. '<td style="' . esc_attr( $value_style ) . '">' . nl2br( esc_html( (string) $value ) ) . '</td>'
				. '</tr>';
		}

		return $output;
	}

	/**
	 * Build custom field rows for HTML output.
	 *
	 * @param array<string, mixed> $lead Lead payload.
	 * @param array<string, string> $brand Brand presentation values.
	 * @return string
	 */
	protected function build_custom_field_rows( $lead, $brand ) {
		$items = $this->get_custom_field_items( $lead );

		if ( empty( $items ) ) {
			$items = array(
				'Custom Fields' => '-',
			);
		}

		return $this->build_rows( $items, $this->cell_label_style( $brand ), $this->cell_value_style( $brand ) );
	}

	/**
	 * Normalize custom field items with labels.
	 *
	 * @param array<string, mixed> $lead Lead payload.
	 * @return array<string, string>
	 */
	protected function get_custom_field_items( $lead ) {
		$mapper          = new YBY_Inquiry_Lead_Mapper();
		$field_manager   = new YBY_Inquiry_Field_Manager();
		$custom_fields   = array();
		$normalized      = array();
		$source_payload  = isset( $lead['custom_fields'] ) ? $lead['custom_fields'] : array();
		$core_field_keys = array_fill_keys( $mapper->get_core_field_ids(), true );

		if ( is_array( $source_payload ) ) {
			$custom_fields = $source_payload;
		} elseif ( is_string( $source_payload ) ) {
			$custom_fields = $mapper->decode_custom_fields( $source_payload );
		} elseif ( isset( $lead['custom_fields_json'] ) && is_string( $lead['custom_fields_json'] ) ) {
			$custom_fields = $mapper->decode_custom_fields( $lead['custom_fields_json'] );
		}

		foreach ( $custom_fields as $field_id => $value ) {
			$field_id = sanitize_key( $field_id );

			if ( '' === $field_id || isset( $core_field_keys[ $field_id ] ) || ! is_scalar( $value ) ) {
				continue;
			}

			$field = $field_manager->get_field( $field_id );
			$label = ! empty( $field['label'] ) ? (string) $field['label'] : $this->humanize_field_id( $field_id );
			$text  = sanitize_textarea_field( (string) $value );

			if ( '' === $text ) {
				continue;
			}

			$normalized[ $label ] = $text;
		}

		return $normalized;
	}

	/**
	 * Humanize a field ID when no registry label is available.
	 *
	 * @param string $field_id Field ID.
	 * @return string
	 */
	protected function humanize_field_id( $field_id ) {
		return ucwords( str_replace( '_', ' ', sanitize_key( $field_id ) ) );
	}

	/**
	 * Build the quick action block.
	 *
	 * @param array<string, mixed> $lead Lead payload.
	 * @param array<string, string> $brand Brand presentation values.
	 * @return string
	 */
	protected function build_quick_actions( $lead, $brand ) {
		$actions              = array();
		$contact_email        = $this->lead_value( $lead, 'contact_email' );
		$contact_whatsapp_url = $this->lead_value( $lead, 'contact_whatsapp_url' );
		$landing_page_url     = $this->lead_value( $lead, 'landing_page_url' );

		if ( '' !== $contact_email && is_email( $contact_email ) ) {
			$actions[] = '<a href="mailto:' . esc_attr( $contact_email ) . '" style="' . esc_attr( $this->action_button_style( $brand['primary'], $brand['primary_text'], $brand['primary'] ) ) . '">Reply by Email</a>';
		}

		if ( '' !== $contact_whatsapp_url ) {
			$actions[] = '<a href="' . esc_url( $contact_whatsapp_url ) . '" style="' . esc_attr( $this->action_button_style( $brand['secondary'], $brand['primary_text'], $brand['secondary'] ) ) . '">Open WhatsApp</a>';
		}

		if ( '' !== $landing_page_url ) {
			$actions[] = '<a href="' . esc_url( $landing_page_url ) . '" style="' . esc_attr( $this->action_button_style( $brand['surface'], $brand['text'], $brand['border'] ) ) . '">Open Landing Page</a>';
		}

		if ( empty( $actions ) ) {
			return '<p style="margin:0 0 24px;font:400 14px Arial,Helvetica,sans-serif;line-height:1.7;color:' . esc_attr( $brand['muted'] ) . ';">No quick actions were available for this lead. Use the raw contact details in Lead Details.</p>';
		}

		return '<div style="margin:0 0 24px;">' . implode( '', $actions ) . '</div>';
	}

	/**
	 * Build action button styles.
	 *
	 * @param string $background Background color.
	 * @param string $text Color.
	 * @param string $border Border color.
	 * @return string
	 */
	protected function action_button_style( $background, $text, $border ) {
		return 'display:inline-block;padding:10px 16px;margin:0 12px 12px 0;background:' . $background . ';color:' . $text . ';text-decoration:none;font:700 13px Arial,Helvetica,sans-serif;border:1px solid ' . $border . ';border-radius:3px;';
	}

	/**
	 * Section title style.
	 *
	 * @param array<string, string> $brand Brand presentation values.
	 * @return string
	 */
	protected function title_style( $brand ) {
		return 'margin:0 0 16px;font:700 24px Arial,Helvetica,sans-serif;line-height:1.25;color:' . $brand['text'] . ';';
	}

	/**
	 * Section heading style.
	 *
	 * @param array<string, string> $brand Brand presentation values.
	 * @return string
	 */
	protected function section_head_style( $brand ) {
		return 'margin:0 0 12px;font:700 18px Arial,Helvetica,sans-serif;line-height:1.35;color:' . $brand['text'] . ';';
	}

	/**
	 * Cell label style.
	 *
	 * @param array<string, string> $brand Brand presentation values.
	 * @return string
	 */
	protected function cell_label_style( $brand ) {
		return 'padding:10px 12px;border:1px solid ' . $brand['border'] . ';background:' . $brand['surface'] . ';font:700 13px Arial,Helvetica,sans-serif;line-height:1.5;color:' . $brand['text'] . ';vertical-align:top;width:34%;';
	}

	/**
	 * Cell value style.
	 *
	 * @param array<string, string> $brand Brand presentation values.
	 * @return string
	 */
	protected function cell_value_style( $brand ) {
		return 'padding:10px 12px;border:1px solid ' . $brand['border'] . ';font:400 13px Arial,Helvetica,sans-serif;line-height:1.6;color:' . $brand['muted'] . ';vertical-align:top;background:' . $brand['surface'] . ';';
	}

	/**
	 * Footer line style.
	 *
	 * @param array<string, string> $brand Brand presentation values.
	 * @return string
	 */
	protected function footer_line_style( $brand ) {
		return 'margin:0;font:400 13px Arial,Helvetica,sans-serif;line-height:1.7;color:' . $brand['text'] . ';';
	}

	/**
	 * Read a lead value safely.
	 *
	 * @param array<string, mixed> $lead Lead payload.
	 * @param string               $key Array key.
	 * @return string
	 */
	protected function lead_value( $lead, $key ) {
		if ( ! isset( $lead[ $key ] ) || is_array( $lead[ $key ] ) || is_object( $lead[ $key ] ) ) {
			return '';
		}

		return (string) $lead[ $key ];
	}

	/**
	 * Get brand values for email presentation.
	 *
	 * @return array<string, string>
	 */
	protected function get_brand_values() {
		return array(
			'name'         => YBY_Brand_Profile::get_brand_name(),
			'website'      => YBY_Brand_Profile::get_website_url(),
			'phone'        => YBY_Brand_Profile::get_phone(),
			'whatsapp'     => YBY_Brand_Profile::get_whatsapp(),
			'support_email'=> YBY_Brand_Profile::get_support_email(),
			'copyright'    => YBY_Brand_Profile::get_footer_copyright(),
			'logo'         => YBY_Brand_Profile::get_logo_url(),
			'reverse_logo' => YBY_Brand_Profile::get_reverse_logo_url(),
			'title'        => YBY_Brand_Profile::get_inquiry_email_title(),
			'primary'      => YBY_Brand_Profile::get_primary_color(),
			'primary_text' => YBY_Brand_Profile::get_primary_text_color(),
			'secondary'    => YBY_Brand_Profile::get_secondary_color(),
			'surface'      => YBY_Brand_Profile::get_surface_color(),
			'text'         => YBY_Brand_Profile::get_text_color(),
			'muted'        => YBY_Brand_Profile::get_muted_text_color(),
			'border'       => YBY_Brand_Profile::get_border_color(),
		);
	}

	/**
	 * Build logo markup.
	 *
	 * @param string $logo_url Logo URL.
	 * @param string $alt Alt text.
	 * @param string $text_color Text color fallback.
	 * @param string $max_width Max width.
	 * @return string
	 */
	protected function build_logo_markup( $logo_url, $alt, $text_color, $max_width ) {
		if ( '' !== $logo_url ) {
			return '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $alt ) . '" style="display:block;max-width:' . esc_attr( $max_width ) . ';height:auto;border:0;">';
		}

		return '<span style="font:700 18px Arial,Helvetica,sans-serif;color:' . esc_attr( $text_color ) . ';">' . esc_html( $alt ) . '</span>';
	}
}
