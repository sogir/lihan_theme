<?php
/**
 * WooCommerce integration: theme support, wrappers, product cards, shop archive,
 * single product, cart drawer, AJAX add to cart and customizer settings.
 *
 * Everything is done with hooks; the only template overrides live in woocommerce/.
 * Loaded from functions.php only when WooCommerce is active.
 *
 * Public helpers other modules may use:
 *   lihan_wc_show_cart_drawer(): bool          Whether #lh-cart-drawer is printed (filter `lihan_show_cart_drawer`).
 *   lihan_wc_free_shipping_markup(): string     Free-delivery progress block (also a cart fragment).
 *   lihan_wc_is_product_archive(): bool         Shop, product category/tag/attribute archive or product search.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Setup
 * ---------------------------------------------------------------------- */

/**
 * Declare WooCommerce support and the product gallery features.
 */
function lihan_wc_setup(): void {
	add_theme_support(
		'woocommerce',
		[
			'thumbnail_image_width' => 600,
			'single_image_width'    => 900,
			'product_grid'          => [
				'default_columns' => 4,
				'default_rows'    => 6,
				'min_columns'     => 2,
				'max_columns'     => 6,
			],
		]
	);
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'lihan_wc_setup' );

/**
 * WooCommerce option defaults (merged into lihan_defaults()).
 *
 * @param array<string,mixed> $defaults Defaults.
 * @return array<string,mixed>
 */
function lihan_wc_option_defaults( array $defaults ): array {
	$defaults['wc_free_shipping_threshold'] = 5000;
	$defaults['wc_size_guide_page']         = 0;
	$defaults['wc_show_buy_now']            = true;
	$defaults['wc_delivery_returns']        = implode(
		"\n\n",
		[
			__( '<strong>Inside Dhaka:</strong> delivered within 1–2 working days.<br><strong>Outside Dhaka:</strong> delivered within 2–5 working days by courier. The delivery charge for your area is shown at checkout.', 'lihan' ),
			__( '<strong>Cash on delivery</strong> is available all over Bangladesh. You can also pay in advance with bKash or Nagad. Please check your parcel in front of the delivery person before paying.', 'lihan' ),
			__( '<strong>Easy exchange within 7 days:</strong> if the size or colour is not right, message us on WhatsApp or Messenger within 7 days of delivery. Items must be unworn and unwashed, with the original tags and packaging. Sale items can be exchanged for a different size only.', 'lihan' ),
		]
	);
	return $defaults;
}
add_filter( 'lihan_defaults', 'lihan_wc_option_defaults' );

/**
 * Sanitize a non-negative decimal amount.
 *
 * @param mixed $value Raw value.
 */
function lihan_wc_sanitize_amount( $value ): float {
	$value = is_scalar( $value ) ? (string) $value : '0';
	$value = (float) preg_replace( '/[^0-9.]/', '', $value );
	return max( 0.0, $value );
}

/**
 * Customizer: "WooCommerce" section inside the Lihan Theme panel.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function lihan_wc_customize_register( WP_Customize_Manager $wp_customize ): void {
	$defaults = lihan_defaults();

	$wp_customize->add_section(
		'lihan_woocommerce',
		[
			'title'    => __( 'Shop & Product Page', 'lihan' ),
			'panel'    => $wp_customize->get_panel( 'lihan_theme' ) ? 'lihan_theme' : '',
			'priority' => 80,
		]
	);

	$wp_customize->add_setting(
		'wc_free_shipping_threshold',
		[
			'default'           => $defaults['wc_free_shipping_threshold'],
			'sanitize_callback' => 'lihan_wc_sanitize_amount',
		]
	);
	$wp_customize->add_control(
		'wc_free_shipping_threshold',
		[
			'label'       => __( 'Free delivery threshold', 'lihan' ),
			'description' => __( 'Order subtotal (in the store currency) that unlocks free delivery. Drives the progress bar in the cart drawer. Set 0 to hide the bar. Keep it in sync with your Free shipping method.', 'lihan' ),
			'section'     => 'lihan_woocommerce',
			'type'        => 'number',
			'input_attrs' => [
				'min'  => 0,
				'step' => 1,
			],
		]
	);

	$wp_customize->add_setting(
		'wc_delivery_returns',
		[
			'default'           => $defaults['wc_delivery_returns'],
			'sanitize_callback' => 'wp_kses_post',
		]
	);
	$wp_customize->add_control(
		'wc_delivery_returns',
		[
			'label'       => __( 'Delivery & returns text', 'lihan' ),
			'description' => __( 'Shown in the "Delivery & Returns" section of every product page. Basic HTML allowed; blank lines start new paragraphs.', 'lihan' ),
			'section'     => 'lihan_woocommerce',
			'type'        => 'textarea',
		]
	);

	$wp_customize->add_setting(
		'wc_size_guide_page',
		[
			'default'           => $defaults['wc_size_guide_page'],
			'sanitize_callback' => 'absint',
		]
	);
	$wp_customize->add_control(
		'wc_size_guide_page',
		[
			'label'       => __( 'Size guide page', 'lihan' ),
			'description' => __( 'Its content opens in a pop-up from the "Size guide" link on products with a size attribute. A product can use its own guide with the custom field _lihan_size_guide. When none is chosen, a page with the slug "size-guide" is used if it exists.', 'lihan' ),
			'section'     => 'lihan_woocommerce',
			'type'        => 'dropdown-pages',
		]
	);

	$wp_customize->add_setting(
		'wc_show_buy_now',
		[
			'default'           => $defaults['wc_show_buy_now'],
			'sanitize_callback' => function_exists( 'lihan_sanitize_checkbox' ) ? 'lihan_sanitize_checkbox' : 'wp_validate_boolean',
		]
	);
	$wp_customize->add_control(
		'wc_show_buy_now',
		[
			'label'   => __( 'Show the "Buy now" button on product pages', 'lihan' ),
			'section' => 'lihan_woocommerce',
			'type'    => 'checkbox',
		]
	);
}
add_action( 'customize_register', 'lihan_wc_customize_register', 30 );

/* -------------------------------------------------------------------------
 * Assets
 * ---------------------------------------------------------------------- */

/**
 * Enqueue the WooCommerce stylesheet and script.
 *
 * Runs after the theme's main assets (priority 20) so `lihan-main` is registered.
 */
