<?php
/**
 * Canonical Analytics business-event contract.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Analytics_Event_Contract {
	const VERSION = '1';

	public static function events() {
		return array(
			'generate_lead' => array( 'category' => 'lead', 'dedupe' => 'case_or_form', 'pii' => false ),
			'thank_you_page_view' => array( 'category' => 'lead', 'dedupe' => 'none', 'pii' => false ),
			'click_whatsapp' => array( 'category' => 'engagement', 'dedupe' => 'none', 'pii' => false ),
			'click_whatsapp_after_lead' => array( 'category' => 'lead', 'dedupe' => 'none', 'pii' => false ),
			'download_catalog' => array( 'category' => 'engagement', 'dedupe' => 'none', 'pii' => false ),
			'submit_project_details' => array( 'category' => 'lead', 'dedupe' => 'case', 'pii' => false ),
			'return_to_lp' => array( 'category' => 'navigation', 'dedupe' => 'none', 'pii' => false ),
			'view_case_study' => array( 'category' => 'engagement', 'dedupe' => 'none', 'pii' => false ),
		);
	}

	public static function names() {
		return array_keys( self::events() );
	}

	public static function has( $event_name ) {
		return isset( self::events()[ sanitize_key( (string) $event_name ) ] );
	}

	public static function describe( $event_name ) {
		$events = self::events();
		$key = sanitize_key( (string) $event_name );
		return isset( $events[ $key ] ) ? $events[ $key ] : array();
	}
}
