<?php
/**
 * Homepage section: FAQ accordion (native <details>) + FAQPage JSON-LD.
 *
 * $args (all optional; missing keys fall back to the customizer):
 *   'heading' string        heading; false hides it
 *   'items'   array|string  rows of [ 'question', 'answer' ], or a string with one
 *                           "Question|Answer" per line ({{cat:slug|label}} tokens allowed)
 *   'schema'  bool          print FAQPage structured data (once per page; default true)
 *   'open'    int           1-based index of an item to open initially (default none)
 *   'id'      string        section HTML id
 *   'class'   string        extra classes
 *
 * @package Lihan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args = lihan_home_args(
	is_array( $args ?? null ) ? $args : array(),
	array(
		'heading' => (string) lihan_home_opt( 'home_faq_heading' ),
		'items'   => lihan_home_opt( 'home_faq_items' ),
		'schema'  => lihan_home_bool( lihan_home_opt( 'home_faq_schema' ) ),
		'open'    => 0,
	)
);

$lihan_items = array_values(
	array_filter(
		lihan_home_parse_rows( $args['items'], array( 'question', 'answer' ) ),
		static function ( $item ) {
			return '' !== $item['question'] && '' !== $item['answer'];
		}
	)
);

if ( ! $lihan_items ) {
	return;
}

$lihan_heading  = trim( (string) $args['heading'] );
$lihan_title_id = wp_unique_id( 'lh-faq-title-' );
$lihan_open     = absint( $args['open'] );
$lihan_allowed  = array(
	'a'      => array(
		'href'   => true,
		'target' => true,
		'rel'    => true,
	),
	'strong' => array(),
	'em'     => array(),
	'br'     => array(),
);
?>
<section<?php echo lihan_home_section_attrs( 'faq', $args, array( 'lh-section', 'lh-faq' ), $lihan_heading ? $lihan_title_id : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="lh-container lh-container--narrow">
		<?php if ( $lihan_heading ) : ?>
			<h2 class="lh-h2 lh-faq__title" id="<?php echo esc_attr( $lihan_title_id ); ?>"><?php echo esc_html( $lihan_heading ); ?></h2>
		<?php endif; ?>
		<div class="lh-faq__list">
			<?php foreach ( $lihan_items as $lihan_i => $lihan_item ) : ?>
				<details class="lh-accordion lh-faq__item"<?php echo ( $lihan_i + 1 ) === $lihan_open ? ' open' : ''; ?>>
					<summary>
						<span class="lh-accordion__title"><?php echo esc_html( wp_strip_all_tags( $lihan_item['question'] ) ); ?></span>
					</summary>
					<div class="lh-accordion__body">
						<p><?php echo wp_kses( lihan_home_expand_tokens( $lihan_item['answer'] ), $lihan_allowed ); ?></p>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	if ( lihan_home_bool( $args['schema'] ) ) {
		lihan_home_faq_schema( $lihan_items );
	}
	?>
</section>
