<?php
/**
 * Tests for {@see RenderWhen\Block_Renderer}.
 *
 * @package RenderWhen
 */

declare( strict_types=1 );

namespace RenderWhen\Tests;

use RenderWhen\Block_Renderer;
use RenderWhen\Conditions_Registry;
use RenderWhen\Tests\Fixtures\Always_False_Condition;
use RenderWhen\Tests\Fixtures\Always_True_Condition;
use RenderWhen\Tests\Fixtures\Context_Recording_Condition;
use RenderWhen\Tests\Fixtures\Throwing_Condition;
use ReflectionClass;
use WP_Block;
use WP_UnitTestCase;

defined( 'ABSPATH' ) || exit;

/**
 * Renderer behaviour: hidden blocks return empty string, visible blocks
 * pass through, malformed `renderWhen` attribute is treated as visible,
 * Query Loop context is honoured, exceptions degrade gracefully.
 */
final class Test_Block_Renderer extends WP_UnitTestCase {

	/**
	 * Block type registered in tests that exercise Query Loop context.
	 *
	 * Declares `uses_context: ['postId']` so a `WP_Block` constructed for
	 * it actually exposes the looped post id on its `context` property —
	 * which is exactly what the renderer reads when building the
	 * `$context` it passes to `evaluate()`.
	 */
	private const CONTEXTUAL_BLOCK = 'renderwhen-test/contextual';

	/**
	 * Renderer under test.
	 *
	 * @var Block_Renderer
	 */
	private Block_Renderer $renderer;

	/**
	 * Conditions registry passed into the renderer.
	 *
	 * @var Conditions_Registry
	 */
	private Conditions_Registry $registry;

	/**
	 * Fresh registry singleton + fresh renderer for every test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$instance = ( new ReflectionClass( Conditions_Registry::class ) )
			->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		$this->registry = Conditions_Registry::instance();
		$this->renderer = new Block_Renderer( $this->registry );

		register_block_type(
			self::CONTEXTUAL_BLOCK,
			array(
				'uses_context' => array( 'postId' ),
			)
		);
	}

	/**
	 * Drop test-only state between cases.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		unregister_block_type( self::CONTEXTUAL_BLOCK );
		remove_all_filters( 'render_block' );
		remove_all_actions( 'renderwhen_render_block_hidden' );
		parent::tear_down();
	}

	/**
	 * Build a parsed-block array carrying a `renderWhen` attribute.
	 *
	 * @param array<string, mixed>|null $render_when Value for `attrs.renderWhen` (null = omit).
	 * @return array<string, mixed>
	 */
	private function block_with_rule( ?array $render_when ): array {
		$attrs = array();
		if ( null !== $render_when ) {
			$attrs['renderWhen'] = $render_when;
		}

		return array(
			'blockName'    => 'core/paragraph',
			'attrs'        => $attrs,
			'innerBlocks'  => array(),
			'innerHTML'    => '<p>hello</p>',
			'innerContent' => array( '<p>hello</p>' ),
		);
	}

	/**
	 * register_hooks() registers the render_block filter at priority 10.
	 */
	public function test_register_hooks_attaches_render_block_filter_at_priority_10(): void {
		$this->renderer->register_hooks();

		$this->assertSame(
			10,
			has_filter( 'render_block', array( $this->renderer, 'filter_render_block' ) )
		);
	}

	/**
	 * No `renderWhen` attribute at all → block renders unchanged.
	 */
	public function test_block_without_attribute_renders_normally(): void {
		$result = $this->renderer->filter_render_block(
			'<p>hello</p>',
			$this->block_with_rule( null )
		);

		$this->assertSame( '<p>hello</p>', $result );
	}

	/**
	 * Empty `renderWhen` object → block renders unchanged.
	 */
	public function test_empty_renderwhen_renders_normally(): void {
		$result = $this->renderer->filter_render_block(
			'<p>hello</p>',
			$this->block_with_rule( array() )
		);

		$this->assertSame( '<p>hello</p>', $result );
	}

	/**
	 * Missing `conditionId` → block renders unchanged.
	 */
	public function test_missing_condition_id_renders_normally(): void {
		$result = $this->renderer->filter_render_block(
			'<p>hello</p>',
			$this->block_with_rule( array( 'settings' => array( 'foo' => 'bar' ) ) )
		);

		$this->assertSame( '<p>hello</p>', $result );
	}

	/**
	 * `conditionId` for an unregistered condition → block renders unchanged.
	 */
	public function test_unregistered_condition_id_renders_normally(): void {
		$result = $this->renderer->filter_render_block(
			'<p>hello</p>',
			$this->block_with_rule(
				array(
					'conditionId' => 'fixture/missing',
					'settings'    => array(),
				)
			)
		);

		$this->assertSame( '<p>hello</p>', $result );
	}

	/**
	 * Condition evaluates true → block renders unchanged.
	 */
	public function test_condition_true_renders_block_content(): void {
		$this->registry->register( new Always_True_Condition() );

		$result = $this->renderer->filter_render_block(
			'<p>hello</p>',
			$this->block_with_rule(
				array(
					'conditionId' => Always_True_Condition::ID,
					'settings'    => array(),
				)
			)
		);

		$this->assertSame( '<p>hello</p>', $result );
	}

