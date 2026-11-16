<?php
/**
 * Homepage section: trending products carousel (WooCommerce loop).
 *
 * Cards come from wc_get_template_part( 'content', 'product' ), so the
 * theme's shop card styles apply. Requires WooCommerce.
 *
 * $args (all optional; missing keys fall back to the customizer):
 *   'heading'   string        heading; false hides it
 *   'source'    string        featured | best_selling | on_sale | latest | category:<slug> | tag:<slug>
 *                             (default best_selling; an empty result falls back to latest)
 *   'products'  array|string  product IDs (array or comma list); overrides 'source'
 *   'count'     int           number of products, 4–24 (default 12)
 *   'per_view'  int           cards per view on desktop (default 4; tablet 3, mobile 2)
 *   'button'    string        optional "view all" button label below the carousel (default none)
 *   'button_url' string       button link (default: shop page)
 *   'id'        string        section HTML id
 *   'class'     string        extra classes
 *
 * @package Lihan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_template_part' ) ) {
	return;
}

$args = lihan_home_args(
	is_array( $args ?? null ) ? $args : array(),
	array(
		'heading'    => (string) lihan_home_opt( 'home_trending_heading' ),
		'source'     => (string) lihan_home_opt( 'home_trending_source' ),
		'products'   => array(),
		'count'      => absint( lihan_home_opt( 'home_trending_count' ) ),
		'per_view'   => 4,
		'button'     => '',
		'button_url' => '',
	)
);

$lihan_count = absint( $args['count'] );
$lihan_count = $lihan_count ? max( 4, min( 24, $lihan_count ) ) : 12;

$lihan_ids = $args['products'];
if ( is_string( $lihan_ids ) ) {
	$lihan_ids = explode( ',', $lihan_ids );
}
$lihan_ids = array_slice( array_values( array_filter( array_map( 'absint', is_array( $lihan_ids ) ? $lihan_ids : array() ) ) ), 0, $lihan_count );

// Keep only published, visible products (a stale ID would otherwise repeat the previous card).
$lihan_ids = array_values(
	array_filter(
		$lihan_ids,
		static function ( $id ) {
			$product = 'product' === get_post_type( $id ) ? wc_get_product( $id ) : null;
			return $product && $product->is_visible();
		}
	)
);

if ( ! $lihan_ids ) {
	$lihan_source = sanitize_text_field( (string) $args['source'] );
	$lihan_ids    = lihan_home_product_ids( '' !== $lihan_source ? $lihan_source : 'best_selling', $lihan_count );
	if ( ! $lihan_ids && 'latest' !== $lihan_source ) {
		$lihan_ids = lihan_home_product_ids( 'latest', $lihan_count );
	}
}

if ( ! $lihan_ids ) {
	return;
}

$lihan_per_view = max( 2, min( 6, absint( $args['per_view'] ) ? absint( $args['per_view'] ) : 4 ) );
$lihan_heading  = trim( (string) $args['heading'] );
$lihan_title_id = wp_unique_id( 'lh-trending-title-' );
$lihan_total    = count( $lihan_ids );
$lihan_pages    = (int) ceil( $lihan_total / $lihan_per_view );

_prime_post_caches( $lihan_ids );

// Loop props so content-product.php behaves like a shortcode loop.
wc_setup_loop(
	array(
		'name'         => 'lihan_trending',
		'columns'      => $lihan_total, // One row: no "first"/"last" clearing classes mid-carousel.
		'is_shortcode' => true,
		'is_search'    => false,
		'is_paginated' => false,
		'total'        => $lihan_total,
		'total_pages'  => 1,
		'per_page'     => $lihan_total,
		'current_page' => 1,
	)
);

$lihan_original_post = $GLOBALS['post'] ?? null;
?>
<section<?php echo lihan_home_section_attrs( 'featured_products', $args, array( 'lh-section', 'lh-trending' ), $lihan_heading ? $lihan_title_id : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="lh-container">
		<?php if ( $lihan_heading ) : ?>
			<h2 class="lh-h2 lh-trending__title" id="<?php echo esc_attr( $lihan_title_id ); ?>"><?php echo esc_html( $lihan_heading ); ?></h2>
		<?php endif; ?>

		<div class="lh-carousel lh-products lh-trending__carousel woocommerce" data-lh-carousel data-per-view="<?php echo (int) $lihan_per_view; ?>" data-per-view-tablet="<?php echo (int) min( 3, $lihan_per_view ); ?>" data-per-view-mobile="2" data-gap="30">
			<div class="lh-carousel__viewport">
				<ul class="products lh-carousel__track">
					<?php
					// Cards are carousel slides from the first paint (before the carousel JS runs).
					$lihan_slide_class = static function ( $classes ) {
						$classes   = is_array( $classes ) ? $classes : array();
						$classes[] = 'lh-carousel__slide';
						return $classes;
					};
					add_filter( 'woocommerce_post_class', $lihan_slide_class );
					foreach ( $lihan_ids as $lihan_id ) {
						$GLOBALS['post'] = get_post( $lihan_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
						setup_postdata( $GLOBALS['post'] );
						wc_get_template_part( 'content', 'product' );
					}
					remove_filter( 'woocommerce_post_class', $lihan_slide_class );
					?>
				</ul>
			</div>
			<?php
			if ( $lihan_pages > 1 ) {
				/* translators: 1: current page, 2: total pages. */
				echo lihan_home_carousel_nav( sprintf( __( '%1$d / %2$d', 'lihan' ), 1, $lihan_pages ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
			}
			?>
		</div>

		<?php if ( '' !== trim( (string) $args['button'] ) ) : ?>
			<div class="lh-trending__cta">
				<a class="lh-btn lh-btn--outline" href="<?php echo esc_url( '' !== trim( (string) $args['button_url'] ) ? (string) $args['button_url'] : lihan_home_shop_url() ); ?>"><?php echo esc_html( $args['button'] ); ?></a>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php
$GLOBALS['post'] = $lihan_original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
wp_reset_postdata();
wc_reset_loop();
