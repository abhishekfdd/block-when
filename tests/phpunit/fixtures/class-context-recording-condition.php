<?php
/**
 * Test fixture: a condition that records the `$context` it receives.
 *
 * Used by the renderer tests to verify that Query Loop state is plumbed
 * through to `evaluate()` correctly — particularly the `post_id` key,
 * which must reflect the looped post and not the outer page's post.
 *
 * @package RenderWhen
 */

declare( strict_types=1 );

namespace RenderWhen\Tests\Fixtures;

use RenderWhen\Conditions\Abstract_Condition;

defined( 'ABSPATH' ) || exit;

/**
 * Records the most recent `$context` argument passed to `evaluate()`.
 */
final class Context_Recording_Condition extends Abstract_Condition {

	/**
	 * Stable id used in test `renderWhen.conditionId` attributes.
	 */
	public const ID = 'fixture/context_recorder';

	/**
	 * Last `$context` argument received by `evaluate()`.
	 *
	 * @var array<string, mixed>|null
	 */
	public ?array $last_context = null;

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
		return 'Context recorder (test fixture)';
	}

	/**
	 * {@inheritDoc}
	 */
	public function evaluate( array $settings, array $context ): bool {
		$this->last_context = $context;
		return true;
	}
}
