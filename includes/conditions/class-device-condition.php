<?php
/**
 * Device-type condition.
 *
 * Show a block on desktop, tablet, or mobile. Detection happens server-side
 * and is cached per-request. The detected type can be filtered via
 * `block_when_device_type` to swap in an alternate detection library.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Conditions;

defined( 'ABSPATH' ) || exit;

/**
 * Visibility based on device type derived from the request user agent.
 */
final class Device_Condition extends Abstract_Condition {

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
