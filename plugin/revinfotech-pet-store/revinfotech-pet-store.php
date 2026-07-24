<?php
/**
 * Plugin Name:       Revinfotech Pet Store
 * Description:       Displays pet data from the Swagger Petstore API in an Elementor widget, with admin-configurable settings and transient caching.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Revinfotech
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       revinfotech-pet-store
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

define( 'RPS_VERSION', '1.0.0' );
define( 'RPS_PATH', plugin_dir_path( __FILE__ ) );
define( 'RPS_URL', plugin_dir_url( __FILE__ ) );
define( 'RPS_BASENAME', plugin_basename( __FILE__ ) );

require_once RPS_PATH . 'includes/class-rps-api-client.php';
require_once RPS_PATH . 'includes/class-rps-settings.php';
require_once RPS_PATH . 'includes/class-rps-elementor-loader.php';
require_once RPS_PATH . 'includes/class-rps-plugin.php';

register_activation_hook( __FILE__, array( 'RPS_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RPS_Plugin', 'deactivate' ) );

RPS_Plugin::instance();
