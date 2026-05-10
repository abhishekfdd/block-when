/**
 * Tests for the editor-indicator HOC.
 *
 * The HOC under test wraps a `BlockListBlock`-shaped component and adds
 * `has-block-when-rule` to its `className` when the block has an
 * evaluable visibility rule. We feed in a fake BlockListBlock that
 * renders its className into the DOM so the test can read it back via
 * `renderToStaticMarkup`, mirroring the pattern in
 * `src/conditions/__tests__/user-state.test.js`.
 *
 * The conditions registry is mocked so each case can decide whether a
 * given id is "registered" without touching real registry state.
 */

const mockGetCondition = jest.fn();

jest.mock( '@wordpress/hooks', () => ( { addFilter: jest.fn() } ), {
	virtual: true,
} );

jest.mock(
	'@wordpress/compose',
	() => ( {
		// `createHigherOrderComponent` only adds a displayName in real
		// usage; for the test we just hand the wrapper back unchanged so
		// we can call it directly.
		createHigherOrderComponent: ( hoc ) => hoc,
	} ),
	{ virtual: true }
);

jest.mock( '../store/conditions-registry', () => ( {
	getCondition: ( ...args ) => mockGetCondition( ...args ),
} ) );

if ( typeof global.TextEncoder === 'undefined' ) {
	// eslint-disable-next-line @typescript-eslint/no-var-requires
	const util = require( 'util' );
	global.TextEncoder = util.TextEncoder;
	global.TextDecoder = util.TextDecoder;
}

const React = require( 'react' );
const { renderToStaticMarkup } = require( 'react-dom/server' );
const { withRuleIndicator } = require( '../editor-indicator' );

// Fake block-list block: renders its className into a data attribute so
// the test can assert on the markup string. Using `data-class` instead
// of `className` keeps the fixture div's own class attribute out of the
// way when we want to detect "no className".
const FakeBlockListBlock = ( props ) =>
	React.createElement( 'div', { 'data-class': props.className || '' } );

const Wrapped = withRuleIndicator( FakeBlockListBlock );

const render = ( attributes, extraProps = {} ) =>
	renderToStaticMarkup(
		React.createElement( Wrapped, { attributes, ...extraProps } )
	);

describe( 'withRuleIndicator', () => {
	beforeEach( () => {
		mockGetCondition.mockReset();
	} );

	it( 'leaves className unchanged when blockWhen attribute is absent', () => {
		const html = render( {}, { className: 'wp-block' } );

		expect( html ).toBe( '<div data-class="wp-block"></div>' );
		expect( mockGetCondition ).not.toHaveBeenCalled();
	} );

	it( 'adds has-block-when-rule when blockWhen has a registered conditionId', () => {
		mockGetCondition.mockReturnValue( {
			id: 'user_state',
			label: 'User state',
		} );

		const html = render(
			{ blockWhen: { conditionId: 'user_state', settings: {} } },
			{ className: 'wp-block' }
		);

		expect( html ).toBe(
			'<div data-class="wp-block has-block-when-rule"></div>'
		);
		expect( mockGetCondition ).toHaveBeenCalledWith( 'user_state' );
	} );

	it( 'leaves className unchanged when conditionId is not registered', () => {
		mockGetCondition.mockReturnValue( undefined );

		const html = render(
			{ blockWhen: { conditionId: 'orphaned_id', settings: {} } },
			{ className: 'wp-block' }
		);

		expect( html ).toBe( '<div data-class="wp-block"></div>' );
		expect( mockGetCondition ).toHaveBeenCalledWith( 'orphaned_id' );
	} );

	it( 'leaves className unchanged when blockWhen has no conditionId field', () => {
		const html = render(
			{ blockWhen: { settings: {} } },
			{ className: 'wp-block' }
		);

		expect( html ).toBe( '<div data-class="wp-block"></div>' );
		expect( mockGetCondition ).not.toHaveBeenCalled();
	} );
} );
