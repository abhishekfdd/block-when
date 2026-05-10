<?php
/**
 * Editor asset enqueuer.
 *
 * Loads the compiled editor JS (built by `@wordpress/scripts`) into the
 * block editor screen. Dependencies and version are read from the asset
 * manifest the build emits next to the bundle, so we never hand-roll
 * either: a missing manifest is a missing build and we fail loudly via
 * `require` rather than silently enqueueing a script with no deps.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the Block When editor script.
 */
final class Editor_Assets {

	/**
	 * Script handle the editor bundle is registered under.
	 *
	 * Exposed so tests (and any third-party JS that wants to depend on
	 * our bundle) can reference the same string we register with.
	 */
	public const SCRIPT_HANDLE = 'block-when-editor';

	/**
	 * Register hooks.
	 *
	 * Idempotent: re-calling does not double-register the action, mirroring
	 * {@see Attribute_Extender::register_hooks()} so a second
	 * `Plugin::init()` call cannot make the script enqueue twice.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( false !== has_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) ) ) {
			return;
		}

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue the editor script.
	 *
	 * Reads `build/index.asset.php` for the dependency list and version
	 * hash that `@wordpress/scripts` emits at build time. We `require` the
	 * file (not `include`) on purpose: a missing manifest means the plugin
	 * was deployed without a build, and silently enqueueing the script with
	 * an empty dependency list would surface as confusing runtime errors in
	 * the editor instead of a clear failure here.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		$asset_file = BLOCK_WHEN_DIR . 'build/index.asset.php';

		$asset = require $asset_file;

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			BLOCK_WHEN_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( self::SCRIPT_HANDLE, 'block-when' );
	}
}
