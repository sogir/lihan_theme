<?php
/**
 * Homepage: section registry, rendering helpers, defaults, customizer,
 * shortcode, assets and FAQ structured data.
 *
 * Sections live in template-parts/home/{name}.php. Each one accepts an
 * $args array (documented at the top of the file) that overrides the
 * customizer values, so page builders can reuse them.
 *
 * Option keys (theme mods) registered here, all with defaults in lihan_defaults:
 *   home_{section}_enable                 bool, one per section
 *   home_hero_autoplay                    int seconds (0 = off)
 *   home_hero_{1-4}_image                 attachment ID (or image URL)
 *   home_hero_{1-4}_image_mobile          attachment ID (or image URL)
 *   home_hero_{1-4}_heading / _text / _button / _url
 *   home_hero_{1-4}_color                 dark|light
 *   home_usps_heading, home_usps_items    "icon|Label" per line
 *   home_intro_heading, home_intro_text, home_categories (comma list of product_cat slugs)
 *   home_inspiration_heading / _text / _button / _tag / _count
 *   home_trending_heading, home_trending_source, home_trending_count
 *   home_reviews_source (auto|manual), home_reviews ("Title|Text|Name" per line)
 *   home_promo_heading / _text / _button / _url / _image, home_promo_logo (bool)
 *   home_about_image / _heading / _text / _button / _url
 *   home_seo_heading, home_seo_text
 *   home_rich_heading, home_rich_text
 *   home_faq_heading, home_faq_items ("Question|Answer" per line), home_faq_schema (bool)
 *
 * Rich text values support link tokens that resolve at render time:
 *   {{shop|label}}      link to the shop page
 *   {{cat:slug|label}}  link to a product category (plain label if it does not exist)
 *   {{page:slug|label}} link to a page (plain label if it does not exist)
 *
 * @package Lihan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Registry
 * ---------------------------------------------------------------------- */

/**
 * All homepage sections, in default order: name => label.
 *
 * @return array<string,string>
 */
function lihan_home_section_registry(): array {
	$sections = array(
		'hero'              => __( 'Hero slider', 'lihan' ),
		'usps'              => __( 'USP bar', 'lihan' ),
		'collection_intro'  => __( 'Collection intro & categories', 'lihan' ),
		'inspiration'       => __( 'Styling inspiration', 'lihan' ),
		'featured_products' => __( 'Trending products', 'lihan' ),
		'reviews'           => __( 'Customer reviews', 'lihan' ),
		'promo_banner'      => __( 'Promo banner', 'lihan' ),
		'image_text'        => __( 'Image with text', 'lihan' ),
		'seo_text'          => __( 'Read-more text', 'lihan' ),
		'rich_text'         => __( 'Rich text', 'lihan' ),
		'faq'               => __( 'FAQ', 'lihan' ),
	);

	/**
	 * Filters the homepage section registry (name => label). A section added
	 * here needs a matching template-parts/home/{name}.php file.
	 *
	 * @param array<string,string> $sections Sections.
	 */
	return (array) apply_filters( 'lihan_home_section_registry', $sections );
}

/**
 * Whether a section name is registered.
 *
 * @param string $name Section name.
 */
function lihan_is_home_section( string $name ): bool {
	return '' !== $name && array_key_exists( $name, lihan_home_section_registry() );
}

/**
 * Ordered list of sections rendered on the front page (enabled ones only).
 *
 * @return string[]
 */
function lihan_home_sections(): array {
	$order = array_keys( lihan_home_section_registry() );

	/**
	 * Filters the order of the homepage sections. Return an array of
	 * section names; unknown names are ignored.
	 *
	 * @param string[] $order Section names.
	 */
	$order = (array) apply_filters( 'lihan_home_sections', $order );

	$enabled = array();
	foreach ( $order as $name ) {
		$name = sanitize_key( (string) $name );
		if ( lihan_is_home_section( $name ) && ! in_array( $name, $enabled, true ) && lihan_home_bool( lihan_home_opt( 'home_' . $name . '_enable' ) ) ) {
			$enabled[] = $name;
		}
	}
	return $enabled;
}

/**
 * Render a homepage section.
 *
 * @param string $name Section name (see lihan_home_section_registry()).
 * @param array  $args Overrides for the section's customizer values.
 */
function lihan_render_section( string $name, array $args = array() ): void {
	$name = sanitize_key( $name );
	if ( ! lihan_is_home_section( $name ) ) {
		return;
	}

	/**
	 * Filters a section's arguments before it renders.
	 *
	 * @param array  $args Arguments.
	 * @param string $name Section name.
	 */
	$args = (array) apply_filters( 'lihan_section_args', $args, $name );

	lihan_enqueue_home_assets();

	get_template_part( 'template-parts/home/' . $name, null, $args );
}

/**
 * Return a section's markup as a string.
 *
 * @param string $name Section name.
 * @param array  $args Arguments.
 */
function lihan_get_section( string $name, array $args = array() ): string {
	ob_start();
	lihan_render_section( $name, $args );
	return (string) ob_get_clean();
}

/* -------------------------------------------------------------------------
 * Option helpers
 * ---------------------------------------------------------------------- */

/**
 * Read a theme option, through lihan_get_option() when the core file is loaded.
 *
 * @param string $key Option key.
 * @return mixed
 */
function lihan_home_opt( string $key ) {
	if ( function_exists( 'lihan_get_option' ) ) {
		return lihan_get_option( $key );
	}
	$defaults = lihan_home_defaults();
	return get_theme_mod( $key, $defaults[ $key ] ?? '' );
}

/**
 * Loose boolean.
 *
 * @param mixed $value Value.
 */
function lihan_home_bool( $value ): bool {
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( is_string( $value ) ) {
		return ! in_array( strtolower( trim( $value ) ), array( '', '0', 'false', 'no', 'off' ), true );
	}
	return (bool) $value;
}

/**
 * Merge section arguments with option-backed defaults.
 *
 * A key missing from $args, or set to null or '', falls back to the option.
 * Set a text key to false to hide that element.
 *
 * @param array $args     Passed arguments.
 * @param array $defaults key => default value (already read from options).
 * @return array
 */
