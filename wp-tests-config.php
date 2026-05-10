<?php
/**
 * WordPress test-suite configuration for Block When.
 *
 * Loaded by wp-phpunit's `bootstrap.php` AND by its `install.php` child
 * process. install.php is spawned via `system( WP_PHP_BINARY ... )` with
 * this file's path as argv[1], so constants defined in our PHPUnit
 * bootstrap do NOT cross the process boundary — every constant the test
 * suite needs has to live in this file.
 *
 * Defaults below match a Local by Flywheel install on this machine
 * (MySQL via Unix socket, root/root, database `wordpress_test`). All
 * settings are overridable via environment variables so CI and other
 * dev machines can run the same suite without editing this file:
 *
 *   WP_TESTS_DB_NAME      Database name           (default: wordpress_test)
 *   WP_TESTS_DB_USER      MySQL user              (default: root)
 *   WP_TESTS_DB_PASSWORD  MySQL password          (default: root)
 *   WP_TESTS_DB_HOST      `host[:port|:/socket]`  (default: Local socket)
 *   WP_TESTS_ABSPATH      WordPress core path     (default: ../../../)
 *   WP_TESTS_DOMAIN       Test domain             (default: example.org)
 *   WP_TESTS_EMAIL        Admin email             (default: admin@example.org)
 *   WP_TESTS_TITLE        Site title              (default: Block When Test Site)
 *
 * The test suite drops and rebuilds tables prefixed with `wptests_` on
 * every run, so it must point at a database OTHER than the live `local`
 * database — `wordpress_test` is the convention.
 *
 * @package Block_When
 */

declare( strict_types=1 );

// The WP test suite (and wp-phpunit's install.php child process) mandates
// these exact constant names and the $table_prefix variable. Renaming
// would defeat the file's only purpose, so we suppress the prefix and
// global-override sniffs file-wide.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
// phpcs:disable WordPress.WP.GlobalVariablesOverride

// Path to the WordPress install (the test suite loads wp-settings.php
// from here). Default targets the Local install three levels up from
// this plugin directory: block-when -> plugins -> wp-content -> public.
if ( ! defined( 'ABSPATH' ) ) {
	define(
		'ABSPATH',
		( getenv( 'WP_TESTS_ABSPATH' ) ?: dirname( __DIR__, 3 ) ) . '/'
	);
}

// Database connection. The default DB_HOST embeds the active Local MySQL
// socket on this machine. Override with an env var on CI / other machines.
define( 'DB_NAME',     getenv( 'WP_TESTS_DB_NAME' )     ?: 'wordpress_test' );
define( 'DB_USER',     getenv( 'WP_TESTS_DB_USER' )     ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASSWORD' ) ?: 'root' );
define(
	'DB_HOST',
	getenv( 'WP_TESTS_DB_HOST' )
		?: 'localhost:/Users/abhishekkumar/Library/Application Support/Local/run/sSgQ3HmFL/mysql/mysqld.sock'
);
define( 'DB_CHARSET',  'utf8' );
define( 'DB_COLLATE',  '' );

// Test-suite metadata.
define( 'WP_TESTS_DOMAIN', getenv( 'WP_TESTS_DOMAIN' ) ?: 'example.org' );
define( 'WP_TESTS_EMAIL',  getenv( 'WP_TESTS_EMAIL' )  ?: 'admin@example.org' );
define( 'WP_TESTS_TITLE',  getenv( 'WP_TESTS_TITLE' )  ?: 'Block When Test Site' );

// PHP binary used by wp-phpunit when it spawns install.php.
define( 'WP_PHP_BINARY', 'php' );

// Misc WP constants the test suite expects.
define( 'WP_DEBUG', true );
define( 'WP_DEFAULT_THEME', 'default' );

// Table prefix. Must NOT collide with the live Local site's prefix —
// `wptests_` is the WP core convention.
$table_prefix = 'wptests_';
