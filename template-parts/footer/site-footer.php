<?php
/**
 * Theme footer: newsletter band, link columns and bottom bar.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;
?>
<footer id="colophon" class="lh-site-footer">
	<?php
	get_template_part( 'template-parts/footer/newsletter' );
	get_template_part( 'template-parts/footer/columns' );
	get_template_part( 'template-parts/footer/bottom-bar' );
	?>
</footer>