function lihan_home_args( array $args, array $defaults ): array {
	$out = $defaults;
	foreach ( $args as $key => $value ) {
		if ( null === $value || '' === $value ) {
			continue;
		}
		$out[ $key ] = $value;
	}
	foreach ( $out as $key => $value ) {
		if ( false === $value && isset( $defaults[ $key ] ) && ! is_bool( $defaults[ $key ] ) ) {
			$out[ $key ] = '';
		}
	}
	return $out;
}

/**
 * Split a list option into rows of fields.
 *
 * Accepts an array of rows (each an array or a "a|b|c" string) or a string
 * with one row per line (" || " also separates rows, for shortcodes).
 *
 * @param mixed    $value  Value.
 * @param string[] $fields Field names, in order.
 * @return array<int,array<string,string>>
 */
function lihan_home_parse_rows( $value, array $fields ): array {
	if ( is_string( $value ) ) {
		$value = preg_split( '/\r\n|\r|\n|\s*\|\|\s*/', $value );
	}
	if ( ! is_array( $value ) ) {
		return array();
	}

	$rows = array();
	foreach ( $value as $row ) {
		if ( is_string( $row ) ) {
			$row = trim( $row );
			if ( '' === $row ) {
				continue;
			}
			$row = array_map( 'trim', explode( '|', $row, count( $fields ) ) );
		}
		if ( ! is_array( $row ) ) {
			continue;
		}
		$item = array();
		foreach ( $fields as $i => $field ) {
			$v              = $row[ $field ] ?? ( $row[ $i ] ?? '' );
			$item[ $field ] = is_scalar( $v ) ? trim( (string) $v ) : '';
		}
		if ( '' !== implode( '', $item ) ) {
			$rows[] = $item;
		}
	}
	return $rows;
}

/**
 * Replace {{shop|label}}, {{cat:slug|label}} and {{page:slug|label}} tokens with links.
 *
 * @param string $html Text.
 */
function lihan_home_expand_tokens( string $html ): string {
	if ( ! str_contains( $html, '{{' ) ) {
		return $html;
	}
	return (string) preg_replace_callback(
		'/\{\{\s*(shop|cat:[a-z0-9_-]+|page:[a-z0-9_\/-]+)\s*(?:\|\s*([^}]*?))?\s*\}\}/i',
		static function ( array $m ): string {
			$target = strtolower( $m[1] );
			$label  = isset( $m[2] ) ? trim( $m[2] ) : '';
			$url    = '';

			if ( 'shop' === $target ) {
				$url   = lihan_home_shop_url();
				$label = '' !== $label ? $label : __( 'shop', 'lihan' );
			} elseif ( str_starts_with( $target, 'cat:' ) ) {
				$term = taxonomy_exists( 'product_cat' ) ? get_term_by( 'slug', substr( $target, 4 ), 'product_cat' ) : false;
				if ( $term instanceof WP_Term ) {
					$link  = get_term_link( $term );
					$url   = is_wp_error( $link ) ? '' : $link;
					$label = '' !== $label ? $label : $term->name;
				}
			} elseif ( str_starts_with( $target, 'page:' ) ) {
				$page = get_page_by_path( substr( $target, 5 ) );
				if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
					$url   = (string) get_permalink( $page );
					$label = '' !== $label ? $label : get_the_title( $page );
				}
			}

			if ( '' === $label ) {
				return '';
			}
			return '' !== $url ? '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>' : esc_html( $label );
		},
		$html
	);
}

/**
 * Format stored rich text for output: tokens, paragraphs, kses.
 *
 * @param string $text Text.
 */
function lihan_home_rich_text( string $text ): string {
	$text = trim( $text );
	if ( '' === $text ) {
		return '';
	}
	return wp_kses_post( wpautop( lihan_home_expand_tokens( $text ) ) );
}

/**
 * Shop page URL, falling back to the product archive or the home page.
 */
function lihan_home_shop_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'shop' );
		if ( $url ) {
			return $url;
		}
	}
	$archive = post_type_exists( 'product' ) ? get_post_type_archive_link( 'product' ) : false;
	return $archive ? $archive : home_url( '/' );
}

/**
 * Product category URL by slug ('' when missing).
 *
 * @param string $slug Category slug.
 */
function lihan_home_category_url( string $slug ): string {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return '';
	}
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		return '';
	}
	$link = get_term_link( $term );
	return is_wp_error( $link ) ? '' : $link;
}

/**
 * Contact page URL, else Messenger, else WhatsApp.
 */
function lihan_home_contact_url(): string {
	foreach ( array( 'contact', 'contact-us' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			return (string) get_permalink( $page );
		}
	}
	$messenger = (string) lihan_home_opt( 'contact_messenger' );
	return '' !== $messenger ? $messenger : lihan_home_whatsapp_url();
}

/**
 * WhatsApp chat URL built from the contact_whatsapp option.
 */
function lihan_home_whatsapp_url(): string {
	$number = preg_replace( '/\D+/', '', (string) lihan_home_opt( 'contact_whatsapp' ) );
	if ( '' === $number ) {
		$number = '8801707629095';
	}
	return 'https://wa.me/' . $number;
}

/**
 * Inline SVG icon through the core helper, if available.
 *
 * @param string $name  Icon name.
 * @param int    $size  Size in px.
 * @param string $class Extra class.
 */
function lihan_home_icon( string $name, int $size = 20, string $class = '' ): string {
	return function_exists( 'lihan_icon' ) ? (string) lihan_icon( $name, $size, $class ) : '';
}

/**
 * Carousel prev/counter/next controls (shared carousel markup).
 *
 * @param string $counter Initial counter text.
 */
function lihan_home_carousel_nav( string $counter = '' ): string {
	return '<div class="lh-carousel__nav">'
		. '<button type="button" class="lh-carousel__prev" aria-label="' . esc_attr__( 'Previous', 'lihan' ) . '">' . lihan_home_icon( 'chevron-left', 18 ) . '</button>'
		. '<span class="lh-carousel__counter" aria-live="polite">' . esc_html( $counter ) . '</span>'
		. '<button type="button" class="lh-carousel__next" aria-label="' . esc_attr__( 'Next', 'lihan' ) . '">' . lihan_home_icon( 'chevron-right', 18 ) . '</button>'
		. '</div>';
}

/**
 * Build the attribute string for a section wrapper.
 *
 * @param string   $name    Section name.
 * @param array    $args    Section args ('id', 'class').
 * @param string[] $classes Extra classes.
 * @param string   $label_id ID of the heading that labels the section.
 */
