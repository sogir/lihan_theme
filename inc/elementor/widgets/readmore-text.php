<?php
/**
 * Elementor widget: Read-more Text (section "seo_text").
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Heading + long text clamped to a few lines with a "Read More" toggle.
 */
class Lihan_Elementor_Widget_Readmore_Text extends Lihan_Elementor_Widget_Base {

	public function get_name() {
		return 'lihan-readmore-text';
	}

	public function get_title() {
		return esc_html__( 'Read-more Text', 'lihan' );
	}

	public function get_icon() {
		return 'eicon-text';
	}

	public function get_keywords(): array {
		return [ 'lihan', 'text', 'read more', 'seo', 'expand', 'collapse' ];
	}

	protected function get_section_name(): string {
		return 'seo_text';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[ 'label' => esc_html__( 'Read-more text', 'lihan' ) ]
		);

		$this->add_heading_controls();

		$this->add_control(
			'text',
			[
				'label'       => esc_html__( 'Text', 'lihan' ),
				'type'        => Controls_Manager::WYSIWYG,
				'description' => esc_html__( 'Empty = Customizer text.', 'lihan' ),
				'dynamic'     => [ 'active' => true ],
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'lines',
			[
				'label'   => esc_html__( 'Visible lines', 'lihan' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 20,
				'default' => 3,
			]
		);

		$this->end_controls_section();
	}

	protected function lihan_section_args( array $settings ): array {
		return [
			'heading' => $this->text_arg( $settings, 'heading', 'hide_heading' ),
			'text'    => $this->text_arg( $settings, 'text' ),
			'lines'   => $this->int_arg( $settings['lines'] ?? null ),
		];
	}
}
