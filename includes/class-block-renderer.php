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

use Block_When\Conditions\Interface_Condition;
use Throwable;

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
	 * Priority 10 keeps us in line with WordPress's own default — third-party
	 * `render_block` filters that need to run before us register at a lower
	 * priority, those that need to run after register at a higher one.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'render_block', array( $this, 'filter_render_block' ), 10, 3 );
	}

	/**
	 * Filter callback for `render_block`.
	 *
	 * Decision tree, in order:
	 *   1. No `blockWhen` attribute, or empty object         → render as-is.
	 *   2. Missing / non-string / empty `conditionId`        → render as-is.
	 *   3. `conditionId` not in the registry                 → render as-is.
	 *   4. Condition's `evaluate()` throws                   → render as-is
	 *      (and `_doing_it_wrong()` when `WP_DEBUG` is on).
	 *   5. Result, after the `block_when_evaluate_{$id}`
	 *      filter, is falsy                                  → return ''.
	 *   6. Otherwise                                          → render as-is.
	 *
	 * Cases 1–3 are "graceful degradation": malformed or stale rules never
	 * silently pull a block off the page. Case 5 is the only path that
	 * suppresses output, and it fires `block_when_render_block_hidden`
	 * before returning so caching / SEO integrations can react.
	 *
	 * @param string                 $block_content Rendered block HTML.
	 * @param array<string, mixed>   $block         Parsed block array.
	 * @param \WP_Block|null         $instance      Block instance, if available.
	 * @return string Filtered block content (empty string when hidden).
	 */
	public function filter_render_block( string $block_content, array $block, ?\WP_Block $instance = null ): string {
		$attrs      = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$block_when = $attrs['blockWhen'] ?? null;

		// Case 1: no rule == always visible.
		if ( ! is_array( $block_when ) || array() === $block_when ) {
			return $block_content;
		}

		// Case 2: malformed rule == always visible.
		$condition_id = $block_when['conditionId'] ?? '';
		if ( ! is_string( $condition_id ) || '' === $condition_id ) {
			return $block_content;
		}

		// Case 3: condition unknown (e.g. registered by a now-deactivated plugin) == always visible.
		$condition = $this->registry->get( $condition_id );
		if ( null === $condition ) {
			return $block_content;
		}

		$settings = $block_when['settings'] ?? array();
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$context = $this->build_context( $block, $instance );

		try {
			$result = $condition->evaluate( $settings, $context );
		} catch ( Throwable $e ) {
			// Case 4: a condition that throws should not take a page down with it.
			$this->log_evaluation_failure( $condition_id, $e );
			return $block_content;
		}

		/**
		 * Filter a condition's evaluation result.
		 *
		 * Last-mile override on the boolean returned by a condition's
		 * `evaluate()` method, indexed by the condition's id. Useful for
		 * site-level overrides ("force this block visible for editors")
		 * and for tests that want to swap an evaluation outcome without
		 * subclassing the condition.
		 *
		 * @param bool                  $result   Whether the block should be visible.
		 * @param array<string, mixed>  $settings Per-block condition settings.
		 * @param array<string, mixed>  $context  Render-time context.
		 */
		$result = (bool) apply_filters( "block_when_evaluate_{$condition_id}", $result, $settings, $context );

		if ( $result ) {
			return $block_content;
		}

		/**
		 * Fires immediately before a block is suppressed by Block When.
		 *
		 * Public extension point for caching layers (so they can vary the
		 * cache key by the condition that hid the block) and SEO plugins
		 * (so they can avoid surfacing hidden content in sitemaps or
		 * social previews). Receives the parsed block array and the
		 * condition object that returned `false`.
		 *
		 * @param array<string, mixed> $block             Parsed block array.
		 * @param Interface_Condition  $matched_condition Condition that hid the block.
		 */
		do_action( 'block_when_render_block_hidden', $block, $condition );

		return '';
	}

	/**
	 * Build the `$context` argument passed to a condition's `evaluate()`.
	 *
	 * Inside a Query Loop, WordPress populates `$instance->context['postId']`
	 * with the looped post id. We honour that first and only fall back to
	 * `get_post()` when no instance context is available — otherwise a
	 * condition reading `post_id` would always see the outer page's post
	 * instead of the looped one.
	 *
	 * @param array<string, mixed> $block    Parsed block array.
	 * @param \WP_Block|null       $instance Block instance, if available.
	 * @return array<string, mixed>
	 */
	private function build_context( array $block, ?\WP_Block $instance ): array {
		$post_id = null;

		if ( null !== $instance && isset( $instance->context['postId'] ) ) {
			$post_id = (int) $instance->context['postId'];
		} else {
			$current = get_post();
			if ( $current instanceof \WP_Post ) {
				$post_id = (int) $current->ID;
			}
		}

		return array(
			'block'    => $block,
			'instance' => $instance,
			'post_id'  => $post_id,
		);
	}

	/**
	 * Surface an `evaluate()` exception when `WP_DEBUG` is on.
	 *
	 * Production sites swallow the failure (the renderer already returned
	 * the unfiltered block content); developers running with `WP_DEBUG`
	 * see the bad condition called out via `_doing_it_wrong()`.
	 *
	 * @param string    $condition_id Condition id whose evaluate threw.
	 * @param Throwable $error        The thrown error.
	 * @return void
	 */
	private function log_evaluation_failure( string $condition_id, Throwable $error ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		_doing_it_wrong(
			__CLASS__ . '::filter_render_block',
			sprintf(
				/* translators: 1: condition id, 2: exception message. */
				esc_html__( 'Block When condition "%1$s" threw during evaluation: %2$s. Block was rendered as if no rule were set.', 'block-when' ),
				esc_html( $condition_id ),
				esc_html( $error->getMessage() )
			),
			'Block When 1.0.0'
		);
	}
}
