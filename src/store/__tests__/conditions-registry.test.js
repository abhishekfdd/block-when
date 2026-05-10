/**
 * Tests for the JS conditions registry.
 *
 * The registry holds module-level state in a `Map`, so each test must
 * load a fresh copy of the module — `jest.resetModules()` plus `require`
 * is the simplest way to do that without exposing a reset seam in the
 * production code.
 *
 * @package
 */

describe( 'conditions-registry', () => {
	let registry;

	beforeEach( () => {
		jest.resetModules();
		registry = require( '../conditions-registry' );
	} );

	const makeCondition = ( id, overrides = {} ) => ( {
		id,
		label: `Label ${ id }`,
		SettingsComponent: () => null,
		defaultSettings: () => ( {} ),
		...overrides,
	} );

	it( 'stores a registered condition retrievable via getCondition', () => {
		const condition = makeCondition( 'user_state' );

		registry.registerCondition( condition );

		expect( registry.getCondition( 'user_state' ) ).toBe( condition );
	} );

	it( 'throws on duplicate registration', () => {
		registry.registerCondition( makeCondition( 'user_state' ) );

		expect( () =>
			registry.registerCondition( makeCondition( 'user_state' ) )
		).toThrow( /already registered/ );
	} );

	it( 'returns conditions from getAllConditions in registration order', () => {
		const first = makeCondition( 'user_state' );
		const second = makeCondition( 'date_range' );
		const third = makeCondition( 'device' );

		registry.registerCondition( first );
		registry.registerCondition( second );
		registry.registerCondition( third );

		expect( registry.getAllConditions() ).toEqual( [
			first,
			second,
			third,
		] );
	} );

	it( 'returns undefined from getCondition for unknown ids', () => {
		expect( registry.getCondition( 'never_registered' ) ).toBeUndefined();
	} );
} );
