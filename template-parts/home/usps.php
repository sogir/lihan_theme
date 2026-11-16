<?php
/**
 * Homepage section: USP bar.
 *
 * $args (all optional; missing keys fall back to the customizer):
 *   'heading' string        heading shown on mobile ("WHY LIHAN?"); false hides it
 *   'items'   array|string  rows of [ 'icon' => icon name, 'label' => text ],
 *                           or a string with one "icon|Label" per line
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
		'heading' => (string) lihan_home_opt( 'home_usps_heading' ),
		'items'   => lihan_home_opt( 'home_usps_items' ),
	)
);

$lihan_items = lihan_home_parse_rows( $args['items'], array( 'icon', 'label' ) );
$lihan_items = array_values(
	array_filter(
		$lihan_items,
		static function ( $item ) {
			return '' !== $item['label'];
		}
	)
);

if ( ! $lihan_items ) {
	return;
}

$lihan_title_id = wp_unique_id( 'lh-usps-title-' );
$lihan_heading  = trim( (string) $args['heading'] );
?>
<section<?php echo lihan_home_section_attrs( 'usps', $args, array( 'lh-usps' ), $lihan_heading ? $lihan_title_id : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="lh-container">
		<?php if ( $lihan_heading ) : ?>
			<p class="lh-h2 lh-usps__title" id="<?php echo esc_attr( $lihan_title_id ); ?>"><?php echo esc_html( $lihan_heading ); ?></p>
		<?php endif; ?>
		<ul class="lh-usps__list" role="list">
			<?php foreach ( $lihan_items as $lihan_item ) : ?>
				<li class="lh-usps__item">
					<?php
					$lihan_svg = '' !== $lihan_item['icon'] ? lihan_home_icon( sanitize_key( $lihan_item['icon'] ), 18, 'lh-usps__icon' ) : '';
					echo $lihan_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the icon helper.
					?>
					<span class="lh-usps__label"><?php echo esc_html( $lihan_item['label'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
