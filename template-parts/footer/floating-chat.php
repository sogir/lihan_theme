<?php
/**
 * Floating chat buttons (WhatsApp, with Messenger revealed on hover/focus; always visible on touch).
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

$lihan_whatsapp  = lihan_whatsapp_url();
$lihan_messenger = trim( (string) lihan_get_option( 'contact_messenger' ) );
$lihan_main_url  = '' !== $lihan_whatsapp ? $lihan_whatsapp : $lihan_messenger;
$lihan_main_icon = '' !== $lihan_whatsapp ? 'whatsapp' : 'messenger';
?>
<div class="lh-chat" role="complementary" aria-label="<?php esc_attr_e( 'Chat with us', 'lihan' ); ?>">
	<?php if ( '' !== $lihan_whatsapp && '' !== $lihan_messenger ) : ?>
		<a class="lh-chat__mini lh-chat__mini--messenger" href="<?php echo esc_url( $lihan_messenger ); ?>" target="_blank" rel="noopener noreferrer">
			<?php echo lihan_icon( 'messenger', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="lh-visually-hidden"><?php esc_html_e( 'Chat on Messenger (opens in a new tab)', 'lihan' ); ?></span>
		</a>
	<?php endif; ?>
	<a class="lh-chat__main lh-chat__main--<?php echo esc_attr( $lihan_main_icon ); ?>" href="<?php echo esc_url( $lihan_main_url ); ?>" target="_blank" rel="noopener noreferrer">
		<?php echo lihan_icon( $lihan_main_icon, 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="lh-visually-hidden">
			<?php
			'whatsapp' === $lihan_main_icon
				? esc_html_e( 'Chat on WhatsApp (opens in a new tab)', 'lihan' )
				: esc_html_e( 'Chat on Messenger (opens in a new tab)', 'lihan' );
			?>
		</span>
	</a>
</div>
