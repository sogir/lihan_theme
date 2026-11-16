<?php
/**
 * Homepage section: promo banner (dark text panel + image).
 *
 * The panel always carries a real text heading, so it never looks like an
 * empty dark box while the image loads.
 *
 * $args (all optional; missing keys fall back to the customizer):
 *   'heading'    string      heading; false hides it
 *   'text'       string      paragraph; false hides it
 *   'button'     string      button label; false hides the button
 *   'url'        string      button link (default: WhatsApp chat from contact_whatsapp)
 *   'image'      int|string  attachment ID or image URL (default: theme mod, then demo/images/promo.jpg)
 *   'show_logo'  bool        show the white logo above the heading
 *   'id'         string      section HTML id
 *   'class'      string      extra classes
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
		'heading'   => (string) lihan_home_opt( 'home_promo_heading' ),
		'text'      => (string) lihan_home_opt( 'home_promo_text' ),
		'button'    => (string) lihan_home_opt( 'home_promo_button' ),
		'url'       => (string) lihan_home_opt( 'home_promo_url' ),
		'image'     => lihan_home_opt( 'home_promo_image' ),
		'show_logo' => lihan_home_bool( lihan_home_opt( 'home_promo_logo' ) ),
	)
);

$lihan_heading = trim( (string) $args['heading'] );
$lihan_text    = trim( (string) $args['text'] );
$lihan_button  = trim( (string) $args['button'] );
$lihan_url     = trim( (string) $args['url'] );
$lihan_url     = '' !== $lihan_url ? $lihan_url : lihan_home_whatsapp_url();
$lihan_is_ext  = ! str_starts_with( $lihan_url, home_url() ) && preg_match( '#^https?://#i', $lihan_url );

// Use the demo image only when no image was passed explicitly.
$lihan_image = lihan_home_resolve_image( $args['image'], empty( $lihan_passed['image'] ) ? 'promo.jpg' : '' );
$lihan_img   = lihan_home_image_html(
	$lihan_image,
	has_image_size( 'lihan-portrait' ) ? 'lihan-portrait' : 'large',
	array(
		'class' => 'lh-promo__img',
		'sizes' => '(max-width: 767px) 100vw, 50vw',
	)
);

$lihan_logo = '';
if ( lihan_home_bool( $args['show_logo'] ) && is_readable( get_template_directory() . '/assets/images/logo-white.png' ) ) {
	$lihan_logo = '<img class="lh-promo__logo" src="' . esc_url( get_template_directory_uri() . '/assets/images/logo-white.png' ) . '" width="720" height="287" alt="" decoding="async">';
}

if ( '' === $lihan_heading && '' === $lihan_text && '' === $lihan_logo ) {
	return;
}

$lihan_title_id = wp_unique_id( 'lh-promo-title-' );
?>
<section<?php echo lihan_home_section_attrs( 'promo_banner', $args, array( 'lh-section', 'lh-section--alt', 'lh-promo', $lihan_img ? 'lh-promo--has-image' : 'lh-promo--no-image' ), $lihan_heading ? $lihan_title_id : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="lh-container">
		<div class="lh-promo__grid">
			<div class="lh-promo__panel">
				<?php echo $lihan_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
				<?php if ( $lihan_heading ) : ?>
					<h2 class="lh-promo__title" id="<?php echo esc_attr( $lihan_title_id ); ?>"><?php echo esc_html( $lihan_heading ); ?></h2>
				<?php endif; ?>
				<span class="lh-promo__rule" aria-hidden="true"></span>
				<?php if ( $lihan_text ) : ?>
					<p class="lh-promo__text"><?php echo esc_html( $lihan_text ); ?></p>
				<?php endif; ?>
				<?php if ( $lihan_button ) : ?>
					<a class="lh-btn lh-btn--accent lh-promo__btn" href="<?php echo esc_url( $lihan_url ); ?>"<?php echo $lihan_is_ext ? ' target="_blank" rel="noopener"' : ''; ?>>
						<?php
						if ( str_contains( $lihan_url, 'wa.me' ) || str_contains( $lihan_url, 'whatsapp' ) ) {
							echo lihan_home_icon( 'whatsapp', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
						<span><?php echo esc_html( $lihan_button ); ?></span>
						<?php if ( $lihan_is_ext ) : ?>
							<span class="lh-visually-hidden"><?php esc_html_e( '(opens in a new tab)', 'lihan' ); ?></span>
						<?php endif; ?>
					</a>
				<?php endif; ?>
			</div>
			<?php if ( $lihan_img ) : ?>
				<div class="lh-promo__media"><?php echo $lihan_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup / escaped. ?></div>
			<?php endif; ?>
		</div>
	</div>
</section>
