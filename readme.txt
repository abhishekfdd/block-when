=== Block When ===
Contributors:      abhishekfdd
Tags:              block editor, gutenberg, visibility, conditional, blocks
Requires at least: 6.5
Tested up to:      6.7
Requires PHP:      7.4
Stable tag:        1.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Show or hide any block when conditions match. Developer-first API and true server-side rendering — hidden blocks never reach the browser.

== Description ==

Block When adds conditional visibility to every block in the WordPress block editor. Decide who sees what, and when, with a panel that reads exactly how you'd say it out loud:

> *Show this block when the user is logged in.*
> *Show this block when the date is between March 1 and March 31.*
> *Show this block when the device is mobile.*

It is built around two ideas that set it apart from other visibility plugins:

* **Server-side rendering only.** When a block is hidden by a condition, it is not rendered into the page output at all. No CSS `display: none`, no DOM bloat, no leaked content in view-source. Search engines, screen readers, and your page weight all benefit.
* **Public Conditions API.** The three built-in conditions are registered through the same public API your plugin or theme can use to add your own. No hacks, no internal-only code paths.

= Built-in conditions (v1.0) =

* **User state** — show to logged-in users, logged-out users, or specific roles
* **Date range** — show between two datetimes, timezone-aware
* **Device type** — show on desktop, tablet, or mobile

= Designed for =

* Agencies and freelancers building client sites who want a clean, predictable visibility tool
* Developers who need an extension point, not another walled garden
* Anyone who cares about page weight and SEO hygiene

= What this plugin deliberately does NOT do =

It does one thing well rather than ten things adequately. Out of scope in v1.0:

* Multi-condition AND/OR groups (planned for v1.1)
* URL / cookie / referrer / geolocation conditions
* WooCommerce, ACF, or membership integrations
* A settings dashboard

If you need those today, [Conditional Blocks](https://wordpress.org/plugins/conditional-blocks/) and [Block Visibility](https://wordpress.org/plugins/block-visibility/) are excellent and well-established choices.

= For developers =

Register a custom condition in three steps. Implement the interface, add it to the registry, ship.

`
add_action( 'block_when_register_conditions', function ( $registry ) {
    $registry->register( new My_Plugin\\Conditions\\Country_Condition() );
} );
`

Your condition class implements `Block_When\Conditions\Interface_Condition`:

`
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
`

The full set of public filters:

* `block_when_register_conditions` — register your conditions here
* `block_when_evaluate_{condition_id}` — last-mile override of any condition's result
* `block_when_render_block_hidden` — fires when a block is hidden, useful for cache busters and SEO tools
* `block_when_device_type` — swap in your own device detection

The plugin is developed openly on GitHub. Issues, PRs, and architecture discussions welcome: https://github.com/abhishekfdd/block-when

== Installation ==

1. Upload the `block-when` folder to `/wp-content/plugins/`, or install through the **Plugins → Add New** screen
2. Activate the plugin
3. Edit any post or page, select a block, and find the **Visibility** panel in the block inspector

There is no settings page. By design.

== Frequently Asked Questions ==

= Does this work with the Site Editor? =

Yes. Conditions apply to blocks anywhere they render — posts, pages, template parts, navigation menus, and patterns.

= Does this work with third-party blocks? =

Yes. The visibility attribute is registered on every registered block, including blocks from other plugins (ACF Blocks, GenerateBlocks, Stackable, etc.).

= Will hidden blocks affect SEO? =

Hidden blocks are never rendered into the page HTML. Search engines see what your visitors see — not the hidden content. This is the opposite of CSS-based hiding, where the content is still in the DOM.

= Can I combine multiple conditions on one block? =

Not in v1.0 — one rule per block. AND/OR condition groups are planned for v1.1.

= How does this compare to Block Visibility or Conditional Blocks? =

Both are excellent, mature plugins with broader feature sets. Block When is intentionally smaller in surface area, server-side-only in rendering, and built around a public extension API. If you need ACF/WooCommerce/cookie conditions today, use one of those. If you want a small, fast, extensible foundation, this is for you.

= How does device detection work? =

By default, via `wp_is_mobile()` plus a user-agent check for tablets. The result is filterable via `block_when_device_type` so you can plug in a more sophisticated library.

= Is this caching-friendly? =

Mostly yes — visibility is evaluated at render time, so server-side page caches will cache whatever was rendered for that request. For varying-by-user content (login state, role), you'll want a cache layer that varies on cookies. The `block_when_render_block_hidden` action is provided so cache plugins and edge integrations can hook in.

== Screenshots ==

1. The Visibility panel in the block inspector
2. Editor indicator showing a block has an active visibility rule
3. Date range condition with timezone-aware datetime pickers
4. User role selector with multi-select support

== Changelog ==

= 1.0.0 =
* Initial release.
* Three built-in conditions: user state, date range, device type.
* Public Conditions API.
* Editor indicator and "Preview as..." mode.

== Upgrade Notice ==

= 1.0.0 =
Initial release.