function lihan_home_section_attrs( string $name, array $args, array $classes = array(), string $label_id = '' ): string {
	$classes = array_merge( array( 'lh-home-section', 'lh-home-' . str_replace( '_', '-', $name ) ), $classes );
	if ( ! empty( $args['class'] ) && is_string( $args['class'] ) ) {
		$classes = array_merge( $classes, preg_split( '/\s+/', $args['class'] ) );
	}
	$classes = array_filter( array_map( 'sanitize_html_class', $classes ) );

	$out = ' class="' . esc_attr( implode( ' ', array_unique( $classes ) ) ) . '"';
	if ( ! empty( $args['id'] ) && is_string( $args['id'] ) ) {
		$out .= ' id="' . esc_attr( sanitize_html_class( $args['id'] ) ) . '"';
	}
	if ( '' !== $label_id ) {
		$out .= ' aria-labelledby="' . esc_attr( $label_id ) . '"';
	}
	return $out;
}

/* -------------------------------------------------------------------------
 * Images
 * ---------------------------------------------------------------------- */

/**
 * Resolve an image option to an attachment ID or URL.
 *
 * @param mixed  $value     Attachment ID, numeric string or URL.
 * @param string $demo_file File name under THEME/demo/images/ used as fallback.
 * @return array{id:int,url:string,width:int,height:int}|null
 */
function lihan_home_resolve_image( $value, string $demo_file = '' ): ?array {
	$id = 0;
	if ( is_numeric( $value ) ) {
		$id = absint( $value );
	} elseif ( is_array( $value ) && isset( $value['id'] ) ) {
		$id = absint( $value['id'] );
		if ( ! $id && ! empty( $value['url'] ) ) {
			$value = (string) $value['url'];
		}
	}
	if ( ! $id && is_string( $value ) && '' !== trim( $value ) && ! is_numeric( $value ) ) {
		$url = esc_url_raw( trim( $value ) );
		if ( '' !== $url ) {
			$id = attachment_url_to_postid( $url );
			if ( ! $id ) {
				return array(
					'id'     => 0,
					'url'    => $url,
					'width'  => 0,
					'height' => 0,
				);
			}
		}
	}
	if ( $id && wp_attachment_is_image( $id ) ) {
		return array(
			'id'     => $id,
			'url'    => '',
			'width'  => 0,
			'height' => 0,
		);
	}

	if ( '' !== $demo_file ) {
		$file = get_template_directory() . '/demo/images/' . $demo_file;
		if ( is_readable( $file ) ) {
			$size = wp_getimagesize( $file );
			return array(
				'id'     => 0,
				'url'    => get_template_directory_uri() . '/demo/images/' . rawurlencode( $demo_file ),
				'width'  => is_array( $size ) ? (int) $size[0] : 0,
				'height' => is_array( $size ) ? (int) $size[1] : 0,
			);
		}
	}
	return null;
}

/**
 * Image markup for a resolved image.
 *
 * @param array|null $image Result of lihan_home_resolve_image().
 * @param string     $size  Registered image size.
 * @param array      $attr  Attributes (class, alt, loading, fetchpriority, sizes).
 */
function lihan_home_image_html( ?array $image, string $size, array $attr = array() ): string {
	if ( ! $image ) {
		return '';
	}
	$attr = array_merge(
		array(
			'loading'  => 'lazy',
			'decoding' => 'async',
		),
		$attr
	);
	if ( ! has_image_size( $size ) && ! in_array( $size, array( 'thumbnail', 'medium', 'medium_large', 'large', 'full' ), true ) ) {
		$size = 'large';
	}

	if ( $image['id'] ) {
		return wp_get_attachment_image( $image['id'], $size, false, $attr );
	}

	$html = '<img src="' . esc_url( $image['url'] ) . '"';
	if ( $image['width'] && $image['height'] ) {
		$html .= ' width="' . (int) $image['width'] . '" height="' . (int) $image['height'] . '"';
	}
	if ( ! isset( $attr['alt'] ) ) {
		$attr['alt'] = '';
	}
	foreach ( $attr as $key => $val ) {
		if ( false === $val || null === $val || 'sizes' === $key ) {
			continue;
		}
		$html .= ' ' . esc_attr( $key ) . '="' . esc_attr( (string) $val ) . '"';
	}
	return $html . '>';
}

/**
 * Brand placeholder shown when an image is not available.
 *
 * @param string $variant 'warm' (light, for dark text) or 'deep' (dark green).
 * @param string $class   Extra class.
 */
function lihan_home_placeholder( string $variant = 'warm', string $class = '' ): string {
	$variant = 'deep' === $variant ? 'deep' : 'warm';
	return '<span class="' . esc_attr( trim( 'lh-ph lh-ph--' . $variant . ' ' . $class ) ) . '" aria-hidden="true"></span>';
}

/* -------------------------------------------------------------------------
 * Products
 * ---------------------------------------------------------------------- */

/**
 * Query visible product IDs for a homepage source.
 *
 * @param string $source featured|best_selling|on_sale|latest|tag:<slug>|category:<slug>.
 * @param int    $limit  Max products.
 * @return int[]
 */
