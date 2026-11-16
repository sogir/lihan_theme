<?php
/**
 * Elementor widget: Hero Slider (section "hero").
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

/**
 * Hero fade slider.
 */
class Lihan_Elementor_Widget_Hero extends Lihan_Elementor_Widget_Base {

	public function get_name() {
		return 'lihan-hero';
	}

	public function get_title() {
		return esc_html__( 'Hero Slider', 'lihan' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_keywords(): array {
		return [ 'lihan', 'hero', 'slider', 'banner', 'carousel' ];
	}

	protected function get_section_name(): string {
		return 'hero';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_slides',
			[ 'label' => esc_html__( 'Slides', 'lihan' ) ]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'image',
			[
				'label'   => esc_html__( 'Image', 'lihan' ),
				'type'    => Controls_Manager::MEDIA,
				'dynamic' => [ 'active' => true ],
				'description' => esc_html__( 'Recommended 1920 × 1000.', 'lihan' ),
			]
		);

		$repeater->add_control(
			'image_mobile',
			[
				'label'       => esc_html__( 'Mobile image (optional)', 'lihan' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => esc_html__( 'Portrait 4:5, e.g. 1080 × 1350. The text sits below it on phones.', 'lihan' ),
			]
		);

		$repeater->add_control(
			'heading',
			[
				'label'       => esc_html__( 'Heading', 'lihan' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$repeater->add_control(
			'text',
			[
				'label'   => esc_html__( 'Text', 'lihan' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'dynamic' => [ 'active' => true ],
			]
		);

		$repeater->add_control(
			'button',
			[
				'label'       => esc_html__( 'Button label', 'lihan' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Leave empty for no button.', 'lihan' ),
			]
		);

		$repeater->add_control(
			'link',
			[
				'label'       => esc_html__( 'Button link', 'lihan' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => [ 'active' => true ],
				'options'     => false,
				'description' => esc_html__( 'Empty = shop page.', 'lihan' ),
			]
		);

		$repeater->add_control(
			'color',
			[
				'label'   => esc_html__( 'Text colour', 'lihan' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dark',
				'options' => [
					'dark'  => esc_html__( 'Dark (for light images)', 'lihan' ),
					'light' => esc_html__( 'Light (for dark images)', 'lihan' ),
				],
			]
		);

		$this->add_control(
			'slides',
			[
				'label'       => esc_html__( 'Slides (up to 4)', 'lihan' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [],
				'title_field' => '{{{ heading || "' . esc_js( __( 'Slide', 'lihan' ) ) . '" }}}',
				'max_items'   => 4,
			]
		);

		$this->add_control(
			'slides_note',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'No slides here? The slider uses the slides from Appearance → Customize → Lihan Theme → Homepage: Hero slider.', 'lihan' ),
				'content_classes' => 'elementor-descriptor',
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label'       => esc_html__( 'Autoplay (seconds)', 'lihan' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 30,
				'step'        => 1,
				'placeholder' => $this->customizer_placeholder(),
				'description' => esc_html__( '0 turns autoplay off. It always pauses on hover and focus.', 'lihan' ),
				'separator'   => 'before',
			]
		);

		$this->end_controls_section();
	}

	protected function lihan_section_args( array $settings ): array {
		$slides = [];

		foreach ( array_slice( (array) ( $settings['slides'] ?? [] ), 0, 4 ) as $slide ) {
			if ( ! is_array( $slide ) ) {
				continue;
			}

			$slides[] = [
				'image'        => $this->media_arg( $slide['image'] ?? null ) ?? '',
				'image_mobile' => $this->media_arg( $slide['image_mobile'] ?? null ) ?? '',
				'heading'      => (string) ( $slide['heading'] ?? '' ),
				'text'         => (string) ( $slide['text'] ?? '' ),
				'button'       => (string) ( $slide['button'] ?? '' ),
				'url'          => $this->url_arg( $slide['link'] ?? null ) ?? '',
				'color'        => 'light' === ( $slide['color'] ?? '' ) ? 'light' : 'dark',
			];
		}

		$autoplay = $this->int_arg( $settings['autoplay'] ?? null );

		return [
			'slides'   => $slides,
			// Seconds; the section treats values under 100 as seconds.
			'autoplay' => null === $autoplay ? null : max( 0, min( 30, $autoplay ) ),
		];
	}
}
