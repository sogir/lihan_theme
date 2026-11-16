<?php
/**
 * Elementor widget: Product Carousel (section "featured_products").
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * WooCommerce product carousel using the theme's product cards.
 */
class Lihan_Elementor_Widget_Product_Carousel extends Lihan_Elementor_Widget_Base {

	public function get_name() {
		return 'lihan-product-carousel';
	}

	public function get_title() {
		return esc_html__( 'Product Carousel', 'lihan' );
	}

	public function get_icon() {
		return 'eicon-products';
	}

	public function get_keywords(): array {
		return [ 'lihan', 'products', 'carousel', 'slider', 'trending', 'best selling', 'featured', 'sale', 'woocommerce' ];
	}

	protected function get_section_name(): string {
		return 'featured_products';
	}

	protected function plain_content_as_shortcode(): bool {
		return true;
	}

	protected function get_empty_message(): string {
		return __( 'No products match this source yet. Product carousels need WooCommerce and published products.', 'lihan' );
	}

	/**
	 * Source choices: the homepage list plus "specific products".
	 *
	 * @return array<string,string>
	 */
	protected function get_source_options(): array {
		$sources = function_exists( 'lihan_home_trending_sources' )
			? lihan_home_trending_sources()
			: [
				'best_selling' => __( 'Best selling', 'lihan' ),
				'featured'     => __( 'Featured', 'lihan' ),
				'on_sale'      => __( 'On sale', 'lihan' ),
				'latest'       => __( 'Latest', 'lihan' ),
			];

		return array_merge(
			[ '' => __( 'Customizer setting', 'lihan' ) ],
			$sources,
			[ 'ids' => __( 'Specific products', 'lihan' ) ]
		);
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[ 'label' => esc_html__( 'Products', 'lihan' ) ]
		);

		$this->add_heading_controls();

		$this->add_control(
			'source',
			[
				'label'     => esc_html__( 'Show', 'lihan' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => $this->get_source_options(),
				'separator' => 'before',
			]
		);

		$this->add_control(
			'products',
			[
				'label'       => esc_html__( 'Product IDs', 'lihan' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => '12, 34, 56',
				'description' => esc_html__( 'Comma separated, in display order.', 'lihan' ),
				'condition'   => [ 'source' => 'ids' ],
			]
		);

		$this->add_control(
			'count',
			[
				'label'       => esc_html__( 'Number of products', 'lihan' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 4,
				'max'         => 24,
				'placeholder' => $this->customizer_placeholder(),
				'condition'   => [ 'source!' => 'ids' ],
			]
		);

		$this->add_control(
			'per_view',
			[
				'label'       => esc_html__( 'Cards per view (desktop)', 'lihan' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '4',
				'options'     => [
					'3' => '3',
					'4' => '4',
					'5' => '5',
				],
				'description' => esc_html__( 'Tablets show up to 3, phones 2.', 'lihan' ),
			]
		);

		$this->add_button_controls( 'button_url', false, esc_html__( 'Optional "view all" button below the carousel. Empty link = shop page.', 'lihan' ) );

		$this->end_controls_section();
	}

	protected function lihan_section_args( array $settings ): array {
		$source = (string) ( $settings['source'] ?? '' );
		$ids    = 'ids' === $source ? $this->ids_arg( $settings['products'] ?? '' ) : null;

		return [
			'heading'    => $this->text_arg( $settings, 'heading', 'hide_heading' ),
			'source'     => '' === $source || 'ids' === $source ? null : sanitize_text_field( $source ),
			'products'   => $ids,
			'count'      => $ids ? count( $ids ) : $this->int_arg( $settings['count'] ?? null ),
			'per_view'   => $this->int_arg( $settings['per_view'] ?? null ),
			'button'     => $this->text_arg( $settings, 'button' ),
			'button_url' => $this->url_arg( $settings['button_url'] ?? null ),
		];
	}
}
