<?php
/**
 * Off-canvas mobile menu drawer.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

$lihan_location = has_nav_menu( 'mobile' ) ? 'mobile' : 'primary';
?>
<div id="lh-mobile-menu" class="lh-drawer lh-drawer--left lh-mobile-menu" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'lihan' ); ?>">
	<div class="lh-drawer__header">
		<?php lihan_logo( [ 'class' => 'lh-logo--drawer' ] ); ?>
		<button type="button" class="lh-drawer__close" data-lh-close>
			<?php echo lihan_icon( 'close', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="lh-visually-hidden"><?php esc_html_e( 'Close menu', 'lihan' ); ?></span>
		</button>
	</div>

	<div class="lh-drawer__body">
		<nav class="lh-mobile-nav" aria-label="<?php esc_attr_e( 'Mobile menu', 'lihan' ); ?>">
			<?php
			wp_nav_menu(
				[
					'theme_location' => $lihan_location,
					'container'      => false,
					'menu_id'        => 'lh-mobile-menu-list',
					'menu_class'     => 'lh-mobile-nav__menu',
					'depth'          => 3,
					'fallback_cb'    => 'lihan_menu_fallback',
					'lihan_context'  => 'mobile',
				]
			);
			?>
		</nav>

		<div class="lh-mobile-menu__extra">
			<a class="lh-mobile-menu__account" href="<?php echo esc_url( lihan_account_url() ); ?>">
				<?php echo lihan_icon( 'user', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php is_user_logged_in() ? esc_html_e( 'My account', 'lihan' ) : esc_html_e( 'Log in / Register', 'lihan' ); ?></span>
			</a>
			<?php
			lihan_contact_list( 'lh-contact--drawer' );
			lihan_social_links( 'lh-social--drawer', 18 );
			?>
		</div>
	</div>
</div>
