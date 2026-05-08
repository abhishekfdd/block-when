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

For end-user docs, screenshots, and the WordPress.org listing copy, see
[`readme.txt`](readme.txt). This file is for developers, contributors, and
anyone reviewing the architecture.

## Why another visibility plugin?

Two things set Block When apart from the existing options on WordPress.org:

1. **Server-side rendering only.** Hidden blocks return `''` from
   `render_block` and are never sent to the browser. No CSS `display: none`,
   no DOM bloat, no leaked content in view-source. Page weight, SEO, and
   accessibility all benefit.

2. **The Conditions API is public, not internal.** The three built-in
   conditions are registered through the same `Conditions_Registry` your
   code uses. There is no separate "extension surface" bolted on after the
   fact — the surface *is* how the plugin works.

If you need URL parameters, cookies, geolocation, ACF, WooCommerce, or
membership integrations today, two excellent mature plugins cover that
ground: [Conditional Blocks][cb] and [Block Visibility][bv]. Block When is
the smaller, sharper alternative for developers who want a clean foundation
to build on.

[cb]: https://wordpress.org/plugins/conditional-blocks/
[bv]: https://wordpress.org/plugins/block-visibility/

## Architecture overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    Block_When\Plugin                            │
│  Singleton orchestrator. Wires everything up on plugins_loaded. │
└────┬─────────────────────┬──────────────────────────┬───────────┘
     │                     │                          │
     ▼                     ▼                          ▼
┌────────────┐   ┌────────────────────┐   ┌──────────────────────┐
│ Conditions │   │ Attribute_Extender │   │   Block_Renderer     │
│ Registry   │   │                    │   │                      │
│            │   │ Hooks              │   │ Hooks render_block.  │
│ Holds all  │   │ register_block_    │   │ Reads attribute,     │
│ Condition  │   │ type_args. Adds    │   │ asks registry to     │
│ instances. │   │ blockWhen attr to  │   │ evaluate, returns    │
│            │   │ every block.       │   │ '' if hidden.        │
└─────┬──────┘   └────────────────────┘   └──────────────────────┘
      │
      ▼
┌──────────────────────────────────────────────────────────────────┐
│ Conditions implement Block_When\Conditions\Interface_Condition   │
│                                                                  │
│  • User_State_Condition   (built-in)                             │
│  • Date_Range_Condition   (built-in)                             │
│  • Device_Condition       (built-in)                             │
│  • <your plugin's conditions go here, registered via the         │
│     block_when_register_conditions action>                       │
└──────────────────────────────────────────────────────────────────┘
```

The editor JS mirrors this structure: a JS-side registry, three built-in
condition modules, and an `editor.BlockEdit` higher-order component that
adds the InspectorControls panel.

See [`PLAN.md`](PLAN.md) for the full design doc, scope decisions, and
naming conventions.

## Public API

### Registering a custom condition

```php
add_action( 'block_when_register_conditions', function ( $registry ) {
    $registry->register( new My_Plugin\Conditions\Country_Condition() );
} );
```

Your condition class implements
`Block_When\Conditions\Interface_Condition`:

```php
namespace My_Plugin\Conditions;

use Block_When\Conditions\Interface_Condition;

class Country_Condition implements Interface_Condition {

    public function get_id(): string {
        return 'country';
    }

    public function get_label(): string {
        return __( 'Visitor country', 'my-plugin' );
    }

    public function get_schema(): array {
        return array(
            'type'       => 'object',
            'properties' => array(
                'countries' => array(
                    'type'  => 'array',
                    'items' => array( 'type' => 'string' ),
                ),
            ),
        );
    }

    public function evaluate( array $settings, array $context ): bool {
        $current = my_plugin_detect_country();
        return in_array( $current, $settings['countries'] ?? array(), true );
    }
}
```

You will also want to register a corresponding JS module so the condition
appears in the editor UI. See `src/conditions/` for examples.

### Filters

| Filter                                      | Purpose                                                     |
| ------------------------------------------- | ----------------------------------------------------------- |
| `block_when_register_conditions`            | Register your conditions on the registry instance.          |
| `block_when_evaluate_{condition_id}`        | Last-mile override of a specific condition's boolean result. |
| `block_when_render_block_hidden`            | Fires when a block is hidden. For cache busters and SEO tools. |
| `block_when_device_type`                    | Override the detected device type string.                    |

These filters are part of the public contract. Breaking changes will be
reflected in the major version per semver.

## Development

### Requirements

- Node.js 20+
- PHP 7.4+
- Composer 2.x
- A local WordPress environment — [Local](https://localwp.com/) or
  [`wp-env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)
  both work. Pick whichever you prefer.

### Clone and install dependencies

```bash
git clone https://github.com/abhishekfdd/block-when.git
cd block-when

npm install
composer install
npm run build       # Build the editor JS into build/
```

### Option A — Local by Flywheel (recommended for newcomers)

