<?php
/**
 * Remove per-site options on uninstall, including every site on a network.
 *
 * @package WHCU
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$option_names = array(
	'whcu_keyword',
	'whcu_slug',
	'whcu_last_hit',
);

/**
 * Delete this site's plugin options.
 */
$whcu_delete_site_options = static function () use ( $option_names ) {
	foreach ( $option_names as $option_name ) {
		delete_option( $option_name );
	}
};

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		$whcu_delete_site_options();
		restore_current_blog();
	}
} else {
	$whcu_delete_site_options();
}
