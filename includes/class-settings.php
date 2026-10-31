<?php
/**
 * Per-site settings screen.
 *
 * @package WHCU
 */

defined( 'ABSPATH' ) || exit;

/**
 * Site-level keyword and slug settings. Available on each site even when
 * the plugin is network-activated.
 */
class WHCU_Settings {

	const PAGE_SLUG = 'whcu-health-check';
	const GROUP     = 'whcu_settings';

	/**
	 * Hook admin menus, settings API, and notices.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_whcu_generate_keyword', array( $this, 'handle_generate_keyword' ) );
		add_action( 'admin_notices', array( $this, 'maybe_permalink_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WHCU_PLUGIN_FILE ), array( $this, 'action_links' ) );
		add_filter( 'network_admin_plugin_action_links_' . plugin_basename( WHCU_PLUGIN_FILE ), array( $this, 'network_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Register site options. These stay per-blog on multisite.
	 */
	public function register_settings() {
		register_setting(
			self::GROUP,
			WHCU_Plugin::OPTION_KEYWORD,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_keyword' ),
				'default'           => '',
			)
		);

		register_setting(
			self::GROUP,
			WHCU_Plugin::OPTION_SLUG,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_slug' ),
				'default'           => WHCU_Plugin::DEFAULT_SLUG,
			)
		);

		add_settings_section(
			'whcu_main',
			__( 'UptimeRobot keyword monitor', 'wp-health-check-uptimerobot' ),
			array( $this, 'render_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			WHCU_Plugin::OPTION_KEYWORD,
			__( 'Keyword', 'wp-health-check-uptimerobot' ),
			array( $this, 'render_keyword_field' ),
			self::PAGE_SLUG,
			'whcu_main'
		);

		add_settings_field(
			WHCU_Plugin::OPTION_SLUG,
			__( 'Endpoint slug', 'wp-health-check-uptimerobot' ),
			array( $this, 'render_slug_field' ),
			self::PAGE_SLUG,
			'whcu_main'
		);
	}

	/**
	 * Settings lives under each site's Settings menu, not Network Admin.
	 */
	public function add_menu() {
		add_options_page(
			__( 'UptimeRobot Health Check', 'wp-health-check-uptimerobot' ),
			__( 'UptimeRobot Health Check', 'wp-health-check-uptimerobot' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * @param string[] $links Plugin row links.
	 * @return string[]
	 */
	public function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( $url ),
				esc_html__( 'Settings', 'wp-health-check-uptimerobot' )
			)
		);

		return $links;
	}

	/**
	 * Network Plugins screen: website only, since settings are per site.
	 *
	 * @param string[] $links Plugin row links.
	 * @return string[]
	 */
	public function network_action_links( $links ) {
		array_unshift(
			$links,
			sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( WHCU_Plugin::HELP_URL ),
				esc_html__( 'Help', 'wp-health-check-uptimerobot' )
			)
		);

		return $links;
	}

	/**
	 * Extra plugin-row links on the Plugins screen.
	 *
	 * @param string[] $links Existing meta links.
	 * @param string   $file  Plugin basename.
	 * @return string[]
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( plugin_basename( WHCU_PLUGIN_FILE ) !== $file ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( WHCU_Plugin::HELP_URL ),
			esc_html__( 'Help', 'wp-health-check-uptimerobot' )
		);

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( WHCU_Plugin::GITHUB_URL ),
			esc_html__( 'GitHub', 'wp-health-check-uptimerobot' )
		);

		return $links;
	}

	/**
	 * Replace the keyword and send the user back to settings.
	 */
	public function handle_generate_keyword() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'wp-health-check-uptimerobot' ) );
		}

		check_admin_referer( 'whcu_generate_keyword' );

		update_option( WHCU_Plugin::OPTION_KEYWORD, WHCU_Plugin::generate_keyword() );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => self::PAGE_SLUG,
					'whcu-generated' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * @param mixed $value Submitted keyword.
	 * @return string
	 */
	public function sanitize_keyword( $value ) {
		$value = sanitize_text_field( (string) $value );
		$value = preg_replace( '/[^A-Za-z0-9_\-]/', '', $value );

		if ( '' === $value ) {
			add_settings_error(
				WHCU_Plugin::OPTION_KEYWORD,
				'whcu_keyword_empty',
				__( 'Keyword cannot be empty. A new one was generated.', 'wp-health-check-uptimerobot' )
			);
			return WHCU_Plugin::generate_keyword();
		}

		return $value;
	}

	/**
	 * @param mixed $value Submitted slug.
	 * @return string
	 */
	public function sanitize_slug( $value ) {
		$old  = WHCU_Plugin::get_slug();
		$slug = sanitize_title( (string) $value );

		if ( '' === $slug ) {
			$slug = WHCU_Plugin::DEFAULT_SLUG;
		}

		if ( $slug !== $old ) {
			add_action( 'shutdown', 'flush_rewrite_rules' );
		}

		return $slug;
	}

	/**
	 * Intro copy for the settings section.
	 */
	public function render_section() {
		echo '<p>' . esc_html__( 'Point an UptimeRobot Keyword monitor at this site’s health-check URL and look for the keyword below. If WordPress cannot boot or the keyword disappears, UptimeRobot will alert.', 'wp-health-check-uptimerobot' ) . '</p>';
	}

	/**
	 * Keyword input plus generate action.
	 */
	public function render_keyword_field() {
		$keyword = WHCU_Plugin::get_keyword();
		?>
		<input
			type="text"
			class="regular-text code"
			id="<?php echo esc_attr( WHCU_Plugin::OPTION_KEYWORD ); ?>"
			name="<?php echo esc_attr( WHCU_Plugin::OPTION_KEYWORD ); ?>"
			value="<?php echo esc_attr( $keyword ); ?>"
			autocomplete="off"
			spellcheck="false"
		/>
		<button type="button" class="button" id="whcu-copy-keyword"><?php esc_html_e( 'Copy', 'wp-health-check-uptimerobot' ); ?></button>
		<p class="description">
			<?php esc_html_e( 'Use this exact string as the UptimeRobot keyword. Letters, numbers, underscores, and hyphens only.', 'wp-health-check-uptimerobot' ); ?>
		</p>
		<?php
	}

	/**
	 * Endpoint slug input.
	 */
	public function render_slug_field() {
		$slug = WHCU_Plugin::get_slug();
		?>
		<input
			type="text"
			class="regular-text code"
			id="<?php echo esc_attr( WHCU_Plugin::OPTION_SLUG ); ?>"
			name="<?php echo esc_attr( WHCU_Plugin::OPTION_SLUG ); ?>"
			value="<?php echo esc_attr( $slug ); ?>"
		/>
		<p class="description">
			<?php esc_html_e( 'Public path on this site. Default is healthcheck, producing /healthcheck/.', 'wp-health-check-uptimerobot' ); ?>
		</p>
		<?php
	}

	/**
	 * Full settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['whcu-generated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_settings_error(
				self::GROUP,
				'whcu_generated',
				__( 'A new keyword was generated. Update the keyword in UptimeRobot to match.', 'wp-health-check-uptimerobot' ),
				'success'
			);
		}

		$monitor_url  = WHCU_Plugin::get_monitor_url();
		$fallback_url = WHCU_Plugin::get_fallback_url();
		$last_hit     = (int) get_option( WHCU_Plugin::OPTION_LAST_HIT, 0 );
		$permalinks   = (string) get_option( 'permalink_structure' );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php echo esc_html( get_admin_page_title() ); ?>
			</h1>
			<span class="title-count"><?php echo esc_html( 'v' . WHCU_VERSION ); ?></span>
			<a href="<?php echo esc_url( WHCU_Plugin::HELP_URL ); ?>" class="page-title-action" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Help', 'wp-health-check-uptimerobot' ); ?></a>
			<a href="<?php echo esc_url( WHCU_Plugin::GITHUB_URL ); ?>" class="page-title-action" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'GitHub', 'wp-health-check-uptimerobot' ); ?></a>
			<hr class="wp-header-end" />
			<?php settings_errors(); ?>

			<?php if ( is_multisite() ) : ?>
				<p class="description">
					<?php esc_html_e( 'This plugin can be network-activated. The keyword, slug, and monitor URL are stored per site so each site can be monitored independently.', 'wp-health-check-uptimerobot' ); ?>
				</p>
			<?php endif; ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Monitor URL', 'wp-health-check-uptimerobot' ); ?></th>
					<td>
						<code id="whcu-monitor-url"><?php echo esc_html( $monitor_url ); ?></code>
						<button type="button" class="button" id="whcu-copy-url"><?php esc_html_e( 'Copy', 'wp-health-check-uptimerobot' ); ?></button>
						<p class="description">
							<?php esc_html_e( 'Paste this into UptimeRobot as an HTTPS Keyword monitor. Alert when the keyword does not exist.', 'wp-health-check-uptimerobot' ); ?>
						</p>
						<?php if ( '' === $permalinks ) : ?>
							<p class="description">
								<?php
								printf(
									/* translators: %s: fallback query-string URL */
									esc_html__( 'Pretty permalinks are disabled. Use this URL instead: %s', 'wp-health-check-uptimerobot' ),
									'<code>' . esc_html( $fallback_url ) . '</code>'
								);
								?>
							</p>
						<?php else : ?>
							<p class="description">
								<?php
								printf(
									/* translators: %s: fallback query-string URL */
									esc_html__( 'Fallback if the pretty URL 404s: %s', 'wp-health-check-uptimerobot' ),
									'<code>' . esc_html( $fallback_url ) . '</code>'
								);
								?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Last probe', 'wp-health-check-uptimerobot' ); ?></th>
					<td>
						<?php
						if ( $last_hit > 0 ) {
							echo esc_html(
								sprintf(
									/* translators: 1: localized date, 2: localized time */
									__( '%1$s at %2$s', 'wp-health-check-uptimerobot' ),
									wp_date( get_option( 'date_format' ), $last_hit ),
									wp_date( get_option( 'time_format' ), $last_hit )
								)
							);
						} else {
							esc_html_e( 'Not hit yet. Open the monitor URL or wait for UptimeRobot’s first check.', 'wp-health-check-uptimerobot' );
						}
						?>
					</td>
				</tr>
			</table>

			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save changes', 'wp-health-check-uptimerobot' ) );
				?>
			</form>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" style="margin-top: -12px;">
				<?php wp_nonce_field( 'whcu_generate_keyword' ); ?>
				<input type="hidden" name="action" value="whcu_generate_keyword" />
				<?php
				submit_button(
					__( 'Generate new keyword', 'wp-health-check-uptimerobot' ),
					'secondary',
					'submit',
					false
				);
				?>
				<p class="description">
					<?php esc_html_e( 'Rotating the keyword will make the current UptimeRobot monitor fail until you update it there too.', 'wp-health-check-uptimerobot' ); ?>
				</p>
			</form>

			<hr />
			<h2><?php esc_html_e( 'UptimeRobot setup', 'wp-health-check-uptimerobot' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Create a new monitor of type Keyword.', 'wp-health-check-uptimerobot' ); ?></li>
				<li><?php esc_html_e( 'URL: the Monitor URL above (HTTPS).', 'wp-health-check-uptimerobot' ); ?></li>
				<li><?php esc_html_e( 'Keyword type: exists. Alert when the keyword does not exist.', 'wp-health-check-uptimerobot' ); ?></li>
				<li><?php esc_html_e( 'Keyword value: the keyword saved on this page.', 'wp-health-check-uptimerobot' ); ?></li>
				<li><?php esc_html_e( 'Interval: 5 minutes is typical.', 'wp-health-check-uptimerobot' ); ?></li>
			</ol>

			<p class="description">
				<?php
				printf(
					/* translators: %s: company name linked to the 637 Digital Solutions website */
					esc_html__( 'A %s plugin.', 'wp-health-check-uptimerobot' ),
					'<a href="' . esc_url( WHCU_Plugin::WEBSITE_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( WHCU_Plugin::AUTHOR_NAME ) . '</a>'
				);
				?>
			</p>
		</div>
		<script>
		(function () {
			function copy(id, button) {
				var node = document.getElementById(id);
				if (!node) return;
				var text = node.value || node.textContent || '';
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(function () {
						button.textContent = <?php echo wp_json_encode( __( 'Copied', 'wp-health-check-uptimerobot' ) ); ?>;
						setTimeout(function () {
							button.textContent = <?php echo wp_json_encode( __( 'Copy', 'wp-health-check-uptimerobot' ) ); ?>;
						}, 1500);
					});
					return;
				}
				window.prompt(<?php echo wp_json_encode( __( 'Copy this value:', 'wp-health-check-uptimerobot' ) ); ?>, text);
			}
			var copyKeyword = document.getElementById('whcu-copy-keyword');
			if (copyKeyword) {
				copyKeyword.addEventListener('click', function () { copy(<?php echo wp_json_encode( WHCU_Plugin::OPTION_KEYWORD ); ?>, copyKeyword); });
			}
			var copyUrl = document.getElementById('whcu-copy-url');
			if (copyUrl) {
				copyUrl.addEventListener('click', function () { copy('whcu-monitor-url', copyUrl); });
			}
		})();
		</script>
		<?php
	}

	/**
	 * Remind admins to flush permalinks if the pretty URL is likely broken.
	 */
	public function maybe_permalink_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'settings_page_' . self::PAGE_SLUG !== $screen->id ) {
			return;
		}

		if ( '' === (string) get_option( 'permalink_structure' ) ) {
			echo '<div class="notice notice-warning"><p>';
			printf(
				/* translators: %s: permalinks settings URL */
				esc_html__( 'Pretty permalinks are off, so /healthcheck/ will not resolve. Enable a permalink structure under %s, or use the fallback URL.', 'wp-health-check-uptimerobot' ),
				'<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">' . esc_html__( 'Settings → Permalinks', 'wp-health-check-uptimerobot' ) . '</a>'
			);
			echo '</p></div>';
		}
	}
}
