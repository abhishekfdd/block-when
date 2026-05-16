# RenderWhen for Blocks — Tests

## Running the suite

```sh
composer run phpunit
```

Verified working on PHP 8.4.12 (Homebrew) against Local by Flywheel's
MySQL 8.4 via Unix socket.

## How it's wired up

- `wp-tests-config.php` (repo root) holds every constant the WordPress
  test suite needs. wp-phpunit's bootstrap spawns `install.php` as a
  child PHP process, so configuration has to live in a file —
  constants set in the PHPUnit bootstrap don't survive the process
  boundary.
- `tests/phpunit/bootstrap.php` points wp-phpunit at the config file
  via `WP_TESTS_CONFIG_FILE_PATH` and at our Composer-installed
  PHPUnit Polyfills via `WP_TESTS_PHPUNIT_POLYFILLS_PATH`, then
  manually loads the plugin during `muplugins_loaded`.
- The `wordpress_test` MySQL database is used. Tables are prefixed
  `wptests_` so they cannot collide with the live `local` site.
- WordPress 6.5+ is loaded from the Local install at `app/public/`.

## Conventions

### Test filenames

Test files use **underscores**, not hyphens — `test_foo_bar.php`,
not `test-foo-bar.php`. PHPUnit's `TestSuiteLoader` requires the
loaded class's lowered shortname to end with the lowered filename;
PHP class names cannot contain hyphens, so the dash convention used
elsewhere in the codebase (`class-*.php`, `interface-*.php`) breaks
test discovery. `phpunit.xml.dist` matches via `prefix="test_"`.

### PHPUnit version

Pinned to `^9.6` in `composer.json`. wp-phpunit's `abstract-testcase.php`
calls `PHPUnit\Util\Test::parseTestMethodAnnotations()`, which was
removed in PHPUnit 10. Bump the pin once wp-phpunit ships a
PHPUnit 10 code path (track upstream at
https://github.com/wp-phpunit/wp-phpunit).

## Environment variable overrides

`wp-tests-config.php` reads these environment variables; defaults
match this developer's Local install. Override on CI / other machines:

| Variable                | Default                                                     |
| ----------------------- | ----------------------------------------------------------- |
| `WP_TESTS_DB_NAME`      | `wordpress_test`                                            |
| `WP_TESTS_DB_USER`      | `root`                                                      |
| `WP_TESTS_DB_PASSWORD`  | `root`                                                      |
| `WP_TESTS_DB_HOST`      | `localhost:<Local socket path>` (machine-specific)          |
| `WP_TESTS_ABSPATH`      | `<repo>/../../../` (i.e. Local's WordPress at `app/public`) |
| `WP_TESTS_DOMAIN`       | `example.org`                                               |
| `WP_TESTS_EMAIL`        | `admin@example.org`                                         |
| `WP_TESTS_TITLE`        | `RenderWhen Test Site`                                      |

CI example (TCP, no Local socket):

```sh
export WP_TESTS_DB_HOST=127.0.0.1
export WP_TESTS_ABSPATH=/var/www/wordpress
composer run phpunit
```

## First-time database setup

The `wordpress_test` database needs to exist before the suite runs.
On Local, create it via PHP (no `mysql` CLI required) — find the
active socket under `~/Library/Application Support/Local/run/`
(the most recently modified `mysqld.sock`):

```sh
php -r '
$sock = "/Users/abhishekkumar/Library/Application Support/Local/run/sSgQ3HmFL/mysql/mysqld.sock";
$m = new mysqli("localhost", "root", "root", "", 0, $sock);
$m->query("CREATE DATABASE IF NOT EXISTS wordpress_test");
echo "ok\n";
'
```

The Local socket UUID changes per install — replace `sSgQ3HmFL` with
your own.
