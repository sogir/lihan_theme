<?php
/**
 * Elementor compatibility.
 *
 * - Registers Elementor Pro's core theme locations (header, footer, single, archive).
 * - Syncs the theme's colours and font into the active Elementor kit once, and only
 *   for kit values that are still Elementor's factory defaults.
 * - Adds the "Lihan" widget category and one widget per homepage section
 *   (inc/elementor/widgets/*.php, thin wrappers around lihan_render_section()).
 * - Re-initialises the theme's JS components (window.Lihan.init) when Elementor
 *   re-renders a widget in the editor preview.
 * - Loads assets/css/elementor.css wherever Elementor loads its frontend styles.
 * - Respects Elementor's "Hide Title" page setting via the lihan_show_page_title filter.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether Elementor is loaded.
 */
function lihan_elementor_active(): bool {
	return did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' );
}

/* -------------------------------------------------------------------------
 * Theme locations (Elementor Pro theme builder)
 * ---------------------------------------------------------------------- */

/**
 * Register header, footer, single and archive locations.
 *
 * header.php/footer.php call elementor_theme_do_location() and fall back to the
 * theme markup when no Elementor template is assigned.
 *
 * @param object $manager ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager.
 */
function lihan_elementor_register_locations( $manager ): void {
	if ( is_object( $manager ) && method_exists( $manager, 'register_all_core_location' ) ) {
		$manager->register_all_core_location();
	}
}
add_action( 'elementor/theme/register_locations', 'lihan_elementor_register_locations' );

/* -------------------------------------------------------------------------
 * Kit sync (runs once, never overrides values the user changed)
 * ---------------------------------------------------------------------- */

/**
 * Elementor's factory defaults for the kit settings the theme manages.
 *
 * Copied from elementor/core/kits/documents/tabs/global-colors.php,
 * global-typography.php and settings-layout.php (Elementor 4.4).
 *
 * @return array{colors:array<string,string>,fonts:array<string,array{0:string,1:string}>,container:int}
 */
function lihan_elementor_factory_defaults(): array {
	return [
		'colors'    => [
			'primary'   => '#6EC1E4',
			'secondary' => '#54595F',
			'text'      => '#7A7A7A',
			'accent'    => '#61CE70',
		],
		'fonts'     => [
			'primary'   => [ 'Roboto', '600' ],
			'secondary' => [ 'Roboto Slab', '400' ],
			'text'      => [ 'Roboto', '400' ],
			'accent'    => [ 'Roboto', '500' ],
		],
		'container' => 1140,
	];
}

/**
 * Read a theme colour (customizer value, else the design token default).
 *
 * @param string $key      Option key.
 * @param string $fallback Default hex.
 */
function lihan_elementor_theme_color( string $key, string $fallback ): string {
	$value = function_exists( 'lihan_get_option' ) ? sanitize_hex_color( (string) lihan_get_option( $key ) ) : '';

	return $value ? $value : $fallback;
}

/**
 * Whether a saved global list (system_colors / system_typography) still holds
 * Elementor's defaults.
 *
 * @param mixed                $saved    Saved list (missing = default).
 * @param array<string,string> $defaults _id => expected default value.
 * @param string               $field    Field compared.
 */
