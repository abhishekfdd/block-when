<?php
/**
 * Date-range condition.
 *
 * Shows a block when the current site time falls within a configured
 * `[start, end]` window. Either bound may be null (open-ended) and
 * both bounds are inclusive.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Conditions;

use DateTimeImmutable;

defined( 'ABSPATH' ) || exit;

/**
 * Time-window visibility, evaluated in the site's timezone.
 *
 * Both bounds are interpreted as wall-clock times in the site's
 * configured timezone (`wp_timezone()`), so DST transitions are
 * transparent — "March 8, 2026 03:30:00" in America/New_York refers
 * to the same UTC moment regardless of when the request happens.
 *
 * Malformed or unparseable bounds resolve to "always visible" rather
 * than throwing, matching the rest of the plugin's policy of never
 * silently removing a block from the page on bad input.
 */
final class Date_Range_Condition extends Abstract_Condition {

	/**
	 * Bound input format. Matches the editor's date-time picker output
	 * and the documented settings shape.
	 */
	private const BOUND_FORMAT = 'Y-m-d H:i:s';

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return 'date_range';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return __( 'Date range', 'block-when' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * Settings shape: `{ start: string|null, end: string|null }`.
	 * Each string is `Y-m-d H:i:s` interpreted in the site's
	 * timezone. A `null` (or empty) bound means open-ended on
	 * that side.
	 */
	public function get_schema(): array {
		return array(
			'start' => array(
				'type'    => array( 'string', 'null' ),
				'default' => null,
			),
			'end'   => array(
				'type'    => array( 'string', 'null' ),
				'default' => null,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * Returns true when the current moment (as reported by
	 * `current_datetime()`, filterable via `block_when_date_range_now`)
	 * is inside the inclusive `[start, end]` window. Either bound may
	 * be null; both null means "always visible". Unparseable bounds
	 * are also treated as open-ended on that side.
	 */
	public function evaluate( array $settings, array $context ): bool {
		$settings = $this->sanitize_settings( $settings );
		$start    = $settings['start'];
		$end      = $settings['end'];

		if ( ! $this->is_bound( $start ) && ! $this->is_bound( $end ) ) {
			return true;
		}

		$site_tz = wp_timezone();

		/**
		 * Reference moment for the date-range evaluation.
		 *
		 * Filter exists so tests and future "preview as date" tooling
		 * can drive the decision without touching the system clock.
		 *
		 * @param DateTimeImmutable $now Current site-time moment.
		 */
		$now = apply_filters( 'block_when_date_range_now', current_datetime() );

		if ( $this->is_bound( $start ) ) {
			$parsed = DateTimeImmutable::createFromFormat( self::BOUND_FORMAT, $start, $site_tz );
			if ( false === $parsed ) {
				return true;
			}
			if ( $now < $parsed ) {
				return false;
			}
		}

		if ( $this->is_bound( $end ) ) {
			$parsed = DateTimeImmutable::createFromFormat( self::BOUND_FORMAT, $end, $site_tz );
			if ( false === $parsed ) {
				return true;
			}
			if ( $now > $parsed ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether a stored bound represents a real value (not "open-ended").
	 *
	 * Both null and empty string mean "no bound on this side"; anything
	 * non-string is treated the same way (the parse step would fail
	 * downstream and a non-string can't be a meaningful date anyway).
	 *
	 * @param mixed $value Stored bound.
	 * @return bool
	 */
	private function is_bound( $value ): bool {
		return is_string( $value ) && '' !== $value;
	}
}
