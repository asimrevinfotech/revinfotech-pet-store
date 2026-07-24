<?php
/**
 * Admin settings page: API base URL, default status filter, and cache
 * duration, plus a manual "Clear Cache" action.
 *
 * @package Revinfotech_Pet_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RPS_Settings {

	const OPTION_KEY     = 'rps_settings';
	const MENU_SLUG      = 'rps-settings';
	const CLEAR_CACHE_ACTION = 'rps_clear_cache';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_' . self::CLEAR_CACHE_ACTION, array( $this, 'handle_clear_cache' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_notices' ) );
	}

	/**
	 * @return array{api_base_url: string, default_status: string, cache_minutes: int}
	 */
	public static function get_default_settings() {
		return array(
			'api_base_url'  => 'https://petstore.swagger.io/v2',
			'default_status' => 'available',
			'cache_minutes' => 60,
		);
	}

	/**
	 * Returns saved settings merged over the defaults, so a partial or
	 * missing option never produces undefined-index notices elsewhere.
	 *
	 * @return array{api_base_url: string, default_status: string, cache_minutes: int}
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, self::get_default_settings() );
	}

	public function register_menu() {
		add_options_page(
			__( 'Pet Store Settings', 'revinfotech-pet-store' ),
			__( 'Pet Store', 'revinfotech-pet-store' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting(
			self::OPTION_KEY,
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::get_default_settings(),
			)
		);

		add_settings_section(
			'rps_main_section',
			__( 'Pet Store API', 'revinfotech-pet-store' ),
			'__return_false',
			self::MENU_SLUG
		);

		add_settings_field(
			'api_base_url',
			__( 'API Base URL', 'revinfotech-pet-store' ),
			array( $this, 'render_api_base_url_field' ),
			self::MENU_SLUG,
			'rps_main_section'
		);

		add_settings_field(
			'default_status',
			__( 'Default Status Filter', 'revinfotech-pet-store' ),
			array( $this, 'render_default_status_field' ),
			self::MENU_SLUG,
			'rps_main_section'
		);

		add_settings_field(
			'cache_minutes',
			__( 'Cache Duration (minutes)', 'revinfotech-pet-store' ),
			array( $this, 'render_cache_minutes_field' ),
			self::MENU_SLUG,
			'rps_main_section'
		);
	}

	/**
	 * @param mixed $input Raw submitted value.
	 * @return array Sanitized settings, always falling back to defaults
	 *               for any field that fails validation.
	 */
	public function sanitize_settings( $input ) {
		$defaults = self::get_default_settings();

		if ( ! is_array( $input ) ) {
			return $defaults;
		}

		$sanitized = array();

		$url = isset( $input['api_base_url'] ) ? esc_url_raw( trim( (string) $input['api_base_url'] ) ) : '';
		$sanitized['api_base_url'] = ( ! empty( $url ) && wp_http_validate_url( $url ) )
			? untrailingslashit( $url )
			: $defaults['api_base_url'];

		$status = isset( $input['default_status'] ) ? sanitize_text_field( (string) $input['default_status'] ) : '';
		$sanitized['default_status'] = in_array( $status, RPS_API_Client::STATUSES, true )
			? $status
			: $defaults['default_status'];

		$minutes = isset( $input['cache_minutes'] ) ? (int) $input['cache_minutes'] : 0;
		$sanitized['cache_minutes'] = $minutes > 0 ? $minutes : $defaults['cache_minutes'];

		return $sanitized;
	}

	public function render_api_base_url_field() {
		$settings = self::get_settings();
		printf(
			'<input type="url" class="regular-text" name="%1$s[api_base_url]" value="%2$s" placeholder="https://petstore.swagger.io/v2" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $settings['api_base_url'] )
		);
	}

	public function render_default_status_field() {
		$settings = self::get_settings();
		echo '<select name="' . esc_attr( self::OPTION_KEY ) . '[default_status]">';
		foreach ( RPS_API_Client::STATUSES as $status ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $status ),
				selected( $settings['default_status'], $status, false ),
				esc_html( ucfirst( $status ) )
			);
		}
		echo '</select>';
	}

	public function render_cache_minutes_field() {
		$settings = self::get_settings();
		printf(
			'<input type="number" min="1" step="1" name="%1$s[cache_minutes]" value="%2$s" class="small-text" /> %3$s',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $settings['cache_minutes'] ),
			esc_html__( 'minutes', 'revinfotech-pet-store' )
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Pet Store Settings', 'revinfotech-pet-store' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_KEY );
				do_settings_sections( self::MENU_SLUG );
				submit_button( __( 'Save Settings', 'revinfotech-pet-store' ) );
				?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Cache', 'revinfotech-pet-store' ); ?></h2>
			<p><?php esc_html_e( 'Clear the cached API responses to force fresh data on the next page load.', 'revinfotech-pet-store' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::CLEAR_CACHE_ACTION ); ?>" />
				<?php wp_nonce_field( self::CLEAR_CACHE_ACTION ); ?>
				<?php submit_button( __( 'Clear Cache Now', 'revinfotech-pet-store' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_clear_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'revinfotech-pet-store' ) );
		}

		check_admin_referer( self::CLEAR_CACHE_ACTION );

		RPS_API_Client::clear_cache();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => self::MENU_SLUG,
					'rps_cache_cleared' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	public function maybe_show_notices() {
		if ( ! isset( $_GET['page'], $_GET['rps_cache_cleared'] ) || self::MENU_SLUG !== $_GET['page'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Pet Store cache cleared.', 'revinfotech-pet-store' ); ?></p>
		</div>
		<?php
	}
}
