<?php
/**
 * Search form.
 *
 * Accepts get_search_form() args: `lihan_products` (bool, search WooCommerce products),
 * `lihan_variant` ('default' | 'header' | 'drawer') and `aria_label`.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

$lihan_args     = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : [],
	[
		'lihan_products' => false,
		'lihan_variant'  => 'default',
		'aria_label'     => '',
	]
);
$lihan_products = $lihan_args['lihan_products'] && post_type_exists( 'product' );
$lihan_id       = wp_unique_id( 'lh-search-' );
$lihan_label    = $lihan_products ? __( 'Search products', 'lihan' ) : __( 'Search', 'lihan' );
?>
<form role="search" method="get" class="lh-search-form lh-search-form--<?php echo esc_attr( sanitize_html_class( (string) $lihan_args['lihan_variant'] ) ); ?>" action="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo $lihan_args['aria_label'] ? ' aria-label="' . esc_attr( $lihan_args['aria_label'] ) . '"' : ''; ?>>
	<label class="lh-visually-hidden" for="<?php echo esc_attr( $lihan_id ); ?>"><?php echo esc_html( $lihan_label ); ?></label>
	<input type="search" id="<?php echo esc_attr( $lihan_id ); ?>" class="lh-search-form__input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search', 'lihan' ); ?>" autocomplete="off" enterkeyhint="search">
	<?php if ( $lihan_products ) : ?>
		<input type="hidden" name="post_type" value="product">
	<?php endif; ?>
	<button type="submit" class="lh-search-form__submit">
		<?php echo lihan_icon( 'search', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="lh-visually-hidden"><?php esc_html_e( 'Submit search', 'lihan' ); ?></span>
	</button>
</form>
