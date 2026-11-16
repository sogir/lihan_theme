<?php
/**
 * Theme setup: supports, menus, widget areas, image sizes and assets.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme supports, menus and image sizes.
 */
function lihan_setup(): void {
	load_theme_textdomain( 'lihan', LIHAN_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'custom-logo',
		[
			'height'               => 287,
			'width'                => 720,
			'flex-height'          => true,
			'flex-width'           => true,
			'unlink-homepage-logo' => false,
		]
	);
	add_theme_support(
		'html5',
		[ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ]
	);
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'customize-selective-refresh-widgets' );

	// Load the brand font inside the block editor too.
	add_editor_style( lihan_google_fonts_url() );

	register_nav_menus(
		[
			'primary'  => __( 'Main menu', 'lihan' ),
			'mobile'   => __( 'Mobile menu (falls back to the main menu)', 'lihan' ),
			'footer-1' => __( 'Footer: Information', 'lihan' ),
			'footer-2' => __( 'Footer: Customer care', 'lihan' ),
			'topbar'   => __( 'Top bar links (optional)', 'lihan' ),
		]
	);

	add_image_size( 'lihan-portrait', 600, 900, true );
	add_image_size( 'lihan-square', 600, 600, true );
	add_image_size( 'lihan-hero', 1920, 1000, true );
	add_image_size( 'lihan-reel', 540, 960, true );
}
add_action( 'after_setup_theme', 'lihan_setup' );

/**
 * Content width used by embeds and images inside post content.
 */
function lihan_content_width(): void {
	$GLOBALS['content_width'] = (int) apply_filters( 'lihan_content_width', 780 );
}
add_action( 'after_setup_theme', 'lihan_content_width', 0 );

/**
 * Friendly names for the theme image sizes in the media modal.
 *
 * @param array<string,string> $sizes Size names.
 * @return array<string,string>
 */
function lihan_image_size_names( array $sizes ): array {
	return array_merge(
		$sizes,
		[
			'lihan-portrait' => __( 'Portrait (2:3)', 'lihan' ),
			'lihan-square'   => __( 'Square', 'lihan' ),
		]
	);
}
add_filter( 'image_size_names_choose', 'lihan_image_size_names' );

/**
 * Register widget areas.
 */
function lihan_widgets_init(): void {
	$common = [
		'before_widget' => '<section id="%1$s" class="widget lh-widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title lh-widget__title">',
		'after_title'   => '</h2>',
	];

	register_sidebar(
		array_merge(
			$common,
			[
				'name'        => __( 'Blog sidebar', 'lihan' ),
				'id'          => 'sidebar-1',
				'description' => __( 'Shown next to blog posts and archives. Leave empty for a full-width blog.', 'lihan' ),
			]
		)
	);

	register_sidebar(
		array_merge(
			$common,
			[
				'name'        => __( 'Shop filters', 'lihan' ),
				'id'          => 'shop-sidebar',
				'description' => __( 'Product filter widgets. Shown in the "Filter" drawer on shop and category pages.', 'lihan' ),
			]
		)
	);
}
add_action( 'widgets_init', 'lihan_widgets_init' );

/**
 * Google Fonts stylesheet URL (filterable, return '' to self-host or disable).
 */
function lihan_google_fonts_url(): string {
	return (string) apply_filters(
		'lihan_google_fonts_url',
		'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap'
	);
}

/**
 * Enqueue front-end assets.
 *
 * Runs at priority 20 so the theme stylesheet loads after plugin base styles
 * (WooCommerce, Elementor) and can refine them without !important.
 */
