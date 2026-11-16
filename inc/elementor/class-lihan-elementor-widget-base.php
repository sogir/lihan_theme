<?php
/**
 * Base class for the Lihan Elementor widgets.
 *
 * Every widget is a thin wrapper: it maps its controls to the $args documented at
 * the top of template-parts/home/{section}.php and calls lihan_render_section().
 * An empty control means "use the Customizer value", so a freshly dropped widget
 * looks exactly like the homepage section.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Plugin;
use Elementor\Widget_Base;

/**
 * Shared behaviour for the section widgets.
 */
abstract class Lihan_Elementor_Widget_Base extends Widget_Base {

	/**
	 * Section name passed to lihan_render_section().
	 */
	abstract protected function get_section_name(): string;

	/**
	 * Map widget settings to section $args.
	 *
	 * @param array<string,mixed> $settings Settings for display.
	 * @return array<string,mixed>
	 */
	abstract protected function lihan_section_args( array $settings ): array;

	/**
	 * Widget category.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return [ 'lihan' ];
	}

	/**
	 * Search keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords(): array {
		return [ 'lihan', 'section', 'home' ];
	}

	/**
	 * Section styles (registered by inc/home.php as "lihan-home").
	 *
	 * @return string[]
	 */
	public function get_style_depends(): array {
		return [ 'lihan-home' ];
	}

	/**
	 * Section script, when the theme ships one.
	 *
	 * @return string[]
	 */
	public function get_script_depends(): array {
		return is_readable( LIHAN_DIR . '/assets/js/home.js' ) ? [ 'lihan-home' ] : [];
	}

