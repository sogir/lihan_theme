<?php
/**
 * Main header row: logo, product search and account/cart icons.
 *
 * On screens below 1024px the same row becomes: [menu][wishlist] — logo — [search][cart].
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

$lihan_wishlist = lihan_wishlist_url();
$lihan_has_wc   = class_exists( 'WooCommerce' ) && function_exists( 'wc_get_cart_url' );
?>
<div class="lh-masthead">
	<div class="lh-container lh-masthead__inner">
		<div class="lh-masthead__start">
			<button type="button" class="lh-header-icon lh-masthead__menu-toggle" data-lh-open="lh-mobile-menu" aria-controls="lh-mobile-menu" aria-expanded="false">
				<?php echo lihan_icon( 'menu', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="lh-visually-hidden"><?php esc_html_e( 'Open menu', 'lihan' ); ?></span>
			</button>
			<?php if ( '' !== $lihan_wishlist ) : ?>
				<a class="lh-header-icon lh-header-icon--wishlist-mobile" href="<?php echo esc_url( $lihan_wishlist ); ?>">
					<?php echo lihan_icon( 'heart', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="lh-visually-hidden"><?php esc_html_e( 'Wishlist', 'lihan' ); ?></span>
				</a>
			<?php endif; ?>
		</div>

		<div class="lh-masthead__brand">
			<?php lihan_logo(); ?>
		</div>

		<div class="lh-masthead__search">
			<?php
			get_search_form(
				[
					'lihan_products' => true,
					'lihan_variant'  => 'header',
					'aria_label'     => __( 'Site search', 'lihan' ),
				]
			);
			?>
		</div>

		<div class="lh-masthead__end">
			<button type="button" class="lh-header-icon lh-masthead__search-toggle" data-lh-open="lh-search-drawer" aria-controls="lh-search-drawer" aria-expanded="false">
				<?php echo lihan_icon( 'search', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="lh-visually-hidden"><?php esc_html_e( 'Search', 'lihan' ); ?></span>
			</button>
			<?php if ( '' !== $lihan_wishlist ) : ?>
				<a class="lh-header-icon lh-header-icon--wishlist" href="<?php echo esc_url( $lihan_wishlist ); ?>">
					<?php echo lihan_icon( 'heart', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="lh-visually-hidden"><?php esc_html_e( 'Wishlist', 'lihan' ); ?></span>
				</a>
			<?php endif; ?>
			<a class="lh-header-icon lh-header-icon--account" href="<?php echo esc_url( lihan_account_url() ); ?>">
				<?php echo lihan_icon( 'user', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="lh-visually-hidden"><?php is_user_logged_in() ? esc_html_e( 'My account', 'lihan' ) : esc_html_e( 'Log in', 'lihan' ); ?></span>
			</a>
			<?php if ( $lihan_has_wc ) : ?>
				<a class="lh-header-icon lh-header-icon--cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-lh-open="lh-cart-drawer" aria-controls="lh-cart-drawer" aria-expanded="false">
					<?php echo lihan_icon( 'bag', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="lh-visually-hidden"><?php esc_html_e( 'Shopping bag', 'lihan' ); ?></span>
					<?php echo lihan_cart_count_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the function. ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>