function lihan_home_product_ids( string $source, int $limit = 8 ): array {
	if ( ! class_exists( 'WooCommerce' ) || ! post_type_exists( 'product' ) ) {
		return array();
	}
	$limit = max( 1, min( 24, $limit ) );

	$tax_query = array( 'relation' => 'AND' );
	if ( function_exists( 'wc_get_product_visibility_term_ids' ) ) {
		$vis     = wc_get_product_visibility_term_ids();
		$exclude = array();
		if ( ! empty( $vis['exclude-from-catalog'] ) ) {
			$exclude[] = $vis['exclude-from-catalog'];
		}
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! empty( $vis['outofstock'] ) ) {
			$exclude[] = $vis['outofstock'];
		}
		if ( $exclude ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $exclude,
				'operator' => 'NOT IN',
			);
		}
	}

	$query = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'posts_per_page'         => $limit,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'ignore_sticky_posts'    => true,
		'update_post_term_cache' => false,
		'orderby'                => array(
			'date' => 'DESC',
			'ID'   => 'DESC',
		),
	);

	if ( 'featured' === $source ) {
		$vis = function_exists( 'wc_get_product_visibility_term_ids' ) ? wc_get_product_visibility_term_ids() : array();
		if ( empty( $vis['featured'] ) ) {
			return array();
		}
		$tax_query[] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'terms'    => array( $vis['featured'] ),
		);
	} elseif ( 'best_selling' === $source ) {
		// Products without sales sort by date, so a new store gets the latest products.
		$query['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$query['orderby']  = array(
			'meta_value_num' => 'DESC',
			'date'           => 'DESC',
		);
	} elseif ( 'on_sale' === $source ) {
		$ids = function_exists( 'wc_get_product_ids_on_sale' ) ? array_filter( array_map( 'absint', wc_get_product_ids_on_sale() ) ) : array();
		if ( ! $ids ) {
			return array();
		}
		$query['post__in'] = $ids;
		// Variations are listed on sale too; the parents are already in the list.
		$query['post_parent'] = 0;
	} elseif ( str_starts_with( $source, 'category:' ) || str_starts_with( $source, 'tag:' ) ) {
		$is_cat      = str_starts_with( $source, 'category:' );
		$slug        = sanitize_title( substr( $source, $is_cat ? 9 : 4 ) );
		$tax_query[] = array(
			'taxonomy'         => $is_cat ? 'product_cat' : 'product_tag',
			'field'            => 'slug',
			'terms'            => array( $slug ),
			'include_children' => true,
		);
	}

	$query['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query

	/**
	 * Filters the homepage product query.
	 *
	 * @param array  $query  WP_Query args.
	 * @param string $source Source.
	 */
	$query = (array) apply_filters( 'lihan_home_product_query', $query, $source );

	$result = new WP_Query( $query );
	return array_map( 'absint', $result->posts );
}

/**
 * Sources offered for the trending carousel.
 *
 * @return array<string,string>
 */
function lihan_home_trending_sources(): array {
	$choices = array(
		'best_selling' => __( 'Best selling', 'lihan' ),
		'featured'     => __( 'Featured', 'lihan' ),
		'on_sale'      => __( 'On sale', 'lihan' ),
		'latest'       => __( 'Latest', 'lihan' ),
	);
	if ( taxonomy_exists( 'product_cat' ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'number'     => 50,
			)
		);
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				/* translators: %s: product category name. */
				$choices[ 'category:' . $term->slug ] = sprintf( __( 'Category: %s', 'lihan' ), $term->name );
			}
		}
	}
	return $choices;
}

/* -------------------------------------------------------------------------
 * Defaults
 * ---------------------------------------------------------------------- */

/**
 * Homepage option defaults.
 *
 * @return array<string,mixed>
 */
