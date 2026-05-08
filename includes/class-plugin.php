<?php
/**
 * Main plugin orchestrator.
 *
 * Singleton entry point. Wires the conditions registry, attribute extender,
 * block renderer, and editor assets together. Reviewers should start here
 * to follow the plugin's wiring.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin orchestrator.
 *
 * Holds references to the long-lived service objects and runs them on
 * the appropriate WordPress hooks.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Conditions registry instance.
	 *
	 * @var Conditions_Registry|null
	 */
	private ?Conditions_Registry $registry = null;

	/**
	 * Block renderer instance.
	 *
	 * @var Block_Renderer|null
	 */
	private ?Block_Renderer $renderer = null;

	/**
	 * Attribute extender instance.
	 *
	 * @var Attribute_Extender|null
	 */
	private ?Attribute_Extender $extender = null;

	/**
	 * Get (or create) the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — use {@see Plugin::instance()}.
	 */
	private function __construct() {}

	/**
	 * Boot the plugin. Called once on `plugins_loaded`.
	 *
	 * @return void
	 */
	public function init(): void {
		// Implementation deferred.
	}

	/**
	 * Accessor for the conditions registry.
	 *
	 * @return Conditions_Registry
	 */
	public function registry(): Conditions_Registry {
		// Implementation deferred.
	}
}
