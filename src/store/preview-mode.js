/**
 * Block When — preview-mode store.
 *
 * Holds the editor-side "Preview as audience" state: whether the
 * simulation is active and, when it is, the audience the editor is
 * pretending to be. The BlockListBlock filter in `preview-renderer.js`
 * subscribes to this store and fades blocks whose rule would hide them
 * for the simulated audience.
 *
 * The store is deliberately transient — there is no persistence layer
 * and no localStorage round-trip. Reloading the editor resets preview
 * mode to off so a stale audience selection from yesterday cannot leave
 * blocks mysteriously faded today. See PLAN.md "Preview as…" notes for
 * the rationale.
 *
 * @package
 */

import { registerStore } from '@wordpress/data';

/**
 * Store key. Exported for consumers (sidebar + BlockListBlock filter)
 * so the string is defined exactly once.
 */
export const STORE_NAME = 'block-when/preview-mode';

/**
 * Initial state. The audience defaults model "the most common content
 * editor": logged in, any role, on a desktop. These are also the values
 * that produce no visual change for a freshly-toggled preview, which is
 * the least confusing first impression.
 */
const DEFAULT_STATE = {
	active: false,
	audience: {
		loggedIn: true,
		role: null,
		device: 'desktop',
	},
};

const SET_ACTIVE = 'BLOCK_WHEN_PREVIEW/SET_ACTIVE';
const SET_AUDIENCE = 'BLOCK_WHEN_PREVIEW/SET_AUDIENCE';

const actions = {
	/**
	 * Toggle the preview simulation on or off.
	 *
	 * @param {boolean} active New active flag.
	 * @return {Object} Action.
	 */
	setPreviewActive( active ) {
		return { type: SET_ACTIVE, active: Boolean( active ) };
	},

	/**
	 * Merge a partial audience update into the current audience.
	 *
	 * Partial updates so the sidebar can change one dimension at a time
	 * (toggle "Logged in" without re-asserting `role` and `device`).
	 *
	 * @param {Object} partial Partial audience patch.
	 * @return {Object} Action.
	 */
	setAudience( partial ) {
		return { type: SET_AUDIENCE, partial: partial || {} };
	},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case SET_ACTIVE:
			return { ...state, active: action.active };
		case SET_AUDIENCE:
			return {
				...state,
				audience: { ...state.audience, ...action.partial },
			};
		default:
			return state;
	}
};

const selectors = {
	isPreviewActive: ( state ) => state.active,
	getAudience: ( state ) => state.audience,
};

registerStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
} );

export default { STORE_NAME };
