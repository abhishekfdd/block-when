<?php
/**
 * Condition interface.
 *
 * Public contract that every visibility condition — built-in or registered
 * by a third-party plugin — must implement to be accepted by the
 * Conditions_Registry.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Conditions;

defined( 'ABSPATH' ) || exit;

/**
 * Contract for block visibility conditions.
 *
 * A condition is a small, self-contained policy object that answers one
 * question: given the per-block settings the editor saved and the render
 * context the server has at render time, should this block be visible?
 *
 * Implementations are expected to be:
 *
 *  - Stateless. The same `( $settings, $context )` pair must always
 *    produce the same boolean. The renderer may evaluate a condition
 *    multiple times per request, and downstream caches assume purity.
 *  - Side-effect free. Do not echo, enqueue, mutate globals, or trigger
 *    HTTP requests from `evaluate()`. Conditions run inside the
 *    `render_block` filter, on every block, on every request.
 *  - Cheap. Anything expensive (geo lookup, remote API) must be cached
 *    per-request by the implementation itself.
 *
 * Most third-party authors should extend {@see Abstract_Condition}
 * rather than implement this interface directly.
 */
interface Interface_Condition {

	/**
	 * Stable, machine-readable identifier for this condition.
	 *
	 * Used as the key in the Conditions_Registry, as the `type` value
	 * stored on the block's `blockWhen` attribute, and as the suffix in
	 * the `block_when_evaluate_{$id}` filter. Because all three of those
	 * are part of the public contract, the id is effectively a semver
	 * surface — once shipped, do not change it.
	 *
	 * Conventions:
	 *  - Lowercase, snake_case, matching the regex `[a-z][a-z0-9_]*`
	 *    (`user_state`, not `user-state` and not `userState`). This is
	 *    the form every built-in ships with, the form documented in
	 *    saved post markup, and the form covered by the registry-level
	 *    regression test in test_plugin.php — mixing in hyphens silently
	 *    breaks lookups at the renderer's unknown-id gate.
	 *  - Globally unique. Third-party authors should namespace their ids
	 *    (`acme_geofence`) to avoid collisions with built-ins.
	 *  - Must not be translated. This is a key, not a label.
	 *
	 * @return string Stable identifier, e.g. `user_state`.
	 */
	public function get_id(): string;

	/**
	 * Translated, human-readable label shown in the inspector UI.
	 *
	 * Rendered in the condition dropdown in the block sidebar. Should be
	 * a short, sentence-case noun phrase that completes the sentence
	 * "Show this block when…" — for example, "User is logged in" or
	 * "Date is within range".
	 *
	 * Implementations MUST localise the string via `__()` (or an
	 * equivalent gettext function) using the `block-when` text domain
	 * for built-ins, or the implementing plugin's own text domain for
	 * third-party conditions.
	 *
	 * @return string Translated, ready-to-display label.
	 */
	public function get_label(): string;

	/**
	 * Schema describing the per-block settings for this condition.
	 *
	 * The shape mirrors WordPress's block-attributes schema (the same
	 * structure accepted by `register_block_type_args`): an associative
	 * array keyed by setting name, where each entry declares `type`,
	 * `default`, and optionally `enum` / `items` / `properties`.
	 *
	 * The Attribute_Extender uses the returned schema to register each
	 * condition's settings under `blockWhen.settings` on every block,
	 * which is what gives the editor's `useBlockProps` validation and
	 * the REST API serialisation a stable contract.
	 *
	 * Example return shape for a "user roles" condition:
	 *
	 *     return array(
	 *         'state' => array(
	 *             'type'    => 'string',
	 *             'enum'    => array( 'logged-in', 'logged-out', 'role' ),
	 *             'default' => 'logged-in',
	 *         ),
	 *         'roles' => array(
	 *             'type'    => 'array',
	 *             'items'   => array( 'type' => 'string' ),
	 *             'default' => array(),
	 *         ),
	 *     );
	 *
	 * Return an empty array if the condition takes no settings.
	 *
	 * @return array<string, mixed> Block-attribute-style schema.
	 */
	public function get_schema(): array;

	/**
	 * Decide whether the block should be visible for this request.
	 *
	 * Called by Block_Renderer once per block-with-condition, per render.
	 * Return `true` to allow normal rendering, `false` to suppress the
	 * block entirely (its HTML is replaced with an empty string before
	 * it reaches the browser — it is not hidden via CSS).
	 *
	 * The `$settings` array is the slice of the block's `blockWhen`
	 * attribute that belongs to this condition, already validated
	 * against `get_schema()`. Treat any missing/empty value as the
	 * documented default; treat malformed input as "always visible"
	 * (i.e. return `true`) rather than throwing — a broken condition
	 * should never take a block off the page silently.
	 *
	 * The `$context` array carries everything the condition might need
	 * to make its decision without reaching for globals:
	 *
	 *  - `block`     (array)         The parsed block array.
	 *  - `instance`  (\WP_Block|null) The block instance, when available.
	 *  - `post_id`   (int|null)      The post being rendered. Inside a
	 *                                Query Loop this is the looped post,
	 *                                not the global `$post`.
	 *
	 * Additional context keys may be added in future minor versions;
	 * implementations should treat unknown keys as harmless.
	 *
	 * After this method returns, Block_Renderer applies the
	 * `block_when_evaluate_{$id}` filter to the result, so site owners
	 * and tests can override evaluation without subclassing.
	 *
	 * @param array<string, mixed> $settings Validated per-block settings
	 *                                       for this condition.
	 * @param array<string, mixed> $context  Render-time context.
	 * @return bool True to render the block, false to hide it.
	 */
	public function evaluate( array $settings, array $context ): bool;
}
