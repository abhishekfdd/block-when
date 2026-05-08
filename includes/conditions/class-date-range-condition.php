<?php
/**
 * Date-range condition.
 *
 * Show a block between two datetimes. Evaluation is timezone-aware and
 * uses the site timezone via `wp_date()`.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Conditions;

defined( 'ABSPATH' ) || exit;

/**
 * Visibility bounded by an inclusive datetime range.
 */
final class Date_Range_Condition extends Abstract_Condition {

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
