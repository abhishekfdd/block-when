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
 * Domain Path:       /languages
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
 *   2. Load the autoloader.
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

// Composer autoloader. Generated via `composer dump-autoload -o`.
$autoloader = BLOCK_WHEN_DIR . 'vendor/autoload.php';

if ( ! file_exists( $autoloader ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__(
					'Block When: Composer dependencies are missing. Run `composer install` in the plugin directory.',
					'block-when'
				)
			);
		}
	);
	return;
}

require_once $autoloader;

// Boot.
add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::instance()->init();
	}
);