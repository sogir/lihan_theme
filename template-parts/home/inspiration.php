<?php
/**
 * Homepage section: styling inspiration reels.
 *
 * Portrait 9:16 cards with the product image (or a muted looping video when
 * the product has a direct .mp4/.webm URL in meta _lihan_reel_video) and a
 * mini product card linking to the product. Requires WooCommerce.
 *
 * $args (all optional; missing keys fall back to the customizer):
 *   'heading'      string        heading; false hides it
 *   'text'         string        one-line intro; false hides it
 *   'products'     array|string  product IDs (array or comma list); overrides the source
 *   'tag'          string        product_tag slug (default 'inspiration'); falls back to
 *                                featured products, then the latest products
 *   'count'        int           number of cards (default 8)
 *   'button'       string        Facebook button label; false hides the button
 *   'button_url'   string        button link (default: the social_facebook option)
 *   'id'           string        section HTML id
 *   'class'        string        extra classes
 *
 * @package Lihan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
	return;
}

$args = lihan_home_args(
	is_array( $args ?? null ) ? $args : array(),
	array(
		'heading'    => (string) lihan_home_opt( 'home_inspiration_heading' ),
		'text'       => (string) lihan_home_opt( 'home_inspiration_text' ),
		'products'   => array(),
		'tag'        => (string) lihan_home_opt( 'home_inspiration_tag' ),
		'count'      => absint( lihan_home_opt( 'home_inspiration_count' ) ),
		'button'     => (string) lihan_home_opt( 'home_inspiration_button' ),
		'button_url' => (string) lihan_home_opt( 'social_facebook' ),
	)
);

$lihan_count = max( 3, min( 16, absint( $args['count'] ) ? absint( $args['count'] ) : 8 ) );

$lihan_ids = $args['products'];
if ( is_string( $lihan_ids ) ) {
	$lihan_ids = explode( ',', $lihan_ids );
}
$lihan_ids = array_values( array_filter( array_map( 'absint', is_array( $lihan_ids ) ? $lihan_ids : array() ) ) );

/**
 * Visible products for a list of IDs.
 *
 * @param int[] $ids   Product IDs.
 * @param int   $limit Max products.
 * @return WC_Product[]
 */
$lihan_load = static function ( array $ids, int $limit ): array {
	$found = array();
	foreach ( array_slice( $ids, 0, $limit ) as $id ) {
		$product = wc_get_product( $id );
		if ( $product && $product->is_visible() ) {
			$found[] = $product;
		}
	}
	return $found;
};

// Explicit products first, then the tag, featured and latest products.
$lihan_products = $lihan_ids ? $lihan_load( $lihan_ids, $lihan_count ) : array();
$lihan_sources  = array();
if ( '' !== trim( (string) $args['tag'] ) ) {
	$lihan_sources[] = 'tag:' . sanitize_title( (string) $args['tag'] );
}
$lihan_sources[] = 'featured';
$lihan_sources[] = 'latest';
foreach ( $lihan_sources as $lihan_source ) {
	if ( $lihan_products ) {
		break;
	}
	$lihan_products = $lihan_load( lihan_home_product_ids( $lihan_source, $lihan_count ), $lihan_count );
}

if ( ! $lihan_products ) {
	return;
}

