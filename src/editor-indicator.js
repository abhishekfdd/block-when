/**
 * RenderWhen — editor indicator.
 *
 * Filters `editor.BlockListBlock` to add the `has-renderwhen-rule` class
 * to any block that has an active visibility rule, so the editor can
 * show a subtle indicator without affecting front-end output.
 *
 * "Active" here means three things, all required:
 *   - `renderWhen` attribute is truthy
 *   - `renderWhen.conditionId` is a non-empty string
 *   - that id resolves to a registered condition module
 *
 * The third check matters: a block may carry a `renderWhen` for a
 * condition that lived in a now-deactivated plugin, and we don't want
 * to visually flag rules the renderer can't actually evaluate.
 *
 * @package
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';

import { getCondition } from './store/conditions-registry';

/**
 * CSS class added to flagged blocks. Locked in PLAN.md naming
 * conventions — keep in sync if either ever changes.
 */
const RULE_CLASS = 'has-renderwhen-rule';

/**
 * Decide whether a block carries an evaluable visibility rule.
 *
 * Exported for the unit test; not part of the public API.
 *
 * @param {Object} [attributes] Block attributes.
 * @return {boolean} True when the block has an active, registered rule.
 */
export function hasActiveRule( attributes ) {
	const renderWhen = attributes && attributes.renderWhen;
	if ( ! renderWhen ) {
		return false;
	}
	const conditionId = renderWhen.conditionId;
	if ( typeof conditionId !== 'string' || conditionId === '' ) {
		return false;
	}
	return Boolean( getCondition( conditionId ) );
}

/**
 * HOC that adds `has-renderwhen-rule` to a flagged block's wrapper.
 *
 * Exported for the unit test. The runtime wiring happens via the
 * `addFilter` call below — importing this module is enough to register
 * the filter.
 */
export const withRuleIndicator = createHigherOrderComponent(
	( BlockListBlock ) => ( props ) => {
		if ( ! hasActiveRule( props.attributes ) ) {
			return <BlockListBlock { ...props } />;
		}
		const className = props.className
			? `${ props.className } ${ RULE_CLASS }`
			: RULE_CLASS;
		return <BlockListBlock { ...props } className={ className } />;
	},
	'withRuleIndicator'
);

addFilter(
	'editor.BlockListBlock',
	'renderwhen/with-rule-indicator',
	withRuleIndicator
);
