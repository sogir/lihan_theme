<?php
/**
 * Elementor widget: Image + Text (section "image_text").
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Two columns: image and rich text with a button.
 */
class Lihan_Elementor_Widget_Image_Text extends Lihan_Elementor_Widget_Base {

	public function get_name() {
		return 'lihan-image-text';
	}

	public function get_title() {
		return esc_html__( 'Image + Text', 'lihan' );
	}

	public function get_icon() {
		return 'eicon-image-box';
	}

	public function get_keywords(): array {
		return [ 'lihan', 'image', 'text', 'about', 'mission', 'story', 'columns' ];
	}

	protected function get_section_name(): string {
		return 'image_text';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[ 'label' => esc_html__( 'Image + text', 'lihan' ) ]
		);

		$this->add_control(
			'image',
			[
				'label'       => esc_html__( 'Image', 'lihan' ),
				'type'        => Controls_Manager::MEDIA,
				'dynamic'     => [ 'active' => true ],
				'description' => esc_html__( 'Empty = Customizer image.', 'lihan' ),
			]
		);

		$this->add_control(
			'image_position',
			[
				'label'   => esc_html__( 'Image position', 'lihan' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => '',
				'toggle'  => true,
				'options' => [
					'left'  => [
						'title' => esc_html__( 'Left', 'lihan' ),
						'icon'  => 'eicon-h-align-left',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'lihan' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
			]
		);

		$this->add_heading_controls( esc_html__( 'Heading (optional)', 'lihan' ) );

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

		$this->add_button_controls( 'url', true, esc_html__( 'Empty = contact page, else Messenger.', 'lihan' ) );

		$this->end_controls_section();
	}

	protected function lihan_section_args( array $settings ): array {
		$position = (string) ( $settings['image_position'] ?? '' );

		return [
			'image'          => $this->media_arg( $settings['image'] ?? null ),
			'image_position' => in_array( $position, [ 'left', 'right' ], true ) ? $position : null,
			'heading'        => $this->text_arg( $settings, 'heading', 'hide_heading' ),
			'text'           => $this->text_arg( $settings, 'text', 'hide_text' ),
			'button'         => $this->text_arg( $settings, 'button', 'hide_button' ),
			'url'            => $this->url_arg( $settings['url'] ?? null ),
		];
	}
}