$lihan_reel_size  = has_image_size( 'lihan-reel' ) ? 'lihan-reel' : ( has_image_size( 'lihan-portrait' ) ? 'lihan-portrait' : 'woocommerce_single' );
$lihan_heading    = trim( (string) $args['heading'] );
$lihan_text       = trim( (string) $args['text'] );
$lihan_title_id   = wp_unique_id( 'lh-inspiration-title-' );
$lihan_button_url = trim( (string) $args['button_url'] );
$lihan_total      = count( $lihan_products );
?>
<section<?php echo lihan_home_section_attrs( 'inspiration', $args, array( 'lh-section', 'lh-inspiration' ), $lihan_heading ? $lihan_title_id : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $lihan_heading || $lihan_text ) : ?>
		<div class="lh-container lh-inspiration__head">
			<?php if ( $lihan_heading ) : ?>
				<h2 class="lh-h2" id="<?php echo esc_attr( $lihan_title_id ); ?>"><?php echo esc_html( $lihan_heading ); ?></h2>
			<?php endif; ?>
			<?php if ( $lihan_text ) : ?>
				<p class="lh-inspiration__text"><?php echo esc_html( $lihan_text ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="lh-container">
		<div class="lh-carousel lh-reels" data-lh-carousel data-lh-reels data-per-view="5" data-per-view-tablet="3" data-per-view-mobile="2.2" data-gap="16">
			<div class="lh-carousel__viewport">
				<div class="lh-carousel__track">
					<?php
					foreach ( $lihan_products as $lihan_i => $lihan_product ) :
						$lihan_link     = $lihan_product->get_permalink();
						$lihan_image_id = absint( $lihan_product->get_image_id() );
						$lihan_name     = $lihan_product->get_name();
						$lihan_video    = trim( (string) $lihan_product->get_meta( '_lihan_reel_video', true ) );
						$lihan_video    = ( '' !== $lihan_video && preg_match( '/\.(mp4|webm|m4v|mov)(\?.*)?$/i', $lihan_video ) ) ? esc_url_raw( $lihan_video ) : '';
						$lihan_poster   = $lihan_image_id ? (string) wp_get_attachment_image_url( $lihan_image_id, $lihan_reel_size ) : '';
						?>
						<div class="lh-carousel__slide lh-reel<?php echo 2 === $lihan_i ? ' is-center' : ''; ?>">
							<div class="lh-reel__card">
								<a class="lh-reel__media" href="<?php echo esc_url( $lihan_link ); ?>" tabindex="-1" aria-hidden="true">
									<?php
									if ( $lihan_video ) {
										printf(
											'<video class="lh-reel__video" src="%1$s"%2$s muted loop playsinline preload="none" disablepictureinpicture></video><span class="lh-reel__play">%3$s</span>',
											esc_url( $lihan_video ),
											$lihan_poster ? ' poster="' . esc_url( $lihan_poster ) . '"' : '',
											lihan_home_icon( 'play', 14 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										);
									} elseif ( $lihan_image_id ) {
										echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											$lihan_image_id,
											$lihan_reel_size,
											false,
											array(
												'class'    => 'lh-reel__img',
												'alt'      => '',
												'loading'  => 'lazy',
												'decoding' => 'async',
												'sizes'    => '(max-width: 767px) 45vw, (max-width: 1023px) 33vw, 20vw',
											)
										);
									} else {
										echo lihan_home_placeholder( 'warm', 'lh-reel__ph' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
									?>
								</a>
								<a class="lh-reel__product" href="<?php echo esc_url( $lihan_link ); ?>">
									<span class="lh-reel__thumb">
										<?php
										if ( $lihan_image_id ) {
											echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												$lihan_image_id,
												'thumbnail',
												false,
												array(
													'alt'      => '',
													'loading'  => 'lazy',
													'decoding' => 'async',
												)
											);
										}
										?>
									</span>
									<span class="lh-reel__info">
										<span class="lh-reel__title"><?php echo esc_html( $lihan_name ); ?></span>
										<span class="lh-reel__price"><?php echo wp_kses_post( $lihan_product->get_price_html() ); ?></span>
									</span>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php
			if ( $lihan_total > 2 ) {
				echo lihan_home_carousel_nav(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
			}
			?>
		</div>

		<?php if ( '' !== trim( (string) $args['button'] ) && '' !== $lihan_button_url ) : ?>
			<div class="lh-inspiration__cta">
				<a class="lh-btn lh-btn--primary" href="<?php echo esc_url( $lihan_button_url ); ?>" target="_blank" rel="noopener">
					<?php echo lihan_home_icon( 'facebook', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( $args['button'] ); ?></span>
					<span class="lh-visually-hidden"><?php esc_html_e( '(opens in a new tab)', 'lihan' ); ?></span>
				</a>
			</div>
		<?php endif; ?>
	</div>
</section>