function lihan_enqueue_assets(): void {
	$fonts = lihan_google_fonts_url();
	if ( '' !== $fonts ) {
		wp_enqueue_style( 'lihan-fonts', $fonts, [], null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google Fonts URL must stay untouched.
	}

	wp_enqueue_style( 'lihan-main', LIHAN_URI . '/assets/css/main.css', [], lihan_asset_version( 'assets/css/main.css' ) );

	if ( function_exists( 'lihan_customizer_css' ) ) {
		$css = lihan_customizer_css();
		if ( '' !== $css ) {
			wp_add_inline_style( 'lihan-main', $css );
		}
	}

	wp_enqueue_script(
		'lihan-main',
		LIHAN_URI . '/assets/js/main.js',
		[],
		lihan_asset_version( 'assets/js/main.js' ),
		[
			'in_footer' => true,
			'strategy'  => 'defer',
		]
	);

	wp_add_inline_script(
		'lihan-main',
		'window.lihanSettings = ' . wp_json_encode(
			[
				'i18n' => [
					'readMore'     => __( 'Read More', 'lihan' ),
					'readLess'     => __( 'Read Less', 'lihan' ),
					'previous'     => __( 'Previous', 'lihan' ),
					'next'         => __( 'Next', 'lihan' ),
					/* translators: %d: slide number. */
					'goToSlide'    => __( 'Go to slide %d', 'lihan' ),
					/* translators: 1: current slide, 2: total slides. */
					'slideOf'      => __( '%1$d of %2$d', 'lihan' ),
					'showSubmenu'  => __( 'Show submenu', 'lihan' ),
					'hideSubmenu'  => __( 'Hide submenu', 'lihan' ),
				],
			]
		) . ';',
		'before'
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'lihan_enqueue_assets', 20 );

/**
 * Cache-busting version for a theme asset (file modification time in debug mode).
 *
 * @param string $relative Path relative to the theme directory.
 */
function lihan_asset_version( string $relative ): string {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$file = LIHAN_DIR . '/' . ltrim( $relative, '/' );
		if ( is_readable( $file ) ) {
			return LIHAN_VERSION . '.' . (string) filemtime( $file );
		}
	}
	return LIHAN_VERSION;
}

/**
 * Preconnect to the Google Fonts hosts.
 *
 * @param array<int,mixed> $urls          URLs to print for resource hints.
 * @param string           $relation_type The relation type.
 * @return array<int,mixed>
 */
function lihan_resource_hints( array $urls, string $relation_type ): array {
	if ( 'preconnect' === $relation_type && wp_style_is( 'lihan-fonts', 'queue' ) ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = [
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		];
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'lihan_resource_hints', 10, 2 );

/**
 * Swap the no-js class as early as possible to avoid a flash of unstyled toggles.
 */
function lihan_js_detection(): void {
	echo "<script>document.documentElement.classList.remove('no-js');document.documentElement.classList.add('js');</script>\n";
}
add_action( 'wp_head', 'lihan_js_detection', 0 );

/**
 * Pingback header on singular content that accepts pings.
 */
function lihan_pingback_header(): void {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">' . "\n", esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'lihan_pingback_header' );

/**
 * Bundled favicon until a Site Icon is set in the customizer.
 */
function lihan_fallback_favicon(): void {
	if ( has_site_icon() ) {
		return;
	}
	printf(
		'<link rel="icon" href="%1$s" type="image/png" sizes="512x512">' . "\n" . '<link rel="apple-touch-icon" href="%1$s">' . "\n",
		esc_url( LIHAN_URI . '/assets/images/favicon.png' )
	);
}
add_action( 'wp_head', 'lihan_fallback_favicon', 99 );

/**
 * Theme colour for mobile browser chrome.
 */
function lihan_theme_color_meta(): void {
	$primary = function_exists( 'lihan_get_option' ) ? (string) lihan_get_option( 'color_primary' ) : '#0b2f29';
	printf( '<meta name="theme-color" content="%s">' . "\n", esc_attr( $primary ) );
}
add_action( 'wp_head', 'lihan_theme_color_meta', 2 );

/**
 * Whether the sticky header is active on the current request.
 */
function lihan_is_header_sticky(): bool {
	$sticky = (bool) lihan_get_option( 'header_sticky' );
	if ( lihan_is_cartflows_step() ) {
		$sticky = false;
	}
	$sticky = (bool) apply_filters( 'lihan_header_sticky', $sticky );

	/**
	 * Alias of `lihan_header_sticky` (used by the CartFlows/Elementor integrations).
	 *
	 * @param bool $sticky Whether the header sticks to the top while scrolling.
	 */
	return (bool) apply_filters( 'lihan_sticky_header', $sticky );
}

/**
 * Whether the current request is a CartFlows step page (checkout, thank you, upsell).
 */
function lihan_is_cartflows_step(): bool {
	if ( is_admin() || ! did_action( 'wp' ) ) {
		return false;
	}
	if ( function_exists( 'wcf' ) ) {
		$wcf = wcf();
		if ( isset( $wcf->utils ) && is_object( $wcf->utils ) && method_exists( $wcf->utils, 'is_step_post_type' ) ) {
			return (bool) $wcf->utils->is_step_post_type();
		}
	}
	return is_singular( 'cartflows_step' );
}

/**
 * Body classes used by the theme stylesheet and scripts.
 *
 * @param array<int,string> $classes Body classes.
 * @return array<int,string>
 */
function lihan_body_classes( array $classes ): array {
	$classes[] = 'lihan';

	if ( lihan_is_header_sticky() ) {
		$classes[] = 'lh-sticky-header';
	}

	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	if ( is_active_sidebar( 'sidebar-1' ) && ( is_home() || is_archive() || is_search() || is_singular( 'post' ) ) && ! lihan_is_woocommerce_page() ) {
		$classes[] = 'lh-has-sidebar';
	}

	if ( class_exists( 'WooCommerce' ) ) {
		$classes[] = 'lh-woocommerce';
	}

	return $classes;
}
add_filter( 'body_class', 'lihan_body_classes' );

/**
 * Whether the current request is a WooCommerce page (shop, product, cart, checkout, account).
 */
function lihan_is_woocommerce_page(): bool {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}
	return ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
		|| ( function_exists( 'is_cart' ) && is_cart() )
		|| ( function_exists( 'is_checkout' ) && is_checkout() )
		|| ( function_exists( 'is_account_page' ) && is_account_page() );
}

/**
 * Plain ellipsis instead of the bracketed "[…]".
 */
function lihan_excerpt_more(): string {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'lihan_excerpt_more' );

/**
 * Shorter excerpts for the blog cards.
 */
function lihan_excerpt_length(): int {
	return 24;
}
add_filter( 'excerpt_length', 'lihan_excerpt_length' );

/**
 * Add the theme classes to the custom logo image.
 *
 * @param array<string,mixed> $attr Image attributes.
 * @return array<string,mixed>
 */
function lihan_custom_logo_attributes( array $attr ): array {
	$attr['class']    = trim( ( $attr['class'] ?? '' ) . ' lh-logo__img' );
	$attr['loading']  = 'eager';
	$attr['decoding'] = 'async';
	return $attr;
}
add_filter( 'get_custom_logo_image_attributes', 'lihan_custom_logo_attributes' );
