<?php
/**
 * Plugin Name:       Block When
 * Plugin URI:        https://github.com/abhishekfdd/block-when
 * Description:       Show or hide any block when conditions match. Three built-in conditions, an extensible developer API, and true server-side rendering — hidden blocks are never sent to the browser.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Abhishek
 * Author URI:        https://confusedblogger.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       block-when
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When;

defined( 'ABSPATH' ) || exit;

/*
 * This bootstrap file deliberately contains no business logic.
 *
 * Its only responsibilities are:
 *   1. Define plugin constants.
 *   2. Register a hand-written PSR-4 autoloader for Block_When\ classes,
 *      following the WPCS file-naming conventions used in includes/.
 *   3. Hand off to Block_When\Plugin::instance()->init().
 *
 * Reviewers and contributors looking for behaviour should start in
 * includes/class-plugin.php — that is the orchestrator.
 */

// Plugin constants.
define( 'BLOCK_WHEN_VERSION', '1.0.0' );
define( 'BLOCK_WHEN_FILE', __FILE__ );
define( 'BLOCK_WHEN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLOCK_WHEN_URL', plugin_dir_url( __FILE__ ) );
define( 'BLOCK_WHEN_BASENAME', plugin_basename( __FILE__ ) );
define( 'BLOCK_WHEN_MIN_PHP', '7.4' );
define( 'BLOCK_WHEN_MIN_WP', '6.5' );

// Bootstrap inside a closure to avoid leaking variables into the global scope.
( static function (): void {
	spl_autoload_register(
		static function ( string $class_name ): void {
			$prefix = 'Block_When\\';

			if ( 0 !== strpos( $class_name, $prefix ) ) {
				return;
			}

			$relative = substr( $class_name, strlen( $prefix ) );
			$segments = explode( '\\', $relative );
			$leaf     = array_pop( $segments );

			$directory = BLOCK_WHEN_DIR . 'includes/';
			if ( ! empty( $segments ) ) {
				$directory .= strtolower( str_replace( '_', '-', implode( '/', $segments ) ) ) . '/';
			}

			$leaf_slug = strtolower( str_replace( '_', '-', $leaf ) );

			// WPCS file-naming conventions used in this codebase:
			//   - Concrete class Foo_Bar    → class-foo-bar.php
			//   - Abstract class Abstract_X → abstract-x.php  (marker IS the prefix)
			//   - Interface    Interface_X  → interface-x.php (marker IS the prefix)
			// The Abstract_/Interface_ marker in the class name maps directly
			// onto the filename prefix — it does not double up.
			$candidates = array( 'class-' . $leaf_slug . '.php' );
			if ( 0 === strpos( $leaf_slug, 'abstract-' ) || 0 === strpos( $leaf_slug, 'interface-' ) ) {
				$candidates[] = $leaf_slug . '.php';
			}

			foreach ( $candidates as $filename ) {
				$candidate = $directory . $filename;
				if ( file_exists( $candidate ) ) {
					require_once $candidate;
					return;
				}
			}
		}
	);

	add_action(
		'plugins_loaded',
		static function (): void {
			Plugin::instance()->init();
		}
	);
} )();