function lihan_home_defaults(): array {
	static $cache = array();
	$locale = determine_locale();
	if ( isset( $cache[ $locale ] ) ) {
		return $cache[ $locale ];
	}

	$d = array();

	foreach ( array_keys( lihan_home_section_registry() ) as $name ) {
		$d[ 'home_' . $name . '_enable' ] = true;
	}

	$d['home_hero_autoplay'] = 6;
	$slides                  = array(
		1 => array( __( 'THE NEW ABAYA EDIT', 'lihan' ), __( 'Timeless silhouettes, crafted for everyday grace', 'lihan' ), __( 'SHOP NOW', 'lihan' ) ),
		2 => array( __( 'HIJABS FOR EVERY DAY', 'lihan' ), __( 'Soft, breathable fabrics in every shade', 'lihan' ), __( 'SHOP HIJABS', 'lihan' ) ),
		3 => array( '', '', '' ),
		4 => array( '', '', '' ),
	);
	foreach ( $slides as $n => $copy ) {
		$d[ "home_hero_{$n}_image" ]        = '';
		$d[ "home_hero_{$n}_image_mobile" ] = '';
		$d[ "home_hero_{$n}_heading" ]      = $copy[0];
		$d[ "home_hero_{$n}_text" ]         = $copy[1];
		$d[ "home_hero_{$n}_button" ]       = $copy[2];
		$d[ "home_hero_{$n}_url" ]          = '';
		$d[ "home_hero_{$n}_color" ]        = 'dark';
	}

	$d['home_usps_heading'] = __( 'WHY LIHAN?', 'lihan' );
	$d['home_usps_items']   = implode(
		"\n",
		array(
			'fabric|' . __( 'Premium Fabrics', 'lihan' ),
			'design|' . __( 'Elegant Designs', 'lihan' ),
			'hand-heart|' . __( 'Crafted with Care', 'lihan' ),
			'cod|' . __( 'Cash on Delivery', 'lihan' ),
			'truck|' . __( 'Nationwide Delivery', 'lihan' ),
			'refresh|' . __( 'Easy Exchange', 'lihan' ),
		)
	);

	$d['home_intro_heading'] = __( 'MODEST FASHION ONLINE: DISCOVER ABAYAS, HIJABS & MORE AT LIHAN', 'lihan' );
	$d['home_intro_text']    = __( 'Graceful {{cat:abaya-set|abaya sets}}, {{cat:hijab|hijabs}} and {{cat:niqab|niqabs}}, chosen for comfort and delivered across Bangladesh with cash on delivery.', 'lihan' );
	$d['home_categories']    = '';

	$d['home_inspiration_heading'] = __( 'STYLING INSPIRATION', 'lihan' );
	$d['home_inspiration_text']    = __( 'Fresh ways to wear your favourite Lihan pieces, from easy everyday hijab looks to elegant abaya layers. Shop each look in one tap.', 'lihan' );
	$d['home_inspiration_button']  = __( 'FOLLOW US ON FACEBOOK', 'lihan' );
	$d['home_inspiration_tag']     = 'inspiration';
	$d['home_inspiration_count']   = 8;

	$d['home_trending_heading'] = __( 'TRENDING NOW', 'lihan' );
	$d['home_trending_source']  = 'best_selling';
	$d['home_trending_count']   = 12;

	$d['home_reviews_source'] = 'auto';
	$d['home_reviews']        = implode(
		"\n",
		array(
			__( 'Beautiful quality|The abaya set fits perfectly and the fabric is so soft. It reached me in Chattogram in two days and I paid cash on delivery. Will order again, in sha Allah.|Nusrat J., Chattogram', 'lihan' ),
			__( 'Exactly as pictured|I was worried about the colour, but the hijab looks exactly like the photos. Light, breathable and easy to style for the office.|Farhana A., Dhaka', 'lihan' ),
			__( 'Such caring service|They helped me pick my size on WhatsApp and exchanged it without any hassle when I needed a longer length. Very kind team.|Sumaiya R., Sylhet', 'lihan' ),
			__( 'My everyday niqab|The niqab combo stays comfortable all day, even in this heat. Neat stitching and lovely packaging too.|Tasnim H., Rajshahi', 'lihan' ),
		)
	);

	$d['home_promo_heading'] = __( 'LIHAN LIFESTYLE', 'lihan' );
	$d['home_promo_text']    = __( 'Modest wear designed in Bangladesh, made to be loved for years. Order online with cash on delivery, or message us on WhatsApp for personal styling help.', 'lihan' );
	$d['home_promo_button']  = __( 'CHAT ON WHATSAPP', 'lihan' );
	$d['home_promo_url']     = '';
	$d['home_promo_image']   = '';
	$d['home_promo_logo']    = false;

	$d['home_about_image']   = '';
	$d['home_about_heading'] = __( 'OUR PROMISE', 'lihan' );
	$d['home_about_text']    = __( 'Our mission is simple: modest clothing that feels as good as it looks, at a fair price. Every Lihan piece is chosen for its fabric, finish and fit, so you can dress with confidence from morning classes to family dinners.', 'lihan' )
		. "\n\n"
		. __( 'Have a question about sizing, fabric or your order? Our team is always happy to help by phone, WhatsApp or Messenger, and your feedback helps us get better every season.', 'lihan' );
	$d['home_about_button']  = __( 'CONTACT US', 'lihan' );
	$d['home_about_url']     = '';

	$d['home_seo_heading'] = __( 'MODEST CLOTHING FOR EVERY DAY IN BANGLADESH', 'lihan' );
	$d['home_seo_text']    = __( 'Lihan Lifestyle was started with one idea: modest fashion in Bangladesh should be easy to find, comfortable to wear and honest in price. That is why we keep our collection focused on the pieces you reach for again and again.', 'lihan' )
		. "\n\n"
		. __( 'Our abaya sets come in breathable fabrics that drape beautifully through the humid months, in classic black, soft neutrals and deep jewel tones. Each one is finished with care, with lengths from 52 to 58 so you can find a fit that suits your height.', 'lihan' )
		. "\n\n"
		. __( 'Pair them with our hijabs in jersey, georgette and chiffon, or complete the look with a niqab, an inner cap and hand socks. Everything is delivered to your door across all 64 districts, and you only pay when the parcel arrives.', 'lihan' )
		. "\n\n"
		. __( 'Not sure what to choose? Send us a message on WhatsApp or Messenger. We are happy to share extra photos, measurements and styling ideas before you order.', 'lihan' );

	$d['home_rich_heading'] = __( 'SHOP ABAYAS, HIJABS, NIQABS & MORE AT LIHAN', 'lihan' );
	$d['home_rich_text']    = __( 'Our modest wear collection blends traditional coverage with clean, contemporary design, so you can dress with confidence for work, study, family events and everything in between.', 'lihan' )
		. "\n\n"
		. __( 'Explore our collections:', 'lihan' )
		. "\n<ul>\n<li>"
		. __( '{{cat:abaya-set|Abaya Set}}: matching abaya sets in soft, easy-care fabrics, from everyday basics to occasion-ready designs.', 'lihan' )
		. "</li>\n<li>"
		. __( '{{cat:hijab|Hijab}}: lightweight hijabs in jersey, georgette and chiffon that are simple to style and comfortable all day.', 'lihan' )
		. "</li>\n<li>"
		. __( '{{cat:niqab|Niqab}}: breathable single and layered niqabs with secure ties and a neat finish.', 'lihan' )
		. "</li>\n<li>"
		. __( '{{cat:niqab-combo|Niqab Combo}}: coordinated niqab and hijab sets that take the guesswork out of matching.', 'lihan' )
		. "</li>\n<li>"
		. __( '{{cat:inner-cap|Inner Cap}}: soft, stretchy under-scarf caps that keep your hijab in place without slipping.', 'lihan' )
		. "</li>\n<li>"
		. __( '{{cat:hat-moja|Hat Moja}}: hand socks in comfortable stretch fabrics for complete coverage, in colours to match every outfit.', 'lihan' )
		. "</li>\n</ul>";

	$d['home_faq_heading'] = __( 'FREQUENTLY ASKED QUESTIONS', 'lihan' );
	$d['home_faq_items']   = implode(
		"\n",
		array(
			__( 'How long does delivery take?|Inside Dhaka, orders usually arrive within 1 to 2 working days. Outside Dhaka, delivery takes 2 to 4 working days through our courier partners.', 'lihan' ),
			__( 'Do you offer cash on delivery?|Yes. Cash on delivery is available all over Bangladesh, so you pay the delivery person when your parcel arrives.', 'lihan' ),
			__( 'What is your exchange policy?|If the size or item is not right, you can request an exchange within 7 days of delivery. Items must be unused, unwashed and in their original packaging. Message us on WhatsApp or Messenger to arrange it.', 'lihan' ),
			__( 'How do I choose the right size?|Check the size guide on each product page. Our abayas come in lengths 52, 54, 56 and 58. If you are between sizes, send us your height and we will recommend the best fit.', 'lihan' ),
			__( 'How should I care for my abaya and hijab?|Hand wash or use a gentle machine cycle in cold water with a mild detergent, dry in the shade and iron on low heat. Avoid bleach to keep the colours rich.', 'lihan' ),
			__( 'Can I pay with bKash or Nagad?|Yes, you can pay in advance with bKash or Nagad if you prefer. Our team will share the payment details when we call to confirm your order.', 'lihan' ),
			__( 'How can I track my order?|After you order, we call to confirm it and send the courier tracking details by SMS or WhatsApp. You can also message us your order number at any time for an update.', 'lihan' ),
			__( 'Do you take custom or bulk orders?|Yes, we take custom length and bulk orders for families, events and gifts. Message us on WhatsApp with what you need and we will share the price and delivery time.', 'lihan' ),
		)
	);
	$d['home_faq_schema']  = true;

	// Cache only once translations can load, so early calls do not pin English copy.
	if ( did_action( 'after_setup_theme' ) ) {
		$cache[ $locale ] = $d;
	}
	return $d;
}

