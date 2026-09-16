<?php
/**
 * Read-only governance scanner for legacy Woo email customizers.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YBY_Email_Legacy_Customizer_Governance {

	public function scan( $templates ) {
		$woo_ids = array();
		foreach ( (array) $templates as $template ) {
			if ( 'woocommerce' === ( $template['provider'] ?? '' ) && ! empty( $template['source_id'] ) ) {
				$woo_ids[] = sanitize_key( (string) $template['source_id'] );
			}
		}
		$woo_ids = array_values( array_unique( $woo_ids ) );
		$active = defined( 'VIWEC_VER' ) || class_exists( 'WooCommerce_Email_Template_Customizer' );
		$legacy_supported = $this->legacy_supported_types();
		$report = array(
			'active' => $active,
			'version' => defined( 'VIWEC_VER' ) ? (string) VIWEC_VER : '',
			'list_url' => admin_url( 'edit.php?post_type=viwec_template' ),
			'total' => 0,
			'published' => 0,
			'draft' => 0,
			'mapped_woo' => 0,
			'woo_native' => count( $woo_ids ),
			'rule_variant_types' => 0,
			'warnings' => array(),
			'mappings' => array(),
			'unmatched_types' => array(),
			'legacy_special_types' => array(),
			'default_count' => 0,
		);

		if ( ! post_type_exists( 'viwec_template' ) ) {
			return $report;
		}

		$ids = get_posts( array(
			'numberposts' => -1,
			'post_type' => 'viwec_template',
			'post_status' => array( 'publish', 'draft', 'pending', 'private', 'trash' ),
			'orderby' => 'menu_order',
			'order' => 'DESC',
			'fields' => 'ids',
		) );
		$groups = array();
		foreach ( (array) $ids as $post_id ) {
			$post_id = absint( $post_id );
			$status = (string) get_post_status( $post_id );
			$type = sanitize_key( (string) get_post_meta( $post_id, 'viwec_settings_type', true ) );
			$rules = get_post_meta( $post_id, 'viwec_setting_rules', true );
			$has_rules = $this->has_meaningful_rules( $rules );
			$report['total']++;
			if ( 'publish' === $status ) {
				$report['published']++;
			} elseif ( 'draft' === $status ) {
				$report['draft']++;
			}
			if ( '' === $type ) {
				continue;
			}
			if ( ! isset( $groups[ $type ] ) ) {
				$groups[ $type ] = array();
			}
			$groups[ $type ][] = array(
				'id' => $post_id,
				'status' => $status,
				'has_rules' => $has_rules,
				'title' => sanitize_text_field( (string) get_the_title( $post_id ) ),
			);
		}

		$report['default_count'] = $this->published_count( $groups['default'] ?? array() );
		if ( $report['default_count'] > 1 ) {
			$report['warnings'][] = 'multiple_default_templates';
		}
		foreach ( $woo_ids as $type ) {
			$published = array_values( array_filter( $groups[ $type ] ?? array(), static function ( $row ) { return 'publish' === $row['status']; } ) );
			$count = count( $published );
			$rule_count = count( array_filter( $published, static function ( $row ) { return ! empty( $row['has_rules'] ); } ) );
			$unconditional = $count - $rule_count;
			$state = 'woocommerce_native';
			if ( 1 === $count ) {
				$state = 'mapped';
			} elseif ( $count > 1 && $unconditional <= 1 ) {
				$state = 'rule_variants';
			} elseif ( $count > 1 ) {
				$state = 'review';
				$report['warnings'][] = 'multiple_unconditional:' . $type;
			}
			if ( $count > 0 ) {
				$report['mapped_woo']++;
				$report['woo_native']--;
			}
			$report['mappings'][ $type ] = array(
				'state' => $state,
				'published_count' => $count,
				'rule_count' => $rule_count,
				'unconditional_count' => $unconditional,
				'editor_url' => $this->editor_url( $type, $published ),
			);
		}
		foreach ( $groups as $type => $rows ) {
			if ( 'default' === $type || 0 === $this->published_count( $rows ) ) { continue; }
			$published = array_values( array_filter( $rows, static function ( $row ) { return 'publish' === $row['status']; } ) );
			$rule_count = count( array_filter( $published, static function ( $row ) { return ! empty( $row['has_rules'] ); } ) );
			$unconditional = count( $published ) - $rule_count;
			$is_recognized = in_array( $type, $woo_ids, true ) || in_array( $type, $legacy_supported, true );
			if ( $is_recognized && count( $published ) > 1 && $rule_count > 0 && $unconditional <= 1 ) { $report['rule_variant_types']++; }
			if ( $is_recognized && count( $published ) > 1 && $unconditional > 1 ) { $report['warnings'][] = 'multiple_unconditional:' . $type; }
			if ( in_array( $type, $legacy_supported, true ) && ! in_array( $type, $woo_ids, true ) ) { $report['legacy_special_types'][ $type ] = array( 'published_count' => count( $published ), 'rule_count' => $rule_count, 'editor_url' => $this->editor_url( $type, $published ) ); continue; }
			if ( ! in_array( $type, $woo_ids, true ) ) { $report['unmatched_types'][] = $type; }
		}
		if ( ! empty( $report['unmatched_types'] ) ) {
			$report['warnings'][] = 'unmatched_published_types';
		}

		$report['warnings'] = array_values( array_unique( $report['warnings'] ) );
		$report['status'] = empty( $report['warnings'] ) ? 'healthy' : 'review';
		return $report;
	}

	protected function published_count( $rows ) {
		return count( array_filter( (array) $rows, static function ( $row ) {
			return 'publish' === ( $row['status'] ?? '' );
		} ) );
	}

	protected function editor_url( $type, $published ) {
		$count = count( $published );
		if ( 1 === $count ) {
			return admin_url( 'post.php?post=' . absint( $published[0]['id'] ) . '&action=edit' );
		}
		if ( $count > 1 ) {
			return add_query_arg( 'viwec_template_filter', sanitize_key( $type ), admin_url( 'edit.php?post_type=viwec_template' ) );
		}
		return '';
	}
	protected function legacy_supported_types() {
		$types = array( 'customer_partially_refunded_order', 'customer_invoice_pending' );
		if ( class_exists( '\\VIWEC\\INCLUDES\\Utils' ) && method_exists( '\\VIWEC\\INCLUDES\\Utils', 'get_email_ids' ) ) { $ids = \VIWEC\INCLUDES\Utils::get_email_ids(); if ( is_array( $ids ) ) { $types = array_merge( $types, array_keys( $ids ) ); } }
		if ( defined( 'WACVP_VERSION' ) || class_exists( 'WACVPInit' ) ) { $types[] = 'abandoned_cart'; }
		return array_values( array_unique( array_filter( array_map( 'sanitize_key', $types ) ) ) );
	}

	protected function has_meaningful_rules( $rules ) {
		if ( ! is_array( $rules ) ) {
			return false;
		}
		foreach ( $rules as $key => $value ) {
			if ( 'price_type' === (string) $key ) {
				continue;
			}
			if ( is_array( $value ) ) {
				if ( ! empty( array_filter( $value, static function ( $item ) { return '' !== (string) $item; } ) ) ) {
					return true;
				}
				continue;
			}
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				return true;
			}
		}
		return false;
	}
}