function lihan_wc_enqueue_assets(): void {
	$deps = wp_style_is( 'lihan-main', 'registered' ) ? [ 'lihan-main' ] : [];
	wp_enqueue_style( 'lihan-woocommerce', LIHAN_URI . '/assets/css/woocommerce.css', $deps, lihan_wc_asset_version( 'assets/css/woocommerce.css' ) );

	// The drawer and header count need fresh fragments on every page (also behind page caches).
	if ( ! lihan_wc_is_cartflows_step() ) {
		wp_enqueue_script( 'wc-add-to-cart' );
		wp_enqueue_script( 'wc-cart-fragments' );
	}

	wp_enqueue_script(
		'lihan-woocommerce',
		LIHAN_URI . '/assets/js/woocommerce.js',
		[],
		lihan_wc_asset_version( 'assets/js/woocommerce.js' ),
		[
			'in_footer' => true,
			'strategy'  => 'defer',
		]
	);

	$settings = [
		'ajaxAddUrl'      => WC_AJAX::get_endpoint( 'lihan_add_to_cart' ),
		'ajaxSingle'      => 'yes' !== get_option( 'woocommerce_cart_redirect_after_add' ) && ! lihan_wc_is_cartflows_step(),
		'openDrawerOnAdd' => lihan_wc_show_cart_drawer() && ! is_cart() && ! is_checkout(),
		'cartUrl'         => wc_get_cart_url(),
		'i18n'            => [
			'readMore'      => __( 'Read more', 'lihan' ),
			'readLess'      => __( 'Read less', 'lihan' ),
			/* translators: %d: slide number. */
			'goToImage'     => __( 'Show image %d', 'lihan' ),
			'addError'      => __( 'Sorry, the product could not be added. Please try again.', 'lihan' ),
			'chooseOptions' => __( 'Please select some product options before adding this product to your bag.', 'lihan' ),
			'unavailable'   => __( 'Unavailable', 'lihan' ),
			'outOfStock'    => __( 'Out of stock', 'lihan' ),
			'selected'      => __( 'Selected:', 'lihan' ),
		],
	];

	wp_add_inline_script( 'lihan-woocommerce', 'window.lihanWoo = ' . wp_json_encode( $settings ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'lihan_wc_enqueue_assets', 25 );

/**
 * Asset version (uses the theme helper when present).
 *
 * @param string $relative Path relative to the theme directory.
 */
function lihan_wc_asset_version( string $relative ): string {
	return function_exists( 'lihan_asset_version' ) ? lihan_asset_version( $relative ) : LIHAN_VERSION;
}

/**
 * Whether the request is a CartFlows step page (checkout, upsell, thank you).
 */
function lihan_wc_is_cartflows_step(): bool {
	return function_exists( 'lihan_is_cartflows_step' ) && lihan_is_cartflows_step();
}

/**
 * Whether the request is a product archive (shop, product taxonomy, product search).
 */
function lihan_wc_is_product_archive(): bool {
	return is_shop() || is_product_taxonomy() || ( is_search() && 'product' === get_query_var( 'post_type' ) );
}

/* -------------------------------------------------------------------------
 * Wrappers, breadcrumbs, sidebar
 * ---------------------------------------------------------------------- */

remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

/**
 * Open the theme content wrapper.
 */
function lihan_wc_wrapper_start(): void {
	$classes = [ 'lh-main', 'lh-shop' ];
	if ( is_product() ) {
		$classes[] = 'lh-shop--single';
	} elseif ( lihan_wc_is_product_archive() ) {
		$classes[] = 'lh-shop--archive';
	}
	echo '<main id="primary" class="' . esc_attr( implode( ' ', $classes ) ) . '"><div class="lh-container">';
}
add_action( 'woocommerce_before_main_content', 'lihan_wc_wrapper_start', 10 );

/**
 * Close the theme content wrapper.
 */
function lihan_wc_wrapper_end(): void {
	echo '</div></main>';
}
add_action( 'woocommerce_after_main_content', 'lihan_wc_wrapper_end', 10 );

/**
 * Breadcrumbs (theme helper wraps woocommerce_breadcrumb()).
 */
function lihan_wc_breadcrumbs(): void {
	if ( function_exists( 'lihan_breadcrumbs' ) ) {
		lihan_breadcrumbs();
	} else {
		woocommerce_breadcrumb();
	}
}
add_action( 'woocommerce_before_main_content', 'lihan_wc_breadcrumbs', 20 );

/**
 * Pretty breadcrumb defaults when WooCommerce prints them elsewhere (e.g. widgets).
 *
 * @param array<string,mixed> $args Breadcrumb args.
 * @return array<string,mixed>
 */
function lihan_wc_breadcrumb_defaults( array $args ): array {
	$args['delimiter'] = '<span class="lh-breadcrumbs__sep" aria-hidden="true">/</span>';
	return $args;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'lihan_wc_breadcrumb_defaults' );

/* -------------------------------------------------------------------------
 * Shop / category archive
 * ---------------------------------------------------------------------- */

remove_action( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header', 10 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

/**
 * Archive header: title, item count, clamped description and sub-category pills.
 */
function lihan_wc_archive_header(): void {
	global $wp_query;

	$title = (string) woocommerce_page_title( false );
	$count = $wp_query instanceof WP_Query ? (int) $wp_query->found_posts : 0;

	// Category / shop page description, captured so plugins hooked here keep working.
	ob_start();
	do_action( 'woocommerce_archive_description' );
	$description = trim( (string) ob_get_clean() );

	echo '<header class="lh-shop-header woocommerce-products-header">';

	if ( apply_filters( 'woocommerce_show_page_title', true ) ) {
		echo '<h1 class="lh-h2 lh-shop-header__title woocommerce-products-header__title page-title">' . esc_html( $title ) . '</h1>';
	}

	if ( $count > 0 ) {
		echo '<p class="lh-shop-header__count">' . esc_html(
			sprintf(
				/* translators: %s: number of products. */
				_n( '(%s item)', '(%s items)', $count, 'lihan' ),
				number_format_i18n( $count )
			)
		) . '</p>';
	}

	if ( '' !== $description ) {
		printf(
			'<div class="lh-shop-header__desc lh-clamp" data-lh-clamp><div class="lh-clamp__content lh-rte">%1$s</div><button type="button" class="lh-clamp__toggle" aria-expanded="false" hidden><span class="lh-visually-hidden">%2$s</span>%3$s</button></div>',
			$description, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output of WooCommerce's own description callbacks (already filtered).
			esc_html__( 'Read more', 'lihan' ),
			lihan_icon( 'chevron-down', 16 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	lihan_wc_subcategory_pills();

	echo '</header>';
}
add_action( 'woocommerce_shop_loop_header', 'lihan_wc_archive_header', 10 );

/**
 * Sub-category pills: children of the current category (or its siblings for a leaf),
 * top-level categories on the shop page.
 */
function lihan_wc_subcategory_pills(): void {
	if ( ! apply_filters( 'lihan_wc_show_subcategory_pills', true ) ) {
		return;
	}

	$current = is_product_category() ? get_queried_object() : null;
	$parent  = 0;

	if ( $current instanceof WP_Term ) {
		$parent = $current->term_id;
	} elseif ( ! is_shop() || is_search() ) {
		return;
	}

	$args = [
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => $parent,
		'orderby'    => 'menu_order',
		'order'      => 'ASC',
		'exclude'    => [ (int) get_option( 'default_product_cat', 0 ) ],
	];

	$terms = get_terms( $args );

	// Leaf category: show its siblings so shoppers can hop between them.
	if ( ( ! is_array( $terms ) || ! $terms ) && $current instanceof WP_Term && $current->parent ) {
		$args['parent'] = $current->parent;
		$terms          = get_terms( $args );
	}

	if ( ! is_array( $terms ) || count( $terms ) < 1 ) {
		return;
	}

	echo '<nav class="lh-subcats" aria-label="' . esc_attr__( 'Categories', 'lihan' ) . '"><ul class="lh-subcats__list">';
	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$is_current = $current instanceof WP_Term && $current->term_id === $term->term_id;
		printf(
			'<li><a class="lh-pill%1$s" href="%2$s"%3$s>%4$s</a></li>',
			$is_current ? ' is-active' : '',
			esc_url( $link ),
			$is_current ? ' aria-current="page"' : '',
			esc_html( $term->name )
		);
	}
	echo '</ul></nav>';
}

/**
 * Query args that hold active layered-nav filters.
 *
 * @return array<int,string>
 */
function lihan_wc_filter_query_keys(): array {
	$keys = [ 'min_price', 'max_price', 'rating_filter' ];
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only inspection of public filter args.
	foreach ( array_keys( $_GET ) as $key ) {
		$key = (string) $key;
		if ( str_starts_with( $key, 'filter_' ) || str_starts_with( $key, 'query_type_' ) ) {
			$keys[] = $key;
		}
	}
	return $keys;
}

/**
 * Number of active product filters.
 */
function lihan_wc_active_filter_count(): int {
	$count = 0;
	if ( class_exists( 'WC_Query' ) ) {
		foreach ( WC_Query::get_layered_nav_chosen_attributes() as $data ) {
			$count += count( (array) ( $data['terms'] ?? [] ) );
		}
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['min_price'] ) || isset( $_GET['max_price'] ) ) {
		++$count;
	}
	if ( ! empty( $_GET['rating_filter'] ) ) {
		++$count;
	}
	// phpcs:enable
	return $count;
}

/**
 * URL of the current archive without filter args.
 */
function lihan_wc_clear_filters_url(): string {
	$url = remove_query_arg( array_merge( lihan_wc_filter_query_keys(), [ 'paged', 'product-page' ] ) );
	return (string) preg_replace( '#/page/\d+/?#', '/', $url );
}

/**
 * Filter bar above the product grid: filter drawer toggle (left) and sort order (right).
 */
function lihan_wc_filter_bar(): void {
	static $printed = false;
	if ( $printed || ! lihan_wc_is_product_archive() ) {
		return;
	}
	$printed = true;

	$has_filters = lihan_wc_has_filters();
	$active      = lihan_wc_active_filter_count();

	echo '<div class="lh-filter-bar"><div class="lh-filter-bar__start">';

	if ( $has_filters ) {
		printf(
			'<button type="button" class="lh-filter-bar__toggle" data-lh-open="lh-filter-drawer" aria-controls="lh-filter-drawer" aria-expanded="false">%1$s<span>%2$s</span>%3$s</button>',
			lihan_icon( 'filter', 16 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Filter', 'lihan' ),
			$active ? '<span class="lh-filter-bar__badge"><span class="lh-visually-hidden">' . esc_html__( 'Active filters:', 'lihan' ) . ' </span>' . (int) $active . '</span>' : ''
		);
	}

	if ( $active ) {
		printf(
			'<a class="lh-filter-bar__clear" href="%1$s">%2$s</a>',
			esc_url( lihan_wc_clear_filters_url() ),
			esc_html__( 'Clear all', 'lihan' )
		);
	}

	echo '</div><div class="lh-filter-bar__end">';
	woocommerce_catalog_ordering( [ 'useLabel' => true ] ); // Visible "Sort by" label + short option names.
	echo '</div></div>';
}
add_action( 'woocommerce_before_shop_loop', 'lihan_wc_filter_bar', 30 );

/**
 * Keep the filter bar visible when filters return no products, so they can be changed.
 */
function lihan_wc_filter_bar_no_results(): void {
	if ( lihan_wc_active_filter_count() > 0 ) {
		lihan_wc_filter_bar();
	}
}
add_action( 'woocommerce_no_products_found', 'lihan_wc_filter_bar_no_results', 5 );

/**
 * Default filters used while the "Shop filters" widget area is empty:
 * price, then one list per product attribute (Colour, Size…), then categories.
 *
 * @return array<int,array{0:string,1:array<string,mixed>}> [ widget class, instance ].
 */
function lihan_wc_default_filters(): array {
	$filters = [];
	if ( class_exists( 'WC_Widget_Price_Filter' ) ) {
		$filters[] = [ 'WC_Widget_Price_Filter', [ 'title' => __( 'Price', 'lihan' ) ] ];
	}
	if ( class_exists( 'WC_Widget_Layered_Nav' ) ) {
		foreach ( wc_get_attribute_taxonomies() as $taxonomy ) {
			$filters[] = [
				'WC_Widget_Layered_Nav',
				[
					'title'        => $taxonomy->attribute_label,
					'attribute'    => $taxonomy->attribute_name,
					'display_type' => 'list',
					'query_type'   => 'or',
				],
			];
		}
	}
	if ( class_exists( 'WC_Widget_Product_Categories' ) ) {
		$filters[] = [
			'WC_Widget_Product_Categories',
			[
				'title'        => __( 'Categories', 'lihan' ),
				'count'        => 1,
				'hierarchical' => 1,
				'hide_empty'   => 1,
			],
		];
	}
	return (array) apply_filters( 'lihan_wc_default_filters', $filters );
}

/**
 * Whether the filter drawer has anything to show.
 */
function lihan_wc_has_filters(): bool {
	return is_active_sidebar( 'shop-sidebar' ) || [] !== lihan_wc_default_filters();
}

/**
 * Render the default filters.
 */
function lihan_wc_default_filter_widgets(): void {
	$args = [
		'before_widget' => '<section class="widget lh-widget %s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title lh-widget__title">',
		'after_title'   => '</h2>',
	];
	foreach ( lihan_wc_default_filters() as $filter ) {
		if ( isset( $filter[0] ) && class_exists( (string) $filter[0] ) ) {
			the_widget( (string) $filter[0], (array) ( $filter[1] ?? [] ), $args );
		}
	}
}

/**
 * Colour dots in the layered-nav filter for the colour attribute.
 *
 * @param string  $html  Term link HTML.
 * @param WP_Term $term  Term.
 * @param string  $link  Filter URL.
 * @param int     $count Product count.
 */
function lihan_wc_layered_nav_color_dot( $html, $term = null, $link = '', $count = 0 ): string {
	$html = (string) $html;
	if ( $term instanceof WP_Term && 'pa_color' === $term->taxonomy ) {
		$color = lihan_wc_swatch_color( $term );
		if ( '' !== $color ) {
			$html = '<span class="lh-dot lh-dot--filter" style="--lh-swatch:' . esc_attr( $color ) . '" aria-hidden="true"></span>' . $html;
		}
	}
	return $html;
}
add_filter( 'woocommerce_layered_nav_term_html', 'lihan_wc_layered_nav_color_dot', 10, 4 );

/**
 * Off-canvas filter drawer with the `shop-sidebar` widgets.
 */
function lihan_wc_filter_drawer(): void {
	if ( ! lihan_wc_is_product_archive() || ! lihan_wc_has_filters() ) {
		return;
	}
	$active = lihan_wc_active_filter_count();
	?>
	<div id="lh-filter-drawer" class="lh-drawer lh-drawer--left lh-filter-drawer" role="dialog" aria-modal="true" aria-labelledby="lh-filter-drawer-title">
		<div class="lh-drawer__header">
			<h2 id="lh-filter-drawer-title" class="lh-drawer__title"><?php esc_html_e( 'Filter', 'lihan' ); ?></h2>
			<button type="button" class="lh-drawer__close" data-lh-close>
				<?php echo lihan_icon( 'close', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="lh-visually-hidden"><?php esc_html_e( 'Close filters', 'lihan' ); ?></span>
			</button>
		</div>
		<div class="lh-drawer__body lh-filter-drawer__body">
			<?php
			if ( is_active_sidebar( 'shop-sidebar' ) ) {
				dynamic_sidebar( 'shop-sidebar' );
			} else {
				lihan_wc_default_filter_widgets();
			}
			?>
		</div>
		<div class="lh-filter-drawer__footer">
			<?php if ( $active ) : ?>
				<a class="lh-btn lh-btn--outline" href="<?php echo esc_url( lihan_wc_clear_filters_url() ); ?>"><?php esc_html_e( 'Clear all', 'lihan' ); ?></a>
			<?php endif; ?>
			<button type="button" class="lh-btn lh-btn--primary" data-lh-close><?php esc_html_e( 'View results', 'lihan' ); ?></button>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'lihan_wc_filter_drawer', 5 );

/**
 * Pagination arrows as icons with accessible text.
 *
 * @param array<string,mixed> $args paginate_links() args.
 * @return array<string,mixed>
 */
function lihan_wc_pagination_args( array $args ): array {
	$args['prev_text'] = lihan_icon( 'chevron-left', 16 ) . '<span class="lh-visually-hidden">' . esc_html__( 'Previous page', 'lihan' ) . '</span>';
	$args['next_text'] = '<span class="lh-visually-hidden">' . esc_html__( 'Next page', 'lihan' ) . '</span>' . lihan_icon( 'chevron-right', 16 );
	$args['mid_size']  = 1;
	$args['end_size']  = 1;
	return $args;
}
add_filter( 'woocommerce_pagination_args', 'lihan_wc_pagination_args' );

/* -------------------------------------------------------------------------
 * Product card
 * ---------------------------------------------------------------------- */

remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

/**
 * Add the card class to product loop items.
 *
 * @param array<int,string> $classes Classes.
 * @return array<int,string>
 */
function lihan_wc_card_class( $classes ): array {
	$classes = is_array( $classes ) ? $classes : [];
	if ( ! is_product() || ! lihan_wc_is_main_product() ) {
		$classes[] = 'lh-product-card';
	}
	return $classes;
}
add_filter( 'woocommerce_post_class', 'lihan_wc_card_class' );

/**
 * Whether the global product is the product of the current single product page.
 */
function lihan_wc_is_main_product(): bool {
	global $product;
	return is_product() && $product instanceof WC_Product && $product->get_id() === get_queried_object_id()
		&& ! did_action( 'woocommerce_after_single_product_summary' ) && ! doing_action( 'woocommerce_after_single_product_summary' );
}

/**
 * Card media: linked image (with hover image), sale badge, wishlist and quick-add bar.
 * Opens the card body that the price, title and swatches go into.
 */
function lihan_wc_card_media(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$link  = (string) apply_filters( 'woocommerce_loop_product_link', get_the_permalink(), $product );
	$main  = $product->get_image( 'woocommerce_thumbnail', [ 'class' => 'lh-product-card__img lh-product-card__img--main' ] );
	$hover = '';

	foreach ( $product->get_gallery_image_ids() as $gallery_id ) {
		if ( (int) $gallery_id && (int) $gallery_id !== (int) $product->get_image_id() ) {
			$hover = wp_get_attachment_image(
				(int) $gallery_id,
				'woocommerce_thumbnail',
				false,
				[
					'class'       => 'lh-product-card__img lh-product-card__img--hover',
					'alt'         => '',
					'loading'     => 'lazy',
					'decoding'    => 'async',
					'aria-hidden' => 'true',
				]
			);
			break;
		}
	}

	echo '<div class="lh-product-card__media' . ( $hover ? ' has-hover-image' : '' ) . '">';
	printf(
		'<a href="%1$s" class="lh-product-card__image woocommerce-LoopProduct-link woocommerce-loop-product__link" tabindex="-1" aria-hidden="true">%2$s%3$s</a>',
		esc_url( $link ),
		$main, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC_Product::get_image() output.
		$hover // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output.
	);

	echo '<div class="lh-product-card__badges">';
	if ( ! $product->is_in_stock() ) {
		echo '<span class="lh-badge lh-badge--soldout">' . esc_html__( 'Sold out', 'lihan' ) . '</span>';
	} else {
		woocommerce_show_product_loop_sale_flash();
	}
	echo '</div>';

	$wishlist = lihan_wc_card_wishlist_html();
	if ( '' !== $wishlist ) {
		echo '<div class="lh-product-card__wishlist">' . $wishlist . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin shortcode output.
	}

	echo '<div class="lh-product-card__actions">';
	woocommerce_template_loop_add_to_cart();
	echo '</div>';

	echo '</div><div class="lh-product-card__body">';
}
add_action( 'woocommerce_before_shop_loop_item', 'lihan_wc_card_media', 10 );

/**
 * Wishlist button for the card (YITH or TI WooCommerce Wishlist).
 */
function lihan_wc_card_wishlist_html(): string {
	$html = '';
	if ( defined( 'YITH_WCWL' ) && shortcode_exists( 'yith_wcwl_add_to_wishlist' ) ) {
		$html = do_shortcode( '[yith_wcwl_add_to_wishlist]' );
	} elseif ( shortcode_exists( 'ti_wishlists_addtocart' ) ) {
		$html = do_shortcode( '[ti_wishlists_addtocart loop="yes"]' );
	}
	return (string) apply_filters( 'lihan_card_wishlist_html', $html );
}

/**
 * Card title (linked; the only focusable link to the product in the card).
 */
function lihan_wc_card_title(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$link = (string) apply_filters( 'woocommerce_loop_product_link', get_the_permalink(), $product );
	printf(
		'<h2 class="%1$s"><a href="%2$s">%3$s</a></h2>',
		esc_attr( apply_filters( 'woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title lh-product-card__title' ) ),
		esc_url( $link ),
		esc_html( get_the_title() )
	);
}
add_action( 'woocommerce_after_shop_loop_item_title', 'lihan_wc_card_title', 15 );

/**
 * Close the card body.
 */
function lihan_wc_card_close(): void {
	echo '</div>';
}
add_action( 'woocommerce_after_shop_loop_item', 'lihan_wc_card_close', 100 );

/**
 * Hex colour for a colour term: term meta `lihan_swatch_color`, else a guess from its slug.
 *
 * @param WP_Term $term Attribute term.
 */
function lihan_wc_swatch_color( WP_Term $term ): string {
	$hex = sanitize_hex_color( (string) get_term_meta( $term->term_id, 'lihan_swatch_color', true ) );
	if ( $hex ) {
		return $hex;
	}

	$named = [
		'black'     => '#111111',
		'white'     => '#ffffff',
		'off-white' => '#f4f0e6',
		'offwhite'  => '#f4f0e6',
		'cream'     => '#f3e9d2',
		'ivory'     => '#fffff0',
		'beige'     => '#d9c4a3',
		'nude'      => '#d8b49c',
		'skin'      => '#e5c4a8',
		'brown'     => '#6b4423',
		'coffee'    => '#6f4e37',
		'chocolate' => '#4e2a1e',
		'grey'      => '#8a8a8a',
		'gray'      => '#8a8a8a',
		'ash'       => '#b2b2ae',
		'silver'    => '#c0c0c0',
		'navy'      => '#1f2a44',
		'blue'      => '#2d5aa0',
		'sky'       => '#8ec5e8',
		'green'     => '#2f5d3a',
		'olive'     => '#6b6b3a',
		'mint'      => '#b6e2cf',
		'bottle'    => '#0b4a35',
		'red'       => '#a4161a',
		'maroon'    => '#5c1a1b',
		'wine'      => '#5e1224',
		'pink'      => '#e7a1b0',
		'purple'    => '#5b3a73',
		'lavender'  => '#b9a7d6',
		'yellow'    => '#e8c547',
		'mustard'   => '#c99a2e',
		'gold'      => '#c9a24a',
		'golden'    => '#c9a24a',
		'orange'    => '#d9772b',
		'peach'     => '#f2c1a0',
	];

	$slug = sanitize_title( $term->slug );
	if ( isset( $named[ $slug ] ) ) {
		return $named[ $slug ];
	}
	foreach ( $named as $name => $value ) {
		if ( str_contains( $slug, $name ) ) {
			return $value;
		}
	}
	return '';
}

/**
 * Colour swatches under the card title (pa_color), max 4 + "+N".
 * For variable products a swatch swaps the card image to that colour's variation image.
 */
function lihan_wc_card_swatches(): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! taxonomy_exists( 'pa_color' ) ) {
		return;
	}

	$attributes = $product->get_attributes();
	if ( empty( $attributes['pa_color'] ) || ! $attributes['pa_color'] instanceof WC_Product_Attribute || ! $attributes['pa_color']->get_visible() && ! $attributes['pa_color']->get_variation() ) {
		return;
	}

	$terms = wc_get_product_terms( $product->get_id(), 'pa_color', [ 'fields' => 'all' ] );
	if ( ! $terms ) {
		return;
	}

	// Map colour slug => variation image for variable products.
	$images = [];
	if ( $product->is_type( 'variable' ) && $attributes['pa_color']->get_variation() ) {
		$children = $product->get_visible_children();
		if ( $children ) {
			update_meta_cache( 'post', $children );
			foreach ( $children as $child_id ) {
				$slug     = (string) get_post_meta( $child_id, 'attribute_pa_color', true );
				$image_id = (int) get_post_thumbnail_id( $child_id );
				if ( '' !== $slug && $image_id && ! isset( $images[ $slug ] ) ) {
					$images[ $slug ] = $image_id;
				}
			}
		}
	}

	$max   = (int) apply_filters( 'lihan_card_swatch_limit', 4 );
	$total = count( $terms );

	echo '<ul class="lh-product-card__swatches" aria-label="' . esc_attr__( 'Available colours', 'lihan' ) . '">';
	foreach ( array_slice( $terms, 0, $max ) as $term ) {
		$color = lihan_wc_swatch_color( $term );
		$style = $color ? ' style="--lh-swatch:' . esc_attr( $color ) . '"' : '';
		$light = $color && '#111111' === ( function_exists( 'lihan_contrast_color' ) ? lihan_contrast_color( $color ) : '' ) ? ' is-light' : '';

		if ( isset( $images[ $term->slug ] ) ) {
			$src    = wp_get_attachment_image_src( $images[ $term->slug ], 'woocommerce_thumbnail' );
			$srcset = wp_get_attachment_image_srcset( $images[ $term->slug ], 'woocommerce_thumbnail' );
			printf(
				'<li><button type="button" class="lh-dot%1$s%2$s"%3$s data-lh-swatch-src="%4$s" data-lh-swatch-srcset="%5$s" aria-label="%6$s" title="%7$s"></button></li>',
				esc_attr( $light ),
				$color ? '' : ' is-unknown',
				$style, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
				esc_url( $src ? $src[0] : '' ),
				esc_attr( $srcset ? $srcset : '' ),
				/* translators: %s: colour name. */
				esc_attr( sprintf( __( 'Show %s', 'lihan' ), $term->name ) ),
				esc_attr( $term->name )
			);
		} else {
			printf(
				'<li><span class="lh-dot%1$s%2$s"%3$s title="%4$s"><span class="lh-visually-hidden">%5$s</span></span></li>',
				esc_attr( $light ),
				$color ? '' : ' is-unknown',
				$style, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
				esc_attr( $term->name ),
				esc_html( $term->name )
			);
		}
	}
	if ( $total > $max ) {
		printf(
			'<li class="lh-product-card__swatch-more"><span aria-hidden="true">+%1$d</span><span class="lh-visually-hidden">%2$s</span></li>',
			(int) ( $total - $max ),
			/* translators: %d: number of additional colours. */
			esc_html( sprintf( _n( 'and %d more colour', 'and %d more colours', $total - $max, 'lihan' ), $total - $max ) )
		);
	}
	echo '</ul>';
}
add_action( 'woocommerce_after_shop_loop_item_title', 'lihan_wc_card_swatches', 20 );

/**
 * Sale badge: "-20%" (largest discount for variable products) or "Sale".
 *
 * @param string     $html    Default badge HTML.
 * @param mixed      $post    Post (unused).
 * @param WC_Product $product Product.
 */
function lihan_wc_sale_flash( $html, $post = null, $product = null ): string {
	$label = __( 'Sale', 'lihan' );

	if ( $product instanceof WC_Product ) {
		$percent = 0;
		if ( $product->is_type( 'variable' ) ) {
			$prices = $product->get_variation_prices();
			foreach ( $prices['regular_price'] as $id => $regular ) {
				$sale = $prices['sale_price'][ $id ] ?? $regular;
				if ( (float) $regular > 0 && (float) $sale < (float) $regular ) {
					$percent = max( $percent, (int) round( 100 - ( (float) $sale / (float) $regular * 100 ) ) );
				}
			}
		} elseif ( $product->is_type( 'simple' ) || $product->is_type( 'external' ) ) {
			$regular = (float) $product->get_regular_price();
			$sale    = (float) $product->get_sale_price();
			if ( $regular > 0 && $sale > 0 && $sale < $regular ) {
				$percent = (int) round( 100 - ( $sale / $regular * 100 ) );
			}
		}
		if ( $percent > 0 ) {
			/* translators: %d: discount percentage. */
			$label = sprintf( __( '-%d%%', 'lihan' ), $percent );
		}
	}

	return '<span class="onsale lh-badge lh-badge--sale">' . esc_html( $label ) . '</span>';
}
add_filter( 'woocommerce_sale_flash', 'lihan_wc_sale_flash', 10, 3 );

/**
 * Loop button labels: "Quick add" for simple products, "Select options" for variable ones.
 *
 * @param string     $text    Button text.
 * @param WC_Product $product Product.
 */
function lihan_wc_loop_add_to_cart_text( $text, $product = null ): string {
	if ( $product instanceof WC_Product ) {
		if ( ! $product->is_in_stock() ) {
			return __( 'View product', 'lihan' );
		}
		if ( $product->is_type( 'simple' ) && $product->is_purchasable() ) {
			return __( 'Quick add', 'lihan' );
		}
		if ( $product->is_type( 'variable' ) ) {
			return __( 'Select options', 'lihan' );
		}
	}
	return (string) $text;
}
add_filter( 'woocommerce_product_add_to_cart_text', 'lihan_wc_loop_add_to_cart_text', 10, 2 );

/* -------------------------------------------------------------------------
 * Single product
 * ---------------------------------------------------------------------- */

/**
 * Main product gets the two-column layout class.
 *
 * @param array<int,string> $classes Classes.
 * @return array<int,string>
 */
function lihan_wc_single_product_class( $classes ): array {
	$classes = is_array( $classes ) ? $classes : [];
	if ( lihan_wc_is_main_product() ) {
		$classes[] = 'lh-product-layout';
	}
	return $classes;
}
add_filter( 'woocommerce_post_class', 'lihan_wc_single_product_class', 20 );

/**
 * The gallery is a 2-column image grid (desktop) / swipe slider (mobile), so
 * Flexslider and hover zoom are turned off on product pages. The PhotoSwipe lightbox stays.
 *
 * @param bool $enabled Enabled.
 */
function lihan_wc_single_gallery_feature( $enabled ): bool {
	if ( is_product() && apply_filters( 'lihan_wc_grid_gallery', true ) ) {
		return false;
	}
	return (bool) $enabled;
}
add_filter( 'woocommerce_single_product_flexslider_enabled', 'lihan_wc_single_gallery_feature' );
add_filter( 'woocommerce_single_product_zoom_enabled', 'lihan_wc_single_gallery_feature' );

/**
 * Full-size (single) images for every gallery cell instead of tiny thumbnails.
 *
 * @param mixed $size Image size.
 * @return mixed
 */
function lihan_wc_gallery_image_size( $size ) {
	if ( is_product() && apply_filters( 'lihan_wc_grid_gallery', true ) ) {
		return 'woocommerce_single';
	}
	return $size;
}
add_filter( 'woocommerce_gallery_image_size', 'lihan_wc_gallery_image_size' );

/**
 * Gallery grid CSS hook.
 *
 * @param array<int,string> $classes Gallery classes.
 * @return array<int,string>
 */
function lihan_wc_gallery_classes( $classes ): array {
	$classes = is_array( $classes ) ? $classes : [];
	if ( is_product() && apply_filters( 'lihan_wc_grid_gallery', true ) ) {
		$classes[] = 'lh-gallery';
	}
	return $classes;
}
add_filter( 'woocommerce_single_product_image_gallery_classes', 'lihan_wc_gallery_classes' );

/**
 * Lightbox options: no share button, keep it simple.
 *
 * @param array<string,mixed> $options PhotoSwipe options.
 * @return array<string,mixed>
 */
function lihan_wc_photoswipe_options( $options ): array {
	$options                    = is_array( $options ) ? $options : [];
	$options['shareEl']         = false;
	$options['bgOpacity']       = 0.96;
	$options['showHideOpacity'] = true;
	return $options;
}
add_filter( 'woocommerce_single_product_photoswipe_options', 'lihan_wc_photoswipe_options' );

// Summary order: title 5, rating 10, price 10, excerpt 20, size guide 25, add to cart 30,
// trust row 32, WhatsApp 34, accordions 45, meta 48, sharing 50.
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 48 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

/**
 * "Add to bag" on product pages.
 */
function lihan_wc_single_add_to_cart_text(): string {
	return __( 'Add to bag', 'lihan' );
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'lihan_wc_single_add_to_cart_text' );

/**
 * Whether button swatches replace the variation selects in this context.
 */
function lihan_wc_swatches_enabled(): bool {
	return (bool) apply_filters( 'lihan_variation_swatches', ! is_admin() && ! lihan_wc_is_cartflows_step() );
}

/**
 * Variation button swatches. The native select stays (visually hidden) and is kept in sync
 * by assets/js/woocommerce.js so WooCommerce's variation script works unchanged.
 *
 * @param string              $html Select HTML.
 * @param array<string,mixed> $args Dropdown args.
 */
function lihan_wc_variation_swatches( $html, $args ): string {
	$html = (string) $html;
	if ( ! lihan_wc_swatches_enabled() || ! is_array( $args ) ) {
		return $html;
	}

	$product   = $args['product'] ?? null;
	$attribute = (string) ( $args['attribute'] ?? '' );
	$options   = $args['options'] ?? [];

	if ( ! $product instanceof WC_Product || '' === $attribute ) {
		return $html;
	}
	if ( empty( $options ) ) {
		$variation_attributes = $product->get_variation_attributes();
		$options              = $variation_attributes[ $attribute ] ?? [];
	}
	if ( empty( $options ) || ! is_array( $options ) ) {
		return $html;
	}

	$items = [];
	if ( taxonomy_exists( $attribute ) ) {
		foreach ( wc_get_product_terms( $product->get_id(), $attribute, [ 'fields' => 'all' ] ) as $term ) {
			if ( in_array( $term->slug, $options, true ) ) {
				$items[] = [
					'value' => $term->slug,
					'label' => (string) apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ),
					'color' => 'pa_color' === $attribute ? lihan_wc_swatch_color( $term ) : '',
				];
			}
		}
	} else {
		foreach ( $options as $option ) {
			$items[] = [
				'value' => (string) $option,
				'label' => (string) apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ),
				'color' => '',
			];
		}
	}

	if ( ! $items ) {
		return $html;
	}

	$name     = 'attribute_' . sanitize_title( $attribute );
	$label    = wc_attribute_label( $attribute, $product );
	$is_color = 'pa_color' === $attribute && count( array_filter( wp_list_pluck( $items, 'color' ) ) ) === count( $items );

	$out = '<div class="lh-swatches' . ( $is_color ? ' lh-swatches--color' : '' ) . '" role="group" aria-label="' . esc_attr( $label ) . '" data-lh-swatches="' . esc_attr( $name ) . '">';
	foreach ( $items as $item ) {
		if ( $is_color ) {
			$light = function_exists( 'lihan_contrast_color' ) && '#111111' === lihan_contrast_color( $item['color'] ) ? ' is-light' : '';
			$out  .= sprintf(
				'<button type="button" class="lh-swatch lh-swatch--color%1$s" data-value="%2$s" aria-pressed="false" title="%3$s" style="--lh-swatch:%4$s"><span class="lh-visually-hidden">%5$s</span></button>',
				esc_attr( $light ),
				esc_attr( $item['value'] ),
				esc_attr( $item['label'] ),
				esc_attr( $item['color'] ),
				esc_html( $item['label'] )
			);
		} else {
			$out .= sprintf(
				'<button type="button" class="lh-swatch" data-value="%1$s" aria-pressed="false">%2$s</button>',
				esc_attr( $item['value'] ),
				esc_html( $item['label'] )
			);
		}
	}
	$out .= '</div>';

	// Hide the select from sighted and keyboard users only once JS is running (see CSS: .js .lh-swatch-select).
	$html = (string) preg_replace( '/<select\b([^>]*?)class="([^"]*)"/', '<select$1class="$2 lh-swatch-select"', $html, 1, $replaced );
	if ( ! $replaced ) {
		$html = (string) preg_replace( '/<select\b/', '<select class="lh-swatch-select"', $html, 1 );
	}

	return $html . $out;
}
add_filter( 'woocommerce_dropdown_variation_attribute_options_html', 'lihan_wc_variation_swatches', 20, 2 );

/**
 * Size guide content for a product: product meta, the chosen page, or a page with the slug "size-guide".
 *
 * @param WC_Product $product Product.
 */
function lihan_wc_size_guide_content( WC_Product $product ): string {
	static $cache = [];
	$id = $product->get_id();
	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}

	$content = '';
	$meta    = (string) $product->get_meta( '_lihan_size_guide', true );

	if ( '' !== trim( $meta ) ) {
		$content = wpautop( wp_kses_post( $meta ) );
	} elseif ( lihan_wc_product_has_size_attribute( $product ) ) {
		$page_id = absint( lihan_get_option( 'wc_size_guide_page' ) );
		if ( ! $page_id ) {
			$page    = get_page_by_path( 'size-guide' );
			$page_id = $page instanceof WP_Post ? $page->ID : 0;
		}
		$page = $page_id ? get_post( $page_id ) : null;
		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			if ( function_exists( 'lihan_is_elementor_page' ) && lihan_is_elementor_page( $page->ID ) && class_exists( '\Elementor\Plugin' ) ) {
				$content = (string) \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $page->ID, true );
			} else {
				$raw     = (string) $page->post_content;
				$content = has_blocks( $raw ) ? do_blocks( $raw ) : wpautop( $raw );
				$content = wp_filter_content_tags( do_shortcode( $content ) );
			}
		}
	}

	$cache[ $id ] = (string) apply_filters( 'lihan_size_guide_content', $content, $product );
	return $cache[ $id ];
}

