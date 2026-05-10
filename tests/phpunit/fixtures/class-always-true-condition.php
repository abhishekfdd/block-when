<?php
/**
 * Test fixture: a condition that always evaluates to true.
 *
 * Lets renderer tests assert "block visible" behaviour without relying on
 * the side-effects of any real condition (timezone, current user, device).
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Tests\Fixtures;

use Block_When\Conditions\Abstract_Condition;

defined( 'ABSPATH' ) || exit;

/**
 * Always-visible fixture condition.
 */
final class Always_True_Condition extends Abstract_Condition {

	/**
	 * Stable id used in test `blockWhen.conditionId` attributes.
	 */
	public const ID = 'fixture/always_true';

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
		return 'Always true (test fixture)';
	}

	/**
	 * {@inheritDoc}
	 */
	public function evaluate( array $settings, array $context ): bool {
		return true;
	}
}
