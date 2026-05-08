<?php
/**
 * Server-side block visibility renderer.
 *
 * Hooks into `render_block` and decides — based on the block's `blockWhen`
 * attribute — whether to return the rendered HTML or an empty string.
 * Hidden blocks are never sent to the browser.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When;

defined( 'ABSPATH' ) || exit;

/**
 * Filters block output based on registered conditions.
 */
final class Block_Renderer {

	/**
	 * Conditions registry.
	 *
	 * @var Conditions_Registry
	 */
	private Conditions_Registry $registry;

	/**
	 * Constructor.
	 *
	 * @param Conditions_Registry $registry Conditions registry.
	 */
	public function __construct( Conditions_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Implementation deferred.
	}

	/**
	 * Filter callback for `render_block`.
	 *
	 * @param string                 $block_content Rendered block HTML.
	 * @param array<string, mixed>   $block         Parsed block array.
	 * @param \WP_Block|null         $instance      Block instance, if available.
	 * @return string Filtered block content (empty string when hidden).
	 */
	public function filter_render_block( string $block_content, array $block, ?\WP_Block $instance = null ): string {
		// Implementation deferred.
	}
}
