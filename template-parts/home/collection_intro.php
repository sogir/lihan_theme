<?php
/**
 * Homepage section: collection intro (the page's H1) + category tiles.
 *
 * $args (all optional; missing keys fall back to the customizer):
 *   'heading'       string        heading; false hides it
 *   'heading_tag'   string        'h1' (default on the front page) or 'h2'
 *   'text'          string        rich text (HTML allowed, {{cat:slug|label}} tokens); false hides it
 *   'categories'    array|string  product_cat slugs or term IDs (array or comma list);
 *                                 empty = top-level categories by menu order
 *   'limit'         int           max tiles when categories are automatic (default 6)
 *   'show_categories' bool        false hides the tiles (default true)
 *   'id'            string        section HTML id
 *   'class'         string        extra classes
 *
 * @package Lihan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args = lihan_home_args(
	is_array( $args ?? null ) ? $args : array(),
	array(
		'heading'         => (string) lihan_home_opt( 'home_intro_heading' ),
		'heading_tag'     => '',
		'text'            => (string) lihan_home_opt( 'home_intro_text' ),
		'categories'      => lihan_home_opt( 'home_categories' ),
		'limit'           => 6,
		'show_categories' => true,
	)
);

// The intro heading is the page H1 on the front page only, unless told otherwise.
$lihan_tag = in_array( $args['heading_tag'], array( 'h1', 'h2' ), true ) ? $args['heading_tag'] : ( is_front_page() && empty( $args['context'] ) ? 'h1' : 'h2' );

// Category tiles: only when WooCommerce categories exist.
$lihan_terms = array();
if ( lihan_home_bool( $args['show_categories'] ) && taxonomy_exists( 'product_cat' ) ) {
	$lihan_wanted = $args['categories'];
	if ( is_string( $lihan_wanted ) ) {
		$lihan_wanted = array_filter( array_map( 'trim', explode( ',', $lihan_wanted ) ) );
	}
	$lihan_wanted = is_array( $lihan_wanted ) ? $lihan_wanted : array();

	if ( $lihan_wanted ) {
		foreach ( $lihan_wanted as $lihan_ref ) {
			$lihan_term = is_numeric( $lihan_ref ) ? get_term( absint( $lihan_ref ), 'product_cat' ) : get_term_by( 'slug', sanitize_title( (string) $lihan_ref ), 'product_cat' );
			if ( $lihan_term instanceof WP_Term ) {
				$lihan_terms[ $lihan_term->term_id ] = $lihan_term;
			}
		}
	} else {
		$lihan_found = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => false,
				'orderby'    => 'menu_order',
				'order'      => 'ASC',
				'exclude'    => array( absint( get_option( 'default_product_cat', 0 ) ) ),
				'number'     => max( 1, min( 12, absint( $args['limit'] ) ) ),
			)
		);
		if ( is_array( $lihan_found ) ) {
			foreach ( $lihan_found as $lihan_term ) {
				$lihan_terms[ $lihan_term->term_id ] = $lihan_term;
			}
		}
	}
}

$lihan_heading = trim( (string) $args['heading'] );
$lihan_text    = lihan_home_rich_text( (string) $args['text'] );

if ( '' === $lihan_heading && '' === $lihan_text && ! $lihan_terms ) {
	return;
}

$lihan_title_id = wp_unique_id( 'lh-intro-title-' );
$lihan_size     = has_image_size( 'lihan-square' ) ? 'lihan-square' : 'woocommerce_thumbnail';
$lihan_cols     = count( $lihan_terms );
?>
<section<?php echo lihan_home_section_attrs( 'collection_intro', $args, array( 'lh-section', 'lh-intro' ), $lihan_heading ? $lihan_title_id : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $lihan_heading || $lihan_text ) : ?>
		<div class="lh-container lh-container--narrow lh-intro__head">
			<?php if ( $lihan_heading ) : ?>
				<<?php echo esc_html( $lihan_tag ); ?> class="lh-h2 lh-intro__title" id="<?php echo esc_attr( $lihan_title_id ); ?>"><?php echo esc_html( $lihan_heading ); ?></<?php echo esc_html( $lihan_tag ); ?>>
			<?php endif; ?>
			<?php if ( $lihan_text ) : ?>
				<div class="lh-rte lh-intro__text"><?php echo $lihan_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses'd in lihan_home_rich_text(). ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $lihan_terms ) : ?>
		<div class="lh-container">
			<ul class="lh-cat-grid lh-cat-grid--<?php echo (int) min( 6, $lihan_cols ); ?>" role="list">
				<?php
				foreach ( $lihan_terms as $lihan_term ) :
					$lihan_link = get_term_link( $lihan_term );
					if ( is_wp_error( $lihan_link ) ) {
						continue;
					}
					$lihan_thumb = absint( get_term_meta( $lihan_term->term_id, 'thumbnail_id', true ) );
					$lihan_img   = $lihan_thumb ? wp_get_attachment_image(
						$lihan_thumb,
						$lihan_size,
						false,
						array(
							'class'    => 'lh-cat-tile__img',
							'alt'      => '',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '(max-width: 767px) 50vw, 33vw',
						)
					) : '';
					?>
					<li class="lh-cat-tile">
						<a class="lh-cat-tile__link" href="<?php echo esc_url( $lihan_link ); ?>">
							<span class="lh-cat-tile__media">
								<?php
								if ( $lihan_img ) {
									echo $lihan_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
								} else {
									echo '<span class="lh-cat-tile__ph" aria-hidden="true">' . lihan_home_icon( 'grid', 28 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</span>
							<span class="lh-cat-tile__label"><?php echo esc_html( $lihan_term->name ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</section>
