<?php
/**
 * Tests for {@see RenderWhen\Conditions\Date_Range_Condition}.
 *
 * @package RenderWhen
 */

declare( strict_types=1 );

namespace RenderWhen\Tests\Conditions;

use RenderWhen\Conditions\Date_Range_Condition;
use DateTimeImmutable;
use WP_UnitTestCase;

defined( 'ABSPATH' ) || exit;

/**
 * Date-range condition: inclusive bounds, site timezone, DST boundaries,
 * open-ended (only-start or only-end) ranges, malformed-input grace.
 */
final class Test_Date_Range_Condition extends WP_UnitTestCase {

	/**
	 * The condition under test.
	 *
	 * @var Date_Range_Condition
	 */
	private Date_Range_Condition $condition;

	/**
	 * Fresh condition per test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->condition = new Date_Range_Condition();
	}

	/**
	 * Reset timezone option and any pinned "now" between tests.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		delete_option( 'timezone_string' );
		remove_all_filters( 'renderwhen_date_range_now' );
		parent::tear_down();
	}

	/**
	 * Pin the condition's "now" to a specific wall-clock moment in the
	 * site's currently configured timezone. Resolution happens at
	 * filter-call time, so tests can `update_option('timezone_string', …)`
	 * before or after pinning — the right zone is always used.
	 *
	 * @param string $wall_clock String in `Y-m-d H:i:s` format.
	 * @return void
	 */
	private function pin_now( string $wall_clock ): void {
		remove_all_filters( 'renderwhen_date_range_now' );
		add_filter(
			'renderwhen_date_range_now',
			static function () use ( $wall_clock ): DateTimeImmutable {
				return new DateTimeImmutable( $wall_clock, wp_timezone() );
			}
		);
	}

	/**
	 * Identity surface: id is the locked-in identifier.
	 */
	public function test_get_id_is_stable(): void {
		$this->assertSame( 'date_range', $this->condition->get_id() );
	}

	/**
	 * Identity surface: label is a non-empty translated string.
	 */
	public function test_get_label_is_non_empty_string(): void {
		$label = $this->condition->get_label();

		$this->assertIsString( $label );
		$this->assertNotSame( '', $label );
	}

	/**
	 * Schema declares nullable string bounds with null defaults.
	 */
	public function test_schema_declares_nullable_string_bounds(): void {
		$schema = $this->condition->get_schema();

		foreach ( array( 'start', 'end' ) as $key ) {
			$this->assertArrayHasKey( $key, $schema );
			$this->assertContains( 'string', (array) $schema[ $key ]['type'] );
			$this->assertContains( 'null', (array) $schema[ $key ]['type'] );
			$this->assertNull( $schema[ $key ]['default'] );
		}
	}

	/**
	 * "Now" inside the configured range → visible.
	 */
	public function test_now_inside_range_is_visible(): void {
		$this->pin_now( '2026-06-15 12:00:00' );

		$this->assertTrue(
			$this->condition->evaluate(
				array(
					'start' => '2026-06-15 00:00:00',
					'end'   => '2026-06-15 23:59:59',
				),
				array()
			)
		);
	}

	/**
	 * "Now" before start → hidden.
	 */
	public function test_now_before_start_is_hidden(): void {
		$this->pin_now( '2026-06-14 23:59:59' );

		$this->assertFalse(
			$this->condition->evaluate(
				array(
					'start' => '2026-06-15 00:00:00',
					'end'   => '2026-06-15 23:59:59',
				),
				array()
			)
		);
	}

	/**
	 * "Now" after end → hidden.
	 */
	public function test_now_after_end_is_hidden(): void {
		$this->pin_now( '2026-06-16 00:00:00' );

		$this->assertFalse(
			$this->condition->evaluate(
				array(
					'start' => '2026-06-15 00:00:00',
					'end'   => '2026-06-15 23:59:59',
				),
				array()
			)
		);
	}

