# Block When — Project Plan

> This file is the source of truth for the plugin's scope, architecture, and
> conventions. Claude Code (and any human contributor) should read this first
> before writing or editing code.

## What this plugin is

A developer-first WordPress plugin that adds conditional visibility to **any**
block in the block editor. Three built-in conditions ship in v1.0:

1. **User state** — logged in / logged out / specific roles
2. **Date range** — show between two datetimes (timezone-aware)
3. **Device type** — desktop / tablet / mobile (server-side detection)

The visibility rules are evaluated **server-side**. Hidden blocks are not
rendered into the page output at all — they are not sent to the browser and
hidden with CSS. This is the primary technical differentiator from most
competitors in the WordPress.org repo.

The plugin name reads naturally in the editor UI: "Show this block **when**
the user is logged in." That phrasing is the product.

## What this plugin is NOT (in v1.0)

Out of scope, deliberately:

- AND/OR condition groups (one rule per block in v1.0; groups land in v1.1)
- URL parameter / cookie / referrer / geolocation conditions
- Recurring schedules
- A/B testing
- WooCommerce / membership plugin integrations
- Any UI for managing conditions outside the block inspector
- Any settings page (no options needed for v1.0)

These are listed as "Planned" in the readme to signal product thinking. They
are NOT to be implemented opportunistically.

## Differentiation

The readme lead, verbatim:

> A developer-first block visibility plugin. Three built-in conditions,
> registered through the same public API you can use to add your own.
> Server-side rendering only — hidden blocks are not sent to the browser.

The Conditions API is the plugin's identity. The three built-in conditions
are themselves registered through that public API — we dogfood it.

## Naming conventions (LOCKED — do not change without find-and-replace)

| Where                          | Value                          |
| ------------------------------ | ------------------------------ |
| Plugin slug                    | `block-when`                   |
| Plugin display name            | `Block When`                   |
| Text domain                    | `block-when`                   |
| PHP namespace root             | `Block_When\`                  |
| PHP function/filter prefix     | `block_when_`                  |
| PHP constant prefix            | `BLOCK_WHEN_`                  |
| Block attribute namespace      | `blockWhen/visibility`         |
| Editor CSS class               | `has-block-when-rule`          |
| JS package name (internal)     | `@block-when/editor`           |
| Main repo / org                | `block-when`                   |

## Architecture

### High-level

- **Server-side renderer** hooks `render_block` and decides whether to return
  the rendered HTML or an empty string.
- **Attribute extender** hooks `register_block_type_args` to add a
  `blockWhen` attribute schema to every registered block.
- **Conditions registry** is a singleton holding all registered conditions
  (built-in + third-party). Exposes a filter for extension.
- **Editor UI** is a higher-order component on `editor.BlockEdit` that adds
  an InspectorControls panel.
- **JS conditions registry** mirrors the PHP one for the live editor preview.

### Public API surface

PHP:

```php
// Register a custom condition.
add_action( 'block_when_register_conditions', function ( $registry ) {
    $registry->register( new My_Custom_Condition() );
} );

// Conditions implement Block_When\Conditions\Interface_Condition.
```

Filters (part of the public contract — semver applies):

- `block_when_register_conditions` — fires after built-ins are registered.
  Receives the `Conditions_Registry` instance.
- `block_when_evaluate_{$condition_id}` — last-mile filter on a condition's
  evaluation result. For overrides and testing.
- `block_when_render_block_hidden` — fires when a block is hidden.
  Receives `( $block, $matched_condition )`. For caching/SEO integrations.
- `block_when_device_type` — filters detected device type. Allows swapping
  in a different detection library.

### File structure

```
block-when/
├── block-when.php                # Bootstrap only (~50 lines, no logic)
├── readme.txt                    # WordPress.org format
├── README.md                     # GitHub-facing
├── LICENSE                       # GPL-2.0-or-later
├── uninstall.php                 # No-op for v1.0; placeholder
├── composer.json
├── package.json
├── phpcs.xml.dist                # Extends WordPress-VIP-Go
├── .wp-env.json
├── .distignore                   # For wp-scripts plugin-zip
├── .gitignore
├── .editorconfig
├── .github/workflows/
│   ├── lint.yml
│   └── test.yml
├── includes/
│   ├── class-plugin.php          # Singleton, wires everything up
│   ├── class-conditions-registry.php
│   ├── class-block-renderer.php
│   ├── class-attribute-extender.php
│   └── conditions/
│       ├── interface-condition.php
│       ├── abstract-condition.php
│       ├── class-user-state-condition.php
│       ├── class-date-range-condition.php
│       └── class-device-condition.php
├── src/                          # Editor JS (built with @wordpress/scripts)
│   ├── index.js                  # Entry — registers HOC, JS conditions
│   ├── inspector-controls.js
│   ├── editor-indicator.js       # BlockListBlock filter, CSS class
│   ├── store/
│   │   └── conditions-registry.js
│   └── conditions/
│       ├── user-state.js
│       ├── date-range.js
│       └── device.js
├── build/                        # Compiled JS (committed for SVN deploy)
├── assets/                       # WordPress.org banner/icon/screenshots
└── tests/
    ├── phpunit/
    │   ├── bootstrap.php
    │   ├── test-conditions-registry.php
    │   ├── test-block-renderer.php
    │   └── conditions/
    │       ├── test-user-state-condition.php
    │       ├── test-date-range-condition.php
    │       └── test-device-condition.php
    └── e2e/
        ├── add-condition-to-block.spec.js
        ├── editor-indicator.spec.js
        └── frontend-rendering.spec.js
