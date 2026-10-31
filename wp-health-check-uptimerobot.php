<?php
/**
 * Plugin Name:       WP Health Check for UptimeRobot
 * Plugin URI:        https://github.com/637-Digital/uptime-robot-for-wp
 * Description:       A 637 Digital Solutions plugin that exposes a per-site keyword URL for UptimeRobot keyword monitors. Network-activateable, with the keyword configured on each site.
 * Version:           1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            637 Digital Solutions
 * Author URI:        https://637digital.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       wp-health-check-uptimerobot
 *
 * @package WHCU
 */

defined( 'ABSPATH' ) || exit;

define( 'WHCU_VERSION', '1.0' );
define( 'WHCU_PLUGIN_FILE', __FILE__ );
define( 'WHCU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WHCU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WHCU_PLUGIN_DIR . 'includes/class-plugin.php';
require_once WHCU_PLUGIN_DIR . 'includes/class-endpoint.php';
require_once WHCU_PLUGIN_DIR . 'includes/class-settings.php';

register_activation_hook( __FILE__, array( 'WHCU_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WHCU_Plugin', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		WHCU_Plugin::instance()->init();
	}
);
