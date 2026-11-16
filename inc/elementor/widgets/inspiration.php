<?php
/**
 * Elementor widget: Inspiration Reels (section "inspiration").
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Portrait reel cards with shoppable mini product cards.
 */
class Lihan_Elementor_Widget_Inspiration extends Lihan_Elementor_Widget_Base {

	public function get_name() {
		return 'lihan-inspiration';
	}

	public function get_title() {
		return esc_html__( 'Inspiration Reels', 'lihan' );
	}

	public function get_icon() {
		return 'eicon-video-playlist';
	}

	public function get_keywords(): array {
		return [ 'lihan', 'inspiration', 'reels', 'lookbook', 'video', 'products', 'woocommerce' ];
	}

	protected function get_section_name(): string {
		return 'inspiration';
	}

	protected function plain_content_as_shortcode(): bool {
		return true;
	}

	protected function get_empty_message(): string {
		return __( 'No products to show. Inspiration reels need WooCommerce and at least one published product.', 'lihan' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[ 'label' => esc_html__( 'Content', 'lihan' ) ]
		);

		$this->add_heading_controls();

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
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'placeholder' => $this->customizer_placeholder(),
				'dynamic'     => [ 'active' => true ],
				'condition'   => [ 'hide_text!' => 'yes' ],
			]
		);

		$this->add_button_controls( 'button_url', true, esc_html__( 'Empty = your Facebook page (Customizer → contact & social).', 'lihan' ) );

		$this->end_controls_section();

		$this->start_controls_section(
			'section_products',
			[ 'label' => esc_html__( 'Products', 'lihan' ) ]
		);

		$this->add_control(
			'tag',
			[
				'label'       => esc_html__( 'Product tag (slug)', 'lihan' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'inspiration',
				'description' => esc_html__( 'Products with this tag; falls back to featured, then the latest products. Add a .mp4/.webm URL in the product field _lihan_reel_video to show a video.', 'lihan' ),
			]
		);

		$this->add_control(
			'products',
			[
				'label'       => esc_html__( 'Or specific product IDs', 'lihan' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => '12, 34, 56',
				'description' => esc_html__( 'Comma separated, in display order. Overrides the tag.', 'lihan' ),
			]
		);

		$this->add_control(
			'count',
			[
				'label'       => esc_html__( 'Number of reels', 'lihan' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 3,
				'max'         => 16,
				'placeholder' => $this->customizer_placeholder(),
			]
		);

		$this->end_controls_section();
	}

	protected function lihan_section_args( array $settings ): array {
		$tag = sanitize_title( (string) ( $settings['tag'] ?? '' ) );

		return [
			'heading'    => $this->text_arg( $settings, 'heading', 'hide_heading' ),
			'text'       => $this->text_arg( $settings, 'text', 'hide_text' ),
			'button'     => $this->text_arg( $settings, 'button', 'hide_button' ),
			'button_url' => $this->url_arg( $settings['button_url'] ?? null ),
			'tag'        => '' === $tag ? null : $tag,
			'products'   => $this->ids_arg( $settings['products'] ?? '' ),
			'count'      => $this->int_arg( $settings['count'] ?? null ),
		];
	}
}
