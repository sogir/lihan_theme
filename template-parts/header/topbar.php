<?php
/**
 * Top bar: social icons, rotating announcements, phone and WhatsApp.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

$lihan_phone    = trim( (string) lihan_get_option( 'contact_phone' ) );
$lihan_whatsapp = lihan_whatsapp_url();
?>
<div class="lh-topbar">
	<div class="lh-container lh-topbar__inner">
		<div class="lh-topbar__left">
			<?php lihan_social_links( 'lh-social--topbar', 16 ); ?>
		</div>

		<div class="lh-topbar__center">
			<?php lihan_render_announcements(); ?>
		</div>

		<div class="lh-topbar__right">
			<?php
			if ( has_nav_menu( 'topbar' ) ) {
				wp_nav_menu(
					[
						'theme_location' => 'topbar',
						'container'      => false,
						'menu_class'     => 'lh-topbar__menu',
						'depth'          => 1,
						'fallback_cb'    => false,
					]
				);
			}
			?>
			<?php if ( '' !== $lihan_phone ) : ?>
				<a class="lh-topbar__link" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $lihan_phone ), [ 'tel' ] ); ?>">
					<?php echo lihan_icon( 'phone', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="lh-visually-hidden"><?php esc_html_e( 'Call us:', 'lihan' ); ?> </span><?php echo esc_html( $lihan_phone ); ?>
				</a>
			<?php endif; ?>
			<?php if ( '' !== $lihan_whatsapp ) : ?>
				<a class="lh-topbar__link" href="<?php echo esc_url( $lihan_whatsapp ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo lihan_icon( 'whatsapp', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'WhatsApp', 'lihan' ); ?><span class="lh-visually-hidden"> <?php esc_html_e( '(opens in a new tab)', 'lihan' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>
