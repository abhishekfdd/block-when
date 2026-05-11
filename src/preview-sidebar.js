/**
 * Block When — "Preview as audience" sidebar.
 *
 * Registers an editor plugin via `@wordpress/plugins` that contributes a
 * `PluginSidebar` to the right-side icon strip. Inside, a single toggle
 * activates the preview simulation; while active, three controls drive
 * the simulated audience (logged-in flag, single role slug, device).
 *
 * Every control is wired through the `block-when/preview-mode` store,
 * which the BlockListBlock filter in `preview-renderer.js` subscribes
 * to. This file holds no rendering logic of its own beyond the UI.
 *
 * Imported for side effect from `src/index.js`.
 *
 * @package
 */

import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { PluginSidebar } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

import { STORE_NAME } from './store/preview-mode';

/**
 * Sentinel for the "Any role" SelectControl option. Mapped back to
 * `null` (the audience-role default) so the store's role slot stores
 * a clean string-or-null rather than an empty-string special case.
 */
const ANY_ROLE = '';

/**
 * Hardcoded role list. Matches the list in `conditions/user-state.js`
 * exactly — v1.0 ships without a REST endpoint for `wp_roles`, so we
 * source from a constant in both places.
 */
const ROLE_OPTIONS = [
	{ value: ANY_ROLE, label: __( 'Any role', 'block-when' ) },
	{ value: 'administrator', label: __( 'Administrator', 'block-when' ) },
	{ value: 'editor', label: __( 'Editor', 'block-when' ) },
	{ value: 'author', label: __( 'Author', 'block-when' ) },
	{ value: 'contributor', label: __( 'Contributor', 'block-when' ) },
	{ value: 'subscriber', label: __( 'Subscriber', 'block-when' ) },
];

const DEVICE_OPTIONS = [
	{ value: 'desktop', label: __( 'Desktop', 'block-when' ) },
	{ value: 'tablet', label: __( 'Tablet', 'block-when' ) },
	{ value: 'mobile', label: __( 'Mobile', 'block-when' ) },
];

const PreviewSidebar = () => {
	const { active, audience } = useSelect( ( select ) => {
		const store = select( STORE_NAME );
		return {
			active: store.isPreviewActive(),
			audience: store.getAudience(),
		};
	}, [] );

	const { setPreviewActive, setAudience } = useDispatch( STORE_NAME );

	return (
		<PluginSidebar
			name="block-when-preview"
			title={ __( 'Block When', 'block-when' ) }
			icon="visibility"
		>
			<PanelBody>
				<ToggleControl
					label={ __( 'Preview as audience', 'block-when' ) }
					help={ __(
						'Fade blocks whose visibility rule would hide them for the audience below. Editor-only — the saved post and front-end are unaffected.',
						'block-when'
					) }
					checked={ active }
					onChange={ setPreviewActive }
					__nextHasNoMarginBottom
				/>
				{ active && (
					<>
						<ToggleControl
							label={ __( 'Logged in', 'block-when' ) }
							checked={ audience.loggedIn }
							onChange={ ( loggedIn ) =>
								setAudience( { loggedIn } )
							}
							__nextHasNoMarginBottom
						/>
						<SelectControl
							label={ __( 'Role', 'block-when' ) }
							value={
								audience.role === null
									? ANY_ROLE
									: audience.role
							}
							options={ ROLE_OPTIONS }
							onChange={ ( value ) =>
								setAudience( {
									role: value === ANY_ROLE ? null : value,
								} )
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<SelectControl
							label={ __( 'Device', 'block-when' ) }
							value={ audience.device }
							options={ DEVICE_OPTIONS }
							onChange={ ( device ) => setAudience( { device } ) }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					</>
				) }
			</PanelBody>
		</PluginSidebar>
	);
};

registerPlugin( 'block-when-preview-sidebar', {
	render: PreviewSidebar,
	icon: 'visibility',
} );
