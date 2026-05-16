/**
 * RenderWhen — JS conditions registry.
 *
 * Mirror of the PHP `Conditions_Registry` for the editor. Exposes a tiny
 * imperative API that condition modules call at module load time to make
 * themselves discoverable to the InspectorControls UI.
 *
 * This module is the public extension surface for the JS side. A third
 * party adding a custom condition does so the same way the built-ins do:
 *
 *     import { registerCondition } from '@renderwhen/editor/store/conditions-registry';
 *
 *     registerCondition( {
 *         id: 'my_custom_condition',           // Must match PHP get_id() exactly.
 *         label: __( 'My condition', 'my-plugin' ),
 *         SettingsComponent: ( { settings, onChange } ) => ( ... ),
 *         defaultSettings: () => ( { foo: 'bar' } ),
 *     } );
 *
 * The `id` MUST be identical to the value returned by the PHP-side
 * `Interface_Condition::get_id()` for the same condition — the editor
 * writes that id into the block's `renderWhen.conditionId` attribute, and
 * the server-side renderer looks the condition up by it. Mismatches
 * produce silent rendering failures, so duplicate JS-side registration
 * throws to surface mistakes immediately during development.
 *
 * `getAllConditions()` returns conditions in registration order. The
 * built-ins are imported in `src/index.js` in the order they should
 * appear in the editor dropdown; third parties append to that order
 * by importing their module after the plugin's entry has loaded.
 *
 * @package
 */

/**
 * @typedef {Object} ConditionModule
 * @property {string}   id                Unique condition id, snake_case, matching the PHP `get_id()`.
 * @property {string}   label             Human-readable, i18n-ready label for the condition-type dropdown.
 * @property {Function} SettingsComponent React component receiving `{ settings, onChange }`.
 * @property {Function} defaultSettings   Returns the initial settings object when the condition is first chosen.
 * @property {Function} evaluate          `( settings, previewContext ) => boolean`. Editor-side mirror of the PHP `evaluate()`,
 *                                        used by the "Preview as audience" sidebar to fade blocks whose rule would hide them
 *                                        for the simulated audience. `previewContext` shape:
 *                                        `{ loggedIn: boolean, role: string|null, device: 'desktop'|'tablet'|'mobile', now?: Date }`.
 */

/**
 * Internal store. A `Map` so iteration order matches insertion order —
 * `getAllConditions()` relies on that for a stable dropdown.
 *
 * @type {Map<string, ConditionModule>}
 */
const conditions = new Map();

/**
 * Register a condition module.
 *
 * Throws on duplicate id rather than overwriting silently, so a typo or
 * accidental double-import is surfaced loudly during development. The PHP
 * registry is last-write-wins because production sites legitimately
 * override built-ins from a `mu-plugin`; the editor bundle has no such
 * use case — a duplicate here is always a bug.
 *
 * @param {ConditionModule} condition Condition module.
 * @return {void}
 */
export function registerCondition( condition ) {
	if ( conditions.has( condition.id ) ) {
		throw new Error(
			`RenderWhen: condition "${ condition.id }" is already registered.`
		);
	}
	conditions.set( condition.id, condition );
}

/**
 * Get a condition by id.
 *
 * @param {string} id Condition id.
 * @return {ConditionModule|undefined} The registered module, or undefined when no condition is registered under that id.
 */
export function getCondition( id ) {
	return conditions.get( id );
}

/**
 * Get every registered condition, in registration order.
 *
 * @return {ConditionModule[]} Conditions in the order they were registered.
 */
export function getAllConditions() {
	return Array.from( conditions.values() );
}

export default { registerCondition, getCondition, getAllConditions };
