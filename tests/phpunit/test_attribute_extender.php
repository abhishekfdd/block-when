<?php
/**
 * Tests for {@see Block_When\Attribute_Extender}.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Tests;

use Block_When\Attribute_Extender;
use WP_Block_Type_Registry;
use WP_UnitTestCase;

defined( 'ABSPATH' ) || exit;

/**
 * Extender behaviour: schema injection, attribute preservation,
 * deterministic overwrite, idempotent hook registration, and reach
 * to third-party blocks registered after our hook is attached.
 */
final class Test_Attribute_Extender extends WP_UnitTestCase {

	/**
	 * Block name used by tests that drive the real
	 * `register_block_type()` API.
	 */
	private const TEST_BLOCK = 'block-when-test/extender';

	/**
	 * Extender under test.
	 *
	 * @var Attribute_Extender
	 */
	private Attribute_Extender $extender;

	/**
	 * Fresh extender per test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->extender = new Attribute_Extender();
	}

	/**
	 * Drop test-only state between cases — both any filter we registered
	 * and any block we registered through the real WP API.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_all_filters( 'register_block_type_args' );

		if ( WP_Block_Type_Registry::get_instance()->is_registered( self::TEST_BLOCK ) ) {
			unregister_block_type( self::TEST_BLOCK );
		}

		parent::tear_down();
	}

	/**
	 * The schema this extender is expected to inject.
	 *
	 * Defined once and asserted against by several tests so a future
	 * schema tweak only changes one place.
	 *
	 * @return array<string, mixed>
	 */
	private function expected_block_when_schema(): array {
		return array(
			'type'       => 'object',
			'default'    => null,
			'properties' => array(
				'conditionId' => array(
					'type' => 'string',
				),
				'settings'    => array(
					'type' => 'object',
				),
			),
		);
	}

	/**
	 * Block args without an `attributes` key gain one containing only `blockWhen`.
	 */
	public function test_filter_creates_attributes_array_when_absent(): void {
		$args = array( 'render_callback' => '__return_empty_string' );

		$filtered = $this->extender->filter_register_block_type_args( $args, 'core/test' );

		$this->assertArrayHasKey( 'attributes', $filtered );
		$this->assertSame(
			array( 'blockWhen' => $this->expected_block_when_schema() ),
			$filtered['attributes']
		);
		// Non-attributes keys are passed through unchanged.
		$this->assertSame( '__return_empty_string', $filtered['render_callback'] );
	}

	/**
	 * Existing attributes are preserved and `blockWhen` is added alongside.
	 */
	public function test_filter_preserves_existing_attributes(): void {
		$args = array(
			'attributes' => array(
				'content' => array( 'type' => 'string' ),
				'level'   => array(
					'type'    => 'integer',
					'default' => 2,
				),
			),
		);

		$filtered = $this->extender->filter_register_block_type_args( $args, 'core/test' );

		$this->assertSame(
			array( 'type' => 'string' ),
			$filtered['attributes']['content']
		);
		$this->assertSame(
			array(
				'type'    => 'integer',
				'default' => 2,
			),
			$filtered['attributes']['level']
		);
		$this->assertSame(
			$this->expected_block_when_schema(),
			$filtered['attributes']['blockWhen']
		);
	}

	/**
	 * A pre-existing `blockWhen` attribute is replaced — last-write-wins.
	 *
	 * Documented as a deliberate choice in Attribute_Extender: the
	 * renderer reads the attribute by name and a foreign shape would
	 * silently break visibility rules, so a deterministic overwrite
	 * is safer than a merge.
	 */
	public function test_filter_overwrites_pre_existing_block_when(): void {
		$args = array(
			'attributes' => array(
				'blockWhen' => array(
					'type'    => 'string',
					'default' => 'foreign-value',
				),
			),
		);

		$filtered = $this->extender->filter_register_block_type_args( $args, 'core/test' );

		$this->assertSame(
			$this->expected_block_when_schema(),
			$filtered['attributes']['blockWhen']
		);
	}

	/**
	 * register_hooks() attaches the filter at priority 10.
	 */
	public function test_register_hooks_attaches_filter_at_priority_10(): void {
		$this->extender->register_hooks();

		$this->assertSame(
			10,
			has_filter(
				'register_block_type_args',
				array( $this->extender, 'filter_register_block_type_args' )
			)
		);
	}

	/**
	 * register_hooks() is idempotent — re-calling does not register a
	 * second copy of the same callback. That keeps a doubled
	 * `Plugin::init()` from doubling the filter cost on every block type.
	 */
	public function test_register_hooks_is_idempotent(): void {
		$this->extender->register_hooks();
		$this->extender->register_hooks();
		$this->extender->register_hooks();

		$callback   = array( $this->extender, 'filter_register_block_type_args' );
		$registered = $GLOBALS['wp_filter']['register_block_type_args']->callbacks[10] ?? array();

		$matching = 0;
		foreach ( $registered as $entry ) {
			if ( $entry['function'] === $callback ) {
				++$matching;
			}
		}

		$this->assertSame( 1, $matching );
	}

	/**
	 * The filter applies to a block registered AFTER hooks are attached,
	 * which is the realistic case for third-party blocks loading later
	 * than our `plugins_loaded`-time hook registration.
	 */
	public function test_filter_reaches_blocks_registered_after_hook(): void {
		$this->extender->register_hooks();

		register_block_type(
			self::TEST_BLOCK,
			array(
				'attributes' => array(
					'caption' => array( 'type' => 'string' ),
				),
			)
		);

		$registered = WP_Block_Type_Registry::get_instance()->get_registered( self::TEST_BLOCK );

		$this->assertNotNull( $registered );
		$this->assertSame(
			array( 'type' => 'string' ),
			$registered->attributes['caption']
		);
		$this->assertSame(
			$this->expected_block_when_schema(),
			$registered->attributes['blockWhen']
		);
	}
}
