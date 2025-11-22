<?php
/**
 * Admin Settings Class
 *
 * @package Limited_Time_Promo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LTP_Admin class
 */
class LTP_Admin {

	/**
	 * Initialize the admin functionality
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add settings page under Marketing menu
	 */
	public static function add_settings_page() {
		add_submenu_page(
			'woocommerce-marketing',
			__( 'Limited Time Promo', 'limited-time-promo' ),
			__( 'Limited Time Promo', 'limited-time-promo' ),
			'manage_woocommerce',
			'limited-time-promo',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings
	 */
	public static function register_settings() {
		register_setting(
			'ltp_settings_group',
			'ltp_settings',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize and validate settings
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( $input ) {
		$sanitized = array();

		// Sanitize dates.
		$sanitized['promo_start'] = ! empty( $input['promo_start'] ) ? sanitize_text_field( $input['promo_start'] ) : '';
		$sanitized['promo_end']   = ! empty( $input['promo_end'] ) ? sanitize_text_field( $input['promo_end'] ) : '';

		// Sanitize product categories (array of integers).
		$sanitized['product_categories'] = array();
		if ( ! empty( $input['product_categories'] ) && is_array( $input['product_categories'] ) ) {
			$sanitized['product_categories'] = array_map( 'intval', $input['product_categories'] );
		}

		// Sanitize discount percentage.
		$sanitized['discount_percentage'] = ! empty( $input['discount_percentage'] ) ? floatval( $input['discount_percentage'] ) : 0;
		$sanitized['discount_percentage'] = max( 0, min( 100, $sanitized['discount_percentage'] ) ); // Clamp between 0-100.

		// Sanitize free shipping toggle.
		$sanitized['free_shipping'] = ! empty( $input['free_shipping'] );

		// Sanitize minimum cart.
		$sanitized['minimum_cart'] = ! empty( $input['minimum_cart'] ) ? floatval( $input['minimum_cart'] ) : 0;
		$sanitized['minimum_cart'] = max( 0, $sanitized['minimum_cart'] );

		// Sanitize promo name.
		$sanitized['promo_name'] = ! empty( $input['promo_name'] ) ? sanitize_text_field( $input['promo_name'] ) : '';

		// Sanitize promo message.
		$sanitized['promo_message'] = ! empty( $input['promo_message'] ) ? sanitize_text_field( $input['promo_message'] ) : '';

		// Sanitize CTA text and link.
		$sanitized['cta_text'] = ! empty( $input['cta_text'] ) ? sanitize_text_field( $input['cta_text'] ) : '';
		$sanitized['cta_link'] = ! empty( $input['cta_link'] ) ? esc_url_raw( $input['cta_link'] ) : '';

		// Sanitize gradient colors.
		$sanitized['gradient_color_1'] = ! empty( $input['gradient_color_1'] ) ? sanitize_hex_color( $input['gradient_color_1'] ) : '#ff6b6b';
		$sanitized['gradient_color_2'] = ! empty( $input['gradient_color_2'] ) ? sanitize_hex_color( $input['gradient_color_2'] ) : '#4ecdc4';

		// Sanitize activate toggle.
		$sanitized['activate'] = ! empty( $input['activate'] );

		return $sanitized;
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_admin_assets( $hook ) {
		// Only load on our settings page.
		if ( 'woocommerce_page_limited-time-promo' !== $hook ) {
			return;
		}

		// Enqueue WordPress color picker.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// Enqueue admin CSS.
		wp_enqueue_style(
			'ltp-admin-css',
			LTP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			LTP_VERSION
		);

		// Enqueue admin JS.
		wp_enqueue_script(
			'ltp-admin-js',
			LTP_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			LTP_VERSION,
			true
		);
	}

	/**
	 * Render settings page
	 */
	public static function render_settings_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Get current settings.
		$settings = get_option( 'ltp_settings', array() );

		// Get all product categories.
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		?>
		<div class="wrap ltp-settings-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'ltp_settings_group' ); ?>

				<table class="form-table" role="presentation">
					<!-- Activation Toggle -->
					<tr>
						<th scope="row">
							<label for="ltp_activate"><?php esc_html_e( 'Activate Promo', 'limited-time-promo' ); ?></label>
						</th>
						<td>
							<label class="ltp-toggle">
								<input type="checkbox" name="ltp_settings[activate]" id="ltp_activate" value="1" <?php checked( ! empty( $settings['activate'] ) ); ?>>
								<span class="ltp-toggle-slider"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Enable or disable the promotional campaign.', 'limited-time-promo' ); ?></p>
						</td>
					</tr>

					<!-- Promo Start Date -->
					<tr>
						<th scope="row">
							<label for="ltp_promo_start"><?php esc_html_e( 'Promo Start Date', 'limited-time-promo' ); ?></label>
						</th>
						<td>
							<input type="date" name="ltp_settings[promo_start]" id="ltp_promo_start" value="<?php echo esc_attr( $settings['promo_start'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Start date for the promotion (midnight).', 'limited-time-promo' ); ?></p>
						</td>
					</tr>

					<!-- Promo End Date -->
					<tr>
						<th scope="row">
							<label for="ltp_promo_end"><?php esc_html_e( 'Promo End Date', 'limited-time-promo' ); ?></label>
						</th>
						<td>
							<input type="date" name="ltp_settings[promo_end]" id="ltp_promo_end" value="<?php echo esc_attr( $settings['promo_end'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'End date for the promotion (midnight).', 'limited-time-promo' ); ?></p>
						</td>
					</tr>

					<!-- Product Categories -->
					<tr>
						<th scope="row">
							<label for="ltp_product_categories"><?php esc_html_e( 'Required Product Categories', 'limited-time-promo' ); ?></label>
						</th>
						<td>
							<select name="ltp_settings[product_categories][]" id="ltp_product_categories" multiple class="ltp-multiselect" size="8">
								<?php foreach ( $categories as $category ) : ?>
									<option value="<?php echo esc_attr( $category->term_id ); ?>" <?php echo in_array( $category->term_id, $settings['product_categories'] ?? array(), true ) ? 'selected' : ''; ?>>
										<?php echo esc_html( $category->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Optional: Cart must contain at least one item from these categories. Hold Ctrl/Cmd to select multiple.', 'limited-time-promo' ); ?></p>
						</td>
					</tr>

					<!-- Discount Percentage -->
					<tr>
						<th scope="row">
							<label for="ltp_discount_percentage"><?php esc_html_e( 'Discount Percentage', 'limited-time-promo' ); ?></label>
						</th>
						<td>
							<input type="number" name="ltp_settings[discount_percentage]" id="ltp_discount_percentage" value="<?php echo esc_attr( $settings['discount_percentage'] ?? 0 ); ?>" min="0" max="100" step="0.01" class="small-text">
							<span>%</span>
							<p class="description"><?php esc_html_e( 'Optional: Percentage discount to apply to the entire cart.', 'limited-time-promo' ); ?></p>
						</td>
					</tr>

					<!-- Free Shipping -->
					<tr>
						<th scope="row">
							<label for="ltp_free_shipping"><?php esc_html_e( 'Free Shipping', 'limited-time-promo' ); ?></label>
						</th>
						<td>
							<label class="ltp-toggle">
								<input type="checkbox" name="ltp_settings[free_shipping]" id="ltp_free_shipping" value="1" <?php checked( ! empty( $settings['free_shipping'] ) ); ?>>
								<span class="ltp-toggle-slider"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Optional: Enable free shipping when promo conditions are met.', 'limited-time-promo' ); ?></p>
						</td>
					</tr>

					<!-- Minimum Cart Amount -->
					<tr>
						<th scope="row">
							<label for="ltp_minimum_cart"><?php esc_html_e( 'Minimum Cart Amount', 'limited-time-promo' ); ?></label>
						</th>
						<td>
							<input type="number" name="ltp_settings[minimum_cart]" id="ltp_minimum_cart" value="<?php echo esc_attr( $settings['minimum_cart'] ?? 0 ); ?>" min="0" step="0.01" class="regular-text">
							<p class="description"><?php esc_html_e( 'Optional: Minimum cart subtotal (before discounts) required for the promo.', 'limited-time-promo' ); ?></p>
						</td>
					</tr>

					<!-- Promo Name -->
					<tr>
						<th scope="row">
							<label for="ltp_promo_name"><?php esc_html_e( 'Promo Name', 'limited-time-promo' ); ?></label>
						</th>
						<td>
							<input type="text" name="ltp_settings[promo_name]" id="ltp_promo_name" value="<?php echo esc_attr( $settings['promo_name'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Optional: Name for the discount (e.g., "Black Friday Sale"). If empty, defaults to "Promo Discount".', 'limited-time-promo' ); ?></p>
						</td>
					</tr>

					<!-- Promo Message -->
					<tr>
						<th scope="row">
							<label for="ltp_promo_message"><?php esc_html_e( 'Promo Message', 'limited-time-promo' ); ?></label>
						</th>
						<td>
							<input type="text" name="ltp_settings[promo_message]" id="ltp_promo_message" value="<?php echo esc_attr( $settings['promo_message'] ?? '' ); ?>" class="large-text">
							<p class="description"><?php esc_html_e( 'Message to display in the sticky bar.', 'limited-time-promo' ); ?></p>
						</td>
					</tr>

					<!-- CTA Button Text -->
					<tr>
						<th scope="row">
							<label for="ltp_cta_text"><?php esc_html_e( 'CTA Button Text', 'limited-time-promo' ); ?></label>
						</th>
						<td>
							<input type="text" name="ltp_settings[cta_text]" id="ltp_cta_text" value="<?php echo esc_attr( $settings['cta_text'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Optional: Text for the call-to-action button.', 'limited-time-promo' ); ?></p>
						</td>
					</tr>

					<!-- CTA Button Link -->
					<tr>
						<th scope="row">
							<label for="ltp_cta_link"><?php esc_html_e( 'CTA Button Link', 'limited-time-promo' ); ?></label>
						</th>
						<td>
							<input type="url" name="ltp_settings[cta_link]" id="ltp_cta_link" value="<?php echo esc_attr( $settings['cta_link'] ?? '' ); ?>" class="large-text">
							<p class="description"><?php esc_html_e( 'Optional: URL for the CTA button.', 'limited-time-promo' ); ?></p>
						</td>
					</tr>

					<!-- Gradient Color 1 -->
					<tr>
						<th scope="row">
							<label for="ltp_gradient_color_1"><?php esc_html_e( 'Gradient Color 1', 'limited-time-promo' ); ?></label>
						</th>
						<td>
							<input type="text" name="ltp_settings[gradient_color_1]" id="ltp_gradient_color_1" value="<?php echo esc_attr( $settings['gradient_color_1'] ?? '#ff6b6b' ); ?>" class="ltp-color-picker">
							<p class="description"><?php esc_html_e( 'First color for the animated gradient background.', 'limited-time-promo' ); ?></p>
						</td>
					</tr>

					<!-- Gradient Color 2 -->
					<tr>
						<th scope="row">
							<label for="ltp_gradient_color_2"><?php esc_html_e( 'Gradient Color 2', 'limited-time-promo' ); ?></label>
						</th>
						<td>
							<input type="text" name="ltp_settings[gradient_color_2]" id="ltp_gradient_color_2" value="<?php echo esc_attr( $settings['gradient_color_2'] ?? '#4ecdc4' ); ?>" class="ltp-color-picker">
							<p class="description"><?php esc_html_e( 'Second color for the animated gradient background.', 'limited-time-promo' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Settings', 'limited-time-promo' ) ); ?>
			</form>
		</div>
		<?php
	}
}