1. Create a new site in Local. Default settings are fine; PHP 8.x, MySQL,
   nginx or Apache.
2. Right-click the site → **Go to site folder**, then navigate to
   `app/public/wp-content/plugins/`.
3. Symlink the repo into that folder so edits in VS Code land instantly:

   ```bash
   # macOS / Linux
   ln -s "/absolute/path/to/block-when" \
         "/path/to/Local Sites/<site>/app/public/wp-content/plugins/block-when"
   ```

   ```powershell
   # Windows — run PowerShell as Administrator
   New-Item -ItemType SymbolicLink `
            -Path   "C:\Users\you\Local Sites\<site>\app\public\wp-content\plugins\block-when" `
            -Target "C:\path\to\block-when"
   ```

4. Activate the plugin in **wp-admin → Plugins**.
5. Right-click the site in Local → **Open site shell** for a terminal where
   `wp` is on PATH (`wp plugin list`, etc.).
6. Enable debugging by editing `app/public/wp-config.php`:

   ```php
   define( 'WP_DEBUG',         true );
   define( 'WP_DEBUG_LOG',     true );
   define( 'WP_DEBUG_DISPLAY', false );
   define( 'SCRIPT_DEBUG',     true );
   ```

   Logs land at `app/public/wp-content/debug.log` — keep `tail -f` open on
   it while developing.

7. For one round of manual testing, switch the site's PHP version to **7.4**
   (Local site → "PHP" tab) to confirm the plugin's `Requires PHP: 7.4`
   declaration holds. Switch back to 8.2/8.3 for daily work.

### Option B — wp-env (recommended if you already have Docker)

```bash
wp-env start
# WordPress is now at http://localhost:8888
# Login: admin / password
```

The plugin is mounted automatically via [`.wp-env.json`](.wp-env.json) and
will already be active. Useful commands:

```bash
wp-env stop
wp-env clean all                       # Reset on schema changes
wp-env run cli wp plugin list          # WP-CLI inside the container
wp-env run tests-cli wp plugin list    # WP-CLI against the test DB
```

### Common commands (environment-agnostic)

```bash
# Editor JS
npm run start              # Watch mode for development
npm run build              # Production build
npm run lint:js
npm run lint:css
npm run format

# PHP
composer run phpcs         # Lint with WordPress-VIP-Go ruleset
composer run phpcbf        # Auto-fix what's fixable

# Tests
composer run phpunit       # PHP unit tests
npm run test:unit          # JS unit tests (Jest)
npm run test:e2e           # End-to-end tests (Playwright)
```

### Coding standards

- **PHP:** WordPress-VIP-Go ruleset, zero warnings. Run `composer run phpcs`
  before pushing.
- **JS:** `@wordpress/eslint-plugin/recommended`, zero warnings.
- **CSS:** `@wordpress/stylelint-config`.
- **Strings:** all user-facing strings internationalized with text domain
  `block-when`. `wp.i18n` in JS, `__()` / `esc_html__()` in PHP.
- **Output:** late-escaped at the point of output.
- **Filenames:** WPCS convention (`class-*.php`, `interface-*.php`,
  `abstract-*.php`).

### Testing

PHPUnit covers the registry, the renderer, and each built-in condition's
evaluation logic in isolation. Playwright covers the editor UX:

- Adding a condition to a block via the Visibility panel
- The editor indicator class appearing on rule-bearing blocks
- The saved attribute persisting across editor reloads
- Frontend rendering matching the rule (block present / absent)

Edge cases that have explicit test coverage:

- Block inside a Query Loop (condition reads looped post via `$block->context`)
- Block inside a hidden parent (children never reach `render_block`)
- Date range crossing a DST boundary
- Empty / malformed attribute (treated as "always visible")
- Third-party block with its own `render_callback`

**Note on PHPUnit + Local:** Running PHPUnit against a Local site requires
the WP test scaffolding (`bin/install-wp-tests.sh` pointed at Local's
MySQL — find host/port/credentials in Local's "Database" tab). If that
feels like more setup than it's worth, run PHPUnit in CI only and rely on
Playwright + manual testing for the local feedback loop. The GitHub Actions
workflow in [`.github/workflows/test.yml`](.github/workflows/test.yml)
runs the full PHPUnit matrix on every push.

## Contributing

Contributions are welcome. Before opening a PR:

1. Read [`PLAN.md`](PLAN.md) for scope and design decisions. Several
   features are deliberately out of scope for v1.0 — please open an issue
   for discussion before implementing them.
2. Run `composer run phpcs` and `npm run lint:js` and fix any warnings.
3. Add or update tests covering your change.
4. Update [`readme.txt`](readme.txt) and the changelog if user-facing
   behaviour changes.

For larger changes — new condition types, API additions, architectural
shifts — please open an issue first so we can talk through it.

## License

GPL-2.0-or-later. See [`LICENSE`](LICENSE).