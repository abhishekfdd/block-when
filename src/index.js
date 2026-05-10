/**
 * Block When — editor entry point.
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

/**
 * Sentinel value for the "no rule" option in the condition-type
 * dropdown. Never persisted — selecting it sets `blockWhen` to `null`.
 */
const ALWAYS = '';

const VisibilityPanel = ( { attributes, setAttributes } ) => {
	const blockWhen = attributes.blockWhen || null;
	const conditionId = blockWhen ? blockWhen.conditionId : ALWAYS;
	const condition = conditionId ? getCondition( conditionId ) : undefined;

	const options = [
		{ value: ALWAYS, label: __( 'Always', 'block-when' ) },
		...getAllConditions().map( ( c ) => ( {
			value: c.id,
			label: c.label,
		} ) ),
	];

	const handleConditionChange = ( newId ) => {
		if ( ! newId ) {
			setAttributes( { blockWhen: null } );
			return;
		}
		const next = getCondition( newId );
		if ( ! next ) {
			return;
		}
		setAttributes( {
			blockWhen: {
				conditionId: newId,
				settings: next.defaultSettings(),
			},
		} );
	};

	const handleSettingsChange = ( newSettings ) => {
		setAttributes( {
			blockWhen: {
				...blockWhen,
				settings: newSettings,
			},
		} );
	};

	const SettingsComponent = condition ? condition.SettingsComponent : null;

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Visibility', 'block-when' ) }
				initialOpen={ false }
			>
				<SelectControl
					label={ __( 'Show this block when…', 'block-when' ) }
					value={ conditionId }
					options={ options }
					onChange={ handleConditionChange }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				{ SettingsComponent && (
					<SettingsComponent
						settings={ blockWhen ? blockWhen.settings : undefined }
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
	'block-when/with-visibility-controls',
	withVisibilityControls
);
