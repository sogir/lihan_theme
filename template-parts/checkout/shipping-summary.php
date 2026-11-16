<?php
/**
 * Checkout order summary: the chosen delivery area as a plain line.
 *
 * Replaces cart/cart-shipping.php on the checkout only, when the simple checkout is on
 * (see inc/checkout.php). The selectable boxes live under the address fields, so this
 * row has no radio buttons of its own.
 *
 * Receives WooCommerce's cart-shipping.php variables: $package, $available_methods,
 * $chosen_method, $package_name, $index.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

$lihan_rates  = isset( $available_methods ) && is_array( $available_methods ) ? $available_methods : [];
$lihan_chosen = isset( $chosen_method ) ? (string) $chosen_method : '';
if ( 1 === count( $lihan_rates ) && '' === $lihan_chosen ) {
	$lihan_chosen = (string) array_key_first( $lihan_rates );
}
$lihan_rate = $lihan_rates[ $lihan_chosen ] ?? null;
?>
<tr class="woocommerce-shipping-totals shipping lh-shipping-summary">
	<th><?php esc_html_e( 'Delivery', 'lihan' ); ?></th>
	<td data-title="<?php esc_attr_e( 'Delivery', 'lihan' ); ?>">
		<?php if ( $lihan_rate instanceof WC_Shipping_Rate ) : ?>
			<?php
			$lihan_cost = (float) $lihan_rate->get_cost() + array_sum( (array) $lihan_rate->get_taxes() );
			echo $lihan_cost > 0 ? wp_kses_post( wc_price( $lihan_cost ) ) : esc_html__( 'Free', 'lihan' );
			?>
			<span class="lh-shipping-summary__area"><?php echo esc_html( $lihan_rate->get_label() ); ?></span>
		<?php elseif ( empty( $lihan_rates ) ) : ?>
			<span class="lh-shipping-summary__area"><?php esc_html_e( 'Not available for this address', 'lihan' ); ?></span>
		<?php else : ?>
			<span class="lh-shipping-summary__area"><?php esc_html_e( 'Choose your area', 'lihan' ); ?></span>
		<?php endif; ?>
	</td>
</tr>
