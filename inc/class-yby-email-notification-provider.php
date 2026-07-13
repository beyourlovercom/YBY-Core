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
	 * @param array<string, string> $lead Lead payload.
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
	 * @param array<string, string> $lead Lead payload.
	 * @return string
	 */
	public function build_subject( $lead ) {
		$renderer = new YBY_Email_Subject_Renderer();

		return $renderer->render( YBY_Config::get_lead_notification_subject_template(), $lead );
	}

	/**
	 * Build headers.
	 *
	 * @param array<string, string> $lead Lead payload.
	 * @return array<int, string>
	 */
	public function build_headers( $lead ) {
		$headers = array(
			'From: ' . $this->build_from_label() . ' <no-reply@ybyirrigation.com>',
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
	 * @param array<string, string> $lead Lead payload.
	 * @return string
	 */
	public function build_html( $lead ) {
		$brand = $this->get_brand_values();

		$header_logo = $brand['logo'];
		$footer_logo = $brand['reverse_logo'] ? $brand['reverse_logo'] : $brand['logo'];
		$company     = $brand['name'];
		$website     = $brand['website'];
		$phone       = $brand['phone'];
		$whatsapp    = $brand['whatsapp'];
		$copyright   = $brand['copyright'];

		$header_logo_html = $this->build_logo_markup( $header_logo, $company, '#FFFFFF', '180px' );
		$footer_logo_html = $this->build_logo_markup( $footer_logo, $company, '#17211B', '160px' );

		$header_html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;"><tr>'
			. '<td style="padding:24px 28px;background:#00754A;color:#FFFFFF;">'
			. '<div style="margin:0 0 10px;">' . $header_logo_html . '</div>'
			. '<p style="margin:0;font:700 12px Arial,Helvetica,sans-serif;letter-spacing:0.08em;text-transform:uppercase;color:#DDEFE6;">' . esc_html( $company ) . '</p>'
			. '<p style="margin:8px 0 0;font:400 13px Arial,Helvetica,sans-serif;line-height:1.6;color:#E7F3ED;">' . esc_html( $website ) . '</p>'
			. '</td></tr></table>';

		$footer_lines = array_filter(
			array(
				$company,
				$website,
				$phone,
				$whatsapp,
				$copyright,
			)
		);

		$quick_view_rows = $this->build_rows(
			array(
				'Customer'      => $this->lead_value( $lead, 'name' ),
				'Country'       => $this->lead_value( $lead, 'country' ),
				'Farm Size'     => $this->lead_value( $lead, 'farm_size' ),
				'Crop'          => $this->lead_value( $lead, 'crop' ),
				'Contact'       => $this->lead_value( $lead, 'contact' ),
				'Water Source'  => $this->lead_value( $lead, 'water_source' ),
				'Customer Need' => $this->lead_value( $lead, 'message' ),
			),
			$this->cell_label_style(),
			$this->cell_value_style()
		);

		$project_rows = $this->build_rows(
			array(
				'Company'            => $this->lead_value( $lead, 'company' ),
				'Recommended System' => $this->lead_value( $lead, 'recommended_system' ),
				'Estimated Range'    => $this->lead_value( $lead, 'estimated_range' ),
				'Selected Country'   => $this->lead_value( $lead, 'selected_country' ),
				'Landing Page URL'   => $this->lead_value( $lead, 'landing_page_url' ),
			),
			$this->cell_label_style(),
			$this->cell_value_style()
		);

		$solution_rows = $this->build_rows(
			array(
				'Source Component'         => $this->lead_value( $lead, 'source_component' ),
				'Default Country Context'  => (string) YBY_Config::get_default_country(),
				'Default Product Interest' => (string) YBY_Config::get_default_product_interest(),
				'Case ID'                  => $this->lead_value( $lead, 'case_id' ),
			),
			$this->cell_label_style(),
			$this->cell_value_style()
		);

		$technical_rows = $this->build_rows(
			array(
				'Case ID'            => $this->lead_value( $lead, 'case_id' ),
				'UTM Source'         => $this->lead_value( $lead, 'utm_source' ),
				'UTM Medium'         => $this->lead_value( $lead, 'utm_medium' ),
				'UTM Campaign'       => $this->lead_value( $lead, 'utm_campaign' ),
				'UTM Content'        => $this->lead_value( $lead, 'utm_content' ),
				'UTM Term'           => $this->lead_value( $lead, 'utm_term' ),
				'GCLID'              => $this->lead_value( $lead, 'gclid' ),
				'Ad Group ID'        => $this->lead_value( $lead, 'yby_adgroup_id' ),
				'Match Type'         => $this->lead_value( $lead, 'yby_matchtype' ),
				'Device'             => $this->lead_value( $lead, 'yby_device' ),
				'Network'            => $this->lead_value( $lead, 'yby_network' ),
				'User Agent'         => $this->lead_value( $lead, 'user_agent' ),
				'Submitted At (UTC)' => gmdate( 'Y-m-d H:i:s' ),
				'Duplicate Window'   => '30 minutes',
			),
			$this->cell_label_style(),
			$this->cell_value_style()
		);

		$quick_actions = $this->build_quick_actions( $lead );

		return '<!doctype html><html><body style="margin:0;padding:0;background:#F4F6F4;">'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#F4F6F4;"><tr><td align="center" style="padding:24px 12px;">'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:640px;background:#FFFFFF;border-collapse:collapse;border:1px solid #DDE5DF;">'
			. '<tr><td>' . $header_html . '</td></tr>'
			. '<tr><td style="padding:24px 28px;background:#FFFFFF;">'
			. '<h2 style="' . esc_attr( $this->section_head_style() ) . '">Sales Quick View</h2>'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">' . $quick_view_rows . '</table>'
			. '<h2 style="' . esc_attr( $this->section_head_style() ) . '">Project Requirements</h2>'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">' . $project_rows . '</table>'
			. '<h2 style="' . esc_attr( $this->section_head_style() ) . '">YBY Solution Context</h2>'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">' . $solution_rows . '</table>'
			. '<h2 style="' . esc_attr( $this->section_head_style() ) . '">Quick Actions</h2>'
			. $quick_actions
			. '<h2 style="' . esc_attr( $this->section_head_style() ) . '">Technical &amp; Attribution Details</h2>'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">' . $technical_rows . '</table>'
			. '<p style="margin:0;font:400 12px Arial,Helvetica,sans-serif;line-height:1.6;color:#6B746E;">This email was generated by YBY Core v' . esc_html( YBY_CORE_VERSION ) . '.</p>'
			. '</td></tr>'
			. '<tr><td style="padding:20px 28px;background:#F7F9F7;border-top:1px solid #DDE5DF;">'
			. '<div style="margin-bottom:10px;">' . $footer_logo_html . '</div>'
			. '<p style="' . esc_attr( $this->footer_line_style() ) . '">' . esc_html( implode( ' | ', array_filter( $footer_lines ) ) ) . '</p>'
			. '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}

	/**
	 * Build the plain text version.
	 *
	 * @param array<string, string> $lead Lead payload.
	 * @return string
	 */
	public function build_plain_text( $lead ) {
		$lines = array(
			'YBY Website Inquiry',
			'Case ID: ' . $this->lead_value( $lead, 'case_id' ),
			'',
			'Sales Quick View',
			'Customer: ' . $this->lead_value( $lead, 'name' ),
			'Country: ' . $this->lead_value( $lead, 'country' ),
			'Farm Size: ' . $this->lead_value( $lead, 'farm_size' ),
			'Crop: ' . $this->lead_value( $lead, 'crop' ),
			'Contact: ' . $this->lead_value( $lead, 'contact' ),
			'Water Source: ' . $this->lead_value( $lead, 'water_source' ),
			'Customer Need: ' . $this->lead_value( $lead, 'message' ),
			'',
			'Project Requirements',
			'Company: ' . $this->lead_value( $lead, 'company' ),
			'Recommended System: ' . $this->lead_value( $lead, 'recommended_system' ),
			'Estimated Range: ' . $this->lead_value( $lead, 'estimated_range' ),
			'Selected Country: ' . $this->lead_value( $lead, 'selected_country' ),
			'Landing Page URL: ' . $this->lead_value( $lead, 'landing_page_url' ),
			'',
			'YBY Solution Context',
			'Source Component: ' . $this->lead_value( $lead, 'source_component' ),
			'Default Country Context: ' . (string) YBY_Config::get_default_country(),
			'Default Product Interest: ' . (string) YBY_Config::get_default_product_interest(),
			'Case ID: ' . $this->lead_value( $lead, 'case_id' ),
			'',
			'Technical & Attribution Details',
			'UTM Source: ' . $this->lead_value( $lead, 'utm_source' ),
			'UTM Medium: ' . $this->lead_value( $lead, 'utm_medium' ),
			'UTM Campaign: ' . $this->lead_value( $lead, 'utm_campaign' ),
			'UTM Content: ' . $this->lead_value( $lead, 'utm_content' ),
			'UTM Term: ' . $this->lead_value( $lead, 'utm_term' ),
			'GCLID: ' . $this->lead_value( $lead, 'gclid' ),
			'Ad Group ID: ' . $this->lead_value( $lead, 'yby_adgroup_id' ),
			'Match Type: ' . $this->lead_value( $lead, 'yby_matchtype' ),
			'Device: ' . $this->lead_value( $lead, 'yby_device' ),
			'Network: ' . $this->lead_value( $lead, 'yby_network' ),
			'User Agent: ' . $this->lead_value( $lead, 'user_agent' ),
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
	 * Build the sender label.
	 *
	 * @return string
	 */
	protected function build_from_label() {
		$company_name = YBY_Config::get_email_company_name();

		return '' !== $company_name ? $company_name : 'YBY Website';
	}

	/**
	 * Resolve the reply-to address according to policy.
	 *
	 * @param array<string, string> $lead Lead payload.
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
			$result[] = $email;
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
			$result[] = $email;
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
	 * @param string $label_style Label style.
	 * @param string $value_style Value style.
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
	 * Build the quick action block.
	 *
	 * @param array<string, string> $lead Lead payload.
	 * @return string
	 */
	protected function build_quick_actions( $lead ) {
		$actions              = array();
		$contact_email        = $this->lead_value( $lead, 'contact_email' );
		$contact_whatsapp_url = $this->lead_value( $lead, 'contact_whatsapp_url' );
		$landing_page_url     = $this->lead_value( $lead, 'landing_page_url' );

		if ( '' !== $contact_email && is_email( $contact_email ) ) {
			$actions[] = '<a href="mailto:' . esc_attr( $contact_email ) . '" style="display:inline-block;padding:10px 16px;margin:0 12px 12px 0;background:#00754A;color:#FFFFFF;text-decoration:none;font:700 13px Arial,Helvetica,sans-serif;border-radius:3px;">Reply by Email</a>';
		}

		if ( '' !== $contact_whatsapp_url ) {
			$actions[] = '<a href="' . esc_url( $contact_whatsapp_url ) . '" style="display:inline-block;padding:10px 16px;margin:0 12px 12px 0;background:#17211B;color:#FFFFFF;text-decoration:none;font:700 13px Arial,Helvetica,sans-serif;border-radius:3px;">Open WhatsApp</a>';
		}

		if ( '' !== $landing_page_url ) {
			$actions[] = '<a href="' . esc_url( $landing_page_url ) . '" style="display:inline-block;padding:10px 16px;margin:0 12px 12px 0;background:#FFFFFF;color:#17211B;text-decoration:none;font:700 13px Arial,Helvetica,sans-serif;border:1px solid #DDE5DF;border-radius:3px;">Open Landing Page</a>';
		}

		if ( empty( $actions ) ) {
			return '<p style="margin:0 0 24px;font:400 14px Arial,Helvetica,sans-serif;line-height:1.7;color:#37433C;">No quick actions were available for this lead. Use the raw contact details in Sales Quick View.</p>';
		}

		return '<div style="margin:0 0 24px;">' . implode( '', $actions ) . '</div>';
	}

	/**
	 * Section heading style.
	 *
	 * @return string
	 */
	protected function section_head_style() {
		return 'margin:0 0 12px;font:700 18px Arial,Helvetica,sans-serif;line-height:1.35;color:#17211B;';
	}

	/**
	 * Cell label style.
	 *
	 * @return string
	 */
	protected function cell_label_style() {
		return 'padding:10px 12px;border:1px solid #DDE5DF;background:#F7F9F7;font:700 13px Arial,Helvetica,sans-serif;line-height:1.5;color:#17211B;vertical-align:top;width:34%;';
	}

	/**
	 * Cell value style.
	 *
	 * @return string
	 */
	protected function cell_value_style() {
		return 'padding:10px 12px;border:1px solid #DDE5DF;font:400 13px Arial,Helvetica,sans-serif;line-height:1.6;color:#37433C;vertical-align:top;';
	}

	/**
	 * Footer line style.
	 *
	 * @return string
	 */
	protected function footer_line_style() {
		return 'margin:0;font:400 13px Arial,Helvetica,sans-serif;line-height:1.7;color:#17211B;';
	}

	/**
	 * Read a lead value safely.
	 *
	 * @param array<string, string> $lead Lead payload.
	 * @param string $key Array key.
	 * @return string
	 */
	protected function lead_value( $lead, $key ) {
		return isset( $lead[ $key ] ) ? (string) $lead[ $key ] : '';
	}

	/**
	 * Get brand values for email presentation.
	 *
	 * @return array<string, string>
	 */
	protected function get_brand_values() {
		return array(
			'name'        => YBY_Config::sanitize_display_text( YBY_Config::get_email_company_name() ),
			'website'     => esc_url_raw( YBY_Config::get_email_company_website() ),
			'phone'       => YBY_Config::sanitize_display_text( YBY_Config::get_email_company_phone() ),
			'whatsapp'    => YBY_Config::sanitize_display_text( YBY_Config::get_email_company_whatsapp() ),
			'copyright'   => YBY_Config::sanitize_display_text( YBY_Config::get_email_footer_copyright() ),
			'logo'        => esc_url_raw( YBY_Config::get_email_logo_url() ),
			'reverse_logo'=> esc_url_raw( YBY_Config::get_email_reverse_logo_url() ),
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
