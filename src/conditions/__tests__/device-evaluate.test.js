/**
 * Tests for `evaluate()` in `conditions/device.js`.
 *
 * Mirrors `tests/phpunit/conditions/test-device-condition.php`: empty
 * list means "no constraint", non-empty list demands membership.
 */

jest.mock( '@wordpress/components', () => ( { CheckboxControl: () => null } ), {
	virtual: true,
} );

jest.mock( '@wordpress/i18n', () => ( { __: ( s ) => s } ), { virtual: true } );

jest.mock( '../../store/conditions-registry', () => ( {
	registerCondition: jest.fn(),
} ) );

const { evaluate } = require( '../device' );

const ctx = ( device ) => ( {
	loggedIn: true,
	role: null,
	device,
} );

describe( 'device evaluate', () => {
	it( 'empty devices list → always visible', () => {
		expect( evaluate( { devices: [] }, ctx( 'desktop' ) ) ).toBe( true );
	} );

	it( 'missing settings → always visible', () => {
		expect( evaluate( {}, ctx( 'mobile' ) ) ).toBe( true );
		expect( evaluate( null, ctx( 'mobile' ) ) ).toBe( true );
	} );

	it( 'matches when audience device is in the list', () => {
		expect( evaluate( { devices: [ 'desktop' ] }, ctx( 'desktop' ) ) ).toBe(
			true
		);
		expect(
			evaluate( { devices: [ 'desktop', 'tablet' ] }, ctx( 'tablet' ) )
		).toBe( true );
	} );

	it( 'does not match when audience device is not in the list', () => {
		expect( evaluate( { devices: [ 'desktop' ] }, ctx( 'mobile' ) ) ).toBe(
			false
		);
		expect(
			evaluate( { devices: [ 'tablet', 'mobile' ] }, ctx( 'desktop' ) )
		).toBe( false );
	} );

	it( 'all-three list matches every device (no-op)', () => {
		const settings = { devices: [ 'desktop', 'tablet', 'mobile' ] };
		expect( evaluate( settings, ctx( 'desktop' ) ) ).toBe( true );
		expect( evaluate( settings, ctx( 'tablet' ) ) ).toBe( true );
		expect( evaluate( settings, ctx( 'mobile' ) ) ).toBe( true );
	} );
} );
