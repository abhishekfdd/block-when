<?php
/**
 * Tests for {@see Block_When\Conditions_Registry}.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Tests;

use Block_When\Conditions\Abstract_Condition;
use Block_When\Conditions\Interface_Condition;
use Block_When\Conditions_Registry;
use ReflectionClass;
use WP_UnitTestCase;

defined( 'ABSPATH' ) || exit;

/**
 * Registry behaviour: registration, retrieval, action firing, type rejection.
 */
final class Test_Conditions_Registry extends WP_UnitTestCase {

	/**
	 * Reset the singleton before every test so each case starts with an
	 * empty registry. The `$instance` property is private static, so we
	 * reach in via reflection rather than adding a reset() seam to
	 * production code.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$instance = ( new ReflectionClass( Conditions_Registry::class ) )
			->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	/**
	 * Build a no-op condition with a configurable id, for use as a fixture.
	 *
	 * @param string $id Condition id.
	 * @return Interface_Condition
	 */
	private function make_condition( string $id ): Interface_Condition {
		return new class( $id ) extends Abstract_Condition {

			/**
			 * Configurable id.
			 *
			 * @var string
			 */
			private string $stub_id;

			/**
			 * Constructor.
			 *
			 * @param string $id Id to report from get_id().
			 */
			public function __construct( string $id ) {
				$this->stub_id = $id;
			}

			/**
			 * {@inheritDoc}
			 */
			public function get_id(): string {
				return $this->stub_id;
			}

			/**
			 * {@inheritDoc}
			 */
			public function get_label(): string {
				return 'Test';
			}

			/**
			 * {@inheritDoc}
			 */
			public function evaluate( array $settings, array $context ): bool {
				return true;
			}
		};
	}

	/**
	 * register() stores a condition under its own id.
	 */
	public function test_register_stores_condition_keyed_by_id(): void {
		$registry  = Conditions_Registry::instance();
		$condition = $this->make_condition( 'stub' );

		$registry->register( $condition );

		$this->assertSame( $condition, $registry->get( 'stub' ) );
	}

	/**
	 * get() returns null when no condition is registered under the id.
	 */
	public function test_get_returns_null_for_unknown_id(): void {
		$registry = Conditions_Registry::instance();

		$this->assertNull( $registry->get( 'nonexistent' ) );
	}

	/**
	 * get_all() returns every registered condition keyed by id.
	 */
	public function test_get_all_returns_every_registered_condition_keyed_by_id(): void {
		$registry = Conditions_Registry::instance();
		$first    = $this->make_condition( 'first' );
		$second   = $this->make_condition( 'second' );

		$registry->register( $first );
		$registry->register( $second );

		$all = $registry->get_all();

		$this->assertCount( 2, $all );
		$this->assertSame( $first, $all['first'] );
		$this->assertSame( $second, $all['second'] );
	}

	/**
	 * Re-registering an id replaces the prior condition (last-write-wins).
	 */
	public function test_register_overwrites_when_id_collides(): void {
		$registry = Conditions_Registry::instance();
		$original = $this->make_condition( 'collision' );
		$override = $this->make_condition( 'collision' );

		$registry->register( $original );
		$registry->register( $override );

		$this->assertSame( $override, $registry->get( 'collision' ) );
		$this->assertCount( 1, $registry->get_all() );
	}

	/**
	 * bootstrap() fires `block_when_register_conditions` with the registry.
	 */
	public function test_bootstrap_fires_action_with_registry_instance(): void {
		$registry = Conditions_Registry::instance();
		$received = null;

		add_action(
			'block_when_register_conditions',
			static function ( $passed ) use ( &$received ): void {
				$received = $passed;
			}
		);

		$registry->bootstrap();

		$this->assertSame( $registry, $received );
	}

	/**
	 * bootstrap() only fires the action once even if called repeatedly.
	 */
	public function test_bootstrap_is_idempotent(): void {
		$registry = Conditions_Registry::instance();
		$count    = 0;

		add_action(
			'block_when_register_conditions',
			static function () use ( &$count ): void {
				++$count;
			}
		);

		$registry->bootstrap();
		$registry->bootstrap();
		$registry->bootstrap();

		$this->assertSame( 1, $count );
	}

	/**
	 * register() rejects arguments that do not implement Interface_Condition.
	 *
	 * Enforcement is provided by PHP itself via the parameter type hint
	 * — a non-conforming argument raises a TypeError before the method
	 * body runs.
	 */
	public function test_register_rejects_non_interface_condition(): void {
		$registry = Conditions_Registry::instance();

		$this->expectException( \TypeError::class );

		// Intentionally passing the wrong type to verify type enforcement.
		$registry->register( new \stdClass() );
	}
}
