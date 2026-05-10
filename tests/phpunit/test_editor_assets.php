<?php
/**
 * Tests for {@see Block_When\Editor_Assets}.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Tests;

use Block_When\Conditions_Registry;
use Block_When\Editor_Assets;
use Block_When\Plugin;
use ReflectionClass;
use WP_UnitTestCase;

defined( 'ABSPATH' ) || exit;

/**
 * Editor asset wiring: hook registration, idempotency, script enqueue
 * shape, and Plugin orchestration.
 */
final class Test_Editor_Assets extends WP_UnitTestCase {

	/**
	 * Asset under test.
	 *
	 * @var Editor_Assets
	 */
	private Editor_Assets $assets;

	/**
	 * Fresh instance per test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->assets = new Editor_Assets();
	}

	/**
	 * Drop both the action we registered and the script handle we may
	 * have enqueued, so state cannot leak across cases.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_all_actions( 'enqueue_block_editor_assets' );

		if ( wp_script_is( Editor_Assets::SCRIPT_HANDLE, 'registered' ) ) {
			wp_deregister_script( Editor_Assets::SCRIPT_HANDLE );
		}
		if ( wp_script_is( Editor_Assets::SCRIPT_HANDLE, 'enqueued' ) ) {
			wp_dequeue_script( Editor_Assets::SCRIPT_HANDLE );
		}

		parent::tear_down();
	}

	/**
	 * Reset Plugin's `$instance` and `$initialized` static state so a
	 * test can drive `init()` from a clean slate. Mirrors the helper used
	 * in {@see Test_Plugin}.
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
	 * Reset Conditions_Registry's `$instance` static state. Plugin::init()
	 * boots the registry, so a clean Plugin requires a clean registry too.
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
	 * register_hooks() attaches the enqueue callback at priority 10.
	 */
	public function test_register_hooks_attaches_action_at_priority_10(): void {
		$this->assets->register_hooks();

		$this->assertSame(
			10,
			has_action(
				'enqueue_block_editor_assets',
				array( $this->assets, 'enqueue' )
			)
		);
	}

	/**
	 * register_hooks() is idempotent — re-calling does not register a
	 * second copy of the same callback. Mirrors the contract documented
	 * on {@see Attribute_Extender::register_hooks()}.
	 */
	public function test_register_hooks_is_idempotent(): void {
		$this->assets->register_hooks();
		$this->assets->register_hooks();
		$this->assets->register_hooks();

		$callback   = array( $this->assets, 'enqueue' );
		$registered = $GLOBALS['wp_filter']['enqueue_block_editor_assets']->callbacks[10] ?? array();

		$matching = 0;
		foreach ( $registered as $entry ) {
			if ( $entry['function'] === $callback ) {
				++$matching;
			}
		}

		$this->assertSame( 1, $matching );
	}

	/**
	 * enqueue() registers our editor script with the dependency list and
	 * version hash that `@wordpress/scripts` writes to the asset manifest.
	 *
	 * The real `build/index.asset.php` is read here on purpose: the file
	 * is committed (it's required for SVN deploys), so a fixture would
	 * just duplicate state that already exists in the repo. If the build
	 * artefact ever drifts, this test catches it.
	 */
	public function test_enqueue_registers_script_from_asset_manifest(): void {
		$asset = require dirname( __DIR__, 2 ) . '/build/index.asset.php';

		$this->assets->enqueue();

		$this->assertTrue( wp_script_is( Editor_Assets::SCRIPT_HANDLE, 'enqueued' ) );

		$registered = wp_scripts()->registered[ Editor_Assets::SCRIPT_HANDLE ] ?? null;

		$this->assertNotNull( $registered );
		$this->assertSame( $asset['version'], $registered->ver );
		$this->assertSame( $asset['dependencies'], $registered->deps );
		$this->assertStringEndsWith( 'build/index.js', $registered->src );
	}

	/**
	 * Plugin::init() instantiates Editor_Assets and registers its hooks,
	 * proving the orchestrator wiring matches the rest of the plugin's
	 * subsystems.
	 */
	public function test_plugin_init_registers_editor_assets(): void {
		$this->reset_plugin_singleton();
		$this->reset_registry_singleton();
		remove_all_actions( 'enqueue_block_editor_assets' );

		Plugin::instance()->init();

		$this->assertNotFalse(
			has_action( 'enqueue_block_editor_assets' ),
			'Plugin::init() must wire Editor_Assets into enqueue_block_editor_assets.'
		);
	}
}
