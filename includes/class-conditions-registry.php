<?php
/**
 * Conditions registry.
 *
 * Holds all registered visibility conditions (built-in and third-party).
 * Exposes the `block_when_register_conditions` action so add-on plugins
 * can register their own conditions.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When;

use Block_When\Conditions\Interface_Condition;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of available conditions.
 *
 * Keyed by the condition's id (e.g. `user-state`).
 */
final class Conditions_Registry {

	/**
	 * Registered conditions, keyed by id.
	 *
	 * @var array<string, Interface_Condition>
	 */
	private array $conditions = array();

	/**
	 * Register a condition.
	 *
	 * @param Interface_Condition $condition Condition to register.
	 * @return void
	 */
	public function register( Interface_Condition $condition ): void {
		// Implementation deferred.
	}

	/**
	 * Unregister a condition by id.
	 *
	 * @param string $id Condition id.
	 * @return void
	 */
	public function unregister( string $id ): void {
		// Implementation deferred.
	}

	/**
	 * Get a condition by id.
	 *
	 * @param string $id Condition id.
	 * @return Interface_Condition|null
	 */
	public function get( string $id ): ?Interface_Condition {
		// Implementation deferred.
	}

	/**
	 * Get all registered conditions.
	 *
	 * @return array<string, Interface_Condition>
	 */
	public function all(): array {
		// Implementation deferred.
	}
}
