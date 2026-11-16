<?php
/**
 * Elementor widget: Category Grid (section "collection_intro").
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Intro heading + rich text + product category tiles.
 */
class Lihan_Elementor_Widget_Category_Grid extends Lihan_Elementor_Widget_Base {

	public function get_name() {
		return 'lihan-category-grid';
	}

	public function get_title() {
		return esc_html__( 'Category Grid', 'lihan' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_keywords(): array {
		return [ 'lihan', 'category', 'categories', 'collection', 'grid', 'intro', 'woocommerce' ];
	}

	protected function get_section_name(): string {
		return 'collection_intro';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_intro',
			[ 'label' => esc_html__( 'Intro', 'lihan' ) ]
		);

		$this->add_heading_controls();

		$this->add_control(
			'heading_tag',
			[
				'label'       => esc_html__( 'Heading level', 'lihan' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => [
					''   => esc_html__( 'Default (H2)', 'lihan' ),
					'h1' => 'H1',
					'h2' => 'H2',
				],
				'description' => esc_html__( 'Use H1 only when this is the main heading of the page (one H1 per page).', 'lihan' ),
				'condition'   => [ 'hide_heading!' => 'yes' ],
			]
		);

		$this->add_control(
			'hide_text',
			[
				'label'        => esc_html__( 'Hide text', 'lihan' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'text',
			[
				'label'       => esc_html__( 'Text', 'lihan' ),
				'type'        => Controls_Manager::WYSIWYG,
				'description' => esc_html__( 'Empty = Customizer text.', 'lihan' ),
				'dynamic'     => [ 'active' => true ],
				'condition'   => [ 'hide_text!' => 'yes' ],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_categories',
			[ 'label' => esc_html__( 'Categories', 'lihan' ) ]
		);

		$this->add_control(
			'show_categories',
			[
				'label'        => esc_html__( 'Show category tiles', 'lihan' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'categories',
			[
				'label'       => esc_html__( 'Categories', 'lihan' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_product_category_options(),
				'description' => esc_html__( 'Shown in the order chosen. Empty = Customizer list, else the top-level categories.', 'lihan' ),
				'condition'   => [ 'show_categories' => 'yes' ],
			]
		);

		$this->add_control(
			'limit',
			[
				'label'       => esc_html__( 'Maximum tiles', 'lihan' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 12,
				'default'     => 6,
				'description' => esc_html__( 'Used when the categories are picked automatically.', 'lihan' ),
				'condition'   => [ 'show_categories' => 'yes' ],
			]
		);

		$this->end_controls_section();
	}

	protected function lihan_section_args( array $settings ): array {
		$categories = array_values( array_filter( array_map( 'sanitize_title', (array) ( $settings['categories'] ?? [] ) ) ) );
		$tag        = (string) ( $settings['heading_tag'] ?? '' );

		return [
			'heading'         => $this->text_arg( $settings, 'heading', 'hide_heading' ),
			'heading_tag'     => in_array( $tag, [ 'h1', 'h2' ], true ) ? $tag : null,
			'text'            => $this->text_arg( $settings, 'text', 'hide_text' ),
			'show_categories' => 'yes' === ( $settings['show_categories'] ?? '' ),
			'categories'      => $categories,
			'limit'           => $this->int_arg( $settings['limit'] ?? null ),
		];
	}
}