add_filter(
	'lihan_defaults',
	static function ( $defaults ) {
		return array_merge( lihan_home_defaults(), is_array( $defaults ) ? $defaults : array() );
	}
);

/* -------------------------------------------------------------------------
 * Assets
 * ---------------------------------------------------------------------- */

/**
 * Register the homepage assets (handle lihan-home for CSS and JS).
 *
 * Runs after the core theme registers lihan-main (priority 20), so the
 * stylesheet can depend on it. Elementor widgets can list 'lihan-home' in
 * get_style_depends() / get_script_depends().
 */
function lihan_register_home_assets(): void {
	if ( wp_style_is( 'lihan-home', 'registered' ) ) {
		return;
	}
	$version = defined( 'LIHAN_VERSION' ) ? LIHAN_VERSION : '1.0.0';
	$uri     = get_template_directory_uri();
	$dir     = get_template_directory();
	$css     = $dir . '/assets/css/home.css';
	$js      = $dir . '/assets/js/home.js';

	// Dependencies are resolved when the tags print, so lihan-main only has to be
	// registered by then (the core theme does it on wp_enqueue_scripts, priority 20).
	$has_main = wp_style_is( 'lihan-main', 'registered' ) || is_readable( $dir . '/assets/css/main.css' );

	wp_register_style(
		'lihan-home',
		$uri . '/assets/css/home.css',
		$has_main ? array( 'lihan-main' ) : array(),
		is_readable( $css ) ? $version . '.' . filemtime( $css ) : $version
	);

	if ( is_readable( $js ) ) {
		wp_register_script(
			'lihan-home',
			$uri . '/assets/js/home.js',
			( wp_script_is( 'lihan-main', 'registered' ) || is_readable( $dir . '/assets/js/main.js' ) ) ? array( 'lihan-main' ) : array(),
			$version . '.' . filemtime( $js ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'lihan_register_home_assets', 25 );

/**
 * Enqueue the homepage CSS and JS. Safe to call any time, also from widgets
 * rendering in the page body (the stylesheet then prints in the footer).
 */
function lihan_enqueue_home_assets(): void {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	if ( ! wp_style_is( 'lihan-home', 'registered' ) ) {
		lihan_register_home_assets();
	}
	wp_enqueue_style( 'lihan-home' );
	if ( wp_script_is( 'lihan-home', 'registered' ) ) {
		wp_enqueue_script( 'lihan-home' );
	}
}

/**
 * Whether the current request needs the homepage assets in the head.
 */
function lihan_home_needs_assets(): bool {
	if ( is_front_page() ) {
		return true;
	}
	if ( ! is_singular() ) {
		return false;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return false;
	}
	if ( has_shortcode( (string) $post->post_content, 'lihan_section' ) ) {
		return true;
	}
	$data = get_post_meta( $post->ID, '_elementor_data', true );
	if ( is_string( $data ) && '' !== $data && preg_match( '/"widgetType":\s*"lihan[-_]/', $data ) ) {
		return true;
	}
	return false;
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( lihan_home_needs_assets() ) {
			lihan_enqueue_home_assets();
		}
	},
	30
);

/* -------------------------------------------------------------------------
 * Shortcode
 * ---------------------------------------------------------------------- */

/**
 * [lihan_section name="usps" heading="..."] – renders any homepage section.
 * Every other attribute is passed to the section as an $arg. Enclosed content
 * becomes the 'items' arg (one row per line, fields separated by |).
 *
 * @param array|string $atts    Attributes.
 * @param string|null  $content Enclosed content.
 */
function lihan_section_shortcode( $atts, $content = null ): string {
	$atts = is_array( $atts ) ? $atts : array();
	$name = isset( $atts['name'] ) ? sanitize_key( (string) $atts['name'] ) : '';
	unset( $atts['name'] );

	if ( ! lihan_is_home_section( $name ) ) {
		return '';
	}

	$args = array();
	foreach ( $atts as $key => $value ) {
		if ( is_int( $key ) ) {
			continue;
		}
		$key = sanitize_key( (string) $key );
		if ( in_array( strtolower( (string) $value ), array( 'true', 'yes' ), true ) ) {
			$value = true;
		} elseif ( in_array( strtolower( (string) $value ), array( 'false', 'no' ), true ) ) {
			$value = false;
		}
		$args[ $key ] = $value;
	}

	if ( is_string( $content ) && '' !== trim( $content ) && ! isset( $args['items'] ) ) {
		$content       = preg_replace( '#<br\s*/?>|</p>\s*<p>#i', "\n", $content );
		$args['items'] = trim( wp_strip_all_tags( (string) $content ) );
	}

	$args['context'] = 'shortcode';

	return lihan_get_section( $name, $args );
}
add_shortcode( 'lihan_section', 'lihan_section_shortcode' );

/* -------------------------------------------------------------------------
 * FAQ structured data
 * ---------------------------------------------------------------------- */

/**
 * Print FAQPage JSON-LD once per request.
 *
 * @param array<int,array{question:string,answer:string}> $items FAQ items.
 */
function lihan_home_faq_schema( array $items ): void {
	static $printed = false;
	if ( $printed || ! $items ) {
		return;
	}
	$printed = true;

	$entities = array();
	foreach ( $items as $item ) {
		$q = trim( wp_strip_all_tags( lihan_home_expand_tokens( $item['question'] ) ) );
		$a = trim( wp_strip_all_tags( lihan_home_expand_tokens( $item['answer'] ) ) );
		if ( '' === $q || '' === $a ) {
			continue;
		}
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $q,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $a,
			),
		);
	}
	if ( ! $entities ) {
		return;
	}

	$json = wp_json_encode(
		array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
	);
	if ( $json ) {
		wp_print_inline_script_tag( $json, array( 'type' => 'application/ld+json' ) );
	}
}

/* -------------------------------------------------------------------------
 * Customizer
 * ---------------------------------------------------------------------- */

/**
 * Sanitize a checkbox.
 *
 * @param mixed $value Value.
 */
function lihan_home_sanitize_bool( $value ): bool {
	return lihan_home_bool( $value );
}

/**
 * Sanitize an image setting: attachment ID, or a URL for images set by code.
 *
 * @param mixed $value Value.
 * @return int|string
 */
function lihan_home_sanitize_image( $value ) {
	if ( is_numeric( $value ) ) {
		return absint( $value );
	}
	return is_string( $value ) ? esc_url_raw( $value ) : '';
}

