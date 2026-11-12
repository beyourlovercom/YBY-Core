<?php
/**
 * Contact Page First: one reusable B2B form schema, independent of sites.
 * Generates a DISABLED site-local Field Studio V2 candidate: no WordPress
 * writes, plugin activation, HTTP, mail, or template-specific Elementor code.
 */
final class Andy_Contact_First_Template {
	public static function field_specs() {
		return array(
			'name' => array( 'Full name', true ),
			'email' => array( 'Work email', true ),
			'country' => array( 'Destination country', true ),
			'product_interest' => array( 'Product or model', false ),
			'quantity' => array( 'Estimated quantity', false ),
			'company' => array( 'Company', false ),
			'message' => array( 'Requirements', true ),
			'site_contact_consent' => array( 'I agree to be contacted about my inquiry.', true ),
		);
	}
	public static function field_ids() { return array_keys( self::field_specs() ); }

	public static function draft() {
		$config = array();
		foreach ( self::field_specs() as $id => $spec ) {
			$mode = array(
				'enabled' => true, 'required' => $spec[1],
				'label' => $spec[0], 'placeholder' => '',
			);
			$config[ $id ] = array( 'desktop' => $mode, 'mobile' => $mode );
		}
		return array(
			'version' => 2,
			'fields' => array(
				'site_contact_consent' => array(
					'id' => 'site_contact_consent', 'type' => 'checkbox',
					'label' => 'I agree to be contacted about my inquiry.',
					'placeholder' => '', 'options' => array(),
				),
			),
			'presets' => array(
				'site_contact_v1' => array(
					'id' => 'site_contact_v1',
					'label' => 'Contact Page First', 'enabled' => false,
					'layout' => 'classic', 'title' => 'Contact Us',
					'subtitle' => '', 'image' => '',
					'page_profiles' => array(), 'configs' => $config,
				),
			),
		);
	}
	public static function shortcode( $title = 'Contact Us' ) {
		$fields = implode( ',', self::field_ids() );
		$title = is_scalar( $title ) ? trim( (string) $title ) : 'Contact Us';
		$title = str_replace( array( '"', '<', '>' ), '', $title );
		if ( '' === $title ) { $title = 'Contact Us'; }
		return '[yby_inquiry preset="site_contact_v1" id="andy-contact-first" '
			. 'title="' . $title . '" submit_label="Send inquiry" '
			. 'desktop_fields="' . $fields . '" mobile_fields="' . $fields . '"]';
	}
}
