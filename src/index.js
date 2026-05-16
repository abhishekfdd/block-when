/**
 * RenderWhen — editor entry point.
 *
 * Registers the higher-order component on `editor.BlockEdit` that adds a
 * "Visibility" panel to every block's InspectorControls. The panel lets
 * the user pick a visibility condition, then renders that condition's
 * own settings UI directly underneath.
 *
 * Built-in condition modules are imported here for their side effect of
 * calling `registerCondition()` at module load time. Their import order
 * is the order they appear in the condition-type dropdown.
 *
 * @package
 */

import { addFilter } from '@wordpress/hooks';
import { PanelBody, SelectControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { createHigherOrderComponent } from '@wordpress/compose';

import { getAllConditions, getCondition } from './store/conditions-registry';

// Built-in condition modules. Each calls `registerCondition()` at import
// time, and the import order here is the order they appear in the
// "Show this block when…" dropdown.
import './conditions/user-state';
import './conditions/date-range';
import './conditions/device';

// Editor-only side-effect imports. `editor-indicator` and
// `preview-renderer` each register a BlockListBlock filter at module
// load. `preview-sidebar` registers a PluginSidebar. `editor.scss` is
// extracted to build/index.css by wp-scripts and enqueued separately
// by PHP. The preview store import is what executes the
// `registerStore()` call the sidebar and renderer rely on; it must
// run before the modules that read from it.
import './store/preview-mode';
import './editor-indicator';
import './preview-renderer';
import './preview-sidebar';
import './editor.scss';

/**
 * Sentinel value for the "no rule" option in the condition-type
 * dropdown. Never persisted — selecting it sets `renderWhen` to `null`.
 */
const ALWAYS = '';

const VisibilityPanel = ( { attributes, setAttributes } ) => {
	const renderWhen = attributes.renderWhen || null;
	const conditionId = renderWhen ? renderWhen.conditionId : ALWAYS;
	const condition = conditionId ? getCondition( conditionId ) : undefined;

	const options = [
		{ value: ALWAYS, label: __( 'Always', 'renderwhen' ) },
		...getAllConditions().map( ( c ) => ( {
			value: c.id,
			label: c.label,
		} ) ),
	];

	const handleConditionChange = ( newId ) => {
		if ( ! newId ) {
			setAttributes( { renderWhen: null } );
			return;
		}
		const next = getCondition( newId );
		if ( ! next ) {
			return;
		}
		setAttributes( {
			renderWhen: {
				conditionId: newId,
				settings: next.defaultSettings(),
			},
		} );
	};

	const handleSettingsChange = ( newSettings ) => {
		setAttributes( {
			renderWhen: {
				...renderWhen,
				settings: newSettings,
			},
		} );
	};

	const SettingsComponent = condition ? condition.SettingsComponent : null;

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Visibility', 'renderwhen' ) }
				initialOpen={ false }
			>
				<SelectControl
					label={ __( 'Show this block when…', 'renderwhen' ) }
					value={ conditionId }
					options={ options }
					onChange={ handleConditionChange }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				{ SettingsComponent && (
					<SettingsComponent
						settings={
							renderWhen ? renderWhen.settings : undefined
						}
						onChange={ handleSettingsChange }
					/>
				) }
			</PanelBody>
		</InspectorControls>
	);
};

const withVisibilityControls = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => (
		<>
			<BlockEdit { ...props } />
			<VisibilityPanel
				attributes={ props.attributes }
				setAttributes={ props.setAttributes }
			/>
		</>
	),
	'withVisibilityControls'
);

addFilter(
	'editor.BlockEdit',
	'renderwhen/with-visibility-controls',
	withVisibilityControls
);