	/**
	 * Drop the extra .elementor-widget-container div when Elementor's
	 * optimized markup is on (the section brings its own wrapper).
	 */
	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
	}

	/**
	 * Output depends on products, reviews and Customizer values: never cache it.
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Whether the post-content copy (used by search and SEO plugins, and shown
	 * if Elementor is deactivated) is a [lihan_section] shortcode instead of
	 * static HTML. Used for product-driven sections that would go stale.
	 */
	protected function plain_content_as_shortcode(): bool {
		return false;
	}

	/**
	 * Render the section.
	 */
	protected function render(): void {
		if ( ! function_exists( 'lihan_render_section' ) ) {
			$this->editor_notice( __( 'The Lihan homepage sections are not available (inc/home.php is missing).', 'lihan' ) );
			return;
		}

		$args = $this->build_args();

		ob_start();
		lihan_render_section( $this->get_section_name(), $args );
		$html = (string) ob_get_clean();

		if ( '' === trim( $html ) ) {
			$this->editor_notice( $this->get_empty_message() );
			return;
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the section template.
	}

	/**
	 * Post-content copy of the widget.
	 */
	public function render_plain_content() {
		if ( ! $this->plain_content_as_shortcode() ) {
			$this->render_content();
			return;
		}

		$attrs = '';

		foreach ( $this->build_args() as $key => $value ) {
			if ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			} elseif ( is_array( $value ) && array_filter( $value, 'is_scalar' ) === $value ) {
				$value = implode( ',', $value ); // ID / slug lists.
			}

			if ( ! is_scalar( $value ) || 'context' === $key ) {
				continue;
			}

			// Characters that would end the attribute or the shortcode.
			$value = str_replace( [ '"', '[', ']' ], '', (string) $value );
			$attrs .= sprintf( ' %s="%s"', sanitize_key( (string) $key ), esc_attr( $value ) );
		}

		echo '[lihan_section name="' . esc_attr( $this->get_section_name() ) . '"' . $attrs . ']'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes escaped above.
	}

	/**
	 * Settings → args, without empty values (so the Customizer value applies).
	 *
	 * @return array<string,mixed>
	 */
	protected function build_args(): array {
		$args = array_filter(
			$this->lihan_section_args( (array) $this->get_settings_for_display() ),
			static function ( $value ): bool {
				return null !== $value && '' !== $value && [] !== $value;
			}
		);

		$args['context'] = 'elementor';

		return $args;
	}

	/**
	 * Message shown in the editor when the section renders nothing.
	 */
	protected function get_empty_message(): string {
		return __( 'This section has nothing to show yet. Add content in the widget settings or in Appearance → Customize → Lihan Theme.', 'lihan' );
	}

	/**
	 * Show a notice in the editor only (nothing on the live site).
	 *
	 * @param string $message Message.
	 */
	protected function editor_notice( string $message ): void {
		if ( ! Plugin::$instance->editor->is_edit_mode() && ! Plugin::$instance->preview->is_preview_mode() ) {
			return;
		}

		printf(
			'<div class="lh-elementor-notice" role="note">%s</div>',
			esc_html( $message )
		);
	}

	/* ---------------------------------------------------------------------
	 * Value helpers
	 * ------------------------------------------------------------------ */

	/**
	 * A text setting: false when its "hide" switcher is on, null when empty.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @param string              $key      Setting key.
	 * @param string              $hide_key Switcher that hides the element.
	 * @return string|false|null
	 */
	protected function text_arg( array $settings, string $key, string $hide_key = '' ) {
		if ( '' !== $hide_key && 'yes' === ( $settings[ $hide_key ] ?? '' ) ) {
			return false;
		}

		$value = isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) ? trim( (string) $settings[ $key ] ) : '';

		return '' === $value ? null : $value;
	}

	/**
	 * A media setting as an attachment ID, else its URL, else null.
	 *
	 * @param mixed $media MEDIA control value.
	 * @return int|string|null
	 */
	protected function media_arg( $media ) {
		if ( ! is_array( $media ) ) {
			return null;
		}

		if ( ! empty( $media['id'] ) ) {
			return absint( $media['id'] );
		}

		$url = isset( $media['url'] ) ? trim( (string) $media['url'] ) : '';

		// Elementor's placeholder image is not real content.
		if ( '' === $url || ( class_exists( '\Elementor\Utils' ) && \Elementor\Utils::get_placeholder_image_src() === $url ) ) {
			return null;
		}

		return esc_url_raw( $url );
	}

	/**
	 * A URL setting as a string, else null.
	 *
	 * @param mixed $link URL control value.
	 */
	protected function url_arg( $link ): ?string {
		$url = is_array( $link ) ? trim( (string) ( $link['url'] ?? '' ) ) : '';

		return '' === $url ? null : $url;
	}

	/**
	 * A number setting as an int, else null.
	 *
	 * @param mixed $value Value.
	 */
	protected function int_arg( $value ): ?int {
		return is_numeric( $value ) ? (int) $value : null;
	}

	/**
	 * A yes/no/default select as bool, else null (Customizer value).
	 *
	 * @param mixed $value '', 'yes' or 'no'.
	 */
	protected function bool_arg( $value ): ?bool {
		if ( 'yes' === $value ) {
			return true;
		}

		return 'no' === $value ? false : null;
	}

	/**
	 * A comma/space separated ID list as int[], else null.
	 *
	 * @param mixed $value Value.
	 * @return int[]|null
	 */
	protected function ids_arg( $value ): ?array {
		$ids = array_values( array_filter( array_map( 'absint', preg_split( '/[\s,]+/', (string) $value ) ?: [] ) ) );

		return $ids ? $ids : null;
	}

	/* ---------------------------------------------------------------------
	 * Control helpers
	 * ------------------------------------------------------------------ */

	/**
	 * Placeholder text for fields that fall back to the Customizer.
	 */
	protected function customizer_placeholder(): string {
		return __( 'Empty = Customizer value', 'lihan' );
	}

	/**
	 * Heading text control + "hide heading" switcher.
	 *
	 * @param string $label Label.
	 */
	protected function add_heading_controls( string $label = '' ): void {
		$this->add_control(
			'heading',
			[
				'label'       => '' !== $label ? $label : esc_html__( 'Heading', 'lihan' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => $this->customizer_placeholder(),
				'dynamic'     => [ 'active' => true ],
				'condition'   => [ 'hide_heading!' => 'yes' ],
			]
		);

		$this->add_control(
			'hide_heading',
			[
				'label'        => esc_html__( 'Hide heading', 'lihan' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			]
		);
	}

	/**
	 * Button label + link controls (+ optional hide switcher).
	 *
	 * @param string $url_key    Setting key for the link.
	 * @param bool   $hideable   Add a "hide button" switcher.
	 * @param string $url_help   Help text for the link.
	 */
	protected function add_button_controls( string $url_key = 'url', bool $hideable = true, string $url_help = '' ): void {
		if ( $hideable ) {
			$this->add_control(
				'hide_button',
				[
					'label'        => esc_html__( 'Hide button', 'lihan' ),
					'type'         => Controls_Manager::SWITCHER,
					'return_value' => 'yes',
					'default'      => '',
					'separator'    => 'before',
				]
			);
		}

		$condition = $hideable ? [ 'hide_button!' => 'yes' ] : [];

		$this->add_control(
			'button',
			[
				'label'       => esc_html__( 'Button label', 'lihan' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => $this->customizer_placeholder(),
				'condition'   => $condition,
				'separator'   => $hideable ? 'none' : 'before',
			]
		);

		$this->add_control(
			$url_key,
			[
				'label'       => esc_html__( 'Button link', 'lihan' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => [ 'active' => true ],
				'options'     => false,
				'description' => $url_help,
				'condition'   => $condition,
			]
		);
	}

	/**
	 * Product category choices (slug => name).
	 *
	 * @return array<string,string>
	 */
	protected function get_product_category_options(): array {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return [];
		}

		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'number'     => 200,
			]
		);

		if ( ! is_array( $terms ) ) {
			return [];
		}

		$options = [];

		foreach ( $terms as $term ) {
			$options[ $term->slug ] = $term->name;
		}

		return $options;
	}
}
