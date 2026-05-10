<?php
/**
 * Test fixture: a condition whose `evaluate()` always throws.
 *
 * Verifies that the renderer's "graceful degradation on exception"
 * branch keeps the page rendering even when a misbehaving condition
 * throws under it.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Tests\Fixtures;

use Block_When\Conditions\Abstract_Condition;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Always-throwing fixture condition.
 */
final class Throwing_Condition extends Abstract_Condition {

	/**
	 * Stable id used in test `blockWhen.conditionId` attributes.
	 */
	public const ID = 'fixture/throwing';

	/**
	 * Message used by the thrown exception. Tests can match against this
	 * to confirm the right exception was caught.
	 */
	public const MESSAGE = 'fixture: evaluate() always throws';

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
		return 'Throwing (test fixture)';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws RuntimeException Always.
	 */
	public function evaluate( array $settings, array $context ): bool {
		throw new RuntimeException( esc_html( self::MESSAGE ) );
	}
}
