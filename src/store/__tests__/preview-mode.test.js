/**
 * Tests for the preview-mode store.
 *
 * The store hits `@wordpress/data.registerStore` at module load, so we
 * mock the package with a tiny in-memory implementation good enough to
 * exercise reducer + selector behaviour. `jest.resetModules()` between
 * tests gives each case a fresh registration.
 */

describe( 'preview-mode store', () => {
	let registered;
	let store;

	beforeEach( () => {
		jest.resetModules();
		registered = null;

		jest.doMock(
			'@wordpress/data',
			() => ( {
				registerStore: ( name, config ) => {
					registered = { name, config };
					let state = config.reducer( undefined, { type: '@@INIT' } );
					const dispatch = ( action ) => {
						state = config.reducer( state, action );
					};
					return {
						getState: () => state,
						dispatch,
					};
				},
			} ),
			{ virtual: true }
		);

		store = require( '../preview-mode' );
	} );

	const init = () => {
		const config = registered.config;
		let state = config.reducer( undefined, { type: '@@INIT' } );
		const dispatch = ( action ) => {
			state = config.reducer( state, action );
		};
		const select = () => {
			const out = {};
			for ( const [ key, fn ] of Object.entries( config.selectors ) ) {
				out[ key ] = ( ...args ) => fn( state, ...args );
			}
			return out;
		};
		return { dispatch, select, getState: () => state, config };
	};

	it( 'registers under the documented store name', () => {
		expect( registered.name ).toBe( 'renderwhen/preview-mode' );
		expect( store.STORE_NAME ).toBe( 'renderwhen/preview-mode' );
	} );

	it( 'exposes the expected default state', () => {
		const { select } = init();
		expect( select().isPreviewActive() ).toBe( false );
		expect( select().getAudience() ).toEqual( {
			loggedIn: true,
			role: null,
			device: 'desktop',
		} );
	} );

	it( 'setPreviewActive toggles the active flag', () => {
		const { dispatch, select, config } = init();
		dispatch( config.actions.setPreviewActive( true ) );
		expect( select().isPreviewActive() ).toBe( true );
		dispatch( config.actions.setPreviewActive( false ) );
		expect( select().isPreviewActive() ).toBe( false );
	} );

	it( 'setPreviewActive coerces the value to a boolean', () => {
		const { dispatch, select, config } = init();
		dispatch( config.actions.setPreviewActive( 'yes' ) );
		expect( select().isPreviewActive() ).toBe( true );
	} );

	it( 'setAudience merges a partial patch into the audience', () => {
		const { dispatch, select, config } = init();
		dispatch( config.actions.setAudience( { loggedIn: false } ) );
		expect( select().getAudience() ).toEqual( {
			loggedIn: false,
			role: null,
			device: 'desktop',
		} );
		dispatch( config.actions.setAudience( { role: 'editor' } ) );
		expect( select().getAudience() ).toEqual( {
			loggedIn: false,
			role: 'editor',
			device: 'desktop',
		} );
	} );

	it( 'setAudience handles a missing patch without throwing', () => {
		const { dispatch, select, config } = init();
		dispatch( config.actions.setAudience() );
		expect( select().getAudience() ).toEqual( {
			loggedIn: true,
			role: null,
			device: 'desktop',
		} );
	} );

	it( 'setPreviewActive and setAudience are independent', () => {
		const { dispatch, select, config } = init();
		dispatch( config.actions.setAudience( { device: 'mobile' } ) );
		expect( select().isPreviewActive() ).toBe( false );
		dispatch( config.actions.setPreviewActive( true ) );
		expect( select().getAudience().device ).toBe( 'mobile' );
	} );
} );
