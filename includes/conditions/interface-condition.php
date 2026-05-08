<?php
/**
 * Condition interface.
 *
 * Public contract that every condition (built-in or third-party) must
 * implement to be registerable via {@see Conditions_Registry::register()}.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Conditions;

defined( 'ABSPATH' ) || exit;

/**
 * Contract for visibility conditions.
 */
interface Interface_Condition {

	/**
	 * Stable, machine-readable id for the condition (e.g. `user-state`).
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Translated, human-readable label for the inspector UI.
	 *
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * Schema for the per-condition settings stored on each block.
	 *
	 * Mirrors the `attributes` shape used by `register_block_type_args`.
	 *
	 * @return array<string, mixed>
	 */
	public function get_schema(): array;

	/**
	 * Evaluate the condition for a given block context.
	 *
	 * @param array<string, mixed> $settings Per-block settings for this condition.
	 * @param array<string, mixed> $context  Render context (block, post, etc.).
	 * @return bool True if the block should be visible, false to hide.
	 */
	public function evaluate( array $settings, array $context ): bool;
}
