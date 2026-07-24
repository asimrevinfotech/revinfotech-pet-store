<?php
/**
 * Elementor widget that renders pet data (name, category, status, image)
 * from RPS_API_Client as a styled, paginated table.
 *
 * @package Revinfotech_Pet_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class RPS_Pet_Table_Widget extends Widget_Base {

	public function get_name() {
		return 'rps-pet-table';
	}

	public function get_title() {
		return __( 'Pet Table', 'revinfotech-pet-store' );
	}

	public function get_icon() {
		return 'eicon-table';
	}

	public function get_categories() {
		return array( 'revinfotech' );
	}

	public function get_keywords() {
		return array( 'pet', 'store', 'table', 'api' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'rps_content_section',
			array(
				'label' => __( 'Pet Table', 'revinfotech-pet-store' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'status_filter',
			array(
				'label'   => __( 'Status Filter', 'revinfotech-pet-store' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''          => __( 'Use Default (Pet Store Settings)', 'revinfotech-pet-store' ),
					'available' => __( 'Available', 'revinfotech-pet-store' ),
					'pending'   => __( 'Pending', 'revinfotech-pet-store' ),
					'sold'      => __( 'Sold', 'revinfotech-pet-store' ),
				),
			)
		);

		$this->add_control(
			'rows_per_page',
			array(
				'label'   => __( 'Rows Per Page', 'revinfotech-pet-store' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 100,
				'default' => 10,
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'rps_style_section',
			array(
				'label' => __( 'Table Style', 'revinfotech-pet-store' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'header_bg_color',
			array(
				'label'   => __( 'Header Background Color', 'revinfotech-pet-store' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#2c3e50',
			)
		);

		$this->add_control(
			'header_text_color',
			array(
				'label'   => __( 'Header Text Color', 'revinfotech-pet-store' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#ffffff',
			)
		);

		$this->add_control(
			'row_text_color',
			array(
				'label'   => __( 'Row Text Color', 'revinfotech-pet-store' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#333333',
			)
		);

		$this->add_control(
			'border_color',
			array(
				'label'   => __( 'Border Color', 'revinfotech-pet-store' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#dddddd',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Only allows CSS color values Elementor's Color control can
	 * actually produce (hex or rgb/rgba); anything else is dropped
	 * instead of being written into the inline <style> block.
	 */
	private function sanitize_color( $color ) {
		if ( ! is_string( $color ) ) {
			return '';
		}

		if ( preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color ) ) {
			return $color;
		}

		if ( preg_match( '/^rgba?\([0-9\s.,%]+\)$/', $color ) ) {
			return $color;
		}

		return '';
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		wp_enqueue_style( 'rps-pet-table', RPS_URL . 'assets/css/pet-table.css', array(), RPS_VERSION );
		wp_enqueue_script( 'rps-pet-table', RPS_URL . 'assets/js/pet-table.js', array(), RPS_VERSION, true );

		$pets = RPS_API_Client::get_pets( isset( $settings['status_filter'] ) ? $settings['status_filter'] : '' );

		if ( is_wp_error( $pets ) ) {
			printf(
				'<div class="rps-pet-table-notice">%s</div>',
				esc_html( $pets->get_error_message() )
			);
			return;
		}

		if ( empty( $pets ) ) {
			printf(
				'<div class="rps-pet-table-notice">%s</div>',
				esc_html__( 'No pets found for the selected status.', 'revinfotech-pet-store' )
			);
			return;
		}

		$widget_id     = 'rps-pet-table-' . $this->get_id();
		$rows_per_page = isset( $settings['rows_per_page'] ) ? absint( $settings['rows_per_page'] ) : 10;
		$rows_per_page = $rows_per_page > 0 ? $rows_per_page : 10;

		$header_bg    = $this->sanitize_color( isset( $settings['header_bg_color'] ) ? $settings['header_bg_color'] : '' );
		$header_text  = $this->sanitize_color( isset( $settings['header_text_color'] ) ? $settings['header_text_color'] : '' );
		$row_text     = $this->sanitize_color( isset( $settings['row_text_color'] ) ? $settings['row_text_color'] : '' );
		$border_color = $this->sanitize_color( isset( $settings['border_color'] ) ? $settings['border_color'] : '' );
		?>
		<div id="<?php echo esc_attr( $widget_id ); ?>" class="rps-pet-table-wrap" data-rows-per-page="<?php echo esc_attr( $rows_per_page ); ?>">
			<style>
				#<?php echo esc_attr( $widget_id ); ?> table { <?php echo $border_color ? 'border-color:' . esc_attr( $border_color ) . ';' : ''; ?> }
				#<?php echo esc_attr( $widget_id ); ?> th { <?php echo $header_bg ? 'background-color:' . esc_attr( $header_bg ) . ';' : ''; ?> <?php echo $header_text ? 'color:' . esc_attr( $header_text ) . ';' : ''; ?> <?php echo $border_color ? 'border-color:' . esc_attr( $border_color ) . ';' : ''; ?> }
				#<?php echo esc_attr( $widget_id ); ?> td { <?php echo $row_text ? 'color:' . esc_attr( $row_text ) . ';' : ''; ?> <?php echo $border_color ? 'border-color:' . esc_attr( $border_color ) . ';' : ''; ?> }
			</style>
			<table class="rps-pet-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Image', 'revinfotech-pet-store' ); ?></th>
						<th><?php esc_html_e( 'Name', 'revinfotech-pet-store' ); ?></th>
						<th><?php esc_html_e( 'Category', 'revinfotech-pet-store' ); ?></th>
						<th><?php esc_html_e( 'Status', 'revinfotech-pet-store' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pets as $pet ) : ?>
						<tr>
							<td>
								<?php if ( ! empty( $pet['image'] ) ) : ?>
									<img class="rps-pet-image" src="<?php echo esc_url( $pet['image'] ); ?>" alt="<?php echo esc_attr( $pet['name'] ); ?>" loading="lazy" />
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $pet['name'] ); ?></td>
							<td><?php echo esc_html( $pet['category'] ); ?></td>
							<td><?php echo esc_html( $pet['status'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<div class="rps-pet-table-pagination">
				<button type="button" class="rps-prev" aria-label="<?php esc_attr_e( 'Previous page', 'revinfotech-pet-store' ); ?>">&laquo; <?php esc_html_e( 'Prev', 'revinfotech-pet-store' ); ?></button>
				<span class="rps-page-indicator"></span>
				<button type="button" class="rps-next" aria-label="<?php esc_attr_e( 'Next page', 'revinfotech-pet-store' ); ?>"><?php esc_html_e( 'Next', 'revinfotech-pet-store' ); ?> &raquo;</button>
			</div>
		</div>
		<?php
	}
}
