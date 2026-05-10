/**
 * Tests for the DeviceSettings render and write decisions.
 *
 * Same harness as user-state.test.js / date-range.test.js.
 */

const mockCheckboxControl = jest.fn( () => null );

jest.mock(
	'@wordpress/components',
	() => ( {
		CheckboxControl: ( props ) => mockCheckboxControl( props ),
	} ),
	{ virtual: true }
);

jest.mock( '@wordpress/i18n', () => ( { __: ( s ) => s } ), {
	virtual: true,
} );

jest.mock( '../../store/conditions-registry', () => ( {
	registerCondition: jest.fn(),
} ) );

if ( typeof global.TextEncoder === 'undefined' ) {
	// eslint-disable-next-line @typescript-eslint/no-var-requires
	const util = require( 'util' );
	global.TextEncoder = util.TextEncoder;
	global.TextDecoder = util.TextDecoder;
}

const React = require( 'react' );
const { renderToStaticMarkup } = require( 'react-dom/server' );
const { DeviceSettings } = require( '../device' );

const findCallByLabel = ( spy, label ) =>
	spy.mock.calls.find( ( [ props ] ) => props.label === label );

describe( 'DeviceSettings', () => {
	let onChange;

	beforeEach( () => {
		mockCheckboxControl.mockClear();
		onChange = jest.fn();
	} );

	const render = ( settings ) =>
		renderToStaticMarkup(
			React.createElement( DeviceSettings, { settings, onChange } )
		);

	it( 'renders all three checkboxes as checked for the default settings', () => {
		render( { devices: [ 'desktop', 'tablet', 'mobile' ] } );

		expect(
			findCallByLabel( mockCheckboxControl, 'Desktop' )[ 0 ].checked
		).toBe( true );
		expect(
			findCallByLabel( mockCheckboxControl, 'Tablet' )[ 0 ].checked
		).toBe( true );
		expect(
			findCallByLabel( mockCheckboxControl, 'Mobile' )[ 0 ].checked
		).toBe( true );
	} );

	it( 'removes Mobile from settings.devices when its checkbox is toggled off', () => {
		render( { devices: [ 'desktop', 'tablet', 'mobile' ] } );

		const mobile = findCallByLabel( mockCheckboxControl, 'Mobile' );
		mobile[ 0 ].onChange( false );

		expect( onChange ).toHaveBeenCalledWith( {
			devices: [ 'desktop', 'tablet' ],
		} );
	} );

	it( 'leaves settings.devices empty when the last device is toggled off', () => {
		render( { devices: [ 'mobile' ] } );

		const mobile = findCallByLabel( mockCheckboxControl, 'Mobile' );
		mobile[ 0 ].onChange( false );

		expect( onChange ).toHaveBeenCalledWith( { devices: [] } );
	} );

	it( 'preserves canonical order when adding a device back', () => {
		render( { devices: [ 'mobile' ] } );

		const desktop = findCallByLabel( mockCheckboxControl, 'Desktop' );
		desktop[ 0 ].onChange( true );

		expect( onChange ).toHaveBeenCalledWith( {
			devices: [ 'desktop', 'mobile' ],
		} );
	} );
} );
