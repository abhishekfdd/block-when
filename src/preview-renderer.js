/**
 * Block When — preview renderer.
 *
 * BlockListBlock filter HOC that drives the "Preview as audience"
 * visualization. When the preview-mode store is active, this HOC reads
 * the simulated audience, evaluates each rule-bearing block against
 * that audience using the registered condition's editor-side
 * `evaluate()`, and adds the `block-when-preview-hidden` class to any
 * block the rule would hide.
 *
 * The class is additive: it composes with `has-block-when-rule` from
 * `editor-indicator.js`. The two flags answer different questions —
 * "this block has a rule" vs. "this block would be hidden right now in
 * preview" — so they live in separate filters that can be reasoned
 * about (and tested) independently.
 *
 * When the preview is off, this HOC is a pure pass-through and pays
 * only the cost of a `useSelect` subscription. Block-list re-renders
 * already churn this filter; adding one selector is cheap.
 *
 * @package
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { getCondition } from './store/conditions-registry';
import { STORE_NAME } from './store/preview-mode';

/**
 * CSS class added to blocks that would be hidden for the simulated
 * audience. Paired with the styles in `editor.scss`.
 */
const PREVIEW_HIDDEN_CLASS = 'block-when-preview-hidden';

/**
 * Inject the localised "Hidden in preview" badge label as a CSS custom
 * property on `:root`. The stylesheet reads it via `var(...)` with a
 * literal English fallback, so a missing JS run (e.g. in unit tests)
 * still produces a readable badge.
 *
 * Runs once at module load — gated by an id so a hot-reload or a
 * second import does not append duplicate `<style>` nodes.
 */
function injectPreviewLabelStyle() {
	if (
		typeof document === 'undefined' ||
		document.getElementById( 'block-when-preview-label' )
	) {
		return;
	}
	const label = __( 'Hidden in preview', 'block-when' );
	// Escape characters that would terminate or break the CSS string.
	const escaped = label
		.replace( /\\/g, '\\\\' )
		.replace( /"/g, '\\"' )
		.replace( /\n/g, '\\A ' );
	const style = document.createElement( 'style' );
	style.id = 'block-when-preview-label';
	style.textContent = `:root { --block-when-preview-label: "${ escaped }"; }`;
	document.head.appendChild( style );
}

injectPreviewLabelStyle();

/**
 * Decide whether a block should be faded for the given audience.
 *
 * Exported for the unit test; not part of the public API. Returns false
 * (do not fade) for blocks without an evaluable rule, for blocks whose
 * condition module never registered an `evaluate`, and for blocks whose
 * `evaluate()` returns truthy.
 *
 * @param {Object} attributes Block attributes.
 * @param {Object} audience   Current audience from the preview-mode store.
 * @return {boolean} True when the block should be faded.
 */
export function shouldHideInPreview( attributes, audience ) {
	const blockWhen = attributes && attributes.blockWhen;
	if ( ! blockWhen ) {
		return false;
	}
	const conditionId = blockWhen.conditionId;
	if ( typeof conditionId !== 'string' || conditionId === '' ) {
		return false;
	}
	const condition = getCondition( conditionId );
	if ( ! condition || typeof condition.evaluate !== 'function' ) {
		return false;
	}
	const previewContext = {
		loggedIn: Boolean( audience && audience.loggedIn ),
		role:
			audience && typeof audience.role === 'string'
				? audience.role
				: null,
		device:
			audience && typeof audience.device === 'string'
				? audience.device
				: 'desktop',
		now: new Date(),
	};
	return ! condition.evaluate( blockWhen.settings || {}, previewContext );
}

/**
 * HOC that adds `block-when-preview-hidden` to a faded block's wrapper.
 *
 * Exported for the unit test. The runtime wiring happens via the
 * `addFilter` call below — importing this module is enough to register
 * the filter.
 */
export const withPreviewRenderer = createHigherOrderComponent(
	( BlockListBlock ) => ( props ) => {
		const { active, audience } = useSelect( ( select ) => {
			const store = select( STORE_NAME );
			return {
				active: store.isPreviewActive(),
				audience: store.getAudience(),
			};
		}, [] );

		if ( ! active ) {
			return <BlockListBlock { ...props } />;
		}
		if ( ! shouldHideInPreview( props.attributes, audience ) ) {
			return <BlockListBlock { ...props } />;
		}

		const className = props.className
			? `${ props.className } ${ PREVIEW_HIDDEN_CLASS }`
			: PREVIEW_HIDDEN_CLASS;
		return <BlockListBlock { ...props } className={ className } />;
	},
	'withPreviewRenderer'
);

addFilter(
	'editor.BlockListBlock',
	'block-when/with-preview-renderer',
	withPreviewRenderer
);
