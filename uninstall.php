<?php
/**
 * Uninstall script
 *
 * @package Limited_Time_Promo
 */

// Exit if accessed directly or not uninstalling.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin settings.
delete_option( 'ltp_settings' );

// Clear any cached data.
wp_cache_flush();
