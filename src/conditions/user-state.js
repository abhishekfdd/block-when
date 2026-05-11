/**
 * Block When — user-state condition (editor).
 *
 * Editor-side counterpart of `User_State_Condition`. Provides the
 * settings UI rendered inside the Visibility panel and registers the
 * condition module with the JS conditions registry at import time.
 *
 * The settings shape produced here MUST match what the PHP evaluator
 * expects, namely `{ states: string[] }` where each entry is one of
 * `logged_in`, `logged_out`, or `role:<role-slug>`. The contract is
 * documented in {@link ../../includes/conditions/class-user-state-condition.php}.
 *
 * @package
 */

import { useMemo } from '@wordpress/element';
import { SelectControl, FormTokenField } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { registerCondition } from '../store/conditions-registry';

/**
 * Discriminator for role-membership entries inside `settings.states`.
 * Mirrors `User_State_Condition::ROLE_PREFIX` on the PHP side.
 */
const ROLE_PREFIX = 'role:';

/**
 * Top-level mode values for the "Show this block to" SelectControl.
 * These are UI-only — they do not appear in the persisted settings,
 * which only ever stores entries from the `states` vocabulary above.
 */
const MODE_LOGGED_IN = 'logged_in';
const MODE_LOGGED_OUT = 'logged_out';
const MODE_ROLES = 'roles';

/**
 * Hardcoded WP role list, used for FormTokenField suggestions and for
 * slug ↔ label conversion.
 *
 * TODO(v1.1): replace with `useSelect( select => select( 'core' ).getEntityRecords( 'root', 'wp_roles' ) )`
 * once we expose roles via REST. Hardcoding is acceptable for v1.0
 * because fetching roles client-side requires an endpoint we do not
 * yet ship, and the five default roles cover the vast majority of
 * sites that will use this plugin in its first release.
 *
 * @type {{ slug: string, label: string }[]}
 */
const DEFAULT_ROLES = [
	{ slug: 'administrator', label: __( 'Administrator', 'block-when' ) },
	{ slug: 'editor', label: __( 'Editor', 'block-when' ) },
	{ slug: 'author', label: __( 'Author', 'block-when' ) },
	{ slug: 'contributor', label: __( 'Contributor', 'block-when' ) },
	{ slug: 'subscriber', label: __( 'Subscriber', 'block-when' ) },
];

/**
 * Derive the active mode from a `states` array.
 *
 * An empty array maps to "Specific roles" — that is the shape produced
 * by `handleModeChange( MODE_ROLES )` before the user has added any
 * tokens, and treating it as LOGGED_IN here was the Phase 2 bug that
 * made the FormTokenField never appear. If any entry is a `role:*`
 * token we are likewise in roles mode, even when `logged_in` is also
 * present — the persisted shape supports mixing but the v1.0 UI does
 * not expose that combination.
 *
 * Non-array input falls back to LOGGED_IN as a defensive default;
 * normal flow always provides an array via `defaultSettings()`.
 *
 * @param {string[]} states Persisted `states` array.
 * @return {string} One of MODE_LOGGED_IN, MODE_LOGGED_OUT, MODE_ROLES.
 */
function deriveMode( states ) {
	if ( ! Array.isArray( states ) ) {
		return MODE_LOGGED_IN;
	}
	if (
		states.length === 0 ||
		states.some(
			( s ) => typeof s === 'string' && s.startsWith( ROLE_PREFIX )
		)
	) {
		return MODE_ROLES;
	}
	if ( states.includes( MODE_LOGGED_OUT ) ) {
		return MODE_LOGGED_OUT;
	}
	return MODE_LOGGED_IN;
}

/**
 * Convert persisted `states` (e.g. `[ "role:administrator" ]`) to the
 * user-friendly labels FormTokenField displays (e.g. `[ "Administrator" ]`).
 * Unknown role slugs fall through as the raw slug, so a value typed by
 * a third party or carried over from a custom role is never lost.
 *
 * @param {string[]} states Persisted states.
 * @return {string[]} Labels.
 */
function statesToRoleLabels( states ) {
	if ( ! Array.isArray( states ) ) {
		return [];
	}
	return states
		.filter( ( s ) => typeof s === 'string' && s.startsWith( ROLE_PREFIX ) )
		.map( ( s ) => {
			const slug = s.slice( ROLE_PREFIX.length );
			const match = DEFAULT_ROLES.find( ( r ) => r.slug === slug );
			return match ? match.label : slug;
		} );
}