	/**
	 * Condition evaluates false → block is suppressed (empty string).
	 */
	public function test_condition_false_returns_empty_string(): void {
		$this->registry->register( new Always_False_Condition() );

		$result = $this->renderer->filter_render_block(
			'<p>hello</p>',
			$this->block_with_rule(
				array(
					'conditionId' => Always_False_Condition::ID,
					'settings'    => array(),
				)
			)
		);

		$this->assertSame( '', $result );
	}

	/**
	 * `renderwhen_render_block_hidden` fires with the block array and the
	 * matched condition object when a block is suppressed.
	 */
	public function test_hidden_action_fires_with_block_and_condition(): void {
		$condition = new Always_False_Condition();
		$this->registry->register( $condition );

		$received_block     = null;
		$received_condition = null;

		add_action(
			'renderwhen_render_block_hidden',
			static function ( $block, $matched ) use ( &$received_block, &$received_condition ): void {
				$received_block     = $block;
				$received_condition = $matched;
			},
			10,
			2
		);

		$block = $this->block_with_rule(
			array(
				'conditionId' => Always_False_Condition::ID,
				'settings'    => array(),
			)
		);

		$this->renderer->filter_render_block( '<p>hello</p>', $block );

		$this->assertSame( $block, $received_block );
		$this->assertSame( $condition, $received_condition );
	}

	/**
	 * The hidden action does NOT fire when the block is visible.
	 */
	public function test_hidden_action_does_not_fire_when_visible(): void {
		$this->registry->register( new Always_True_Condition() );

		$call_count = 0;
		add_action(
			'renderwhen_render_block_hidden',
			static function () use ( &$call_count ): void {
				++$call_count;
			}
		);

		$this->renderer->filter_render_block(
			'<p>hello</p>',
			$this->block_with_rule(
				array(
					'conditionId' => Always_True_Condition::ID,
					'settings'    => array(),
				)
			)
		);

		$this->assertSame( 0, $call_count );
	}

	/**
	 * Inside a Query Loop, the `$context` passed to `evaluate()` carries
	 * the looped post id (from `$instance->context['postId']`), not the
	 * outer page's post.
	 *
	 * The "outer page" is simulated by navigating the test request to a
	 * different post via `go_to()` — that's the global post the renderer
	 * would otherwise fall back to. The looped post id comes from the
	 * `WP_Block` instance's `context` property, exactly as WordPress
	 * populates it for a real Query Loop.
	 */
	public function test_query_loop_context_passes_looped_post_id(): void {
		$outer_id  = self::factory()->post->create();
		$looped_id = self::factory()->post->create();

		$this->go_to( get_permalink( $outer_id ) );

		$recorder = new Context_Recording_Condition();
		$this->registry->register( $recorder );

		$instance = new WP_Block(
			array( 'blockName' => self::CONTEXTUAL_BLOCK ),
			array( 'postId' => $looped_id )
		);

		$this->renderer->filter_render_block(
			'<p>hello</p>',
			$this->block_with_rule(
				array(
					'conditionId' => Context_Recording_Condition::ID,
					'settings'    => array(),
				)
			),
			$instance
		);

		$this->assertNotNull( $recorder->last_context );
		$this->assertSame( $looped_id, $recorder->last_context['post_id'] );
		$this->assertSame( $instance, $recorder->last_context['instance'] );
	}

	/**
	 * Outside a Query Loop, `post_id` falls back to the global post.
	 */
	public function test_context_falls_back_to_global_post_outside_query_loop(): void {
		$outer_id = self::factory()->post->create();
		$this->go_to( get_permalink( $outer_id ) );

		$recorder = new Context_Recording_Condition();
		$this->registry->register( $recorder );

		$this->renderer->filter_render_block(
			'<p>hello</p>',
			$this->block_with_rule(
				array(
					'conditionId' => Context_Recording_Condition::ID,
					'settings'    => array(),
				)
			)
		);

		$this->assertNotNull( $recorder->last_context );
		$this->assertSame( $outer_id, $recorder->last_context['post_id'] );
	}

	/**
	 * A condition that throws is treated as "always visible" — the block
	 * still renders, the page does not fatal, and `_doing_it_wrong()`
	 * fires under `WP_DEBUG`.
	 */
	public function test_exception_during_evaluate_is_swallowed_and_block_renders(): void {
		$this->setExpectedIncorrectUsage( 'RenderWhen\Block_Renderer::filter_render_block' );

		$this->registry->register( new Throwing_Condition() );

		$result = $this->renderer->filter_render_block(
			'<p>hello</p>',
			$this->block_with_rule(
				array(
					'conditionId' => Throwing_Condition::ID,
					'settings'    => array(),
				)
			)
		);

		$this->assertSame( '<p>hello</p>', $result );
	}

	/**
	 * The `renderwhen_evaluate_{$id}` filter can flip a true result to
	 * false, suppressing the block.
	 */
	public function test_evaluate_filter_can_flip_result_to_false(): void {
		$this->registry->register( new Always_True_Condition() );

		add_filter(
			'renderwhen_evaluate_' . Always_True_Condition::ID,
			static function (): bool {
				return false;
			}
		);

		$result = $this->renderer->filter_render_block(
			'<p>hello</p>',
			$this->block_with_rule(
				array(
					'conditionId' => Always_True_Condition::ID,
					'settings'    => array(),
				)
			)
		);

		remove_all_filters( 'renderwhen_evaluate_' . Always_True_Condition::ID );

		$this->assertSame( '', $result );
	}
}