	/**
	 * Open-ended start (null start, set end), now before end → visible.
	 */
	public function test_open_ended_start_with_now_before_end_is_visible(): void {
		$this->pin_now( '2026-06-15 12:00:00' );

		$this->assertTrue(
			$this->condition->evaluate(
				array(
					'start' => null,
					'end'   => '2027-01-01 00:00:00',
				),
				array()
			)
		);
	}

	/**
	 * Open-ended start (null start, set end), now after end → hidden.
	 */
	public function test_open_ended_start_with_now_after_end_is_hidden(): void {
		$this->pin_now( '2027-06-01 00:00:00' );

		$this->assertFalse(
			$this->condition->evaluate(
				array(
					'start' => null,
					'end'   => '2027-01-01 00:00:00',
				),
				array()
			)
		);
	}

	/**
	 * Open-ended end (set start, null end), now after start → visible.
	 */
	public function test_open_ended_end_with_now_after_start_is_visible(): void {
		$this->pin_now( '2027-01-01 00:00:00' );

		$this->assertTrue(
			$this->condition->evaluate(
				array(
					'start' => '2026-01-01 00:00:00',
					'end'   => null,
				),
				array()
			)
		);
	}

	/**
	 * Open-ended end (set start, null end), now before start → hidden.
	 */
	public function test_open_ended_end_with_now_before_start_is_hidden(): void {
		$this->pin_now( '2025-12-31 23:59:59' );

		$this->assertFalse(
			$this->condition->evaluate(
				array(
					'start' => '2026-01-01 00:00:00',
					'end'   => null,
				),
				array()
			)
		);
	}

	/**
	 * Both bounds null → always visible (no constraint).
	 */
	public function test_both_bounds_null_is_always_visible(): void {
		$this->pin_now( '2026-06-15 12:00:00' );

		$this->assertTrue(
			$this->condition->evaluate(
				array(
					'start' => null,
					'end'   => null,
				),
				array()
			)
		);
	}

	/**
	 * A range that crosses a DST forward jump in the site's timezone is
	 * evaluated by wall-clock semantics. The site is set to
	 * America/New_York where DST starts on 2026-03-08 (clocks jump from
	 * 02:00 EST to 03:00 EDT). The configured range spans that jump and
	 * the "now" is pinned to a wall-clock moment inside the range —
	 * regardless of whether the moment falls in EST or EDT.
	 */
	public function test_dst_transition_does_not_break_evaluation(): void {
		update_option( 'timezone_string', 'America/New_York' );

		$settings = array(
			'start' => '2026-03-07 12:00:00',
			'end'   => '2026-03-09 12:00:00',
		);

		// Wall-clock noon on the DST transition day, after the jump.
		$this->pin_now( '2026-03-08 12:00:00' );
		$this->assertTrue( $this->condition->evaluate( $settings, array() ) );

		// A moment clearly before the start, same site TZ.
		$this->pin_now( '2026-03-06 12:00:00' );
		$this->assertFalse( $this->condition->evaluate( $settings, array() ) );

		// A wall-clock moment at the DST jump itself (3:30 AM EDT) —
		// inside the narrower range that crosses the jump.
		$this->pin_now( '2026-03-08 03:30:00' );
		$this->assertTrue(
			$this->condition->evaluate(
				array(
					'start' => '2026-03-08 01:00:00',
					'end'   => '2026-03-08 04:00:00',
				),
				array()
			)
		);
	}

	/**
	 * Malformed start string → graceful "always visible".
	 */
	public function test_malformed_start_is_always_visible(): void {
		$this->pin_now( '2026-06-15 12:00:00' );

		$this->assertTrue(
			$this->condition->evaluate(
				array(
					'start' => 'not a real date',
					'end'   => '2030-01-01 00:00:00',
				),
				array()
			)
		);
	}

	/**
	 * Malformed end string → graceful "always visible".
	 */
	public function test_malformed_end_is_always_visible(): void {
		$this->pin_now( '2026-06-15 12:00:00' );

		$this->assertTrue(
			$this->condition->evaluate(
				array(
					'start' => '2020-01-01 00:00:00',
					'end'   => 'banana',
				),
				array()
			)
		);
	}
}
