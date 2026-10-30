<?php
/**
 * Public health-check endpoint.
 *
 * @package WHCU
 */

defined( 'ABSPATH' ) || exit;

/**
 * Serves the site keyword at /healthcheck (or a custom slug).
 */
class WHCU_Endpoint {

	/**
	 * Hook rewrite rules and request handling.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_rewrite' ) );
		add_filter( 'query_vars', array( $this, 'register_query_var' ) );
		add_action( 'template_redirect', array( $this, 'maybe_serve' ), 0 );
	}

	/**
	 * Keep the rewrite rule registered on every load.
	 */
	public function register_rewrite() {
		WHCU_Plugin::add_rewrite_rules();
	}

	/**
	 * @param string[] $vars Public query vars.
	 * @return string[]
	 */
	public function register_query_var( $vars ) {
		$vars[] = WHCU_Plugin::QUERY_VAR;
		return $vars;
	}

	/**
	 * Serve the keyword response when this request is the health check.
	 */
	public function maybe_serve() {
		if ( ! $this->is_healthcheck_request() ) {
			return;
		}

		$this->serve();
	}

	/**
	 * Match via rewrite query var or raw request path so the URL works
	 * even before rewrite rules have been flushed.
	 *
	 * @return bool
	 */
	private function is_healthcheck_request() {
		if ( (int) get_query_var( WHCU_Plugin::QUERY_VAR ) === 1 ) {
			return true;
		}

		if ( isset( $_GET[ WHCU_Plugin::QUERY_VAR ] ) && '1' === (string) wp_unslash( $_GET[ WHCU_Plugin::QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
		$path        = trim( $path, '/' );

		$home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path = trim( $home_path, '/' );

		if ( '' !== $home_path && 0 === strpos( $path, $home_path ) ) {
			$path = trim( substr( $path, strlen( $home_path ) ), '/' );
		}

		return $path === WHCU_Plugin::get_slug();
	}

	/**
	 * Output a cache-busting plaintext body containing the keyword.
	 */
	private function serve() {
		$keyword = WHCU_Plugin::get_keyword();

		if ( '' === $keyword ) {
			status_header( 503 );
			nocache_headers();
			header( 'Content-Type: text/plain; charset=utf-8' );
			header( 'X-Robots-Tag: noindex, nofollow' );
			echo 'Health check is not configured.';
			exit;
		}

		update_option( WHCU_Plugin::OPTION_LAST_HIT, time(), false );

		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex, nofollow' );

		echo "OK\n" . $keyword . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}
