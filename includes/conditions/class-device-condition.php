<?php
/**
 * Device-type condition.
 *
 * Shows a block when the request's device class matches one of a
 * configured set: desktop, tablet, or mobile. Classification happens
 * server-side, is cached per request, and is filterable so sites can
 * swap in an alternative detection library.
 *
 * @package Block_When
 */

declare( strict_types=1 );

namespace Block_When\Conditions;

defined( 'ABSPATH' ) || exit;

/**
 * Visibility based on the device class of the current request.
 */
final class Device_Condition extends Abstract_Condition {

	/**
	 * Recognised device types.
	 */
	private const DEVICE_TYPES = array( 'desktop', 'tablet', 'mobile' );

	/**
	 * Per-request cached detection result.
	 *
	 * One CLI/web process serves one request, so a static property is
	 * the natural lifetime here — the cache is implicitly invalidated
	 * when the process ends. Tests reset it via reflection.
	 *
	 * @var string|null
	 */
	private static ?string $cached_device = null;

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return 'device';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return __( 'Device type', 'block-when' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * Settings shape: `{ devices: ('desktop'|'tablet'|'mobile')[] }`.
	 * The `items.enum` constraint is the canonical list of recognised
	 * types — values outside it are silently dropped at evaluate time.
	 */
	public function get_schema(): array {
		return array(
			'devices' => array(
				'type'    => 'array',
				'items'   => array(
					'type' => 'string',
					'enum' => self::DEVICE_TYPES,
				),
				'default' => array(),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * Returns true when the current device's class is in the configured
	 * `devices` array. Empty / all-malformed settings resolve to
	 * "always visible" (no constraint), matching the plugin's
	 * graceful-degradation policy.
	 */
	public function evaluate( array $settings, array $context ): bool {
		$settings = $this->sanitize_settings( $settings );
		$devices  = $settings['devices'];

		if ( empty( $devices ) ) {
			return true;
		}

		$allowed = array_values( array_intersect( $devices, self::DEVICE_TYPES ) );
		if ( empty( $allowed ) ) {
			return true;
		}

		return in_array( self::detect_device(), $allowed, true );
	}

	/**
	 * Detect the current request's device class.
	 *
	 * Cached per request via {@see self::$cached_device} — many blocks
	 * on a single page must not redo the work. The cache check sits
	 * before `apply_filters()` so the filter is only invoked on the
	 * cache-miss path.
	 *
	 * @return string One of 'desktop', 'tablet', 'mobile'.
	 */
	private static function detect_device(): string {
		if ( null !== self::$cached_device ) {
			return self::$cached_device;
		}

		/*
		 * VIP-Go's caching sniffs flag the intentional design here:
		 * Block When exists specifically to render device-specific
		 * output server-side, which requires inspecting the User-
		 * Agent and using `wp_is_mobile()`. Sites that use page
		 * caching must vary the cache by device class — a caller
		 * concern, not the condition's. `jetpack_is_mobile()` (the
		 * sniff's recommendation) would impose a Jetpack dependency
		 * this plugin will not take.
		 */
		// phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
		// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.wp_is_mobile_wp_is_mobile

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		if ( self::looks_like_tablet( $user_agent ) ) {
			$detected = 'tablet';
		} elseif ( wp_is_mobile() ) {
			$detected = 'mobile';
		} else {
			$detected = 'desktop';
		}

		// phpcs:enable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
		// phpcs:enable WordPressVIPMinimum.Functions.RestrictedFunctions.wp_is_mobile_wp_is_mobile

		/**
		 * Filter the detected device type before it is cached.
		 *
		 * Lets sites swap in an alternative detection library (e.g.
		 * Mobile_Detect) without subclassing. Result is cached, so
		 * the callback fires at most once per request.
		 *
		 * @param string $detected   One of 'desktop', 'tablet', 'mobile'.
		 * @param string $user_agent The User-Agent string.
		 */
		$detected = apply_filters( 'block_when_device_type', $detected, $user_agent );

		self::$cached_device = is_string( $detected ) ? $detected : 'desktop';

		return self::$cached_device;
	}

	/**
	 * Heuristic tablet detection from the User-Agent string.
	 *
	 * Looks for iPad, the literal "Tablet" keyword, or Android without
	 * the "Mobile" token (Android tablets typically omit "Mobile" from
	 * their UA, while Android phones include it).
	 *
	 * @param string $user_agent User-Agent string.
	 * @return bool
	 */
	private static function looks_like_tablet( string $user_agent ): bool {
		if ( '' === $user_agent ) {
			return false;
		}
		if ( false !== stripos( $user_agent, 'iPad' ) ) {
			return true;
		}
		if ( false !== stripos( $user_agent, 'Tablet' ) ) {
			return true;
		}
		return false !== stripos( $user_agent, 'Android' )
			&& false === stripos( $user_agent, 'Mobile' );
	}
}
