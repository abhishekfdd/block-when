<?php
/**
 * Tests for {@see RenderWhen\Plugin}.
 *
 * Scope is intentionally narrow: this class only verifies orchestration
 * and dogfooding of the public registration action. The detail behaviour
 * of each wired component is covered by its own dedicated test class.
 *
 * @package RenderWhen
 */

declare( strict_types=1 );

namespace RenderWhen\Tests;

use RenderWhen\Conditions\Abstract_Condition;
use RenderWhen\Conditions\Interface_Condition;
use RenderWhen\Conditions_Registry;
use RenderWhen\Plugin;
use ReflectionClass;
use WP_UnitTestCase;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin orchestrator behaviour: singleton identity, built-in registration,
 * idempotent init(), and the public extension action.
 */
final class Test_Plugin extends WP_UnitTestCase {

	/**
	 * Reset both the Plugin and Conditions_Registry singletons and clear
	 * any callbacks on `renderwhen_register_conditions` before each case.
	 *
	 * The plugin bootstrap fires `init()` once on `plugins_loaded`, so by
	 * the time a test runs both singletons already hold post-init state
	 * and the registry has the built-ins. Without this reset, individual
	 * tests cannot exercise a fresh boot sequence.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->reset_plugin_singleton();
		$this->reset_registry_singleton();

		remove_all_actions( 'renderwhen_register_conditions' );
	}

	/**
	 * Drop any callbacks our `init()` added to the extension action so
	 * they cannot leak into other test classes.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_all_actions( 'renderwhen_register_conditions' );
		parent::tear_down();
	}

	/**
	 * Reset Plugin's `$instance` and `$initialized` static state.
	 *
	 * @return void
	 */
	private function reset_plugin_singleton(): void {
		$class = new ReflectionClass( Plugin::class );

		$instance = $class->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		$initialized = $class->getProperty( 'initialized' );
		$initialized->setAccessible( true );
		$initialized->setValue( null, false );
	}

	/**
	 * Reset Conditions_Registry's `$instance` static state.
	 *
	 * @return void
	 */
	private function reset_registry_singleton(): void {
		$instance = ( new ReflectionClass( Conditions_Registry::class ) )
			->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	/**
	 * Build a minimal stub condition with a configurable id for the
	 * dogfooding test.
	 *
	 * @param string $id Condition id to report.
	 * @return Interface_Condition
	 */
	private function make_stub_condition( string $id ): Interface_Condition {
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
				return 'Stub';
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
	 * instance() returns the same object on every call.
	 */
	public function test_instance_returns_singleton(): void {
		$this->assertSame( Plugin::instance(), Plugin::instance() );
	}

	/**
	 * After init() the registry has the three built-in conditions.
	 */
	public function test_init_registers_three_built_in_conditions(): void {
		Plugin::instance()->init();

		$registry = Conditions_Registry::instance();

		$this->assertNotNull( $registry->get( 'user_state' ) );
		$this->assertNotNull( $registry->get( 'date_range' ) );
		$this->assertNotNull( $registry->get( 'device' ) );
	}

	/**
	 * Every built-in condition id is snake_case (`[a-z][a-z0-9_]*`).
	 *
	 * Regression: an early build shipped with `user-state` (kebab) next to
	 * `date_range` (snake), and the inconsistency silently broke saved post
	 * markup whose `conditionId` used the snake form — the renderer's
	 * unknown-id case kicked in and the block rendered as if no rule were
	 * set. This test pins every built-in to the documented convention so
	 * the inconsistency cannot creep back in.
	 */
	public function test_built_in_condition_ids_use_snake_case(): void {
		Plugin::instance()->init();

		$registry = Conditions_Registry::instance();

		foreach ( array( 'user_state', 'date_range', 'device' ) as $expected_id ) {
			$this->assertNotNull(
				$registry->get( $expected_id ),
				sprintf( 'Built-in condition "%s" must be registered under its snake_case id.', $expected_id )
			);

			$this->assertMatchesRegularExpression(
				'/^[a-z][a-z0-9_]*$/',
				$registry->get( $expected_id )->get_id(),
				sprintf( 'Built-in condition id "%s" must match the snake_case convention.', $expected_id )
			);
		}
	}

	/**
	 * Calling init() twice does not re-run registration — the registry
	 * still holds exactly the three built-ins.
	 */
	public function test_init_is_idempotent(): void {
		Plugin::instance()->init();
		Plugin::instance()->init();

		$this->assertCount( 3, Conditions_Registry::instance()->get_all() );
	}

	/**
	 * Third-party callbacks on `renderwhen_register_conditions` receive
	 * the live registry and may register conditions that survive boot —
	 * the same path the built-ins themselves go through.
	 */
	public function test_renderwhen_register_conditions_action_fires(): void {
		$received = null;
		$custom   = $this->make_stub_condition( 'custom-from-test' );

		add_action(
			'renderwhen_register_conditions',
			static function ( $registry ) use ( &$received, $custom ): void {
				$received = $registry;
				$registry->register( $custom );
			}
		);

		Plugin::instance()->init();

		$registry = Conditions_Registry::instance();

		$this->assertSame( $registry, $received );
		$this->assertSame( $custom, $registry->get( 'custom-from-test' ) );
	}
}
