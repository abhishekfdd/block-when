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

// Composer autoloader (plugin classes + PHPUnit Polyfills).
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$plugin_root  = dirname( __DIR__, 2 );
$wp_tests_dir = $plugin_root . '/vendor/wp-phpunit/wp-phpunit';

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find WordPress test suite. Did you run `composer install`?" . PHP_EOL;
	exit( 1 );
}

// Tell wp-phpunit where to find our config (used by both the parent
// process AND the install.php child process).
defined( 'WP_TESTS_CONFIG_FILE_PATH' )
	|| define( 'WP_TESTS_CONFIG_FILE_PATH', $plugin_root . '/wp-tests-config.php' );

if ( ! file_exists( WP_TESTS_CONFIG_FILE_PATH ) ) {
	printf(
		'Missing wp-tests-config.php at "%s". See tests/README for setup.' . PHP_EOL,
		WP_TESTS_CONFIG_FILE_PATH
	);
	exit( 1 );
}

// PHPUnit Polyfills location, so wp-phpunit's install.php can find them
// in our local vendor directory rather than expecting the WP core layout.
defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' )
	|| define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $plugin_root . '/vendor/yoast/phpunit-polyfills' );

// Give us access to tests_add_filter() before WP boots.
require_once $wp_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin before WP finishes booting, so its hooks
 * register on the test environment's `plugins_loaded` action.
 */
tests_add_filter(
	'muplugins_loaded',
	static function () use ( $plugin_root ): void {
		require $plugin_root . '/block-when.php';
	}
);

// Start up the WP testing environment.
require $wp_tests_dir . '/includes/bootstrap.php';
