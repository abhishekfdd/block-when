<?php
/**
 * User-state condition.
 *
 * Show a block when the visitor is logged in, logged out, or has one of a
 * specified set of roles.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Conditions;

defined( 'ABSPATH' ) || exit;

/**
 * Visibility based on the current user's authentication state and roles.
 */
final class User_State_Condition extends Abstract_Condition {

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		// Implementation deferred.
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		// Implementation deferred.
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_schema(): array {
		// Implementation deferred.
	}

	/**
	 * {@inheritDoc}
	 */
	public function evaluate( array $settings, array $context ): bool {
		// Implementation deferred.
	}
}
