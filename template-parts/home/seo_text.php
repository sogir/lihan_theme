<?php
/**
 * Homepage section: read-more text block (SEO copy), clamped to a few lines.
 *
 * $args (all optional; missing keys fall back to the customizer):
 *   'heading' string  heading (left-aligned); false hides it
 *   'text'    string  rich text (HTML allowed, {{cat:slug|label}} tokens)
 *   'lines'   int     visible lines before "Read More" (default 3)
 *   'id'      string  section HTML id
 *   'class'   string  extra classes
 *
 * @package Lihan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args = lihan_home_args(
	is_array( $args ?? null ) ? $args : array(),
	array(
		'heading' => (string) lihan_home_opt( 'home_seo_heading' ),
		'text'    => (string) lihan_home_opt( 'home_seo_text' ),
		'lines'   => 3,
	)
);

$lihan_heading = trim( (string) $args['heading'] );
$lihan_text    = lihan_home_rich_text( (string) $args['text'] );

if ( '' === $lihan_text ) {
	return;
}

$lihan_lines    = max( 1, min( 20, absint( $args['lines'] ) ? absint( $args['lines'] ) : 3 ) );
$lihan_title_id = wp_unique_id( 'lh-seo-title-' );
$lihan_body_id  = wp_unique_id( 'lh-seo-body-' );
?>
<section<?php echo lihan_home_section_attrs( 'seo_text', $args, array( 'lh-section', 'lh-section--alt', 'lh-seo' ), $lihan_heading ? $lihan_title_id : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="lh-container lh-container--narrow">
		<?php if ( $lihan_heading ) : ?>
			<h2 class="lh-h2 lh-h2--left lh-seo__title" id="<?php echo esc_attr( $lihan_title_id ); ?>"><?php echo esc_html( $lihan_heading ); ?></h2>
		<?php endif; ?>
		<div class="lh-readmore" data-lh-readmore data-lines="<?php echo (int) $lihan_lines; ?>" style="--lh-lines: <?php echo (int) $lihan_lines; ?>;">
			<div class="lh-readmore__content lh-rte" id="<?php echo esc_attr( $lihan_body_id ); ?>"><?php echo $lihan_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses'd. ?></div>
			<button type="button" class="lh-readmore__toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $lihan_body_id ); ?>" data-label-more="<?php esc_attr_e( 'Read More', 'lihan' ); ?>" data-label-less="<?php esc_attr_e( 'Read Less', 'lihan' ); ?>"><?php esc_html_e( 'Read More', 'lihan' ); ?></button>
		</div>
	</div>
</section>