/**
 * Whether the product has a size-like attribute (Size, Length…).
 *
 * @param WC_Product $product Product.
 */
function lihan_wc_product_has_size_attribute( WC_Product $product ): bool {
	foreach ( $product->get_attributes() as $key => $attribute ) {
		$name = $attribute instanceof WC_Product_Attribute ? $attribute->get_name() : (string) $key;
		if ( preg_match( '/size|length|সাইজ/iu', $name . ' ' . wc_attribute_label( $name, $product ) ) ) {
			return true;
		}
	}
	return false;
}

/**
 * "Size guide" link that opens the size guide dialog.
 */
function lihan_wc_size_guide_link(): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! lihan_wc_is_main_product() || '' === lihan_wc_size_guide_content( $product ) ) {
		return;
	}
	printf(
		'<p class="lh-size-guide-link-wrap"><button type="button" class="lh-size-guide-link" data-lh-dialog="lh-size-guide" aria-haspopup="dialog" aria-controls="lh-size-guide">%1$s<span>%2$s</span></button></p>',
		lihan_icon( 'ruler', 16 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html__( 'Size guide', 'lihan' )
	);
}
add_action( 'woocommerce_after_variations_table', 'lihan_wc_size_guide_link', 10 );

/**
 * Size guide link for non-variable products (above the add-to-cart form).
 */
