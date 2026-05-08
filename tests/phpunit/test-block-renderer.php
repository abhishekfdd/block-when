<?php
/**
 * Tests for {@see Block_When\Block_Renderer}.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Tests;

use WP_UnitTestCase;

defined( 'ABSPATH' ) || exit;

/**
 * Renderer behaviour: hidden blocks return empty string, visible blocks
 * pass through, malformed `blockWhen` attribute is treated as visible,
 * Query Loop context is honoured, parent/child nesting works.
 */
final class Test_Block_Renderer extends WP_UnitTestCase {

	// Implementation deferred.
}
