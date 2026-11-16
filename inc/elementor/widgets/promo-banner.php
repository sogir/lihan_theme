<?php
/**
 * Elementor widget: Promo Banner (section "promo_banner").
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Dark brand panel with text and button, next to an image.
 */
class Lihan_Elementor_Widget_Promo_Banner extends Lihan_Elementor_Widget_Base {

	public function get_name() {
		return 'lihan-promo-banner';
	}

	public function get_title() {
		return esc_html__( 'Promo Banner', 'lihan' );
	}

	public function get_icon() {
		return 'eicon-call-to-action';
	}

	public function get_keywords(): array {
		return [ 'lihan', 'promo', 'banner', 'call to action', 'cta', 'club', 'whatsapp' ];
	}

	protected function get_section_name(): string {
		return 'promo_banner';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[ 'label' => esc_html__( 'Promo banner', 'lihan' ) ]
		);

		$this->add_control(
			'show_logo',
			[
				'label'   => esc_html__( 'White logo above the heading', 'lihan' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''    => esc_html__( 'Customizer setting', 'lihan' ),
					'yes' => esc_html__( 'Show', 'lihan' ),
					'no'  => esc_html__( 'Hide', 'lihan' ),
				],
			]
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
				'rows'        => 4,
				'placeholder' => $this->customizer_placeholder(),
				'dynamic'     => [ 'active' => true ],
				'condition'   => [ 'hide_text!' => 'yes' ],
			]
		);

		$this->add_button_controls( 'url', true, esc_html__( 'Empty = WhatsApp chat.', 'lihan' ) );

		$this->add_control(
			'image',
			[
				'label'       => esc_html__( 'Image', 'lihan' ),
				'type'        => Controls_Manager::MEDIA,
				'dynamic'     => [ 'active' => true ],
				'description' => esc_html__( 'Empty = Customizer image. Portrait images (about 4:5) work best.', 'lihan' ),
				'separator'   => 'before',
			]
		);

		$this->end_controls_section();
	}

	protected function lihan_section_args( array $settings ): array {
		return [
			'show_logo' => $this->bool_arg( $settings['show_logo'] ?? '' ),
			'heading'   => $this->text_arg( $settings, 'heading', 'hide_heading' ),
			'text'      => $this->text_arg( $settings, 'text', 'hide_text' ),
			'button'    => $this->text_arg( $settings, 'button', 'hide_button' ),
			'url'       => $this->url_arg( $settings['url'] ?? null ),
			'image'     => $this->media_arg( $settings['image'] ?? null ),
		];
	}
}