```

## Coding standards

- **PHP:** WordPress-VIP-Go ruleset. Zero warnings, zero errors. PHP 7.4+.
- **JS:** `@wordpress/eslint-plugin/recommended`. Zero warnings.
- **CSS:** `@wordpress/stylelint-config`.
- **All user-facing strings:** internationalized with text domain
  `block-when`. Use `wp.i18n` in JS, `__()` / `esc_html__()` in PHP.
- **All output:** late-escaped at the point of output.
- **All input:** sanitized via the appropriate `sanitize_*` function for the
  data type, not blanket `sanitize_text_field`.
- **Nonces:** any state-changing AJAX/REST endpoints (none in v1.0).
- **Capability checks:** any settings UI (none in v1.0).
- **Direct DB queries:** none. Use the WP API.
- **Filenames:** WPCS convention (`class-*.php`, `interface-*.php`,
  `abstract-*.php`).
- **Function/class names:** namespaced PHP classes, prefixed standalone
  functions (none expected — everything is a class method).

## Build, lint, test commands

```bash
# JS
npm install
npm run build           # Production build into build/
npm run start           # Watch mode
npm run lint:js
npm run lint:css
npm run format
npm run test:unit       # Jest via @wordpress/scripts
npm run test:e2e        # Playwright via @wordpress/e2e-test-utils-playwright

# PHP
composer install
composer run phpcs
composer run phpcbf     # Auto-fix what's fixable
composer run phpunit

# Local WP
wp-env start
wp-env clean all        # Reset on schema changes
```

## Edge cases that must be tested

- Block inside a Query Loop. The condition has access to the looped post via
  `$block->context`, not the global `$post`.
- Block inside a Group/Cover/Columns wrapper. If parent is hidden, children
  are also hidden (parent returns empty string, so children never render).
  If a child is hidden, parent renders without it.
- Block inside a synced pattern. The pattern instance is the boundary.
- Block in the site editor (template parts, navigation). Conditions apply
  there too.
- Date range that crosses a DST boundary. Use `wp_date()` and site timezone.
- Device detection cached per-request — must not vary by call site.
- Empty / malformed `blockWhen` attribute. Treat as "always visible."
- Third-party block with its own `render_callback`. Our filter runs after.

## Release plan

- **v1.0.0** — three conditions, public API, editor indicator, "preview as"
  toggle, full test suite, full docs.
- **v1.1.0** — AND/OR condition groups.
- **v1.2.0** — URL parameter and cookie conditions.
- **v1.3.0+** — to be decided based on user feedback in the support forum.

## Submission checklist

- [x] Slug `block-when` confirmed available at
      `https://wordpress.org/plugins/block-when/` (404 response)
- [ ] `readme.txt` validated at the WordPress.org readme validator
- [ ] All PHPCS warnings resolved (VIP-Go ruleset)
- [ ] All ESLint warnings resolved
- [ ] PHPUnit suite passing on PHP 7.4, 8.0, 8.1, 8.2, 8.3
- [ ] Playwright e2e suite passing
- [ ] Manually tested with: Twenty Twenty-Four, a classic theme, and a
      block theme with FSE
- [ ] Manually tested inside Query Loop, Group, Columns, Cover, synced pattern
- [ ] No `eval()`, no `extract()`, no `create_function()`, no remote loads
- [ ] No PHP errors with `WP_DEBUG` and `WP_DEBUG_LOG` on
- [ ] Banner and icon prepared per WordPress.org asset guidelines
- [ ] At least 4 screenshots for the WordPress.org listing
- [ ] GitHub Actions: lint workflow green, test workflow green
- [ ] Tag matches `Stable tag` in readme.txt
- [ ] ZIP built via `wp-scripts plugin-zip` (excludes dev files)
- [ ] Submitted at `https://wordpress.org/plugins/developers/add/`

## After approval

- Set up `10up/action-wordpress-plugin-deploy` to mirror GitHub tags to SVN.
- Set up `10up/action-wordpress-plugin-asset-update` for banner/icon updates.
- Watch the WP.org support forum daily for the first two weeks.
- Write a launch post on confusedblogger.com covering the public API and
  why server-side rendering matters.
