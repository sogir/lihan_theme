<?php
/**
 * Blog sidebar.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>
<aside id="secondary" class="lh-sidebar widget-area" aria-label="<?php esc_attr_e( 'Blog sidebar', 'lihan' ); ?>">
	<?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>
