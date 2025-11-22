<?php
/**
 * Promo Logic Class
 *
 * @package Limited_Time_Promo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LTP_Promo_Logic class
 */
class LTP_Promo_Logic {

	/**
	 * Initialize the promo logic
	 */
	public static function init() {
		// Apply discount to cart.
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'apply_cart_discount' ) );

		// Modify shipping rates for free shipping - use high priority to run last.
		add_filter( 'woocommerce_package_rates', array( __CLASS__, 'apply_free_shipping' ), 100, 2 );

		// Force shipping recalculation when cart changes.
		add_action( 'woocommerce_cart_item_removed', array( __CLASS__, 'refresh_shipping_on_cart_change' ) );
		add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'refresh_shipping_on_cart_change' ) );
		add_action( 'woocommerce_cart_item_set_quantity', array( __CLASS__, 'refresh_shipping_on_cart_change' ) );
		
		// Force recalculation on checkout page load.
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'refresh_shipping_on_cart_change' ) );
	}

	/**
	 * Refresh shipping calculations when cart changes
	 */
	public static function refresh_shipping_on_cart_change() {
		do_action( 'qm/debug', 'LTP: Cart changed - refreshing shipping calculations' );
		if ( WC()->cart ) {
			// Clear shipping packages cache.
			WC()->session->set( 'shipping_for_package_0', null );
			WC()->cart->calculate_shipping();
			WC()->cart->calculate_totals();
		}
	}

	/**
	 * Get settings with caching to avoid repeated database calls
	 *
	 * @return array
	 */
	private static function get_settings() {
		static $cached_settings = null;

		if ( null === $cached_settings ) {
			$cached_settings = get_option( 'ltp_settings', array() );
			do_action( 'qm/debug', 'LTP: Settings loaded from database (cached for this request)' );
		} else {
			do_action( 'qm/debug', 'LTP: Using cached settings (no database call)' );
		}

		return $cached_settings;
	}

	/**
	 * Check if promo is active and conditions are met
	 *
	 * @return bool
	 */
	public static function is_promo_active() {
		$settings = self::get_settings();

		// Check if activated.
		if ( empty( $settings['activate'] ) ) {
			return false;
		}

		// Check date range.
		if ( ! empty( $settings['promo_start'] ) && ! empty( $settings['promo_end'] ) ) {
			$current_date = current_time( 'Y-m-d' );
			$start_date   = $settings['promo_start'];
			$end_date     = $settings['promo_end'];

			if ( $current_date < $start_date || $current_date > $end_date ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if cart meets category requirements
	 *
	 * @return bool
	 */
	public static function check_category_requirement() {
		$settings = self::get_settings();

		// If no categories specified, requirement is met.
		if ( empty( $settings['product_categories'] ) || ! is_array( $settings['product_categories'] ) ) {
			do_action( 'qm/debug', 'LTP: No category requirement set - requirement met' );
			return true;
		}

		// Check if cart has any items from required categories.
		if ( ! WC()->cart ) {
			do_action( 'qm/warning', 'LTP: Cart not available' );
			return false;
		}

		$required_categories = $settings['product_categories'];
		do_action( 'qm/debug', 'LTP: Required categories: ' . implode( ', ', $required_categories ) );

		$cart_items = WC()->cart->get_cart();
		do_action( 'qm/debug', 'LTP: Cart has ' . count( $cart_items ) . ' items' );

		foreach ( $cart_items as $cart_item ) {
			$product_id = $cart_item['product_id'];
			$terms      = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

			do_action( 'qm/debug', 'LTP: Product ID ' . $product_id . ' has categories: ' . implode( ', ', $terms ) );

			// Check if product belongs to any required category.
			$matching_cats = array_intersect( $terms, $required_categories );
			if ( ! empty( $matching_cats ) ) {
				do_action( 'qm/info', 'LTP: Category requirement MET - Product ' . $product_id . ' matches categories: ' . implode( ', ', $matching_cats ) );
				return true;
			}
		}

		do_action( 'qm/warning', 'LTP: Category requirement NOT MET - No products from required categories in cart' );
		return false;
	}

	/**
	 * Check if cart meets minimum amount requirement
	 *
	 * @return bool
	 */
	public static function check_minimum_cart_requirement() {
		$settings = self::get_settings();

		// If no minimum cart specified, requirement is met.
		if ( empty( $settings['minimum_cart'] ) || $settings['minimum_cart'] <= 0 ) {
			do_action( 'qm/debug', 'LTP: No minimum cart requirement set - requirement met' );
			return true;
		}

		// Check cart subtotal (before discounts).
		if ( ! WC()->cart ) {
			do_action( 'qm/warning', 'LTP: Cart not available for minimum check' );
			return false;
		}

		$cart_subtotal = WC()->cart->get_subtotal();
		$minimum_cart  = floatval( $settings['minimum_cart'] );

		$meets_requirement = $cart_subtotal >= $minimum_cart;
		do_action( 'qm/debug', sprintf( 'LTP: Minimum cart check - Subtotal: %s, Required: %s, Met: %s', $cart_subtotal, $minimum_cart, $meets_requirement ? 'YES' : 'NO' ) );

		return $meets_requirement;
	}

	/**
	 * Check if all promo conditions are met
	 *
	 * @return bool
	 */
	public static function check_promo_conditions() {
		do_action( 'qm/debug', '=== LTP: Checking promo conditions ===' );

		if ( ! self::is_promo_active() ) {
			do_action( 'qm/info', 'LTP: Promo is NOT active' );
			return false;
		}

		do_action( 'qm/info', 'LTP: Promo is ACTIVE' );

		// Both category and minimum cart requirements must be met (AND logic).
		$category_met = self::check_category_requirement();
		if ( ! $category_met ) {
			do_action( 'qm/warning', 'LTP: FINAL RESULT - Promo conditions NOT MET (category requirement failed)' );
			return false;
		}

		$minimum_met = self::check_minimum_cart_requirement();
		if ( ! $minimum_met ) {
			do_action( 'qm/warning', 'LTP: FINAL RESULT - Promo conditions NOT MET (minimum cart failed)' );
			return false;
		}

		do_action( 'qm/info', 'LTP: FINAL RESULT - All promo conditions MET ✓' );
		return true;
	}

	/**
	 * Apply cart discount
	 *
	 * @param WC_Cart $cart Cart object.
	 */
	public static function apply_cart_discount( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Check if promo conditions are met.
		if ( ! self::check_promo_conditions() ) {
			return;
		}

		$settings = self::get_settings();

		// Check if discount is set.
		if ( empty( $settings['discount_percentage'] ) || $settings['discount_percentage'] <= 0 ) {
			return;
		}

		$discount_percentage = floatval( $settings['discount_percentage'] );
		$cart_subtotal       = $cart->get_subtotal();
		$discount_amount     = ( $cart_subtotal * $discount_percentage ) / 100;

		if ( $discount_amount > 0 ) {
			// Use custom promo name if set, otherwise default to "Promo Discount".
			$promo_name = ! empty( $settings['promo_name'] ) ? $settings['promo_name'] : __( 'Promo Discount', 'limited-time-promo' );

			$cart->add_fee(
				sprintf(
					/* translators: 1: promo name, 2: discount percentage */
					__( '%1$s (%2$s%%)', 'limited-time-promo' ),
					$promo_name,
					number_format( $discount_percentage, 2 )
				),
				-$discount_amount
			);
		}
	}

	/**
	 * Apply free shipping
	 *
	 * @param array $rates Shipping rates.
	 * @param array $package Shipping package.
	 * @return array Modified shipping rates.
	 */
	public static function apply_free_shipping( $rates, $package ) {
		do_action( 'qm/debug', '--- LTP: apply_free_shipping called ---' );

		// Check if promo conditions are met.
		if ( ! self::check_promo_conditions() ) {
			do_action( 'qm/info', 'LTP: Free shipping NOT applied - conditions not met' );
			return $rates;
		}

		$settings = self::get_settings();

		// Check if free shipping is enabled.
		if ( empty( $settings['free_shipping'] ) ) {
			do_action( 'qm/info', 'LTP: Free shipping NOT applied - feature disabled in settings' );
			return $rates;
		}

		do_action( 'qm/info', 'LTP: Applying FREE SHIPPING ✓' );

		// Set all shipping costs to 0.
		foreach ( $rates as $rate_id => $rate ) {
			$rates[ $rate_id ]->cost = 0;
			$rates[ $rate_id ]->taxes = array();
			do_action( 'qm/debug', 'LTP: Set shipping rate ' . $rate_id . ' to $0' );
		}

		return $rates;
	}
}
