<?php
/**
 * Core plugin bootstrap.
 *
 * @package WHCU
 */

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates activation, rewrite rules, and component wiring.
 */
class WHCU_Plugin {

	const OPTION_KEYWORD  = 'whcu_keyword';
	const OPTION_SLUG     = 'whcu_slug';
	const OPTION_LAST_HIT = 'whcu_last_hit';
	const DEFAULT_SLUG    = 'healthcheck';
	const QUERY_VAR       = 'whcu_healthcheck';
	const WEBSITE_URL     = 'https://637digital.com';
	const HELP_URL        = 'https://637digital.com/wiki/';
	const GITHUB_URL      = 'https://github.com/637-Digital/uptime-robot-for-wp';
	const AUTHOR_NAME     = '637 Digital Solutions';

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @var WHCU_Endpoint
	 */
	public $endpoint;

	/**
	 * @var WHCU_Settings
	 */
	public $settings;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Wire frontend and admin components.
	 */
	public function init() {
		load_plugin_textdomain( 'wp-health-check-uptimerobot', false, dirname( plugin_basename( WHCU_PLUGIN_FILE ) ) . '/languages' );

		$this->endpoint = new WHCU_Endpoint();
		$this->endpoint->init();

		if ( is_admin() ) {
			$this->settings = new WHCU_Settings();
			$this->settings->init();
		}

		add_action( 'wp_initialize_site', array( $this, 'on_initialize_site' ) );
	}

	/**
	 * Network-aware activation: seed each site and flush rewrites.
	 *
	 * @param bool $network_wide Whether the plugin is being network-activated.
	 */
	public static function activate( $network_wide ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::activate_site();
				restore_current_blog();
			}

			return;
		}

		self::activate_site();
	}

	/**
	 * Seed this site's keyword and flush its rewrite rules.
	 */
	public static function activate_site() {
		if ( ! get_option( self::OPTION_KEYWORD ) ) {
			update_option( self::OPTION_KEYWORD, self::generate_keyword() );
		}

		if ( ! get_option( self::OPTION_SLUG ) ) {
			update_option( self::OPTION_SLUG, self::DEFAULT_SLUG );
		}

		self::add_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Flush rewrite rules on deactivation without deleting site options.
	 *
	 * @param bool $network_wide Whether the plugin is being network-deactivated.
	 */
	public static function deactivate( $network_wide ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				flush_rewrite_rules();
				restore_current_blog();
			}

			return;
		}

		flush_rewrite_rules();
	}

	/**
	 * Seed a newly created site when the plugin is network-activated.
	 *
	 * @param WP_Site $site New site object.
	 */
	public function on_initialize_site( $site ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active_for_network( plugin_basename( WHCU_PLUGIN_FILE ) ) ) {
			return;
		}

		switch_to_blog( (int) $site->blog_id );
		self::activate_site();
		restore_current_blog();
	}

	/**
	 * Register the public health-check rewrite rule for the current site.
	 */
	public static function add_rewrite_rules() {
		$slug = self::get_slug();
		add_rewrite_rule( '^' . preg_quote( $slug, '/' ) . '/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	/**
	 * Current site's endpoint slug.
	 *
	 * @return string
	 */
	public static function get_slug() {
		$slug = get_option( self::OPTION_SLUG, self::DEFAULT_SLUG );
		$slug = sanitize_title( (string) $slug );

		if ( '' === $slug ) {
			$slug = self::DEFAULT_SLUG;
		}

		return $slug;
	}

	/**
	 * Current site's monitor keyword.
	 *
	 * @return string
	 */
	public static function get_keyword() {
		return (string) get_option( self::OPTION_KEYWORD, '' );
	}

	/**
	 * Pretty URL UptimeRobot should poll.
	 *
	 * @return string
	 */
	public static function get_monitor_url() {
		return home_url( '/' . self::get_slug() . '/' );
	}

	/**
	 * Query-string fallback if pretty permalinks are off or not flushed.
	 *
	 * @return string
	 */
	public static function get_fallback_url() {
		return add_query_arg( self::QUERY_VAR, '1', home_url( '/' ) );
	}

	/**
	 * Alphanumeric keyword that UptimeRobot can match reliably.
	 *
	 * @return string
	 */
	public static function generate_keyword() {
		return 'whcu_' . wp_generate_password( 24, false, false );
	}
}
