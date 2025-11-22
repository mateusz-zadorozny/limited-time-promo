<?php
/**
 * Plugin Name: Limited Time Promo
 * Description: Create time-limited promotional campaigns with conditional discounts, free shipping, and animated sticky message bars.
 * Version: 0.1.0
 * Author: Mateusz Zadorozny
 * Author URI: https://zadorozny.rocks
 * Text Domain: limited-time-promo
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 10.0
 * WC tested up to: 10.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'LTP_VERSION', '1.0.0' );
define( 'LTP_PLUGIN_FILE', __FILE__ );
define( 'LTP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LTP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LTP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if WooCommerce is active
 */
function ltp_check_woocommerce() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'ltp_woocommerce_missing_notice' );
		return false;
	}
	return true;
}

/**
 * Display admin notice if WooCommerce is not active
 */
function ltp_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'Limited Time Promo requires WooCommerce to be installed and active.', 'limited-time-promo' ); ?></p>
	</div>
	<?php
}

/**
 * Initialize the plugin
 */
function ltp_init() {
	if ( ! ltp_check_woocommerce() ) {
		return;
	}

	// Load plugin classes.
	require_once LTP_PLUGIN_DIR . 'includes/class-ltp-admin.php';
	require_once LTP_PLUGIN_DIR . 'includes/class-ltp-frontend.php';
	require_once LTP_PLUGIN_DIR . 'includes/class-ltp-promo-logic.php';

	// Initialize classes.
	LTP_Admin::init();
	LTP_Frontend::init();
	LTP_Promo_Logic::init();
}
add_action( 'plugins_loaded', 'ltp_init' );

/**
 * Plugin activation hook
 */
function ltp_activate() {
	// Set default settings.
	$default_settings = array(
		'promo_start'         => '',
		'promo_end'           => '',
		'product_categories'  => array(),
		'discount_percentage' => 0,
		'free_shipping'       => false,
		'minimum_cart'        => 0,
		'promo_name'          => '',
		'promo_message'       => '',
		'cta_text'            => '',
		'cta_link'            => '',
		'gradient_color_1'    => '#ff6b6b',
		'gradient_color_2'    => '#4ecdc4',
		'activate'            => false,
	);

	if ( ! get_option( 'ltp_settings' ) ) {
		// Use add_option with autoload=true for better performance.
		add_option( 'ltp_settings', $default_settings, '', 'yes' );
	}
}
register_activation_hook( __FILE__, 'ltp_activate' );

/**
 * Plugin deactivation hook
 */
function ltp_deactivate() {
	// Clean up if needed.
	// Note: We don't delete settings on deactivation, only on uninstall.
}
register_deactivation_hook( __FILE__, 'ltp_deactivate' );
