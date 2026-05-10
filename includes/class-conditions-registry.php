<?php
/**
 * Conditions registry.
 *
 * Holds every registered visibility condition — the three built-ins plus
 * anything third-party plugins add. Implemented as a singleton because
 * there is exactly one canonical set of conditions per request, and
 * both the editor (via REST) and the front-end renderer need to hit
 * the same instance.
 *
 * Lifecycle:
 *
 *   1. Plugin boot creates the registry and registers built-ins.
 *   2. Plugin boot calls {@see bootstrap()}, which fires the
 *      `block_when_register_conditions` action so third-party plugins
 *      can register their own conditions against this instance.
 *   3. Subsequent calls to bootstrap() are no-ops.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When;

use Block_When\Conditions\Interface_Condition;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton registry of visibility conditions, keyed by id.
 */
final class Conditions_Registry {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered conditions, keyed by id.
	 *
	 * @var array<string, Interface_Condition>
	 */
	private array $conditions = array();

	/**
	 * Whether `block_when_register_conditions` has already fired.
	 *
	 * @var bool
	 */
	private bool $bootstrapped = false;

	/**
	 * Singleton — use {@see instance()}.
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
		throw new \LogicException( 'Cannot unserialize Conditions_Registry.' );
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register a condition.
	 *
	 * Stored under the condition's own id. If a condition with the same
	 * id is already registered it is replaced — last-write-wins. This is
	 * deliberate: it lets a site override a built-in condition with its
	 * own implementation by registering on a later priority.
	 *
	 * The `Interface_Condition` parameter type is the rejection mechanism
	 * for non-conforming arguments — PHP raises a `TypeError` natively
	 * when something else is passed in.
	 *
	 * @param Interface_Condition $condition Condition to register.
	 * @return void
	 */
	public function register( Interface_Condition $condition ): void {
		$this->conditions[ $condition->get_id() ] = $condition;
	}

	/**
	 * Get a condition by id.
	 *
	 * @param string $id Condition id, e.g. `user-state`.
	 * @return Interface_Condition|null Null when nothing is registered under that id.
	 */
	public function get( string $id ): ?Interface_Condition {
		return $this->conditions[ $id ] ?? null;
	}

	/**
	 * Get every registered condition, keyed by id.
	 *
	 * @return array<string, Interface_Condition>
	 */
	public function get_all(): array {
		return $this->conditions;
	}

	/**
	 * Fire the `block_when_register_conditions` extension hook once.
	 *
	 * Plugin orchestration calls this after registering built-ins so
	 * third-party plugins can register their own conditions against
	 * this instance. Idempotent — calling it more than once is a no-op,
	 * which keeps test ordering and accidental double-init harmless.
	 *
	 * @return void
	 */
	public function bootstrap(): void {
		if ( $this->bootstrapped ) {
			return;
		}
		$this->bootstrapped = true;

		/**
		 * Fires after built-in conditions are registered.
		 *
		 * Third-party plugins should hook here to register their own
		 * conditions — call `$registry->register( new My_Condition() )`.
		 *
		 * @param Conditions_Registry $registry The registry instance.
		 */
		do_action( 'block_when_register_conditions', $this );
	}
}