function lihan_wc_size_guide_link_simple(): void {
	global $product;
	if ( $product instanceof WC_Product && ! $product->is_type( 'variable' ) ) {
		lihan_wc_size_guide_link();
	}
}
add_action( 'woocommerce_single_product_summary', 'lihan_wc_size_guide_link_simple', 25 );

/**
 * Size guide dialog (footer).
 */
function lihan_wc_size_guide_dialog(): void {
	if ( ! is_product() ) {
		return;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$content = lihan_wc_size_guide_content( $product );
	if ( '' === $content ) {
		return;
	}
	?>
	<dialog id="lh-size-guide" class="lh-modal" aria-labelledby="lh-size-guide-title">
		<div class="lh-modal__header">
			<h2 id="lh-size-guide-title" class="lh-modal__title"><?php esc_html_e( 'Size guide', 'lihan' ); ?></h2>
			<button type="button" class="lh-modal__close" data-lh-dialog-close>
				<?php echo lihan_icon( 'close', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="lh-visually-hidden"><?php esc_html_e( 'Close size guide', 'lihan' ); ?></span>
			</button>
		</div>
		<div class="lh-modal__body lh-rte">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized post content / builder output. ?>
		</div>
	</dialog>
	<?php
}
add_action( 'wp_footer', 'lihan_wc_size_guide_dialog', 5 );

/**
 * Quantity − / + buttons around the quantity input.
 */
function lihan_wc_quantity_minus(): void {
	if ( lihan_wc_quantity_buttons_enabled() ) {
		echo '<button type="button" class="lh-qty__btn lh-qty__btn--minus" data-lh-qty="-1" tabindex="-1">' . lihan_icon( 'minus', 14 ) . '<span class="lh-visually-hidden">' . esc_html__( 'Decrease quantity', 'lihan' ) . '</span></button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'woocommerce_before_quantity_input_field', 'lihan_wc_quantity_minus' );

/**
 * Plus button.
 */
function lihan_wc_quantity_plus(): void {
	if ( lihan_wc_quantity_buttons_enabled() ) {
		echo '<button type="button" class="lh-qty__btn lh-qty__btn--plus" data-lh-qty="1" tabindex="-1">' . lihan_icon( 'plus', 14 ) . '<span class="lh-visually-hidden">' . esc_html__( 'Increase quantity', 'lihan' ) . '</span></button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'woocommerce_after_quantity_input_field', 'lihan_wc_quantity_plus' );

/**
 * Whether to print the quantity buttons (not on CartFlows steps, which style their own).
 */
function lihan_wc_quantity_buttons_enabled(): bool {
	return (bool) apply_filters( 'lihan_quantity_buttons', ! is_admin() && ! lihan_wc_is_cartflows_step() );
}

/**
 * "Buy now" button: adds the product and goes straight to checkout (CartFlows takes over when set up).
 */
function lihan_wc_buy_now_button(): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! lihan_wc_is_main_product() || ! lihan_get_option( 'wc_show_buy_now' ) ) {
		return;
	}
	if ( $product->is_type( 'external' ) || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}
	$action = (string) apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() );
	printf(
		'<button type="submit" name="add-to-cart" value="%1$d" class="lh-btn lh-btn--outline lh-btn--block lh-buy-now" formaction="%2$s" data-lh-buy-now>%3$s</button>',
		(int) $product->get_id(),
		esc_url( add_query_arg( 'lihan_buy_now', '1', $action ) ),
		esc_html__( 'Buy now', 'lihan' )
	);
}
add_action( 'woocommerce_after_add_to_cart_button', 'lihan_wc_buy_now_button', 5 );

/**
 * Redirect "Buy now" submissions to checkout (without the "added to cart" notice).
 *
 * @param string|false $url Redirect URL.
 * @return string|false
 */
function lihan_wc_buy_now_redirect( $url ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Same request WooCommerce's form handler accepts.
	if ( empty( $_GET['lihan_buy_now'] ) || lihan_wc_is_ajax_add() ) {
		return $url;
	}
	$notices = wc_get_notices();
	unset( $notices['success'] );
	wc_set_notices( $notices );
	return wc_get_checkout_url();
}
add_filter( 'woocommerce_add_to_cart_redirect', 'lihan_wc_buy_now_redirect', 99 );

/**
 * Trust row under the buy buttons.
 */
function lihan_wc_trust_row(): void {
	if ( ! lihan_wc_is_main_product() ) {
		return;
	}
	$items = (array) apply_filters(
		'lihan_product_trust_items',
		[
			[ 'icon' => 'cod', 'label' => __( 'Cash on delivery', 'lihan' ) ],
			[ 'icon' => 'truck', 'label' => __( 'Nationwide delivery', 'lihan' ) ],
			[ 'icon' => 'refresh', 'label' => __( '7-day exchange', 'lihan' ) ],
		]
	);
	if ( ! $items ) {
		return;
	}
	echo '<ul class="lh-trust-row">';
	foreach ( $items as $item ) {
		if ( empty( $item['label'] ) ) {
			continue;
		}
		echo '<li>' . lihan_icon( (string) ( $item['icon'] ?? 'check' ), 18 ) . '<span>' . esc_html( (string) $item['label'] ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</ul>';
}
add_action( 'woocommerce_single_product_summary', 'lihan_wc_trust_row', 32 );

/**
 * "Order on WhatsApp" link pre-filled with the product name and URL.
 */
function lihan_wc_whatsapp_link(): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! lihan_wc_is_main_product() || ! function_exists( 'lihan_whatsapp_url' ) ) {
		return;
	}
	$base = lihan_whatsapp_url();
	if ( '' === $base ) {
		return;
	}
	$message = sprintf(
		/* translators: 1: product name, 2: product URL. */
		__( "Hi, I'd like to order: %1\$s\n%2\$s", 'lihan' ),
		wp_strip_all_tags( $product->get_name() ),
		$product->get_permalink()
	);
	printf(
		'<p class="lh-wa-order"><a href="%1$s" target="_blank" rel="noopener" data-lh-wa-base="%2$s" data-lh-wa-text="%3$s">%4$s<span>%5$s</span></a></p>',
		esc_url( lihan_whatsapp_url( $message ) ),
		esc_url( $base ),
		esc_attr( $message ),
		lihan_icon( 'whatsapp', 18 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html__( 'Order on WhatsApp', 'lihan' )
	);
}
add_action( 'woocommerce_single_product_summary', 'lihan_wc_whatsapp_link', 34 );

/**
 * Add the "Delivery & Returns" tab (also picked up by page builders' tab widgets).
 *
 * @param array<string,array<string,mixed>> $tabs Product tabs.
 * @return array<string,array<string,mixed>>
 */
function lihan_wc_product_tabs( $tabs ): array {
	$tabs = is_array( $tabs ) ? $tabs : [];
	if ( '' !== trim( (string) lihan_get_option( 'wc_delivery_returns' ) ) ) {
		$tabs['lihan_delivery'] = [
			'title'    => __( 'Delivery & Returns', 'lihan' ),
			'priority' => 25,
			'callback' => 'lihan_wc_delivery_tab_content',
		];
	}
	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'lihan_wc_product_tabs', 20 );

/**
 * Delivery & returns content (customizer), with the free-delivery threshold when set.
 */
function lihan_wc_delivery_tab_content(): void {
	$threshold = (float) lihan_get_option( 'wc_free_shipping_threshold' );
	if ( $threshold > 0 ) {
		echo '<p class="lh-delivery-highlight">' . lihan_icon( 'truck', 18 ) . '<span>' . wp_kses_post( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			sprintf(
				/* translators: %s: formatted amount. */
				__( 'Free delivery on orders over %s', 'lihan' ),
				wc_price( $threshold )
			)
		) . '</span></p>';
	}
	echo wp_kses_post( wpautop( (string) lihan_get_option( 'wc_delivery_returns' ) ) );
}

/**
 * Product information as accordions (native <details>) in the summary column, replacing tabs.
 */
function lihan_wc_product_accordions(): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! lihan_wc_is_main_product() ) {
		return;
	}

	$tabs = (array) apply_filters( 'woocommerce_product_tabs', [] );
	if ( ! $tabs ) {
		return;
	}

	$labels = [
		'description'            => __( 'Product description', 'lihan' ),
		'additional_information' => __( 'Additional information', 'lihan' ),
		'lihan_delivery'         => __( 'Delivery & Returns', 'lihan' ),
		/* translators: %d: number of reviews. */
		'reviews'                => sprintf( __( 'Reviews (%d)', 'lihan' ), (int) $product->get_review_count() ),
	];

	add_filter( 'woocommerce_product_description_heading', '__return_empty_string' );
	add_filter( 'woocommerce_product_additional_information_heading', '__return_empty_string' );

	echo '<div class="lh-product-accordions">';
	foreach ( $tabs as $key => $tab ) {
		if ( empty( $tab['callback'] ) || ! is_callable( $tab['callback'] ) ) {
			continue;
		}
		$key   = (string) $key;
		$title = $labels[ $key ] ?? wp_strip_all_tags( (string) apply_filters( 'woocommerce_product_' . $key . '_tab_title', $tab['title'] ?? '', $key ) );

		printf(
			'<details class="lh-accordion lh-product-accordion lh-product-accordion--%1$s" id="lh-acc-%1$s"><summary>%2$s</summary><div class="lh-accordion__body">',
			esc_attr( sanitize_html_class( $key ) ),
			esc_html( $title )
		);
		call_user_func( $tab['callback'], $key, $tab );
		echo '</div></details>';
	}
	echo '</div>';

	remove_filter( 'woocommerce_product_description_heading', '__return_empty_string' );
	remove_filter( 'woocommerce_product_additional_information_heading', '__return_empty_string' );
}
add_action( 'woocommerce_single_product_summary', 'lihan_wc_product_accordions', 45 );

/**
 * Whether the related/upsell loop being rendered should become a carousel.
 *
 * @param bool|null $set Set the flag (internal).
 */
function lihan_wc_carousel_loop( ?bool $set = null ): bool {
	static $active = false;
	if ( null !== $set ) {
		$active = $set;
	}
	return $active;
}

/**
 * Upsells, then related products, as carousels below the product (outside div.product).
 */
function lihan_wc_related_and_upsells(): void {
	lihan_wc_carousel_loop( true );
	woocommerce_upsell_display( 8, 4 );
	woocommerce_output_related_products();
	lihan_wc_carousel_loop( false );
}
// After the product wrapper closes, so the sticky summary stops at the end of the gallery.
add_action( 'woocommerce_after_single_product', 'lihan_wc_related_and_upsells', 20 );

/**
 * More related products for the carousel.
 *
 * @param array<string,mixed> $args Related args.
 * @return array<string,mixed>
 */
function lihan_wc_related_args( $args ): array {
	$args                   = is_array( $args ) ? $args : [];
	$args['posts_per_page'] = 8;
	$args['columns']        = 4;
	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'lihan_wc_related_args' );

/**
 * Section headings.
 */
function lihan_wc_related_heading(): string {
	return __( 'You may also like', 'lihan' );
}
add_filter( 'woocommerce_product_related_products_heading', 'lihan_wc_related_heading' );

/**
 * Upsells heading.
 */
function lihan_wc_upsells_heading(): string {
	return __( 'Complete the look', 'lihan' );
}
add_filter( 'woocommerce_product_upsells_products_heading', 'lihan_wc_upsells_heading' );

/**
 * Wrap related/upsell loops in the shared carousel markup.
 *
 * @param string $html Loop start HTML.
 */
function lihan_wc_carousel_loop_start( $html ): string {
	$html = (string) $html;
	if ( ! lihan_wc_carousel_loop() ) {
		return $html;
	}
	$html = (string) preg_replace( '/<ul class="products/', '<ul class="products lh-carousel__track', $html, 1 );
	return '<div class="lh-carousel lh-products lh-product-carousel" data-lh-carousel data-per-view="4" data-per-view-tablet="3" data-per-view-mobile="2" data-gap="30"><div class="lh-carousel__viewport">' . $html;
}
add_filter( 'woocommerce_product_loop_start', 'lihan_wc_carousel_loop_start', 20 );

/**
 * Close the carousel markup.
 *
 * @param string $html Loop end HTML.
 */
function lihan_wc_carousel_loop_end( $html ): string {
	$html = (string) $html;
	if ( ! lihan_wc_carousel_loop() ) {
		return $html;
	}
	$nav = sprintf(
		'<div class="lh-carousel__nav"><button type="button" class="lh-carousel__prev" aria-label="%1$s">%2$s</button><span class="lh-carousel__counter" aria-live="polite"></span><button type="button" class="lh-carousel__next" aria-label="%3$s">%4$s</button></div>',
		esc_attr__( 'Previous', 'lihan' ),
		lihan_icon( 'chevron-left', 18 ),
		esc_attr__( 'Next', 'lihan' ),
		lihan_icon( 'chevron-right', 18 )
	);
	return $html . '</div>' . $nav . '</div>';
}
add_filter( 'woocommerce_product_loop_end', 'lihan_wc_carousel_loop_end', 20 );

/**
 * Carousel slides need the slide class from the first paint.
 *
 * @param array<int,string> $classes Classes.
 * @return array<int,string>
 */
function lihan_wc_carousel_slide_class( $classes ): array {
	$classes = is_array( $classes ) ? $classes : [];
	if ( lihan_wc_carousel_loop() ) {
		$classes[] = 'lh-carousel__slide';
	}
	return $classes;
}
add_filter( 'woocommerce_post_class', 'lihan_wc_carousel_slide_class', 30 );

/**
 * Gold stars: review form star SVG markup is CSS-only, nothing to do server-side.
 * Avatar size in reviews.
 */
function lihan_wc_review_gravatar_size(): int {
	return 48;
}
add_filter( 'woocommerce_review_gravatar_size', 'lihan_wc_review_gravatar_size' );

/* -------------------------------------------------------------------------
 * Cart drawer, fragments, AJAX add to cart
 * ---------------------------------------------------------------------- */

/**
 * Whether the off-canvas cart drawer is printed.
 */
function lihan_wc_show_cart_drawer(): bool {
	return (bool) apply_filters( 'lihan_show_cart_drawer', ! is_admin() && ! lihan_wc_is_cartflows_step() );
}

/**
 * Number of items in the cart.
 */
function lihan_wc_cart_count(): int {
	if ( function_exists( 'lihan_cart_count' ) ) {
		return lihan_cart_count();
	}
	return WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
}

/**
 * Drawer title count "(N)".
 */
function lihan_wc_drawer_count_markup(): string {
	$count = lihan_wc_cart_count();
	return '<span class="lh-cart-drawer__count" data-count="' . (int) $count . '">(' . esc_html( number_format_i18n( $count ) ) . ')</span>';
}

/**
 * Free-delivery progress block (always printed so the fragment selector matches).
 */
function lihan_wc_free_shipping_markup(): string {
	$threshold = (float) apply_filters( 'lihan_free_shipping_threshold', (float) lihan_get_option( 'wc_free_shipping_threshold' ) );
	$cart      = WC()->cart;

	if ( $threshold <= 0 || ! $cart || $cart->is_empty() ) {
		return '<div class="lh-free-shipping" hidden></div>';
	}

	$discount = (float) $cart->get_discount_total();
	if ( $cart->display_prices_including_tax() ) {
		$discount += (float) $cart->get_discount_tax();
	}
	$total     = max( 0.0, (float) $cart->get_displayed_subtotal() - $discount );
	$remaining = max( 0.0, $threshold - $total );
	$percent   = (int) min( 100, floor( $total / $threshold * 100 ) );

	if ( $remaining > 0 ) {
		$text = sprintf(
			/* translators: %s: formatted amount still needed. */
			__( 'Spend %s more for free delivery', 'lihan' ),
			'<strong>' . wc_price( $remaining ) . '</strong>'
		);
	} else {
		$text = __( 'You have unlocked <strong>free delivery</strong>!', 'lihan' );
	}

	return sprintf(
		'<div class="lh-free-shipping%1$s"><p class="lh-free-shipping__text">%2$s%3$s</p><div class="lh-free-shipping__bar" role="progressbar" aria-label="%4$s" aria-valuemin="0" aria-valuemax="100" aria-valuenow="%5$d"><span style="width:%5$d%%"></span></div></div>',
		$remaining > 0 ? '' : ' is-complete',
		lihan_icon( 'truck', 18 ),
		wp_kses_post( $text ),
		esc_attr__( 'Progress towards free delivery', 'lihan' ),
		$percent
	);
}

/**
 * Cart drawer markup (footer, before scripts).
 */
function lihan_wc_cart_drawer(): void {
	if ( ! lihan_wc_show_cart_drawer() ) {
		return;
	}
	?>
	<button type="button" class="lh-cart-drawer-trigger" data-lh-open="lh-cart-drawer" aria-controls="lh-cart-drawer" aria-expanded="false" tabindex="-1" hidden><?php esc_html_e( 'Open bag', 'lihan' ); ?></button>
	<div id="lh-cart-drawer" class="lh-drawer lh-drawer--right lh-cart-drawer" role="dialog" aria-modal="true" aria-labelledby="lh-cart-drawer-title">
		<div class="lh-drawer__header lh-cart-drawer__header">
			<h2 id="lh-cart-drawer-title" class="lh-drawer__title lh-cart-drawer__title"><?php esc_html_e( 'Your bag', 'lihan' ); ?> <?php echo lihan_wc_drawer_count_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
			<button type="button" class="lh-drawer__close" data-lh-close>
				<?php echo lihan_icon( 'close', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="lh-visually-hidden"><?php esc_html_e( 'Close bag', 'lihan' ); ?></span>
			</button>
		</div>
		<div class="lh-drawer__body lh-cart-drawer__body">
			<?php echo lihan_wc_free_shipping_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'lihan_wc_cart_drawer', 5 );

/**
 * Cart fragments: header count badge, drawer count and free-delivery progress.
 *
 * @param array<string,string> $fragments Fragments.
 * @return array<string,string>
 */
function lihan_wc_cart_fragments( $fragments ): array {
	$fragments = is_array( $fragments ) ? $fragments : [];
	if ( function_exists( 'lihan_cart_count_markup' ) ) {
		$fragments['.lh-cart-count'] = lihan_cart_count_markup();
	}
	$fragments['.lh-cart-drawer__count'] = lihan_wc_drawer_count_markup();
	$fragments['.lh-free-shipping']      = lihan_wc_free_shipping_markup();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'lihan_wc_cart_fragments' );

remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_proceed_to_checkout', 20 );

/**
 * Mini cart buttons: "View bag" (outline) and "Checkout" (primary).
 */
function lihan_wc_mini_cart_buttons(): void {
	printf(
		'<a href="%1$s" class="button wc-forward lh-btn lh-btn--outline">%2$s</a><a href="%3$s" class="button checkout wc-forward lh-btn lh-btn--primary">%4$s</a>',
		esc_url( wc_get_cart_url() ),
		esc_html__( 'View bag', 'lihan' ),
		esc_url( wc_get_checkout_url() ),
		esc_html__( 'Checkout', 'lihan' )
	);
}
add_action( 'woocommerce_widget_shopping_cart_buttons', 'lihan_wc_mini_cart_buttons', 10 );

/**
 * Empty mini cart: a way back to the shop.
 */
function lihan_wc_mini_cart_empty_cta(): void {
	if ( WC()->cart && WC()->cart->is_empty() ) {
		printf(
			'<p class="lh-mini-cart-empty-cta"><a class="lh-btn lh-btn--primary" href="%1$s">%2$s</a></p>',
			esc_url( wc_get_page_permalink( 'shop' ) ),
			esc_html__( 'Continue shopping', 'lihan' )
		);
	}
}
add_action( 'woocommerce_after_mini_cart', 'lihan_wc_mini_cart_empty_cta' );

/**
 * Whether the current request is the theme's AJAX add to cart.
 */
function lihan_wc_is_ajax_add(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Routing check only.
	return isset( $_GET['wc-ajax'] ) && 'lihan_add_to_cart' === sanitize_key( wp_unslash( $_GET['wc-ajax'] ) );
}

/**
 * Remember whether WooCommerce's form handler added something during this request.
 *
 * @param bool|null $set Set the flag (internal).
 */
function lihan_wc_ajax_added( ?bool $set = null ): bool {
	static $added = false;
	if ( null !== $set ) {
		$added = $set;
	}
	return $added;
}

/**
 * Flag a successful add during the theme's AJAX request.
 */
function lihan_wc_flag_added(): void {
	if ( lihan_wc_is_ajax_add() ) {
		lihan_wc_ajax_added( true );
	}
}
add_action( 'woocommerce_add_to_cart', 'lihan_wc_flag_added' );

/**
 * Remember a redirect requested by WooCommerce or a plugin during the AJAX add, and stop it
 * so the JSON response can tell the browser where to go.
 *
 * @param mixed $url Redirect URL.
 * @return mixed
 */
function lihan_wc_ajax_capture_redirect( $url ) {
	static $captured = '';
	if ( 'lihan_get' === $url ) {
		return $captured;
	}
	if ( lihan_wc_is_ajax_add() ) {
		if ( $url ) {
			$captured = (string) $url;
		}
		return false;
	}
	return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'lihan_wc_ajax_capture_redirect', PHP_INT_MAX );

/**
 * Never let the "redirect to cart" option redirect the AJAX request (JS skips AJAX when it is on).
 *
 * @param mixed $value Option value.
 * @return mixed
 */
function lihan_wc_ajax_no_cart_redirect( $value ) {
	return lihan_wc_is_ajax_add() ? 'no' : $value;
}
add_filter( 'pre_option_woocommerce_cart_redirect_after_add', 'lihan_wc_ajax_no_cart_redirect' );

/**
 * AJAX add to cart for the single product form (?wc-ajax=lihan_add_to_cart).
 *
 * The posted form is handled by WooCommerce's own WC_Form_Handler::add_to_cart_action()
 * on `wp_loaded` (so variations, grouped products and add-on plugins work unchanged);
 * this endpoint only reports the result as JSON with fresh fragments.
 */
function lihan_wc_ajax_add_to_cart(): void {
	$errors = wc_get_notices( 'error' );

	if ( ! lihan_wc_ajax_added() || $errors ) {
		if ( ! $errors && ! lihan_wc_ajax_added() ) {
			wc_add_notice( __( 'Sorry, the product could not be added. Please try again.', 'lihan' ), 'error' );
		}
		wp_send_json(
			[
				'error'   => true,
				'notices' => wc_print_notices( true ),
			]
		);
	}

	// Keep other notices (e.g. a plugin's info) for the next page, drop "added to your cart".
	$notices = wc_get_notices();
	unset( $notices['success'] );
	wc_set_notices( $notices );

	$redirect = (string) lihan_wc_ajax_capture_redirect( 'lihan_get' );
	if ( '' !== $redirect ) {
		wp_send_json( [ 'redirect' => esc_url_raw( $redirect ) ] );
	}

	WC_AJAX::get_refreshed_fragments();
}
add_action( 'wc_ajax_lihan_add_to_cart', 'lihan_wc_ajax_add_to_cart' );

/* -------------------------------------------------------------------------
 * Cart, checkout, account
 * ---------------------------------------------------------------------- */

// Cross-sells go below the cart (full width) instead of into the narrow totals column.
remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
add_action( 'woocommerce_after_cart', 'woocommerce_cross_sell_display' );

/**
 * Cross-sells: 4 per row under the cart.
 */
function lihan_wc_cross_sells_columns(): int {
	return 4;
}
add_filter( 'woocommerce_cross_sells_columns', 'lihan_wc_cross_sells_columns' );

/**
 * Body classes for WooCommerce pages.
 *
 * @param array<int,string> $classes Body classes.
 * @return array<int,string>
 */
function lihan_wc_body_classes( array $classes ): array {
	if ( is_cart() || is_checkout() || is_account_page() ) {
		$classes[] = 'lh-wc-page';
	}
	if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
		$classes[] = 'lh-wc-checkout';
	}
	return $classes;
}
add_filter( 'body_class', 'lihan_wc_body_classes' );
