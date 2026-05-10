<?php
/**
 * Tests for {@see Block_When\Conditions\Device_Condition}.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Tests\Conditions;

use Block_When\Conditions\Device_Condition;
use ReflectionClass;
use WP_UnitTestCase;

defined( 'ABSPATH' ) || exit;

/**
 * Device-type condition: desktop / tablet / mobile classification,
 * per-request caching, and the `block_when_device_type` filter override.
 */
final class Test_Device_Condition extends WP_UnitTestCase {

	/**
	 * The condition under test.
	 *
	 * @var Device_Condition
	 */
	private Device_Condition $condition;

	/**
	 * Fresh condition + cleared static cache per test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->condition = new Device_Condition();
		$this->reset_device_cache();
	}

	/**
	 * Clear cache + filters between tests so state doesn't leak.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$this->reset_device_cache();
		remove_all_filters( 'block_when_device_type' );
		parent::tear_down();
	}

	/**
	 * Reset the static cache via reflection so each test starts fresh.
	 * Reflection is used in preference to a public reset_cache() seam
	 * — production code shouldn't carry test-only methods.
	 *
	 * @return void
	 */
	private function reset_device_cache(): void {
		$prop = ( new ReflectionClass( Device_Condition::class ) )
			->getProperty( 'cached_device' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );
	}

	/**
	 * Pin the cached detection result to a specific value, bypassing
	 * the real UA-sniffing path. Use this when the test cares about
	 * the result, not the route to it.
	 *
	 * @param string $type One of 'desktop', 'tablet', 'mobile'.
	 * @return void
	 */
	private function force_device( string $type ): void {
		$prop = ( new ReflectionClass( Device_Condition::class ) )
			->getProperty( 'cached_device' );
		$prop->setAccessible( true );
		$prop->setValue( null, $type );
	}

	/**
	 * Identity surface: id is the locked-in identifier.
	 */
	public function test_get_id_is_stable(): void {
		$this->assertSame( 'device', $this->condition->get_id() );
	}

	/**
	 * Identity surface: label is a non-empty translated string.
	 */
	public function test_get_label_is_non_empty_string(): void {
		$label = $this->condition->get_label();

		$this->assertIsString( $label );
		$this->assertNotSame( '', $label );
	}

	/**
	 * Schema declares a string-array of devices with the canonical enum.
	 */
	public function test_schema_declares_devices_array_with_enum(): void {
		$schema = $this->condition->get_schema();

		$this->assertArrayHasKey( 'devices', $schema );
		$this->assertSame( 'array', $schema['devices']['type'] );
		$this->assertSame( 'string', $schema['devices']['items']['type'] );
		$this->assertSame(
			array( 'desktop', 'tablet', 'mobile' ),
			$schema['devices']['items']['enum']
		);
		$this->assertSame( array(), $schema['devices']['default'] );
	}

	/**
	 * Desktop visitor + desktop-only setting → visible.
	 */
	public function test_desktop_matched_when_devices_include_desktop(): void {
		$this->force_device( 'desktop' );

		$this->assertTrue(
			$this->condition->evaluate( array( 'devices' => array( 'desktop' ) ), array() )
		);
	}

	/**
	 * Desktop visitor + non-desktop setting → hidden.
	 */
	public function test_desktop_unmatched_when_devices_exclude_desktop(): void {
		$this->force_device( 'desktop' );

		$this->assertFalse(
			$this->condition->evaluate( array( 'devices' => array( 'mobile', 'tablet' ) ), array() )
		);
	}

	/**
	 * Tablet visitor + tablet-allowed setting → visible.
	 */
	public function test_tablet_matched_when_devices_include_tablet(): void {
		$this->force_device( 'tablet' );

		$this->assertTrue(
			$this->condition->evaluate( array( 'devices' => array( 'tablet' ) ), array() )
		);
	}

	/**
	 * Tablet visitor + non-tablet setting → hidden.
	 */
	public function test_tablet_unmatched_when_devices_exclude_tablet(): void {
		$this->force_device( 'tablet' );

		$this->assertFalse(
			$this->condition->evaluate( array( 'devices' => array( 'desktop', 'mobile' ) ), array() )
		);
	}

	/**
	 * Mobile visitor + mobile-allowed setting → visible.
	 */
	public function test_mobile_matched_when_devices_include_mobile(): void {
		$this->force_device( 'mobile' );

		$this->assertTrue(
			$this->condition->evaluate( array( 'devices' => array( 'mobile' ) ), array() )
		);
	}

	/**
	 * Mobile visitor + non-mobile setting → hidden.
	 */
	public function test_mobile_unmatched_when_devices_exclude_mobile(): void {
		$this->force_device( 'mobile' );

		$this->assertFalse(
			$this->condition->evaluate( array( 'devices' => array( 'desktop' ) ), array() )
		);
	}

	/**
	 * Empty devices array → no constraint → always visible.
	 */
	public function test_empty_devices_resolves_to_visible(): void {
		$this->force_device( 'desktop' );

		$this->assertTrue(
			$this->condition->evaluate( array( 'devices' => array() ), array() )
		);
	}

	/**
	 * Missing devices key (no settings at all) → always visible.
	 */
	public function test_missing_devices_key_resolves_to_visible(): void {
		$this->force_device( 'desktop' );

		$this->assertTrue( $this->condition->evaluate( array(), array() ) );
	}

	/**
	 * The `block_when_device_type` filter overrides whatever UA detection
	 * produced — registering a callback that returns 'tablet' must let
	 * a tablet-only block evaluate true.
	 */
	public function test_filter_override_drives_evaluation(): void {
		// Don't pre-pin — exercise the real detection path so the
		// filter has something to override.
		$this->reset_device_cache();

		add_filter(
			'block_when_device_type',
			static function (): string {
				return 'tablet';
			}
		);

		$this->assertTrue(
			$this->condition->evaluate( array( 'devices' => array( 'tablet' ) ), array() )
		);
	}

	/**
	 * The static cache means the detection filter fires at most once
	 * per request, no matter how many blocks call evaluate().
	 */
	public function test_detection_cached_across_evaluations(): void {
		$this->reset_device_cache();

		$call_count = 0;
		add_filter(
			'block_when_device_type',
			static function () use ( &$call_count ): string {
				++$call_count;
				return 'mobile';
			}
		);

		$this->condition->evaluate( array( 'devices' => array( 'mobile' ) ), array() );
		$this->condition->evaluate( array( 'devices' => array( 'mobile' ) ), array() );
		$this->condition->evaluate( array( 'devices' => array( 'mobile' ) ), array() );

		$this->assertSame( 1, $call_count );
	}

	/**
	 * Unknown / malformed device strings in settings are silently
	 * dropped — they never match, but they also don't poison valid
	 * neighbours in the same array.
	 */
	public function test_unknown_device_strings_silently_ignored(): void {
		$this->force_device( 'desktop' );

		// Unknown alongside a valid match → still visible.
		$this->assertTrue(
			$this->condition->evaluate(
				array( 'devices' => array( 'banana', 'desktop' ) ),
				array()
			)
		);

		// Unknown alongside a valid non-match → hidden (the valid entry decides).
		$this->assertFalse(
			$this->condition->evaluate(
				array( 'devices' => array( 'banana', 'mobile' ) ),
				array()
			)
		);

		// All entries unknown → no effective constraint → visible.
		$this->assertTrue(
			$this->condition->evaluate(
				array( 'devices' => array( 'banana', 'apple' ) ),
				array()
			)
		);
	}
}
