<?php
/**
 * Frontend Display Class
 *
 * @package Limited_Time_Promo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LTP_Frontend class
 */
class LTP_Frontend {

	/**
	 * Initialize the frontend functionality
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
		add_action( 'wp_body_open', array( __CLASS__, 'render_sticky_bar' ), 1 );
		// Fallback for themes that don't support wp_body_open.
		add_action( 'wp_footer', array( __CLASS__, 'render_sticky_bar_fallback' ), 1 );
	}

	/**
	 * Check if promo should be displayed
	 *
	 * @return bool
	 */
	public static function should_display_promo() {
		// Don't show in admin.
		if ( is_admin() ) {
			return false;
		}

		// Check if dismissed cookie exists.
		if ( isset( $_COOKIE['ltp_dismissed'] ) ) {
			return false;
		}

		// Get settings.
		$settings = get_option( 'ltp_settings', array() );

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

		// Check if there's a message to display.
		if ( empty( $settings['promo_message'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Enqueue frontend assets
	 */
	public static function enqueue_frontend_assets() {
		if ( ! self::should_display_promo() ) {
			return;
		}

		// Enqueue frontend CSS.
		wp_enqueue_style(
			'ltp-frontend-css',
			LTP_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			LTP_VERSION
		);

		// Get settings for inline styles.
		$settings = get_option( 'ltp_settings', array() );
		$color1   = $settings['gradient_color_1'] ?? '#ff6b6b';
		$color2   = $settings['gradient_color_2'] ?? '#4ecdc4';

		// Add inline styles for gradient colors.
		$custom_css = "
			.ltp-sticky-bar {
				background: linear-gradient(-45deg, {$color1}, {$color2}, {$color1}, {$color2});
				background-size: 400% 400%;
			}
		";
		wp_add_inline_style( 'ltp-frontend-css', $custom_css );

		// Enqueue frontend JS.
		wp_enqueue_script(
			'ltp-frontend-js',
			LTP_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			LTP_VERSION,
			true
		);
	}

	/**
	 * Render sticky bar
	 */
	public static function render_sticky_bar() {
		static $rendered = false;

		if ( $rendered || ! self::should_display_promo() ) {
			return;
		}

		$rendered = true;

		$settings = get_option( 'ltp_settings', array() );
		$message  = $settings['promo_message'] ?? '';
		$cta_text = $settings['cta_text'] ?? '';
		$cta_link = $settings['cta_link'] ?? '';

		?>
		<div class="ltp-sticky-bar" role="banner" aria-label="<?php esc_attr_e( 'Promotional message', 'limited-time-promo' ); ?>">
			<div class="ltp-sticky-bar-content">
				<div class="ltp-message">
					<?php echo esc_html( $message ); ?>
				</div>
				<?php if ( ! empty( $cta_text ) ) : ?>
					<div class="ltp-cta">
						<?php if ( ! empty( $cta_link ) ) : ?>
							<a href="<?php echo esc_url( $cta_link ); ?>" class="ltp-cta-button">
								<?php echo esc_html( $cta_text ); ?>
							</a>
						<?php else : ?>
							<span class="ltp-cta-button ltp-cta-button-no-link">
								<?php echo esc_html( $cta_text ); ?>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
			<button class="ltp-close-button" aria-label="<?php esc_attr_e( 'Dismiss promotional message', 'limited-time-promo' ); ?>">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		</div>
		<?php
	}

	/**
	 * Render sticky bar fallback for themes without wp_body_open
	 */
	public static function render_sticky_bar_fallback() {
		static $rendered = false;

		// Only render if not already rendered via wp_body_open.
		if ( $rendered || did_action( 'wp_body_open' ) ) {
			return;
		}

		self::render_sticky_bar();
	}
}
