<?php
/**
 * Homepage section: rich text with a heading (e.g. a category list).
 *
 * $args (all optional; missing keys fall back to the customizer):
 *   'heading' string  heading (left-aligned); false hides it
 *   'text'    string  rich text (HTML allowed, {{cat:slug|label}} tokens)
 *   'align'   string  'left' (default) | 'center'
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
		'heading' => (string) lihan_home_opt( 'home_rich_heading' ),
		'text'    => (string) lihan_home_opt( 'home_rich_text' ),
		'align'   => 'left',
	)
);

$lihan_heading = trim( (string) $args['heading'] );
$lihan_text    = lihan_home_rich_text( (string) $args['text'] );

if ( '' === $lihan_heading && '' === $lihan_text ) {
	return;
}

$lihan_title_id = wp_unique_id( 'lh-rich-title-' );
$lihan_center   = 'center' === $args['align'];
?>
<section<?php echo lihan_home_section_attrs( 'rich_text', $args, array( 'lh-section', 'lh-richtext', $lihan_center ? 'lh-richtext--center' : '' ), $lihan_heading ? $lihan_title_id : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="lh-container">
		<?php if ( $lihan_heading ) : ?>
			<h2 class="lh-h2<?php echo $lihan_center ? '' : ' lh-h2--left'; ?> lh-richtext__title" id="<?php echo esc_attr( $lihan_title_id ); ?>"><?php echo esc_html( $lihan_heading ); ?></h2>
		<?php endif; ?>
		<?php if ( $lihan_text ) : ?>
			<div class="lh-rte lh-richtext__body"><?php echo $lihan_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses'd. ?></div>
		<?php endif; ?>
	</div>
</section>
