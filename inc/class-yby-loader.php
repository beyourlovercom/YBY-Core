<?php
/**
 * Hook loader.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central hook registry.
 */
class YBY_Loader {

	/**
	 * Registered actions.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $actions = array();

	/**
	 * Registered filters.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $filters = array();

	/**
	 * Add action.
	 *
	 * @param string $hook Hook name.
	 * @param object $component Class instance.
	 * @param string $callback Callback method.
	 * @param int    $priority Priority.
	 * @param int    $accepted_args Args count.
	 * @return void
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions[] = $this->build_hook( $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add filter.
	 *
	 * @param string $hook Hook name.
	 * @param object $component Class instance.
	 * @param string $callback Callback method.
	 * @param int    $priority Priority.
	 * @param int    $accepted_args Args count.
	 * @return void
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters[] = $this->build_hook( $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Register hooks with WordPress.
	 *
	 * @return void
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}

	/**
	 * Build hook descriptor.
	 *
	 * @param string $hook Hook name.
	 * @param object $component Class instance.
	 * @param string $callback Callback method.
	 * @param int    $priority Priority.
	 * @param int    $accepted_args Args count.
	 * @return array<string, mixed>
	 */
	protected function build_hook( $hook, $component, $callback, $priority, $accepted_args ) {
		return array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}
