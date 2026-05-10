<?php
/**
 * Main plugin orchestrator.
 *
 * Singleton entry point. Wires the conditions registry, attribute extender,
 * and block renderer together on `plugins_loaded`. Reviewers and
 * contributors looking for behaviour should start here.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When;

use Block_When\Conditions\Date_Range_Condition;
use Block_When\Conditions\Device_Condition;
use Block_When\Conditions\User_State_Condition;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin orchestrator.
 *
 * The registry is intentionally not stored on this singleton and not
 * exposed via an accessor — the renderer that needs it receives it by
 * constructor injection, and third-party code reaches the registry via
 * the public `block_when_register_conditions` action argument.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Whether {@see init()} has already run.
	 *
	 * Set to true at the *start* of init() before any work, so a partial
	 * failure mid-boot does not leave the plugin half-wired and re-runnable
	 * on a retry — better to fail loud than to double-register hooks.
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Singleton — use {@see Plugin::instance()}.
	 */
	private function __construct() {}

	/**
	 * Singletons are not cloneable.
	 */
	private function __clone() {}

	/**
	 * Singletons are not unserializable.
	 *
	 * @throws \LogicException Always.
	 */
	public function __wakeup() {
		throw new \LogicException( 'Cannot unserialize Plugin.' );
	}

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
	 * Boot the plugin. Called once on `plugins_loaded`.
	 *
	 * Order:
	 *   1. Load the plugin text domain.
	 *   2. Resolve the conditions registry.
	 *   3. Hook our own callback into `block_when_register_conditions`
	 *      that registers the three built-in conditions, then bootstrap
	 *      the registry — built-ins go through the same public action
	 *      third parties use, which proves the path works.
	 *   4. Wire the attribute extender.
	 *   5. Wire the renderer with the registry injected.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( self::$initialized ) {
			return;
		}
		self::$initialized = true;

		load_plugin_textdomain(
			'block-when',
			false,
			dirname( BLOCK_WHEN_BASENAME ) . '/languages'
		);

		$registry = Conditions_Registry::instance();

		add_action(
			'block_when_register_conditions',
			static function ( Conditions_Registry $registering_into ): void {
				$registering_into->register( new User_State_Condition() );
				$registering_into->register( new Date_Range_Condition() );
				$registering_into->register( new Device_Condition() );
			}
		);

		$registry->bootstrap();

		( new Attribute_Extender() )->register_hooks();
		( new Block_Renderer( $registry ) )->register_hooks();
	}
}