/**
 * Sanitize a choice against the control's choices.
 *
 * @param mixed                $value   Value.
 * @param WP_Customize_Setting $setting Setting.
 */
function lihan_home_sanitize_choice( $value, $setting ) {
	$value   = is_string( $value ) ? $value : '';
	$control = $setting->manager->get_control( $setting->id );
	$choices = $control ? (array) $control->choices : array();
	return array_key_exists( $value, $choices ) ? $value : $setting->default;
}

/**
 * Sanitize a URL or a relative path.
 *
 * @param mixed $value Value.
 */
function lihan_home_sanitize_url( $value ): string {
	$value = is_string( $value ) ? trim( $value ) : '';
	if ( '' === $value ) {
		return '';
	}
	if ( str_starts_with( $value, '/' ) || str_starts_with( $value, '#' ) ) {
		return sanitize_text_field( $value );
	}
	return esc_url_raw( $value );
}

/**
 * Sanitize multi-line plain text (lists).
 *
 * @param mixed $value Value.
 */
function lihan_home_sanitize_lines( $value ): string {
	return is_string( $value ) ? sanitize_textarea_field( $value ) : '';
}

/**
 * Sanitize rich text.
 *
 * @param mixed $value Value.
 */
function lihan_home_sanitize_html( $value ): string {
	return is_string( $value ) ? wp_kses_post( $value ) : '';
}

/**
 * Sanitize a small integer.
 *
 * @param mixed $value Value.
 */
function lihan_home_sanitize_int( $value ): int {
	return absint( $value );
}

