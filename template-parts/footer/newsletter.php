<?php
/**
 * Footer newsletter band.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

$lihan_heading = (string) lihan_get_option( 'newsletter_heading' );
$lihan_text    = (string) lihan_get_option( 'newsletter_text' );

if ( ! apply_filters( 'lihan_show_footer_newsletter', true ) ) {
	return;
}
?>
<section id="lh-newsletter" class="lh-newsletter" aria-labelledby="lh-newsletter-heading">
	<div class="lh-container lh-newsletter__inner">
		<h2 id="lh-newsletter-heading" class="lh-h2 lh-newsletter__heading"><?php echo esc_html( $lihan_heading ); ?></h2>
		<p class="lh-newsletter__text"><?php echo esc_html( $lihan_text ); ?></p>
		<?php lihan_newsletter_form( [ 'id' => 'lh-newsletter-form' ] ); ?>
	</div>
</section>
