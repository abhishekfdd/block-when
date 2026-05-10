<?php
/**
 * Test fixture: a condition that always evaluates to false.
 *
 * Lets renderer tests assert "block hidden" behaviour deterministically,
 * independent of any real condition's evaluation logic.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Tests\Fixtures;

use Block_When\Conditions\Abstract_Condition;

defined( 'ABSPATH' ) || exit;

/**
 * Always-hidden fixture condition.
 */
final class Always_False_Condition extends Abstract_Condition {

	/**
	 * Stable id used in test `blockWhen.conditionId` attributes.
	 */
	public const ID = 'fixture/always_false';

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return self::ID;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return 'Always false (test fixture)';
	}

	/**
	 * {@inheritDoc}
	 */
	public function evaluate( array $settings, array $context ): bool {
		return false;
	}
}
