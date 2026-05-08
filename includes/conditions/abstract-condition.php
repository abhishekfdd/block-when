<?php
/**
 * Abstract base class for conditions.
 *
 * Provides shared scaffolding so most conditions only have to implement
 * `evaluate()` and declare their id, label, and schema.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Conditions;

defined( 'ABSPATH' ) || exit;

/**
 * Convenience base class for conditions.
 */
abstract class Abstract_Condition implements Interface_Condition {

	/**
	 * {@inheritDoc}
	 */
	abstract public function get_id(): string;

	/**
	 * {@inheritDoc}
	 */
	abstract public function get_label(): string;

	/**
	 * {@inheritDoc}
	 */
	abstract public function get_schema(): array;

	/**
	 * {@inheritDoc}
	 */
	abstract public function evaluate( array $settings, array $context ): bool;
}
