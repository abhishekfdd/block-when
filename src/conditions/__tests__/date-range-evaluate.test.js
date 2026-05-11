/**
 * Tests for `evaluate()` in `conditions/date-range.js`.
 *
 * `previewContext.now` is always passed explicitly so the tests are
 * deterministic. Bounds are local-time `Y-m-d H:i:s` strings and the
 * Date objects we pass for `now` are constructed in local time too
 * (via the multi-arg Date constructor) so the comparison is unaffected
 * by the test host's timezone offset.
 */

jest.mock(
	'@wordpress/components',
	() => ( { CheckboxControl: () => null, DateTimePicker: () => null } ),
	{ virtual: true }
);

jest.mock( '@wordpress/i18n', () => ( { __: ( s ) => s } ), { virtual: true } );

jest.mock( '../../store/conditions-registry', () => ( {
	registerCondition: jest.fn(),
} ) );

const { evaluate } = require( '../date-range' );

const at = ( y, mo, d, h = 0, mi = 0, s = 0 ) =>
	new Date( y, mo - 1, d, h, mi, s );

const baseCtx = ( now ) => ( {
	loggedIn: true,
	role: null,
	device: 'desktop',
	now,
} );

describe( 'date-range evaluate', () => {
	it( 'both bounds null → always visible', () => {
		expect(
			evaluate( { start: null, end: null }, baseCtx( at( 2026, 5, 10 ) ) )
		).toBe( true );
	} );

	it( 'missing settings → always visible', () => {
		expect( evaluate( {}, baseCtx( at( 2026, 5, 10 ) ) ) ).toBe( true );
		expect( evaluate( null, baseCtx( at( 2026, 5, 10 ) ) ) ).toBe( true );
	} );

	it( 'now inside [start, end] → visible', () => {
		expect(
			evaluate(
				{ start: '2026-01-01 00:00:00', end: '2026-12-31 23:59:59' },
				baseCtx( at( 2026, 6, 15, 12 ) )
			)
		).toBe( true );
	} );

	it( 'now before start → hidden', () => {
		expect(
			evaluate(
				{ start: '2026-06-01 00:00:00', end: null },
				baseCtx( at( 2026, 5, 31, 23, 59, 59 ) )
			)
		).toBe( false );
	} );

	it( 'now after end → hidden', () => {
		expect(
			evaluate(
				{ start: null, end: '2026-06-01 00:00:00' },
				baseCtx( at( 2026, 6, 1, 0, 0, 1 ) )
			)
		).toBe( false );
	} );

	it( 'open-ended end → visible at any time after start', () => {
		expect(
			evaluate(
				{ start: '2020-01-01 00:00:00', end: null },
				baseCtx( at( 2099, 12, 31 ) )
			)
		).toBe( true );
	} );

	it( 'open-ended start → visible at any time before end', () => {
		expect(
			evaluate(
				{ start: null, end: '2099-12-31 23:59:59' },
				baseCtx( at( 1999, 1, 1 ) )
			)
		).toBe( true );
	} );

	it( 'now equals start (inclusive lower bound) → visible', () => {
		expect(
			evaluate(
				{ start: '2026-05-10 14:30:00', end: null },
				baseCtx( at( 2026, 5, 10, 14, 30, 0 ) )
			)
		).toBe( true );
	} );

	it( 'now equals end (inclusive upper bound) → visible', () => {
		expect(
			evaluate(
				{ start: null, end: '2026-05-10 14:30:00' },
				baseCtx( at( 2026, 5, 10, 14, 30, 0 ) )
			)
		).toBe( true );
	} );

	it( 'malformed start → treated as open-ended on that side', () => {
		expect(
			evaluate(
				{ start: 'not-a-date', end: '2026-06-01 00:00:00' },
				baseCtx( at( 2020, 1, 1 ) )
			)
		).toBe( true );
	} );

	it( 'empty-string bounds → treated as both null (always visible)', () => {
		expect(
			evaluate( { start: '', end: '' }, baseCtx( at( 2026, 5, 10 ) ) )
		).toBe( true );
	} );
} );
