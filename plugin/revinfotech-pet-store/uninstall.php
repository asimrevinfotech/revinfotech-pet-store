<?php
/**
 * Fires when the plugin is deleted from the Plugins screen. Removes all
 * options and cached transients this plugin created.
 *
 * @package Revinfotech_Pet_Store
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$cache_keys = get_option( 'rps_cache_keys', array() );

if ( is_array( $cache_keys ) ) {
	foreach ( $cache_keys as $cache_key ) {
		delete_transient( $cache_key );
		delete_option( $cache_key . '_fb' );
	}
}

delete_option( 'rps_cache_keys' );
delete_option( 'rps_settings' );
