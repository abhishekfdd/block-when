<?php
/**
 * Block attribute extender.
 *
 * Hooks `register_block_type_args` to inject the `renderWhen` attribute
 * schema into every registered block, server-side, so the editor JS can
 * read and write it without each block declaring the attribute itself —
 * and, just as importantly, so Gutenberg's block validation does not
 * strip it from the saved post markup.
 *
 * Without this class running, {@see RenderWhen\Block_Renderer} would
 * have no attribute to read at render time.
 *
 * @package RenderWhen
 */

declare( strict_types=1 );

namespace RenderWhen;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the `renderWhen` attribute to every block type.
 */
final class Attribute_Extender {

	/**
	 * Register hooks.
	 *
	 * Idempotent: re-calling does not double-register the filter, so a
	 * second `Plugin::init()` call (e.g. accidental re-bootstrap or a
	 * test harness exercising the wiring twice) cannot make the filter
	 * fire twice per block type.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( false !== has_filter( 'register_block_type_args', array( $this, 'filter_register_block_type_args' ) ) ) {
			return;
		}

		add_filter( 'register_block_type_args', array( $this, 'filter_register_block_type_args' ), 10, 2 );
	}

	/**
	 * Filter callback for `register_block_type_args`.
	 *
	 * Adds the `renderWhen` attribute schema to every block type. If a
	 * block author has somehow already declared an attribute by the same
	 * name (vanishingly unlikely — `renderWhen` is camelCased and reserved
	 * for this plugin) our schema wins. The renderer reads the attribute
	 * by name and would silently break against a foreign shape, so a
	 * deterministic overwrite is safer than a merge.
	 *
	 * @param array<string, mixed> $args       Block type arguments.
	 * @param string               $block_type Block type name. Unused — every block gets the attribute.
	 * @return array<string, mixed> Filtered arguments with the `renderWhen` attribute injected.
	 */
	public function filter_register_block_type_args( array $args, string $block_type ): array {
		unset( $block_type );

		$attributes = isset( $args['attributes'] ) && is_array( $args['attributes'] )
			? $args['attributes']
			: array();

		// Last-write-wins: any pre-existing `renderWhen` entry is replaced. See docblock.
		$attributes['renderWhen'] = array(
			'type'       => 'object',
			'default'    => null,
			'properties' => array(
				'conditionId' => array(
					'type' => 'string',
				),
				'settings'    => array(
					'type' => 'object',
				),
			),
		);

		$args['attributes'] = $attributes;

		return $args;
	}
}
