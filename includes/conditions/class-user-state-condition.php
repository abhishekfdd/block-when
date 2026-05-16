<?php
/**
 * User-state condition.
 *
 * Shows a block when the current visitor matches at least one of a
 * configured set of states. Each state is one of:
 *
 *   - `logged_in`           — any authenticated user.
 *   - `logged_out`          — any anonymous visitor.
 *   - `role:<role-slug>`    — an authenticated user with that WP role.
 *
 * Multiple states combine with OR. An empty `states` array means
 * "no constraint" and resolves to visible — consistent with the
 * "always visible on missing input" policy from Interface_Condition.
 *
 * @package RenderWhen
 */

declare( strict_types=1 );

namespace RenderWhen\Conditions;

defined( 'ABSPATH' ) || exit;

/**
 * Visibility based on the current user's authentication state and roles.
 */
final class User_State_Condition extends Abstract_Condition {

	/**
	 * Prefix marking a role-membership entry inside the `states` array.
	 */
	private const ROLE_PREFIX = 'role:';

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return 'user_state';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return __( 'User state', 'renderwhen' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * Settings shape: `{ states: string[] }`. We deliberately do not
	 * declare an `enum` for the items because `role:<slug>` is open-ended
	 * — site administrators can register custom roles at any time, and
	 * an enum would have to be regenerated on each role change.
	 */
	public function get_schema(): array {
		return array(
			'states' => array(
				'type'    => 'array',
				'items'   => array( 'type' => 'string' ),
				'default' => array(),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * Walks the `states` array and returns true on the first match.
	 * The current user is read once via `wp_get_current_user()` and
	 * its `roles` are cached in a local variable, so a settings array
	 * with many `role:*` entries still only touches the user object once.
	 */
	public function evaluate( array $settings, array $context ): bool {
		$settings = $this->sanitize_settings( $settings );
		$states   = $settings['states'];

		if ( empty( $states ) ) {
			return true;
		}

		$is_logged_in = is_user_logged_in();
		$user_roles   = null;

		foreach ( $states as $state ) {
			if ( ! is_string( $state ) || '' === $state ) {
				continue;
			}

			if ( 'logged_in' === $state ) {
				if ( $is_logged_in ) {
					return true;
				}
				continue;
			}

			if ( 'logged_out' === $state ) {
				if ( ! $is_logged_in ) {
					return true;
				}
				continue;
			}

			if ( 0 === strpos( $state, self::ROLE_PREFIX ) ) {
				if ( ! $is_logged_in ) {
					continue;
				}

				$role = substr( $state, strlen( self::ROLE_PREFIX ) );
				if ( '' === $role ) {
					continue;
				}

				if ( null === $user_roles ) {
					$user_roles = wp_get_current_user()->roles;
				}

				if ( in_array( $role, $user_roles, true ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