/**
 * Convert FormTokenField labels back into `role:<slug>` entries.
 * If the token isn't one of our known labels we lowercase it and use
 * it as-is — good enough for the hardcoded v1.0 list.
 *
 * @param {string[]} labels Tokens from FormTokenField.
 * @return {string[]} Persisted state entries.
 */
function roleLabelsToStates( labels ) {
	return labels.map( ( label ) => {
		const match = DEFAULT_ROLES.find( ( r ) => r.label === label );
		const slug = match ? match.slug : String( label ).toLowerCase();
		return ROLE_PREFIX + slug;
	} );
}

/**
 * Settings component rendered inside the Visibility panel when
 * "User state" is the selected condition.
 *
 * @param {Object}   props
 * @param {Object}   [props.settings] Current settings, or undefined on first render.
 * @param {Function} props.onChange   Called with a new settings object.
 * @return {JSX.Element} Settings UI.
 */
export function UserStateSettings( { settings, onChange } ) {
	const states =
		settings && Array.isArray( settings.states ) ? settings.states : [];
	const mode = deriveMode( states );

	const roleSuggestions = useMemo(
		() => DEFAULT_ROLES.map( ( r ) => r.label ),
		[]
	);

	const handleModeChange = ( newMode ) => {
		if ( newMode === MODE_LOGGED_IN ) {
			onChange( { states: [ 'logged_in' ] } );
			return;
		}
		if ( newMode === MODE_LOGGED_OUT ) {
			onChange( { states: [ 'logged_out' ] } );
			return;
		}
		onChange( { states: [] } );
	};

	const handleRolesChange = ( newLabels ) => {
		onChange( { states: roleLabelsToStates( newLabels ) } );
	};

	return (
		<>
			<SelectControl
				label={ __( 'Show this block to', 'block-when' ) }
				value={ mode }
				options={ [
					{
						value: MODE_LOGGED_IN,
						label: __( 'Logged-in users', 'block-when' ),
					},
					{
						value: MODE_LOGGED_OUT,
						label: __( 'Logged-out users', 'block-when' ),
					},
					{
						value: MODE_ROLES,
						label: __( 'Specific roles', 'block-when' ),
					},
				] }
				onChange={ handleModeChange }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			{ mode === MODE_ROLES && (
				<FormTokenField
					label={ __( 'Roles', 'block-when' ) }
					value={ statesToRoleLabels( states ) }
					suggestions={ roleSuggestions }
					onChange={ handleRolesChange }
					__experimentalExpandOnFocus
					__nextHasNoMarginBottom
				/>
			) }
		</>
	);
}

/**
 * Editor-side mirror of `User_State_Condition::evaluate()`.
 *
 * Walks the persisted `states` array with OR semantics — visibility is
 * granted as soon as one entry matches the previewed audience. Empty /
 * malformed input resolves to "always visible", matching the PHP-side
 * "never silently hide on bad input" policy.
 *
 * The `role:X` arm intentionally requires an exact slug match against
 * `previewContext.role`. A null preview role models "Any role" — and
 * since we can't know which roles the visitor has, a role-restricted
 * block is treated as hidden under that audience.
 *
 * @param {Object} settings       Persisted settings, `{ states: string[] }`.
 * @param {Object} previewContext Simulated audience. `{ loggedIn: boolean, role: string|null, device: string }`.
 * @return {boolean} True if the block should render for this audience.
 */
export function evaluate( settings, previewContext ) {
	const states =
		settings && Array.isArray( settings.states ) ? settings.states : [];

	if ( states.length === 0 ) {
		return true;
	}

	const loggedIn = Boolean( previewContext && previewContext.loggedIn );
	const role =
		previewContext && typeof previewContext.role === 'string'
			? previewContext.role
			: null;

	for ( const state of states ) {
		if ( typeof state !== 'string' || state === '' ) {
			continue;
		}
		if ( state === 'logged_in' ) {
			if ( loggedIn ) {
				return true;
			}
			continue;
		}
		if ( state === 'logged_out' ) {
			if ( ! loggedIn ) {
				return true;
			}
			continue;
		}
		if ( state.startsWith( ROLE_PREFIX ) ) {
			if ( ! loggedIn ) {
				continue;
			}
			const slug = state.slice( ROLE_PREFIX.length );
			if ( slug === '' ) {
				continue;
			}
			if ( role !== null && slug === role ) {
				return true;
			}
		}
	}

	return false;
}

registerCondition( {
	id: 'user_state',
	label: __( 'User state', 'block-when' ),
	SettingsComponent: UserStateSettings,
	defaultSettings: () => ( { states: [ 'logged_in' ] } ),
	evaluate,
} );
