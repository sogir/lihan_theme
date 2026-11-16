<?php
/**
 * Elementor widget: USP Bar (section "usps").
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

/**
 * Row of icon + label selling points.
 */
class Lihan_Elementor_Widget_Usps extends Lihan_Elementor_Widget_Base {

	public function get_name() {
		return 'lihan-usps';
	}

	public function get_title() {
		return esc_html__( 'USP Bar', 'lihan' );
	}

	public function get_icon() {
		return 'eicon-info-box';
	}

	public function get_keywords(): array {
		return [ 'lihan', 'usp', 'features', 'benefits', 'icons', 'trust' ];
	}

	protected function get_section_name(): string {
		return 'usps';
	}

	/**
	 * Icons offered for the items (names from lihan_icon()).
	 *
	 * @return array<string,string>
	 */
	protected function get_icon_options(): array {
		return [
			'fabric'     => esc_html__( 'Fabric', 'lihan' ),
			'design'     => esc_html__( 'Design', 'lihan' ),
			'hand-heart' => esc_html__( 'Hand with heart', 'lihan' ),
			'cod'        => esc_html__( 'Cash on delivery', 'lihan' ),
			'truck'      => esc_html__( 'Delivery truck', 'lihan' ),
			'refresh'    => esc_html__( 'Exchange', 'lihan' ),
			'shield'     => esc_html__( 'Shield', 'lihan' ),
			'star'       => esc_html__( 'Star', 'lihan' ),
			'gift'       => esc_html__( 'Gift', 'lihan' ),
			'reviews'    => esc_html__( 'Reviews', 'lihan' ),
			'check'      => esc_html__( 'Check mark', 'lihan' ),
			'phone'      => esc_html__( 'Phone', 'lihan' ),
			'clock'      => esc_html__( 'Clock', 'lihan' ),
			'whatsapp'   => esc_html__( 'WhatsApp', 'lihan' ),
			'map-pin'    => esc_html__( 'Location', 'lihan' ),
		];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[ 'label' => esc_html__( 'USP bar', 'lihan' ) ]
		);

		$this->add_heading_controls( esc_html__( 'Heading (shown on mobile)', 'lihan' ) );

		$repeater = new Repeater();

		$repeater->add_control(
			'icon',
			[
				'label'   => esc_html__( 'Icon', 'lihan' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'check',
				'options' => $this->get_icon_options(),
			]
		);

		$repeater->add_control(
			'label',
			[
				'label'       => esc_html__( 'Label', 'lihan' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'items',
			[
				'label'       => esc_html__( 'Items', 'lihan' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [],
				'title_field' => '{{{ label }}}',
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'items_note',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'No items here? The bar uses the items from the Customizer (Homepage: USP bar). Six items fit one row on desktop.', 'lihan' ),
				'content_classes' => 'elementor-descriptor',
			]
		);

		$this->end_controls_section();
	}

	protected function lihan_section_args( array $settings ): array {
		$items = [];

		foreach ( (array) ( $settings['items'] ?? [] ) as $item ) {
			$label = is_array( $item ) ? trim( (string) ( $item['label'] ?? '' ) ) : '';

			if ( '' === $label ) {
				continue;
			}

			$items[] = [
				'icon'  => sanitize_key( (string) ( $item['icon'] ?? 'check' ) ),
				'label' => $label,
			];
		}

		return [
			'heading' => $this->text_arg( $settings, 'heading', 'hide_heading' ),
			'items'   => $items,
		];
	}
}