/**
 * Register the Homepage customizer sections.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function lihan_home_customize_register( $wp_customize ): void {
	if ( ! $wp_customize->get_panel( 'lihan_theme' ) ) {
		$wp_customize->add_panel(
			'lihan_theme',
			array(
				'title'    => __( 'Lihan Theme', 'lihan' ),
				'priority' => 30,
			)
		);
	}

	$defaults = lihan_home_defaults();
	$priority = 200;

	$add_section = static function ( string $id, string $title, string $description = '' ) use ( $wp_customize, &$priority ) {
		$wp_customize->add_section(
			$id,
			array(
				'title'           => $title,
				'description'     => $description,
				'panel'           => 'lihan_theme',
				'priority'        => $priority++,
				'active_callback' => 'is_front_page',
			)
		);
	};

	$add = static function ( string $section, string $key, string $label, string $type = 'text', array $extra = array() ) use ( $wp_customize, $defaults ) {
		$sanitize = array(
			'text'     => 'sanitize_text_field',
			'textarea' => 'lihan_home_sanitize_lines',
			'html'     => 'lihan_home_sanitize_html',
			'checkbox' => 'lihan_home_sanitize_bool',
			'url'      => 'lihan_home_sanitize_url',
			'select'   => 'lihan_home_sanitize_choice',
			'radio'    => 'lihan_home_sanitize_choice',
			'number'   => 'lihan_home_sanitize_int',
			'image'    => 'lihan_home_sanitize_image',
		);

		$wp_customize->add_setting(
			$key,
			array(
				'default'           => $defaults[ $key ] ?? '',
				'transport'         => 'refresh',
				'sanitize_callback' => $sanitize[ $type ] ?? 'sanitize_text_field',
			)
		);

		$args = array_merge(
			array(
				'label'   => $label,
				'section' => $section,
			),
			$extra
		);

		if ( 'image' === $type ) {
			$args['mime_type'] = 'image';
			$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, $key, $args ) );
			return;
		}

		$args['type'] = 'html' === $type ? 'textarea' : $type;
		$wp_customize->add_control( $key, $args );
	};

	$token_help = __( 'Links: {{shop|text}}, {{cat:category-slug|text}} or {{page:page-slug|text}}.', 'lihan' );

	// Section toggles.
	$add_section( 'lihan_home', __( 'Homepage', 'lihan' ), __( 'Choose which sections appear on the static front page. If the front page is built with Elementor, its own layout is shown instead.', 'lihan' ) );
	foreach ( lihan_home_section_registry() as $name => $label ) {
		/* translators: %s: section name. */
		$add( 'lihan_home', 'home_' . $name . '_enable', sprintf( __( 'Show: %s', 'lihan' ), $label ), 'checkbox' );
	}

	// Hero.
	$add_section( 'lihan_home_hero', __( 'Homepage: Hero slider', 'lihan' ), __( 'Up to 4 slides. A slide shows when it has an image or a heading. Recommended image size 1920 × 1000 (mobile 1080 × 1350).', 'lihan' ) );
	$add( 'lihan_home_hero', 'home_hero_autoplay', __( 'Autoplay interval (seconds, 0 = off)', 'lihan' ), 'number', array( 'input_attrs' => array( 'min' => 0, 'max' => 30, 'step' => 1 ) ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing
	for ( $n = 1; $n <= 4; $n++ ) {
		/* translators: %d: slide number. */
		$p = sprintf( __( 'Slide %d', 'lihan' ), $n ) . ': ';
		$add( 'lihan_home_hero', "home_hero_{$n}_image", $p . __( 'Image', 'lihan' ), 'image' );
		$add( 'lihan_home_hero', "home_hero_{$n}_image_mobile", $p . __( 'Mobile image (optional, 4:5)', 'lihan' ), 'image' );
		$add( 'lihan_home_hero', "home_hero_{$n}_heading", $p . __( 'Heading', 'lihan' ) );
		$add( 'lihan_home_hero', "home_hero_{$n}_text", $p . __( 'Text', 'lihan' ) );
		$add( 'lihan_home_hero', "home_hero_{$n}_button", $p . __( 'Button label', 'lihan' ) );
		$add( 'lihan_home_hero', "home_hero_{$n}_url", $p . __( 'Button link (empty = shop)', 'lihan' ), 'url' );
		$add(
			'lihan_home_hero',
			"home_hero_{$n}_color",
			$p . __( 'Text colour', 'lihan' ),
			'select',
			array(
				'choices' => array(
					'dark'  => __( 'Dark text (for light images)', 'lihan' ),
					'light' => __( 'Light text (for dark images)', 'lihan' ),
				),
			)
		);
	}

	// USPs.
	$add_section( 'lihan_home_usps', __( 'Homepage: USP bar', 'lihan' ) );
	$add( 'lihan_home_usps', 'home_usps_heading', __( 'Heading (mobile only)', 'lihan' ) );
	$add(
		'lihan_home_usps',
		'home_usps_items',
		__( 'Items', 'lihan' ),
		'textarea',
		array( 'description' => __( 'One per line: icon|Label. Icons: fabric, design, hand-heart, cod, truck, refresh, shield, star, gift, reviews, check, phone, clock.', 'lihan' ) )
	);

	// Collection intro.
	$add_section( 'lihan_home_intro', __( 'Homepage: Intro & categories', 'lihan' ) );
	$add( 'lihan_home_intro', 'home_intro_heading', __( 'Heading (the page H1)', 'lihan' ) );
	$add( 'lihan_home_intro', 'home_intro_text', __( 'Text', 'lihan' ), 'html', array( 'description' => $token_help ) );
	$add( 'lihan_home_intro', 'home_categories', __( 'Category slugs', 'lihan' ), 'text', array( 'description' => __( 'Comma separated, e.g. abaya-set, hijab, niqab. Empty = top-level categories (max 6).', 'lihan' ) ) );

	// Inspiration.
	$add_section( 'lihan_home_inspiration', __( 'Homepage: Styling inspiration', 'lihan' ), __( 'Shows products with the tag below (else featured, else latest). Add a video URL in the product meta field _lihan_reel_video to show a video.', 'lihan' ) );
	$add( 'lihan_home_inspiration', 'home_inspiration_heading', __( 'Heading', 'lihan' ) );
	$add( 'lihan_home_inspiration', 'home_inspiration_text', __( 'Text', 'lihan' ), 'textarea' );
	$add( 'lihan_home_inspiration', 'home_inspiration_button', __( 'Facebook button label', 'lihan' ) );
	$add( 'lihan_home_inspiration', 'home_inspiration_tag', __( 'Product tag slug', 'lihan' ) );
	$add( 'lihan_home_inspiration', 'home_inspiration_count', __( 'Number of products', 'lihan' ), 'number', array( 'input_attrs' => array( 'min' => 3, 'max' => 16 ) ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing

	// Trending.
	$add_section( 'lihan_home_trending', __( 'Homepage: Trending products', 'lihan' ) );
	$add( 'lihan_home_trending', 'home_trending_heading', __( 'Heading', 'lihan' ) );
	$add( 'lihan_home_trending', 'home_trending_source', __( 'Products', 'lihan' ), 'select', array( 'choices' => lihan_home_trending_sources() ) );
	$add( 'lihan_home_trending', 'home_trending_count', __( 'Number of products', 'lihan' ), 'number', array( 'input_attrs' => array( 'min' => 4, 'max' => 24 ) ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing

	// Reviews.
	$add_section( 'lihan_home_reviews', __( 'Homepage: Reviews', 'lihan' ) );
	$add(
		'lihan_home_reviews',
		'home_reviews_source',
		__( 'Source', 'lihan' ),
		'radio',
		array(
			'choices' => array(
				'auto'   => __( 'Product reviews (4★ and up), else the list below', 'lihan' ),
				'manual' => __( 'Only the list below', 'lihan' ),
			),
		)
	);
	$add( 'lihan_home_reviews', 'home_reviews', __( 'Reviews', 'lihan' ), 'textarea', array( 'description' => __( 'One per line: Title|Text|Name', 'lihan' ) ) );

	// Promo.
	$add_section( 'lihan_home_promo', __( 'Homepage: Promo banner', 'lihan' ) );
	$add( 'lihan_home_promo', 'home_promo_logo', __( 'Show the white logo above the heading', 'lihan' ), 'checkbox' );
	$add( 'lihan_home_promo', 'home_promo_heading', __( 'Heading', 'lihan' ) );
	$add( 'lihan_home_promo', 'home_promo_text', __( 'Text', 'lihan' ), 'textarea' );
	$add( 'lihan_home_promo', 'home_promo_button', __( 'Button label', 'lihan' ) );
	$add( 'lihan_home_promo', 'home_promo_url', __( 'Button link (empty = WhatsApp chat)', 'lihan' ), 'url' );
	$add( 'lihan_home_promo', 'home_promo_image', __( 'Image', 'lihan' ), 'image' );

	// Image + text.
	$add_section( 'lihan_home_about', __( 'Homepage: Image with text', 'lihan' ) );
	$add( 'lihan_home_about', 'home_about_image', __( 'Image', 'lihan' ), 'image' );
	$add( 'lihan_home_about', 'home_about_heading', __( 'Heading (optional)', 'lihan' ) );
	$add( 'lihan_home_about', 'home_about_text', __( 'Text', 'lihan' ), 'html', array( 'description' => $token_help ) );
	$add( 'lihan_home_about', 'home_about_button', __( 'Button label', 'lihan' ) );
	$add( 'lihan_home_about', 'home_about_url', __( 'Button link (empty = contact page)', 'lihan' ), 'url' );

	// SEO text.
	$add_section( 'lihan_home_seo', __( 'Homepage: Read-more text', 'lihan' ) );
	$add( 'lihan_home_seo', 'home_seo_heading', __( 'Heading', 'lihan' ) );
	$add( 'lihan_home_seo', 'home_seo_text', __( 'Text', 'lihan' ), 'html', array( 'description' => $token_help ) );

	// Rich text.
	$add_section( 'lihan_home_rich', __( 'Homepage: Rich text', 'lihan' ) );
	$add( 'lihan_home_rich', 'home_rich_heading', __( 'Heading', 'lihan' ) );
	$add( 'lihan_home_rich', 'home_rich_text', __( 'Text (HTML allowed)', 'lihan' ), 'html', array( 'description' => $token_help ) );

	// FAQ.
	$add_section( 'lihan_home_faq', __( 'Homepage: FAQ', 'lihan' ) );
	$add( 'lihan_home_faq', 'home_faq_heading', __( 'Heading', 'lihan' ) );
	$add( 'lihan_home_faq', 'home_faq_items', __( 'Questions', 'lihan' ), 'textarea', array( 'description' => __( 'One per line: Question|Answer', 'lihan' ) ) );
	$add( 'lihan_home_faq', 'home_faq_schema', __( 'Add FAQ structured data for search engines', 'lihan' ), 'checkbox' );
}
add_action( 'customize_register', 'lihan_home_customize_register', 20 );
