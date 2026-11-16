<?php
/**
 * Homepage section: image with text (mission / about).
 *
 * $args (all optional; missing keys fall back to the customizer):
 *   'image'          int|string  attachment ID or image URL (default: theme mod, then demo/images/about.jpg)
 *   'heading'        string      optional heading; false hides it
 *   'text'           string      rich text (HTML allowed, {{cat:slug|label}} tokens); false hides it
 *   'button'         string      button label; false hides the button
 *   'url'            string      button link (default: contact page, else Messenger)
 *   'image_position' string      'left' (default) | 'right'
 *   'id'             string      section HTML id
 *   'class'          string      extra classes
 *
 * @package Lihan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lihan_passed = is_array( $args ?? null ) ? $args : array();

$args = lihan_home_args(
	$lihan_passed,
	array(
		'image'          => lihan_home_opt( 'home_about_image' ),
		'heading'        => (string) lihan_home_opt( 'home_about_heading' ),
		'text'           => (string) lihan_home_opt( 'home_about_text' ),
		'button'         => (string) lihan_home_opt( 'home_about_button' ),
		'url'            => (string) lihan_home_opt( 'home_about_url' ),
		'image_position' => 'left',
	)
);

$lihan_heading = trim( (string) $args['heading'] );
$lihan_text    = lihan_home_rich_text( (string) $args['text'] );
$lihan_button  = trim( (string) $args['button'] );

if ( '' === $lihan_heading && '' === $lihan_text ) {
	return;
}

$lihan_url = trim( (string) $args['url'] );
$lihan_url = '' !== $lihan_url ? $lihan_url : lihan_home_contact_url();

$lihan_image = lihan_home_resolve_image( $args['image'], empty( $lihan_passed['image'] ) ? 'about.jpg' : '' );
$lihan_img   = lihan_home_image_html(
	$lihan_image,
	has_image_size( 'lihan-portrait' ) ? 'lihan-portrait' : 'large',
	array(
		'class' => 'lh-about__img',
		'sizes' => '(max-width: 767px) 100vw, 533px',
	)
);

$lihan_title_id = wp_unique_id( 'lh-about-title-' );
$lihan_classes  = array( 'lh-section', 'lh-about' );
if ( 'right' === $args['image_position'] ) {
	$lihan_classes[] = 'lh-about--image-right';
}
?>
<section<?php echo lihan_home_section_attrs( 'image_text', $args, $lihan_classes, $lihan_heading ? $lihan_title_id : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="lh-container">
		<div class="lh-about__grid">
			<div class="lh-about__media">
				<?php
				if ( $lihan_img ) {
					echo $lihan_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup / escaped.
				} else {
					echo lihan_home_placeholder( 'warm', 'lh-about__ph' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</div>
			<div class="lh-about__body">
				<?php if ( $lihan_heading ) : ?>
					<h2 class="lh-h2 lh-about__title" id="<?php echo esc_attr( $lihan_title_id ); ?>"><?php echo esc_html( $lihan_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $lihan_text ) : ?>
					<div class="lh-rte lh-about__text"><?php echo $lihan_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses'd. ?></div>
				<?php endif; ?>
				<?php if ( $lihan_button ) : ?>
					<a class="lh-btn lh-btn--primary lh-about__btn" href="<?php echo esc_url( $lihan_url ); ?>"><?php echo esc_html( $lihan_button ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