function lihan_elementor_list_is_default( $saved, array $defaults, string $field ): bool {
	if ( empty( $saved ) ) {
		return true;
	}

	if ( ! is_array( $saved ) ) {
		return false;
	}

	foreach ( $saved as $item ) {
		if ( ! is_array( $item ) || empty( $item['_id'] ) || ! isset( $defaults[ $item['_id'] ] ) ) {
			continue;
		}

		$value = isset( $item[ $field ] ) ? (string) $item[ $field ] : '';

		if ( '' !== $value && strtoupper( $value ) !== strtoupper( $defaults[ $item['_id'] ] ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Write the theme tokens into the active kit where it still has defaults.
 *
 * @return bool True when the sync is finished (done, or nothing to do), false
 *              when Elementor or its kit is not available yet (try again later).
 */
function lihan_elementor_sync_kit(): bool {
	if ( ! lihan_elementor_active() || ! isset( \Elementor\Plugin::$instance->kits_manager ) ) {
		return false;
	}

	$kit_id = (int) \Elementor\Plugin::$instance->kits_manager->get_active_id();
	$kit    = $kit_id ? get_post( $kit_id ) : null;

	if ( ! $kit instanceof WP_Post ) {
		return false;
	}

	$meta_key = '_elementor_page_settings';
	$settings = get_post_meta( $kit_id, $meta_key, true );
	$settings = is_array( $settings ) ? $settings : [];
	$factory  = lihan_elementor_factory_defaults();
	$changed  = false;

	if ( lihan_elementor_list_is_default( $settings['system_colors'] ?? null, $factory['colors'], 'color' ) ) {
		$settings['system_colors'] = [
			[
				'_id'   => 'primary',
				'title' => __( 'Primary', 'lihan' ),
				'color' => lihan_elementor_theme_color( 'color_primary', '#0b2f29' ),
			],
			[
				'_id'   => 'secondary',
				'title' => __( 'Secondary', 'lihan' ),
				'color' => lihan_elementor_theme_color( 'color_accent', '#e8d3a6' ),
			],
			[
				'_id'   => 'text',
				'title' => __( 'Text', 'lihan' ),
				'color' => lihan_elementor_theme_color( 'color_text', '#111111' ),
			],
			[
				'_id'   => 'accent',
				'title' => __( 'Accent', 'lihan' ),
				'color' => lihan_elementor_theme_color( 'color_gold', '#aa791b' ),
			],
		];
		$changed                   = true;
	}

	if ( empty( $settings['custom_colors'] ) ) {
		$settings['custom_colors'] = [
			[
				'_id'   => 'lhbgalt',
				'title' => __( 'Lihan light grey', 'lihan' ),
				'color' => lihan_elementor_theme_color( 'color_bg_alt', '#f2f3f6' ),
			],
			[
				'_id'   => 'lhbgwrm',
				'title' => __( 'Lihan warm sand', 'lihan' ),
				'color' => lihan_elementor_theme_color( 'color_bg_warm', '#f5efe6' ),
			],
			[
				'_id'   => 'lhmuted',
				'title' => __( 'Lihan muted text', 'lihan' ),
				'color' => '#6b6b6b',
			],
		];
		$changed                   = true;
	}

	$font_defaults = array_map(
		static function ( array $font ): string {
			return $font[0];
		},
		$factory['fonts']
	);

	if ( lihan_elementor_list_is_default( $settings['system_typography'] ?? null, $font_defaults, 'typography_font_family' ) ) {
		$fonts = [
			'primary'   => [ __( 'Primary', 'lihan' ), '400' ],
			'secondary' => [ __( 'Secondary', 'lihan' ), '400' ],
			'text'      => [ __( 'Text', 'lihan' ), '400' ],
			'accent'    => [ __( 'Accent', 'lihan' ), '500' ],
		];

		$settings['system_typography'] = [];

		foreach ( $fonts as $id => $font ) {
			$settings['system_typography'][] = [
				'_id'                    => $id,
				'title'                  => $font[0],
				'typography_typography'  => 'custom',
				'typography_font_family' => 'Montserrat',
				'typography_font_weight' => $font[1],
			];
		}
		$changed = true;
	}

	$width = $settings['container_width']['size'] ?? null;

	if ( null === $width || '' === $width || (int) $width === $factory['container'] ) {
		$settings['container_width'] = [
			'unit'  => 'px',
			'size'  => 1376, // 1440px container minus 2 × 32px gutters.
			'sizes' => [],
		];
		$changed                     = true;
	}

	if ( $changed ) {
		// Same storage Elementor uses (Core\Settings\Page\Manager::save_settings_to_db()).
		update_metadata( 'post', $kit_id, $meta_key, wp_slash( $settings ) );

		if ( isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
	}

	return true;
}

/**
 * Run the kit sync once (first admin request after the theme is activated, or
 * after Elementor is installed later). Guarded by the lihan_elementor_kit_synced option.
 */
function lihan_elementor_maybe_sync_kit(): void {
	if ( get_option( 'lihan_elementor_kit_synced' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( lihan_elementor_sync_kit() ) {
		update_option( 'lihan_elementor_kit_synced', defined( 'LIHAN_VERSION' ) ? LIHAN_VERSION : '1', true );
	}
}
add_action( 'after_switch_theme', 'lihan_elementor_maybe_sync_kit', 20 );
add_action( 'admin_init', 'lihan_elementor_maybe_sync_kit', 20 );

/* -------------------------------------------------------------------------
 * Widgets
 * ---------------------------------------------------------------------- */

/**
 * Add the "Lihan" widget category.
 *
 * @param \Elementor\Elements_Manager $elements_manager Elements manager.
 */
function lihan_elementor_register_category( $elements_manager ): void {
	$elements_manager->add_category(
		'lihan',
		[
			'title' => esc_html__( 'Lihan', 'lihan' ),
			'icon'  => 'eicon-theme-style',
		]
	);
}
add_action( 'elementor/elements/categories_registered', 'lihan_elementor_register_category' );

/**
 * Widget class names keyed by file name (inc/elementor/widgets/{file}.php).
 *
 * @return array<string,string>
 */
function lihan_elementor_widget_classes(): array {
	return (array) apply_filters(
		'lihan_elementor_widgets',
		[
			'hero'             => 'Lihan_Elementor_Widget_Hero',
			'usps'             => 'Lihan_Elementor_Widget_Usps',
			'category-grid'    => 'Lihan_Elementor_Widget_Category_Grid',
			'inspiration'      => 'Lihan_Elementor_Widget_Inspiration',
			'product-carousel' => 'Lihan_Elementor_Widget_Product_Carousel',
			'reviews'          => 'Lihan_Elementor_Widget_Reviews',
			'promo-banner'     => 'Lihan_Elementor_Widget_Promo_Banner',
			'image-text'       => 'Lihan_Elementor_Widget_Image_Text',
			'readmore-text'    => 'Lihan_Elementor_Widget_Readmore_Text',
			'faq'              => 'Lihan_Elementor_Widget_Faq',
		]
	);
}

/**
 * Register the theme widgets.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
 */
function lihan_elementor_register_widgets( $widgets_manager ): void {
	require_once LIHAN_DIR . '/inc/elementor/class-lihan-elementor-widget-base.php';

	foreach ( lihan_elementor_widget_classes() as $file => $class ) {
		$path = LIHAN_DIR . '/inc/elementor/widgets/' . sanitize_file_name( $file ) . '.php';

		if ( ! is_readable( $path ) ) {
			continue;
		}

		require_once $path;

		if ( class_exists( $class ) ) {
			$widgets_manager->register( new $class() );
		}
	}
}
add_action( 'elementor/widgets/register', 'lihan_elementor_register_widgets' );

/* -------------------------------------------------------------------------
 * Assets
 * ---------------------------------------------------------------------- */

/**
 * Enqueue the Elementor compatibility stylesheet wherever Elementor loads its
 * own frontend styles (Elementor pages, theme builder templates, the preview).
 */
function lihan_elementor_enqueue_styles(): void {
	$file    = LIHAN_DIR . '/assets/css/elementor.css';
	$version = function_exists( 'lihan_asset_version' ) ? lihan_asset_version( 'assets/css/elementor.css' ) : LIHAN_VERSION;

	if ( is_readable( $file ) ) {
		wp_enqueue_style( 'lihan-elementor', LIHAN_URI . '/assets/css/elementor.css', [ 'lihan-main' ], $version );
	}
}
add_action( 'elementor/frontend/after_enqueue_styles', 'lihan_elementor_enqueue_styles' );
add_action( 'elementor/preview/enqueue_styles', 'lihan_elementor_enqueue_styles' );

/**
 * Re-run the theme's component init (carousels, read-more…) when Elementor
 * renders a widget in the editor preview.
 *
 * Only in edit mode: on the live site main.js already initialises the page,
 * and calling it again for every element would initialise components twice.
 */
function lihan_elementor_editor_script(): void {
	$script = <<<'JS'
(function () {
	function reinit($scope) {
		var el = $scope && $scope[0] ? $scope[0] : $scope;
		if (!el || !el.classList || !el.classList.contains('elementor-widget')) {
			return;
		}
		if (window.Lihan && typeof window.Lihan.init === 'function') {
			window.Lihan.init(el);
		}
	}
	function hook() {
		var ef = window.elementorFrontend;
		if (!ef || !ef.hooks || typeof ef.isEditMode !== 'function' || !ef.isEditMode()) {
			return;
		}
		ef.hooks.addAction('frontend/element_ready/global', reinit);
	}
	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hook();
	} else {
		window.addEventListener('elementor/frontend/init', hook, { once: true });
	}
})();
JS;

	wp_add_inline_script( 'elementor-frontend', $script, 'before' );
}
add_action( 'elementor/frontend/after_register_scripts', 'lihan_elementor_editor_script' );

/* -------------------------------------------------------------------------
 * Page title
 * ---------------------------------------------------------------------- */

/**
 * Hide the theme's page title when Elementor's "Hide Title" page setting is on,
 * so the title is not printed at all (instead of printed and hidden with CSS).
 *
 * @param bool $show Whether to show the title.
 */
function lihan_elementor_page_title( $show ) {
	if ( ! $show || ! is_singular() || ! lihan_elementor_active() ) {
		return $show;
	}

	$document = \Elementor\Plugin::$instance->documents->get( (int) get_queried_object_id() );

	if ( $document && $document->is_built_with_elementor() && 'yes' === $document->get_settings( 'hide_title' ) ) {
		return false;
	}

	return $show;
}
add_filter( 'lihan_show_page_title', 'lihan_elementor_page_title' );
