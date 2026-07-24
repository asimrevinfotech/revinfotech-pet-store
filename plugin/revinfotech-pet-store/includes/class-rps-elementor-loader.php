<?php
/**
 * Registers the Pet Table widget with Elementor once Elementor is
 * confirmed active, and shows an admin notice instead of fataling when
 * it isn't.
 *
 * @package Revinfotech_Pet_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RPS_Elementor_Loader {

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'check_elementor' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
	}

	public function check_elementor() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'render_missing_elementor_notice' ) );
		}
	}

	public function render_missing_elementor_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'Revinfotech Pet Store requires the Elementor plugin to display the Pet Table widget. Please install and activate Elementor.', 'revinfotech-pet-store' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'revinfotech',
			array(
				'title' => __( 'Revinfotech', 'revinfotech-pet-store' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widgets( $widgets_manager ) {
		require_once RPS_PATH . 'widgets/class-rps-pet-table-widget.php';
		$widgets_manager->register( new RPS_Pet_Table_Widget() );
	}
}
