<?php
/**
 * Tests for {@see Block_When\Conditions\User_State_Condition}.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Tests\Conditions;

use Block_When\Conditions\User_State_Condition;
use WP_UnitTestCase;

defined( 'ABSPATH' ) || exit;

/**
 * User-state condition: logged-in / logged-out / role membership, OR semantics.
 */
final class Test_User_State_Condition extends WP_UnitTestCase {

	/**
	 * The condition under test.
	 *
	 * @var User_State_Condition
	 */
	private User_State_Condition $condition;

	/**
	 * Fresh condition instance per test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->condition = new User_State_Condition();
	}

	/**
	 * Reset auth state so each test starts anonymous.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Build a settings array. Convenience for readability in tests.
	 *
	 * @param array<int, string> $states States array.
	 * @return array<string, mixed>
	 */
	private function settings( array $states ): array {
		return array( 'states' => $states );
	}

	/**
	 * get_id() returns the locked-in identifier.
	 */
	public function test_get_id_is_stable(): void {
		$this->assertSame( 'user-state', $this->condition->get_id() );
	}

	/**
	 * get_label() returns a non-empty translated string.
	 */
	public function test_get_label_is_non_empty_string(): void {
		$label = $this->condition->get_label();

		$this->assertIsString( $label );
		$this->assertNotSame( '', $label );
	}

	/**
	 * get_schema() declares a `states` array of strings with empty default.
	 */
	public function test_schema_describes_states_array(): void {
		$schema = $this->condition->get_schema();

		$this->assertArrayHasKey( 'states', $schema );
		$this->assertSame( 'array', $schema['states']['type'] );
		$this->assertSame( 'string', $schema['states']['items']['type'] );
		$this->assertSame( array(), $schema['states']['default'] );
	}

	/**
	 * Empty `states` is treated as "no constraint" → visible.
	 */
	public function test_empty_states_resolves_to_visible(): void {
		$this->assertTrue( $this->condition->evaluate( $this->settings( array() ), array() ) );
	}

	/**
	 * Missing `states` key (settings entirely empty) → visible.
	 */
	public function test_missing_states_key_resolves_to_visible(): void {
		$this->assertTrue( $this->condition->evaluate( array(), array() ) );
	}

	/**
	 * `logged_in` matches an authenticated user.
	 */
	public function test_logged_in_state_matches_authenticated_user(): void {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

		$this->assertTrue(
			$this->condition->evaluate( $this->settings( array( 'logged_in' ) ), array() )
		);
	}

	/**
	 * `logged_in` does not match an anonymous visitor.
	 */
	public function test_logged_in_state_does_not_match_anonymous_visitor(): void {
		wp_set_current_user( 0 );

		$this->assertFalse(
			$this->condition->evaluate( $this->settings( array( 'logged_in' ) ), array() )
		);
	}

	/**
	 * `logged_out` matches an anonymous visitor.
	 */
	public function test_logged_out_state_matches_anonymous_visitor(): void {
		wp_set_current_user( 0 );

		$this->assertTrue(
			$this->condition->evaluate( $this->settings( array( 'logged_out' ) ), array() )
		);
	}

	/**
	 * `logged_out` does not match an authenticated user.
	 */
	public function test_logged_out_state_does_not_match_authenticated_user(): void {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

		$this->assertFalse(
			$this->condition->evaluate( $this->settings( array( 'logged_out' ) ), array() )
		);
	}

	/**
	 * `role:<slug>` matches a user with that role.
	 */
	public function test_role_state_matches_user_with_that_role(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$this->assertTrue(
			$this->condition->evaluate( $this->settings( array( 'role:administrator' ) ), array() )
		);
	}

	/**
	 * `role:<slug>` does not match a user with a different role.
	 */
	public function test_role_state_does_not_match_user_with_different_role(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$this->assertFalse(
			$this->condition->evaluate( $this->settings( array( 'role:administrator' ) ), array() )
		);
	}

	/**
	 * `role:<slug>` does not match anonymous visitors.
	 */
	public function test_role_state_does_not_match_anonymous_visitor(): void {
		wp_set_current_user( 0 );

		$this->assertFalse(
			$this->condition->evaluate( $this->settings( array( 'role:administrator' ) ), array() )
		);
	}

	/**
	 * Multiple states combine with OR — any match is enough.
	 */
	public function test_states_combine_with_or_semantics(): void {
		$editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor_id );

		$this->assertTrue(
			$this->condition->evaluate(
				$this->settings( array( 'role:administrator', 'role:editor' ) ),
				array()
			)
		);
	}

	/**
	 * `logged_out` ORed with a role match still resolves correctly when
	 * the visitor is anonymous (the logged_out branch wins).
	 */
	public function test_logged_out_or_role_visible_for_anonymous(): void {
		wp_set_current_user( 0 );

		$this->assertTrue(
			$this->condition->evaluate(
				$this->settings( array( 'logged_out', 'role:administrator' ) ),
				array()
			)
		);
	}

	/**
	 * Unknown state strings are ignored — they neither match nor error.
	 */
	public function test_unknown_states_are_ignored(): void {
		wp_set_current_user( 0 );

		$this->assertFalse(
			$this->condition->evaluate(
				$this->settings( array( 'banana', 'role-administrator' ) ),
				array()
			)
		);
	}

	/**
	 * `role:` with no slug is ignored (does not match every role).
	 */
	public function test_empty_role_slug_is_ignored(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$this->assertFalse(
			$this->condition->evaluate( $this->settings( array( 'role:' ) ), array() )
		);
	}

	/**
	 * Mixing valid and unknown entries — the valid one still decides.
	 */
	public function test_unknown_entries_do_not_block_valid_match(): void {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

		$this->assertTrue(
			$this->condition->evaluate(
				$this->settings( array( 'banana', 'logged_in' ) ),
				array()
			)
		);
	}
}
