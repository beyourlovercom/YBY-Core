<?php
/**
 * Runtime executor for registered Andy Core extension modules.
 *
 * Existing built-in modules retain their v1.6 boot paths. This runtime is
 * the forward-compatible seam for modules registered through Registry V2.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Module_Runtime {
	protected $discovered = false;
	protected $booted = array();

	/**
	 * Open the registration window and boot eligible extension modules.
	 *
	 * External adapters should register on:
	 *   andy_core_register_modules
	 *
	 * @return void
	 */
	public function discover_and_boot() {
		if ( ! $this->discovered ) {
			do_action( 'andy_core_register_modules' );
			$this->discovered = true;
		}
		$this->boot_registered_extensions();
	}

	/**
	 * Boot only registered extension modules that pass enable/dependency gates.
	 *
	 * @return void
	 */
	public function boot_registered_extensions() {
		foreach ( YBY_Module_Registry::registered_modules() as $id => $module ) {
			if ( ! YBY_Module_Registry::is_enabled( $id ) ) { continue; }
			if ( ! YBY_Module_Registry::dependencies_met( $id ) ) { continue; }
			$this->boot_extension( $module );
		}
	}

	/**
	 * Register one extension's runtime/admin seams exactly once.
	 *
	 * @param array<string,mixed> $module Normalized registry metadata.
	 * @return void
	 */
	protected function boot_extension( $module ) {
		$id = isset( $module['id'] ) ? sanitize_key( $module['id'] ) : '';
		if ( '' === $id || isset( $this->booted[ $id ] ) ) { return; }

		// Mark before invoking callbacks so a recursive adapter cannot double boot.
		$this->booted[ $id ] = true;

		if ( ! empty( $module['boot'] ) && is_callable( $module['boot'] ) ) {
			call_user_func( $module['boot'], $module );
		}

		if ( ! empty( $module['admin_menu'] ) && is_callable( $module['admin_menu'] ) ) {
			$callback = $module['admin_menu'];
			add_action(
				'admin_menu',
				static function () use ( $callback, $module ) {
					call_user_func( $callback, $module );
				},
				max( 1, (int) $module['admin_menu_priority'] ),
				0
			);
		}

		if ( ! empty( $module['settings_register'] ) && is_callable( $module['settings_register'] ) ) {
			$callback = $module['settings_register'];
			add_action(
				'admin_init',
				static function () use ( $callback, $module ) {
					call_user_func( $callback, $module );
				},
				max( 1, (int) $module['settings_priority'] ),
				0
			);
		}

		$this->register_asset_channel( $module, 'admin' );
		$this->register_asset_channel( $module, 'frontend' );
	}

	protected function register_asset_channel( $module, $channel ) {
		$config = isset( $module['assets'][ $channel ] ) && is_array( $module['assets'][ $channel ] ) ? $module['assets'][ $channel ] : array();
		$enqueue = isset( $config['enqueue'] ) ? $config['enqueue'] : null;
		$condition = isset( $config['condition'] ) ? $config['condition'] : null;
		if ( ! is_callable( $enqueue ) || ! is_callable( $condition ) ) { return; }

		$priority = isset( $config['priority'] ) ? max( 1, (int) $config['priority'] ) : 10;
		if ( 'admin' === $channel ) {
			add_action(
				'admin_enqueue_scripts',
				static function ( $hook_suffix ) use ( $enqueue, $condition, $module ) {
					if ( ! call_user_func( $condition, $hook_suffix, $module ) ) { return; }
					call_user_func( $enqueue, $hook_suffix, $module );
				},
				$priority,
				1
			);
			return;
		}

		add_action(
			'wp_enqueue_scripts',
			static function () use ( $enqueue, $condition, $module ) {
				if ( ! call_user_func( $condition, $module ) ) { return; }
				call_user_func( $enqueue, $module );
			},
			$priority,
			0
		);
	}
}
