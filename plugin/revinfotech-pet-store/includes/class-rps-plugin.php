<?php
/**
 * Core plugin bootstrap: wires up settings, Elementor integration, and
 * activation/deactivation lifecycle.
 *
 * @package Revinfotech_Pet_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RPS_Plugin {

	/**
	 * @var RPS_Plugin|null
	 */
	private static $instance = null;

	/**
	 * @return RPS_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		load_plugin_textdomain( 'revinfotech-pet-store', false, dirname( RPS_BASENAME ) . '/languages' );

		new RPS_Settings();
		new RPS_Elementor_Loader();
	}

	/**
	 * Runs on plugin activation. Sets sane defaults for options that don't
	 * exist yet; does not overwrite existing configuration on reactivation.
	 */
	public static function activate() {
		if ( false === get_option( 'rps_settings' ) ) {
			add_option( 'rps_settings', RPS_Settings::get_default_settings() );
		}
	}

	/**
	 * Runs on plugin deactivation. Clears any cached API responses so a
	 * later reactivation starts from a clean cache rather than serving
	 * stale data from a previous configuration.
	 */
	public static function deactivate() {
		RPS_API_Client::clear_cache();
	}
}
