<?php
/**
 * Fetches pet data from the configured Petstore-compatible API, with
 * transient caching and a persistent stale-data fallback for when the
 * live request fails.
 *
 * @package Revinfotech_Pet_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RPS_API_Client {

	const STATUSES = array( 'available', 'pending', 'sold' );

	/**
	 * Returns a normalized list of pets for the given status, using the
	 * cache when available and falling back to the last known-good
	 * response if a live fetch fails.
	 *
	 * @param string $status One of self::STATUSES, or '' to use the
	 *                       configured default.
	 * @return array|WP_Error Array of pets (each: name, category, status,
	 *                        image) or WP_Error on failure with no
	 *                        fallback available.
	 */
	public static function get_pets( $status = '' ) {
		$settings = RPS_Settings::get_settings();

		if ( ! in_array( $status, self::STATUSES, true ) ) {
			$status = $settings['default_status'];
		}

		$api_base_url = untrailingslashit( $settings['api_base_url'] );
		$cache_key    = self::get_cache_key( $api_base_url, $status );

		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$pets = self::fetch_from_api( $api_base_url, $status );

		if ( is_wp_error( $pets ) ) {
			$fallback = get_option( self::get_fallback_key( $cache_key ), false );
			if ( is_array( $fallback ) && ! empty( $fallback ) ) {
				return $fallback;
			}

			return $pets;
		}

		$ttl = max( 1, absint( $settings['cache_minutes'] ) ) * MINUTE_IN_SECONDS;
		set_transient( $cache_key, $pets, $ttl );
		update_option( self::get_fallback_key( $cache_key ), $pets, false );
		self::register_cache_key( $cache_key );

		return $pets;
	}

	/**
	 * Performs the live HTTP request and normalizes the response.
	 *
	 * @return array|WP_Error
	 */
	private static function fetch_from_api( $api_base_url, $status ) {
		if ( empty( $api_base_url ) || ! wp_http_validate_url( $api_base_url ) ) {
			return new WP_Error(
				'rps_invalid_api_url',
				__( 'The configured Pet Store API URL is invalid. Please check it in Pet Store Settings.', 'revinfotech-pet-store' )
			);
		}

		$url = add_query_arg(
			array( 'status' => $status ),
			trailingslashit( $api_base_url ) . 'pet/findByStatus'
		);

		$response = wp_remote_get(
			esc_url_raw( $url ),
			array(
				'timeout' => 15,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'rps_request_failed',
				__( 'Could not reach the Pet Store API. Please check the API URL in Pet Store Settings and your site\'s outbound connectivity.', 'revinfotech-pet-store' )
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'rps_bad_response',
				sprintf(
					/* translators: %d: HTTP status code returned by the API. */
					__( 'The Pet Store API returned an unexpected response (HTTP %d).', 'revinfotech-pet-store' ),
					(int) $code
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'rps_invalid_json',
				__( 'The Pet Store API returned data in an unexpected format.', 'revinfotech-pet-store' )
			);
		}

		$pets = array();
		foreach ( $data as $item ) {
			$normalized = self::normalize_pet( $item );
			if ( null !== $normalized ) {
				$pets[] = $normalized;
			}
		}

		return $pets;
	}

	/**
	 * Defensively extracts and sanitizes only the fields this plugin
	 * displays, so malformed or unexpected API payloads never leak
	 * unescaped data downstream.
	 *
	 * @return array{name: string, category: string, status: string, image: string}|null
	 */
	private static function normalize_pet( $item ) {
		if ( ! is_array( $item ) || empty( $item['name'] ) || ! is_scalar( $item['name'] ) ) {
			return null;
		}

		$image = '';
		if ( ! empty( $item['photoUrls'] ) && is_array( $item['photoUrls'] ) ) {
			$first_image = reset( $item['photoUrls'] );
			if ( is_string( $first_image ) && wp_http_validate_url( $first_image ) ) {
				$image = esc_url_raw( $first_image );
			}
		}

		return array(
			'name'     => sanitize_text_field( (string) $item['name'] ),
			'category' => isset( $item['category']['name'] ) && is_scalar( $item['category']['name'] )
				? sanitize_text_field( (string) $item['category']['name'] )
				: '',
			'status'   => isset( $item['status'] ) && is_scalar( $item['status'] )
				? sanitize_text_field( (string) $item['status'] )
				: '',
			'image'    => $image,
		);
	}

	private static function get_cache_key( $api_base_url, $status ) {
		return 'rps_p_' . md5( $api_base_url . '|' . $status );
	}

	private static function get_fallback_key( $cache_key ) {
		return $cache_key . '_fb';
	}

	private static function register_cache_key( $cache_key ) {
		$keys = get_option( 'rps_cache_keys', array() );
		if ( ! is_array( $keys ) ) {
			$keys = array();
		}

		if ( ! in_array( $cache_key, $keys, true ) ) {
			$keys[] = $cache_key;
			update_option( 'rps_cache_keys', $keys, false );
		}
	}

	/**
	 * Deletes every cached API response (transient + fallback copy)
	 * tracked by the plugin. Used by the admin "Clear Cache" action and
	 * on plugin deactivation.
	 */
	public static function clear_cache() {
		$keys = get_option( 'rps_cache_keys', array() );

		if ( is_array( $keys ) ) {
			foreach ( $keys as $cache_key ) {
				delete_transient( $cache_key );
				delete_option( self::get_fallback_key( $cache_key ) );
			}
		}

		delete_option( 'rps_cache_keys' );
	}
}
