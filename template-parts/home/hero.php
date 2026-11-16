<?php
/**
 * Homepage section: hero fade slider.
 *
 * $args (all optional; missing keys fall back to the customizer):
 *   'slides'   array  Up to 4 slides, each an array with:
 *                       'image'        int|string  attachment ID or image URL
 *                       'image_mobile' int|string  attachment ID or image URL (4:5)
 *                       'heading'      string
 *                       'text'         string
 *                       'button'       string      button label (empty = no button)
 *                       'url'          string      button link (empty = shop)
 *                       'color'        string      'dark' | 'light' text
 *   'autoplay' int    seconds between slides, 0 = off (default 6)
 *   'id'       string section HTML id
 *   'class'    string extra classes
 *
 * @package Lihan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args = is_array( $args ?? null ) ? $args : array();

$lihan_autoplay = isset( $args['autoplay'] ) && '' !== $args['autoplay'] ? absint( $args['autoplay'] ) : absint( lihan_home_opt( 'home_hero_autoplay' ) );
if ( $lihan_autoplay > 0 && $lihan_autoplay < 100 ) {
	$lihan_autoplay *= 1000; // Seconds to ms (values of 100+ are taken as ms already).
}

// Fallback links for the default slides.
$lihan_fallback_urls = array(
	1 => lihan_home_shop_url(),
	2 => lihan_home_category_url( 'hijab' ),
);

if ( ! empty( $args['slides'] ) && is_array( $args['slides'] ) ) {
	$lihan_raw = array_values( array_filter( $args['slides'], 'is_array' ) );
	$lihan_use_demo = false;
} else {
	$lihan_raw = array();
	for ( $lihan_n = 1; $lihan_n <= 4; $lihan_n++ ) {
		$lihan_raw[] = array(
			'image'        => lihan_home_opt( "home_hero_{$lihan_n}_image" ),
			'image_mobile' => lihan_home_opt( "home_hero_{$lihan_n}_image_mobile" ),
			'heading'      => lihan_home_opt( "home_hero_{$lihan_n}_heading" ),
			'text'         => lihan_home_opt( "home_hero_{$lihan_n}_text" ),
			'button'       => lihan_home_opt( "home_hero_{$lihan_n}_button" ),
			'url'          => lihan_home_opt( "home_hero_{$lihan_n}_url" ),
			'color'        => lihan_home_opt( "home_hero_{$lihan_n}_color" ),
		);
	}
	$lihan_use_demo = true;
}

$lihan_slides = array();
foreach ( array_slice( $lihan_raw, 0, 4 ) as $lihan_i => $lihan_s ) {
	$lihan_n       = $lihan_i + 1;
	$lihan_heading = trim( (string) ( $lihan_s['heading'] ?? '' ) );
	$lihan_image   = lihan_home_resolve_image( $lihan_s['image'] ?? '', $lihan_use_demo ? "hero-{$lihan_n}.jpg" : '' );

	if ( '' === $lihan_heading && ! $lihan_image ) {
		continue;
	}

	$lihan_url = trim( (string) ( $lihan_s['url'] ?? '' ) );
	if ( '' === $lihan_url ) {
		$lihan_url = ! empty( $lihan_fallback_urls[ $lihan_n ] ) ? $lihan_fallback_urls[ $lihan_n ] : lihan_home_shop_url();
	}

	$lihan_slides[] = array(
		'image'        => $lihan_image,
		'image_mobile' => lihan_home_resolve_image( $lihan_s['image_mobile'] ?? '', ( $lihan_use_demo && ! lihan_home_resolve_image( $lihan_s['image'] ?? '' ) ) ? "hero-{$lihan_n}-mobile.jpg" : '' ),
		'heading'      => $lihan_heading,
		'text'         => trim( (string) ( $lihan_s['text'] ?? '' ) ),
		'button'       => trim( (string) ( $lihan_s['button'] ?? '' ) ),
		'url'          => $lihan_url,
		'color'        => 'light' === ( $lihan_s['color'] ?? '' ) ? 'light' : 'dark',
	);
}

if ( ! $lihan_slides ) {
	return;
}

$lihan_count    = count( $lihan_slides );
$lihan_multiple = $lihan_count > 1;
?>
<section<?php echo lihan_home_section_attrs( 'hero', $args, array( 'lh-hero', $lihan_multiple ? 'lh-hero--slider' : 'lh-hero--single' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?><?php echo $lihan_multiple ? ' aria-roledescription="' . esc_attr__( 'carousel', 'lihan' ) . '"' : ''; ?> aria-label="<?php esc_attr_e( 'Featured collections', 'lihan' ); ?>">
	<div class="lh-carousel lh-hero__carousel"<?php echo $lihan_multiple ? ' data-lh-carousel data-lh-carousel-fade data-per-view="1" data-per-view-tablet="1" data-per-view-mobile="1" data-gap="0" data-loop="1" data-autoplay="' . (int) $lihan_autoplay . '"' : ''; ?>>
		<div class="lh-carousel__viewport">
			<div class="lh-carousel__track">
				<?php
				foreach ( $lihan_slides as $lihan_i => $lihan_slide ) :
					$lihan_first = 0 === $lihan_i;
					$lihan_attr  = array(
						'class'   => 'lh-hero__img',
						'sizes'   => '100vw',
						'loading' => $lihan_first ? 'eager' : 'lazy',
					);
					if ( $lihan_first ) {
						$lihan_attr['fetchpriority'] = 'high';
					}
					$lihan_img = lihan_home_image_html( $lihan_slide['image'], 'lihan-hero', $lihan_attr );

					// Mobile art direction.
					$lihan_source = '';
					if ( $lihan_slide['image_mobile'] ) {
						$lihan_m = $lihan_slide['image_mobile'];
						if ( $lihan_m['id'] ) {
							// 'full' keeps the uploaded 4:5 ratio; the srcset only lists sizes with that ratio.
							$lihan_srcset = wp_get_attachment_image_srcset( $lihan_m['id'], 'full' );
							if ( ! $lihan_srcset ) {
								$lihan_srcset = (string) wp_get_attachment_image_url( $lihan_m['id'], 'large' );
							}
						} else {
							$lihan_srcset = $lihan_m['url'];
						}
						if ( $lihan_srcset ) {
							$lihan_source = '<source media="(max-width: 767px)" srcset="' . esc_attr( $lihan_srcset ) . '" sizes="100vw">';
						}
					}
					?>
					<div class="lh-carousel__slide lh-hero__slide lh-hero__slide--<?php echo esc_attr( $lihan_slide['color'] ); ?><?php echo $lihan_first ? ' is-active' : ''; ?>"<?php echo $lihan_multiple ? ' role="group" aria-roledescription="' . esc_attr__( 'slide', 'lihan' ) . '" aria-label="' . esc_attr( sprintf( /* translators: 1: slide number, 2: total slides. */ __( '%1$d of %2$d', 'lihan' ), $lihan_i + 1, $lihan_count ) ) . '"' : ''; ?>>
						<div class="lh-hero__media">
							<?php
							if ( $lihan_img ) {
								echo '<picture>' . $lihan_source . $lihan_img . '</picture>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts / core image functions.
							} else {
								echo lihan_home_placeholder( 'light' === $lihan_slide['color'] ? 'deep' : 'warm', 'lh-hero__ph' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</div>
						<?php if ( $lihan_slide['heading'] || $lihan_slide['text'] || $lihan_slide['button'] ) : ?>
							<div class="lh-hero__content">
								<div class="lh-container">
									<div class="lh-hero__text">
										<?php if ( $lihan_slide['heading'] ) : ?>
											<p class="lh-hero__heading"><?php echo esc_html( $lihan_slide['heading'] ); ?></p>
										<?php endif; ?>
										<?php if ( $lihan_slide['text'] ) : ?>
											<p class="lh-hero__sub"><?php echo esc_html( $lihan_slide['text'] ); ?></p>
										<?php endif; ?>
										<?php if ( $lihan_slide['button'] ) : ?>
											<a class="lh-btn <?php echo 'light' === $lihan_slide['color'] ? 'lh-btn--accent' : 'lh-btn--primary'; ?> lh-hero__btn" href="<?php echo esc_url( $lihan_slide['url'] ); ?>"><?php echo esc_html( $lihan_slide['button'] ); ?></a>
										<?php endif; ?>
									</div>
								</div>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php if ( $lihan_multiple ) : ?>
			<button type="button" class="lh-carousel__prev lh-carousel__arrow--side lh-hero__arrow lh-hero__arrow--prev" aria-label="<?php esc_attr_e( 'Previous slide', 'lihan' ); ?>"><?php echo lihan_home_icon( 'chevron-left', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			<button type="button" class="lh-carousel__next lh-carousel__arrow--side lh-hero__arrow lh-hero__arrow--next" aria-label="<?php esc_attr_e( 'Next slide', 'lihan' ); ?>"><?php echo lihan_home_icon( 'chevron-right', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			<div class="lh-carousel__dots lh-hero__dots"></div>
		<?php endif; ?>
	</div>
</section>
