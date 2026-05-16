<?php
/**
 * Abstract base class for conditions.
 *
 * Provides shared scaffolding so most conditions only have to declare
 * their id, label, optional schema, and the body of `evaluate()`. The
 * base class also offers a `sanitize_settings()` helper that subclasses
 * should call at the top of `evaluate()` to coerce the incoming
 * `$settings` array against the schema before reading from it.
 *
 * @package RenderWhen
 */

declare( strict_types=1 );

namespace RenderWhen\Conditions;

defined( 'ABSPATH' ) || exit;

/**
 * Convenience base class for conditions.
 *
 * Concrete subclasses must implement {@see get_id()}, {@see get_label()},
 * and {@see evaluate()}. They MAY override {@see get_schema()} when the
 * condition takes per-block settings; the default is an empty schema,
 * which suits conditions whose only signal is the request itself
 * (e.g. "user is logged in" with no further options).
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
	 *
	 * Default implementation returns an empty schema. Subclasses with
	 * per-block settings should override this and return a block-
	 * attribute-style schema — see Interface_Condition::get_schema()
	 * for the expected shape.
	 */
	public function get_schema(): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	abstract public function evaluate( array $settings, array $context ): bool;

	/**
	 * Coerce raw per-block settings against the condition's schema.
	 *
	 * Subclasses should call this at the top of `evaluate()` rather
	 * than reading from `$settings` directly. The returned array is
	 * guaranteed to:
	 *
	 *  - contain every key declared in the schema, with its declared
	 *    default applied when the input is missing the key;
	 *  - have each value coerced to the schema-declared type;
	 *  - have any value that fails an `enum` constraint replaced with
	 *    the declared default;
	 *  - drop any keys that are not declared in the schema.
	 *
	 * Malformed input is normalised, never thrown on — the policy is
	 * "always visible on bad data," which lives at the renderer level.
	 *
	 * @param array<string, mixed> $settings Raw settings as supplied by the renderer.
	 * @return array<string, mixed> Sanitised settings, safe to read from.
	 */
	protected function sanitize_settings( array $settings ): array {
		$schema = $this->get_schema();
		$clean  = array();

		foreach ( $schema as $key => $rules ) {
			if ( ! is_array( $rules ) ) {
				continue;
			}

			$default = array_key_exists( 'default', $rules )
				? $rules['default']
				: $this->default_for_type( $rules['type'] ?? 'string' );

			$value = array_key_exists( $key, $settings )
				? $this->coerce_value( $settings[ $key ], $rules )
				: $default;

			if (
				isset( $rules['enum'] ) && is_array( $rules['enum'] )
				&& ! in_array( $value, $rules['enum'], true )
			) {
				$value = $default;
			}

			$clean[ $key ] = $value;
		}

		return $clean;
	}

	/**
	 * Coerce a single value to its schema-declared type.
	 *
	 * Recurses into `items` for arrays. Unknown types pass through
	 * unchanged so future schema extensions don't break older bases.
	 *
	 * @param mixed                $value Raw input value.
	 * @param array<string, mixed> $rules Schema entry for this value.
	 * @return mixed Coerced value.
	 */
	private function coerce_value( $value, array $rules ) {
		$type = $rules['type'] ?? 'string';

		switch ( $type ) {
			case 'string':
				return is_scalar( $value ) ? (string) $value : '';

			case 'integer':
				return is_scalar( $value ) ? (int) $value : 0;

			case 'number':
				return is_scalar( $value ) ? (float) $value : 0.0;

			case 'boolean':
				return rest_sanitize_boolean( $value );

			case 'array':
				if ( ! is_array( $value ) ) {
					return array();
				}
				$items = $rules['items'] ?? null;
				if ( is_array( $items ) ) {
					$coerced = array();
					foreach ( $value as $item ) {
						$coerced[] = $this->coerce_value( $item, $items );
					}
					return $coerced;
				}
				return array_values( $value );

			case 'object':
				return is_array( $value ) ? $value : array();

			default:
				return $value;
		}
	}

	/**
	 * Type-appropriate empty value, used when a schema entry omits `default`.
	 *
	 * @param string $type Schema type name.
	 * @return mixed
	 */
	private function default_for_type( string $type ) {
		switch ( $type ) {
			case 'integer':
				return 0;

			case 'number':
				return 0.0;

			case 'boolean':
				return false;

			case 'array':
			case 'object':
				return array();

			case 'string':
			default:
				return '';
		}
	}
}
