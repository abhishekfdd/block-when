<?php
/**
 * PHPUnit bootstrap for Block When.
 *
 * Wires up Composer autoload, points wp-phpunit at our wp-tests-config.php,
 * loads the plugin during the WP test bootstrap, and hands off to
 * wp-phpunit's own bootstrap to install/load WordPress.
 *
 * Why a config file (and not just constants here)? wp-phpunit spawns
 * `install.php` as a child PHP process and passes the config-file path
 * as argv[1] — constants defined in this bootstrap do not cross the
 * process boundary. See wp-tests-config.php for the constants the
 * suite needs.
 *
 * @package Block_When
 */

declare( strict_types=1 );

// `WP_TESTS_CONFIG_FILE_PATH` and `WP_TESTS_PHPUNIT_POLYFILLS_PATH` are
// names mandated by wp-phpunit; we cannot rename them.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound

// Composer autoloader (plugin classes + PHPUnit Polyfills).
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$block_when_plugin_root  = dirname( __DIR__, 2 );
$block_when_wp_tests_dir = $block_when_plugin_root . '/vendor/wp-phpunit/wp-phpunit';

if ( ! file_exists( $block_when_wp_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find WordPress test suite. Did you run `composer install`?" . PHP_EOL;
	exit( 1 );
}

// Tell wp-phpunit where to find our config (used by both the parent
// process AND the install.php child process).
defined( 'WP_TESTS_CONFIG_FILE_PATH' )
	|| define( 'WP_TESTS_CONFIG_FILE_PATH', $block_when_plugin_root . '/wp-tests-config.php' );

if ( ! file_exists( WP_TESTS_CONFIG_FILE_PATH ) ) {
	printf(
		'Missing wp-tests-config.php at "%s". See tests/README for setup.' . PHP_EOL,
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Constant from our own config, CLI bootstrap (no HTML), WP not yet loaded so esc_html() is unavailable.
		WP_TESTS_CONFIG_FILE_PATH
	);
	exit( 1 );
}

// PHPUnit Polyfills location, so wp-phpunit's install.php can find them
// in our local vendor directory rather than expecting the WP core layout.
defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' )
	|| define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $block_when_plugin_root . '/vendor/yoast/phpunit-polyfills' );

// Give us access to tests_add_filter() before WP boots.
require_once $block_when_wp_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin before WP finishes booting, so its hooks
 * register on the test environment's `plugins_loaded` action.
 */
tests_add_filter(
	'muplugins_loaded',
	static function () use ( $block_when_plugin_root ): void {
		require $block_when_plugin_root . '/block-when.php';
	}
);

// Start up the WP testing environment.
require $block_when_wp_tests_dir . '/includes/bootstrap.php';
