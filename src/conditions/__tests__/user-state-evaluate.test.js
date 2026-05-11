/**
 * Tests for `evaluate()` in `conditions/user-state.js`.
 *
 * Covers the OR semantics of the persisted `states` array and the
 * three vocabulary entries (`logged_in`, `logged_out`, `role:<slug>`).
 * Mirrors the PHP-side test cases in
 * `tests/phpunit/conditions/test-user-state-condition.php`.
 */

jest.mock(
	'@wordpress/components',
	() => ( { SelectControl: () => null, FormTokenField: () => null } ),
	{ virtual: true }
);

jest.mock( '@wordpress/i18n', () => ( { __: ( s ) => s } ), { virtual: true } );

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

const { evaluate } = require( '../user-state' );

const ctx = ( overrides = {} ) => ( {
	loggedIn: true,
	role: 'administrator',
	device: 'desktop',
	...overrides,
} );

describe( 'user-state evaluate', () => {
	it( 'returns true on empty states (always visible)', () => {
		expect( evaluate( { states: [] }, ctx() ) ).toBe( true );
	} );

	it( 'returns true on missing settings (always visible)', () => {
		expect( evaluate( {}, ctx() ) ).toBe( true );
		expect( evaluate( null, ctx() ) ).toBe( true );
	} );

	it( 'matches logged_in when audience is logged in', () => {
		expect( evaluate( { states: [ 'logged_in' ] }, ctx() ) ).toBe( true );
	} );

	it( 'does not match logged_in when audience is logged out', () => {
		expect(
			evaluate( { states: [ 'logged_in' ] }, ctx( { loggedIn: false } ) )
		).toBe( false );
	} );

	it( 'matches logged_out when audience is logged out', () => {
		expect(
			evaluate( { states: [ 'logged_out' ] }, ctx( { loggedIn: false } ) )
		).toBe( true );
	} );

	it( 'does not match logged_out when audience is logged in', () => {
		expect( evaluate( { states: [ 'logged_out' ] }, ctx() ) ).toBe( false );
	} );

	it( 'matches role:X when audience role equals X', () => {
		expect(
			evaluate( { states: [ 'role:editor' ] }, ctx( { role: 'editor' } ) )
		).toBe( true );
	} );

	it( 'does not match role:X when audience role differs', () => {
		expect(
			evaluate(
				{ states: [ 'role:editor' ] },
				ctx( { role: 'administrator' } )
			)
		).toBe( false );
	} );

	it( 'does not match role:X when audience is logged out', () => {
		expect(
			evaluate(
				{ states: [ 'role:editor' ] },
				ctx( { loggedIn: false, role: 'editor' } )
			)
		).toBe( false );
	} );

	it( 'does not match role:X when audience role is null (Any role)', () => {
		expect(
			evaluate( { states: [ 'role:editor' ] }, ctx( { role: null } ) )
		).toBe( false );
	} );

	it( 'OR-combines multiple states (any match wins)', () => {
		const settings = { states: [ 'role:editor', 'role:author' ] };
		expect( evaluate( settings, ctx( { role: 'author' } ) ) ).toBe( true );
		expect( evaluate( settings, ctx( { role: 'editor' } ) ) ).toBe( true );
		expect( evaluate( settings, ctx( { role: 'subscriber' } ) ) ).toBe(
			false
		);
	} );

	it( 'ignores empty / non-string entries in the array', () => {
		const settings = { states: [ '', null, 'role:', 'logged_in' ] };
		expect( evaluate( settings, ctx() ) ).toBe( true );
	} );
} );
