<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package YBY_Core
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * YBY Core preserves plugin options during MVP uninstall.
 *
 * Reason:
 * - The plugin is intended to become shared platform infrastructure.
 * - Case ID behavior and configuration values may be needed after reinstallation.
 * - Automatic deletion is intentionally deferred until a future data-retention policy exists.
 */
