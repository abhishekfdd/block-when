# Block When

> Show or hide any block when conditions match. Developer-first API and true
> server-side rendering — hidden blocks never reach the browser.

[![Lint](https://github.com/abhishekfdd/block-when/actions/workflows/lint.yml/badge.svg)](https://github.com/abhishekfdd/block-when/actions/workflows/lint.yml)
[![Tests](https://github.com/abhishekfdd/block-when/actions/workflows/test.yml/badge.svg)](https://github.com/abhishekfdd/block-when/actions/workflows/test.yml)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](LICENSE)

Block When adds conditional visibility to every block in the WordPress block
editor. v1.0 ships three built-in conditions — user state, date range, and
device type — registered through a public API your plugin or theme can use
to add your own.

For end-user docs and screenshots, see [`readme.txt`](readme.txt). For the
full architecture and design decisions, see [`PLAN.md`](PLAN.md).

## Why another visibility plugin?

Two things set Block When apart from existing options:

1. **Server-side rendering only.** Hidden blocks return `''` from
   `render_block` and are never sent to the browser. No CSS `display: none`,
   no DOM bloat, no leaked content in view-source.
2. **The Conditions API is public, not internal.** The three built-in
   conditions are registered through the same registry your code uses. The
   extension surface *is* how the plugin works.

If you need URL parameters, cookies, geolocation, ACF, WooCommerce, or
membership integrations today, [Conditional Blocks][cb] and
[Block Visibility][bv] cover that ground well.

[cb]: https://wordpress.org/plugins/conditional-blocks/
[bv]: https://wordpress.org/plugins/block-visibility/

## Public API

Register a custom condition:

```php
add_action( 'block_when_register_conditions', function ( $registry ) {
    $registry->register( new My_Plugin\Conditions\Country_Condition() );
} );
```

Implement `Block_When\Conditions\Interface_Condition`:

```php
class Country_Condition implements Interface_Condition {
    public function get_id(): string    { return 'country'; }
    public function get_label(): string { return __( 'Visitor country', 'my-plugin' ); }
    public function get_schema(): array { /* JSON Schema */ }

    public function evaluate( array $settings, array $context ): bool {
        return in_array( my_detect_country(), $settings['countries'] ?? [], true );
    }
}
```

Filters: `block_when_register_conditions`,
`block_when_evaluate_{condition_id}`, `block_when_render_block_hidden`,
`block_when_device_type`. These are part of the public contract — semver
applies.

## Development

Requires Node 20+, PHP 7.4+, Composer 2.x, and [Local](https://localwp.com/).

```bash
git clone https://github.com/abhishekfdd/block-when.git
cd block-when
npm install
composer install
npm run build
```

Symlink into your Local site:

```bash
# macOS / Linux
ln -s "$(pwd)" "/path/to/Local Sites/<site>/app/public/wp-content/plugins/block-when"
```

```powershell
# Windows — run PowerShell as Administrator
New-Item -ItemType SymbolicLink `
         -Path   "C:\Users\you\Local Sites\<site>\app\public\wp-content\plugins\block-when" `
         -Target "C:\path\to\block-when"
```

Activate in **wp-admin → Plugins**. Right-click the site in Local →
**Open site shell** for a terminal with `wp` on PATH. Add to
`app/public/wp-config.php` for debugging:

```php
define( 'WP_DEBUG',         true );
define( 'WP_DEBUG_LOG',     true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG',     true );
```

### Commands

```bash
npm run start              # Watch mode
npm run build              # Production build
npm run lint:js
npm run test:e2e           # Playwright

composer run phpcs         # WordPress-VIP-Go ruleset
composer run phpcbf        # Auto-fix
```

PHPUnit runs in CI on every push — see
[`.github/workflows/test.yml`](.github/workflows/test.yml). The matrix
covers PHP 7.4 through 8.3.

### Standards

WordPress-VIP-Go for PHP, `@wordpress/eslint-plugin/recommended` for JS.
Zero warnings on either before pushing. All user-facing strings
internationalized with text domain `block-when`. Output late-escaped at
the point of output.

## Contributing

Read [`PLAN.md`](PLAN.md) first — several features are deliberately out
of scope for v1.0. For larger changes, open an issue before a PR.

## License

GPL-2.0-or-later. See [`LICENSE`](LICENSE).