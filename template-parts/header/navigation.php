<?php
/**
 * Desktop navigation bar (hidden below 1024px, where the drawer menu takes over).
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;
?>
<nav class="lh-nav" aria-label="<?php esc_attr_e( 'Main menu', 'lihan' ); ?>">
	<div class="lh-container">
		<?php
		wp_nav_menu(
			[
				'theme_location' => 'primary',
				'container'      => false,
				'menu_id'        => 'lh-primary-menu',
				'menu_class'     => 'lh-menu',
				'depth'          => 3,
				'fallback_cb'    => 'lihan_menu_fallback',
				'lihan_context'  => 'primary',
			]
		);
		?>
	</div>
</nav>
