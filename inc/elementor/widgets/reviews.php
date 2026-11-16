<?php
/**
 * Elementor widget: Reviews (section "reviews").
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

/**
 * Customer review carousel.
 */
class Lihan_Elementor_Widget_Reviews extends Lihan_Elementor_Widget_Base {

	public function get_name() {
		return 'lihan-reviews';
	}

	public function get_title() {
		return esc_html__( 'Reviews', 'lihan' );
	}

	public function get_icon() {
		return 'eicon-testimonial';
	}

	public function get_keywords(): array {
		return [ 'lihan', 'reviews', 'testimonials', 'quotes', 'rating', 'stars' ];
	}

	protected function get_section_name(): string {
		return 'reviews';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[ 'label' => esc_html__( 'Reviews', 'lihan' ) ]
		);

		$this->add_control(
			'source',
			[
				'label'   => esc_html__( 'Source', 'lihan' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''       => esc_html__( 'Default', 'lihan' ),
					'auto'   => esc_html__( 'Product reviews (4★ and up), else the list', 'lihan' ),
					'manual' => esc_html__( 'Only the list', 'lihan' ),
				],
				'description' => esc_html__( 'Default: the list below when it has reviews, otherwise the Customizer setting.', 'lihan' ),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'lihan' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'text',
			[
				'label' => esc_html__( 'Review', 'lihan' ),
				'type'  => Controls_Manager::TEXTAREA,
				'rows'  => 4,
			]
		);

		$repeater->add_control(
			'name',
			[
				'label' => esc_html__( 'Customer name', 'lihan' ),
				'type'  => Controls_Manager::TEXT,
			]
		);

		$repeater->add_control(
			'rating',
			[
				'label'   => esc_html__( 'Rating', 'lihan' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '5',
				'options' => [
					'5' => '★★★★★',
					'4' => '★★★★',
					'3' => '★★★',
					'2' => '★★',
					'1' => '★',
				],
			]
		);

		$this->add_control(
			'items',
			[
				'label'       => esc_html__( 'Review list', 'lihan' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [],
				'title_field' => '{{{ title || name }}}',
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'count',
			[
				'label'       => esc_html__( 'Maximum product reviews', 'lihan' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 20,
				'placeholder' => '8',
				'condition'   => [ 'source!' => 'manual' ],
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label'       => esc_html__( 'Autoplay (seconds)', 'lihan' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 30,
				'placeholder' => '7',
				'description' => esc_html__( '0 turns autoplay off. It always pauses on hover and focus.', 'lihan' ),
			]
		);

		$this->add_control(
			'heading',
			[
				'label'       => esc_html__( 'Section label (screen readers)', 'lihan' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Customer reviews', 'lihan' ),
			]
		);

		$this->end_controls_section();
	}

	protected function lihan_section_args( array $settings ): array {
		$items = [];

		foreach ( (array) ( $settings['items'] ?? [] ) as $item ) {
			if ( ! is_array( $item ) || '' === trim( (string) ( $item['text'] ?? '' ) ) ) {
				continue;
			}

			$items[] = [
				'title'  => trim( (string) ( $item['title'] ?? '' ) ),
				'text'   => trim( (string) $item['text'] ),
				'name'   => trim( (string) ( $item['name'] ?? '' ) ),
				'rating' => (string) max( 1, min( 5, (int) ( $item['rating'] ?? 5 ) ) ),
			];
		}

		$source   = (string) ( $settings['source'] ?? '' );
		$autoplay = $this->int_arg( $settings['autoplay'] ?? null );

		return [
			'source'   => in_array( $source, [ 'auto', 'manual' ], true ) ? $source : null,
			'items'    => $items,
			'count'    => $this->int_arg( $settings['count'] ?? null ),
			// The section expects milliseconds.
			'autoplay' => null === $autoplay ? null : max( 0, min( 30, $autoplay ) ) * 1000,
			'heading'  => $this->text_arg( $settings, 'heading' ),
		];
	}
}
