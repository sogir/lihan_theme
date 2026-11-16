<?php
/**
 * Elementor widget: FAQ (section "faq").
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

/**
 * Accordion of questions (native <details>) with FAQPage structured data.
 */
class Lihan_Elementor_Widget_Faq extends Lihan_Elementor_Widget_Base {

	public function get_name() {
		return 'lihan-faq';
	}

	public function get_title() {
		return esc_html__( 'FAQ', 'lihan' );
	}

	public function get_icon() {
		return 'eicon-accordion';
	}

	public function get_keywords(): array {
		return [ 'lihan', 'faq', 'questions', 'accordion', 'toggle', 'schema' ];
	}

	protected function get_section_name(): string {
		return 'faq';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[ 'label' => esc_html__( 'FAQ', 'lihan' ) ]
		);

		$this->add_heading_controls();

		$repeater = new Repeater();

		$repeater->add_control(
			'question',
			[
				'label'       => esc_html__( 'Question', 'lihan' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'answer',
			[
				'label'       => esc_html__( 'Answer', 'lihan' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 5,
				'description' => esc_html__( 'Links, bold and italic are allowed. Line breaks are kept.', 'lihan' ),
			]
		);

		$this->add_control(
			'items',
			[
				'label'       => esc_html__( 'Questions', 'lihan' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [],
				'title_field' => '{{{ question }}}',
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'items_note',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'No questions here? The list from the Customizer (Homepage: FAQ) is shown.', 'lihan' ),
				'content_classes' => 'elementor-descriptor',
			]
		);

		$this->add_control(
			'open',
			[
				'label'       => esc_html__( 'Open item', 'lihan' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 50,
				'placeholder' => '0',
				'description' => esc_html__( 'Number of the question to show open (0 = all closed).', 'lihan' ),
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'schema',
			[
				'label'       => esc_html__( 'FAQ structured data', 'lihan' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => [
					''    => esc_html__( 'Customizer setting', 'lihan' ),
					'yes' => esc_html__( 'On', 'lihan' ),
					'no'  => esc_html__( 'Off', 'lihan' ),
				],
				'description' => esc_html__( 'Printed once per page, for the first FAQ.', 'lihan' ),
			]
		);

		$this->end_controls_section();
	}

	protected function lihan_section_args( array $settings ): array {
		$items = [];

		foreach ( (array) ( $settings['items'] ?? [] ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$question = trim( (string) ( $item['question'] ?? '' ) );
			$answer   = trim( (string) ( $item['answer'] ?? '' ) );

			if ( '' === $question || '' === $answer ) {
				continue;
			}

			$items[] = [
				'question' => $question,
				// The section allows <br>, so keep the editor's line breaks.
				'answer'   => nl2br( $answer, false ),
			];
		}

		$open = $this->int_arg( $settings['open'] ?? null );

		return [
			'heading' => $this->text_arg( $settings, 'heading', 'hide_heading' ),
			'items'   => $items,
			'open'    => $open && $open > 0 ? $open : null,
			'schema'  => $this->bool_arg( $settings['schema'] ?? '' ),
		];
	}
}
