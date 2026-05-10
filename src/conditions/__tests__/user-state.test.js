/**
 * Tests for the UserStateSettings render decisions.
 *
 * The component renders FormTokenField only when the persisted `states`
 * represents "Specific roles" mode. We mock the `@wordpress/*` packages
 * because they aren't installed locally — the production build treats
 * them as externals — and assert against a spy on the mocked component.
 * `react-dom/server.renderToStaticMarkup` is sufficient: the only hook
 * in play is `useMemo`, which works under server rendering, and we
 * don't need event simulation here.
 */

// Names must start with `mock` so jest.mock's factory (hoisted by Babel
// above all imports) is allowed to reference them.
const mockFormTokenField = jest.fn( () => null );
const mockSelectControl = jest.fn( () => null );

// `virtual: true` because the production build treats these as externals
// (resolved at runtime against the WP-provided globals), so they aren't
// installed in node_modules and Jest can't resolve them otherwise.
jest.mock(
	'@wordpress/components',
	() => ( {
		SelectControl: ( props ) => mockSelectControl( props ),
		FormTokenField: ( props ) => mockFormTokenField( props ),
	} ),
	{ virtual: true }
);

jest.mock( '@wordpress/i18n', () => ( { __: ( s ) => s } ), {
	virtual: true,
} );

jest.mock(
	'@wordpress/element',
	() => {
		const react = jest.requireActual( 'react' );
		return { useMemo: react.useMemo };
	},
	{ virtual: true }
);

jest.mock( '../../store/conditions-registry', () => ( {
	registerCondition: jest.fn(),
} ) );

// jsdom (the default test environment) doesn't expose TextEncoder, but
// react-dom/server resolves to its browser build here and pulls it in
// at module load. Polyfill from Node's `util` before requiring it.
if ( typeof global.TextEncoder === 'undefined' ) {
	// eslint-disable-next-line @typescript-eslint/no-var-requires
	const util = require( 'util' );
	global.TextEncoder = util.TextEncoder;
	global.TextDecoder = util.TextDecoder;
}

const React = require( 'react' );
const { renderToStaticMarkup } = require( 'react-dom/server' );
const { UserStateSettings } = require( '../user-state' );

describe( 'UserStateSettings', () => {
	beforeEach( () => {
		mockFormTokenField.mockClear();
		mockSelectControl.mockClear();
	} );

	const render = ( settings ) =>
		renderToStaticMarkup(
			React.createElement( UserStateSettings, {
				settings,
				onChange: jest.fn(),
			} )
		);

	it( 'renders FormTokenField when states is empty (Specific roles, none added yet)', () => {
		render( { states: [] } );
		expect( mockFormTokenField ).toHaveBeenCalled();
	} );

	it( 'renders FormTokenField with the correct token when a role is set', () => {
		render( { states: [ 'role:editor' ] } );
		expect( mockFormTokenField ).toHaveBeenCalled();
		expect( mockFormTokenField.mock.calls[ 0 ][ 0 ].value ).toEqual( [
			'Editor',
		] );
	} );

	it( 'does not render FormTokenField for logged_in mode', () => {
		render( { states: [ 'logged_in' ] } );
		expect( mockFormTokenField ).not.toHaveBeenCalled();
	} );

	it( 'does not render FormTokenField for logged_out mode', () => {
		render( { states: [ 'logged_out' ] } );
		expect( mockFormTokenField ).not.toHaveBeenCalled();
	} );
} );
