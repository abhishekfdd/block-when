<?php
/**
 * Block attribute extender.
 *
 * Hooks `register_block_type_args` to inject the `blockWhen` attribute
 * schema into every registered block, server-side, so the editor JS can
 * read and write it without each block having to declare the attribute.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the `blockWhen` attribute to every block type.
 */
final class Attribute_Extender {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Implementation deferred.
	}

	/**
	 * Filter callback for `register_block_type_args`.
	 *
	 * @param array<string, mixed> $args      Block type arguments.
	 * @param string               $block_type Block type name.
	 * @return array<string, mixed> Filtered arguments with the `blockWhen` attribute injected.
	 */
	public function filter_register_block_type_args( array $args, string $block_type ): array {
		// Implementation deferred.
	}
}
