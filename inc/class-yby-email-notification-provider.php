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
		$cc_list   = YBY_Config::get_lead_notification_cc_recipient_emails();
		$bcc_list  = YBY_Config::get_lead_notification_bcc_recipient_emails();
		$subject   = $this->build_subject( $lead );
		$html      = $this->build_html( $lead );
		$headers   = $this->build_headers( $lead );

		$this->current_alt_body = $this->build_plain_text( $lead );
		add_action( 'phpmailer_init', array( $this, 'apply_alt_body' ) );

		try {
			$sent = (bool) wp_mail( $recipient, $subject, $html, $headers );

			return array(
				'mail_sent'      => $sent,
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
		$country = $this->safe_subject_value( $this->lead_value( $lead, 'country' ) );
		$farm    = $this->safe_subject_value( $this->lead_value( $lead, 'farm_size' ) );
		$crop    = $this->safe_subject_value( $this->lead_value( $lead, 'crop' ) );
		$case_id = $this->safe_subject_value( $this->lead_value( $lead, 'case_id' ) );

		return sprintf(
			'[YBY New Lead] %1$s | %2$s | %3$s | %4$s',
			$country ?: 'Unknown Country',
			$farm ?: 'Unknown Farm Size',
			$crop ?: 'Unknown Crop',
			$case_id ?: 'Pending'
		);
	}

	/**
	 * Build headers.
	 *
	 * @param array<string, string> $lead Lead payload.
	 * @return array<int, string>
	 */
	public function build_headers( $lead ) {
		$headers = array(
			'From: YBY Website <no-reply@ybyirrigation.com>',
			'Content-Type: text/html; charset=UTF-8',
		);
		$seen    = array();
		$primary = YBY_Config::get_lead_notification_primary_recipient_email();

		$reply_to = $this->resolve_reply_to( $lead );

		if ( '' !== $reply_to ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}

		if ( is_email( $primary ) ) {
			$seen[ strtolower( $primary ) ] = true;
		}

		foreach ( $this->normalize_email_list( YBY_Config::get_lead_notification_cc_recipient_emails() ) as $email ) {
			if ( isset( $seen[ strtolower( $email ) ] ) ) {
				continue;
			}

			$headers[] = 'Cc: ' . $email;
			$seen[ strtolower( $email ) ] = true;
		}

		foreach ( $this->normalize_email_list( YBY_Config::get_lead_notification_bcc_recipient_emails() ) as $email ) {
			if ( isset( $seen[ strtolower( $email ) ] ) ) {
				continue;
			}

			$headers[] = 'Bcc: ' . $email;
			$seen[ strtolower( $email ) ] = true;
		}

		return $headers;
	}

	/**
	 * Build the HTML email body.
	 *
	 * @param array<string, string> $lead Lead payload.
	 * @return string
	 */
	public function build_html( $lead ) {
		$header_style = 'background:#00754A;padding:24px 28px;';
		$body_style   = 'background:#FFFFFF;padding:24px 28px;';
		$title_style  = 'margin:0;font:700 28px Arial,Helvetica,sans-serif;line-height:1.25;color:#17211B;';
		$text_style   = 'margin:0;font:400 14px Arial,Helvetica,sans-serif;line-height:1.7;color:#37433C;';
		$muted_style  = 'margin:0;font:400 12px Arial,Helvetica,sans-serif;line-height:1.6;color:#6B746E;';
		$section_head = 'margin:0 0 12px;font:700 18px Arial,Helvetica,sans-serif;line-height:1.35;color:#17211B;';
		$cell_label   = 'padding:10px 12px;border:1px solid #DDE5DF;background:#F7F9F7;font:700 13px Arial,Helvetica,sans-serif;line-height:1.5;color:#17211B;vertical-align:top;width:34%;';
		$cell_value   = 'padding:10px 12px;border:1px solid #DDE5DF;font:400 13px Arial,Helvetica,sans-serif;line-height:1.6;color:#37433C;vertical-align:top;';

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
			$cell_label,
			$cell_value
		);

		$project_rows = $this->build_rows(
			array(
				'Company'            => $this->lead_value( $lead, 'company' ),
				'Recommended System' => $this->lead_value( $lead, 'recommended_system' ),
				'Estimated Range'    => $this->lead_value( $lead, 'estimated_range' ),
				'Selected Country'   => $this->lead_value( $lead, 'selected_country' ),
				'Landing Page URL'   => $this->lead_value( $lead, 'landing_page_url' ),
			),
			$cell_label,
			$cell_value
		);

		$solution_rows = $this->build_rows(
			array(
				'Source Component'         => $this->lead_value( $lead, 'source_component' ),
				'Default Country Context'  => (string) YBY_Config::get_default_country(),
				'Default Product Interest' => (string) YBY_Config::get_default_product_interest(),
				'Case ID'                  => $this->lead_value( $lead, 'case_id' ),
			),
			$cell_label,
			$cell_value
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
			$cell_label,
			$cell_value
		);

		return '<!doctype html><html><body style="margin:0;padding:0;background:#F4F6F4;">'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#F4F6F4;">'
			. '<tr><td align="center" style="padding:24px 12px;">'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:640px;background:#FFFFFF;border-collapse:collapse;">'
			. '<tr><td style="' . $header_style . '">'
			. '<p style="margin:0 0 8px;font:700 13px Arial,Helvetica,sans-serif;letter-spacing:0.08em;text-transform:uppercase;color:#DDEFE6;">YBY Website Inquiry</p>'
			. '<h1 style="' . $title_style . '">New Irrigation Lead Received</h1>'
			. '<p style="margin:12px 0 0;font:400 14px Arial,Helvetica,sans-serif;line-height:1.7;color:#E7F3ED;">Case ID: ' . esc_html( $this->lead_value( $lead, 'case_id' ) ) . '</p>'
			. '</td></tr>'
			. '<tr><td style="' . $body_style . '">'
			. '<h2 style="' . $section_head . '">Sales Quick View</h2>'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">' . $quick_view_rows . '</table>'
			. '<h2 style="' . $section_head . '">Project Requirements</h2>'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">' . $project_rows . '</table>'
			. '<h2 style="' . $section_head . '">YBY Solution Context</h2>'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">' . $solution_rows . '</table>'
			. '<h2 style="' . $section_head . '">Quick Actions</h2>'
			. $this->build_quick_actions( $lead )
			. '<h2 style="' . $section_head . '">Technical &amp; Attribution Details</h2>'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">' . $technical_rows . '</table>'
			. '<p style="' . $muted_style . '">This email was generated by YBY Core v' . esc_html( YBY_CORE_VERSION ) . '. Sensitive recipient settings remain stored in WordPress only.</p>'
			. '</td></tr>'
			. '<tr><td style="padding:18px 28px;background:#F7F9F7;border-top:1px solid #DDE5DF;">'
			. '<p style="' . $text_style . '">YBY Core Inquiry Delivery</p>'
			. '<p style="' . $muted_style . '">YBY Website inquiry routing for governed sales follow-up.</p>'
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
		$pieces  = preg_split( '/[\s,]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
		$pieces  = is_array( $pieces ) ? $pieces : array();

		foreach ( $pieces as $piece ) {
			$email = sanitize_email( $piece );

			if ( is_email( $email ) ) {
				$emails[ strtolower( $email ) ] = $email;
			}
		}

		return array_values( $emails );
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
				. '<td style="' . $label_style . '">' . esc_html( $label ) . '</td>'
				. '<td style="' . $value_style . '">' . nl2br( esc_html( (string) $value ) ) . '</td>'
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
		$actions = array();
		$contact_email = $this->lead_value( $lead, 'contact_email' );
		$contact_whatsapp_url = $this->lead_value( $lead, 'contact_whatsapp_url' );
		$landing_page_url = $this->lead_value( $lead, 'landing_page_url' );

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
	 * Sanitize a subject segment.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected function safe_subject_value( $value ) {
		return trim( str_replace( array( "\r", "\n" ), '', sanitize_text_field( (string) $value ) ) );
	}

	/**
	 * Read a lead value safely.
	 *
	 * @param array<string, string> $lead Lead payload.
	 * @param string                $key Array key.
	 * @return string
	 */
	protected function lead_value( $lead, $key ) {
		return isset( $lead[ $key ] ) ? (string) $lead[ $key ] : '';
	}
}
