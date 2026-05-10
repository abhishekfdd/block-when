/**
 * Block When — editor entry point.
 *
 * Registers the higher-order component on `editor.BlockEdit` that adds a
 * "Visibility" InspectorControls panel to every block. The panel is
 * intentionally empty in this phase — wiring the integration point is
 * verified before any condition UI lands inside it.
 */

import { Fragment } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { PanelBody } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { createHigherOrderComponent } from '@wordpress/compose';

const withVisibilityControls = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => (
		<Fragment>
			<BlockEdit { ...props } />
			<InspectorControls>
				<PanelBody
					title={ __( 'Visibility', 'block-when' ) }
					initialOpen={ false }
				/>
			</InspectorControls>
		</Fragment>
	),
	'withVisibilityControls'
);

addFilter(
	'editor.BlockEdit',
	'block-when/with-visibility-controls',
	withVisibilityControls
);
