<?php
/**
 * Site footer.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;
?>
</div><!-- #content -->

<?php
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'footer' ) ) {
	get_template_part( 'template-parts/footer/site-footer' );
}

if ( function_exists( 'lihan_show_floating_chat' ) && lihan_show_floating_chat() ) {
	get_template_part( 'template-parts/footer/floating-chat' );
}

wp_footer();
?>
</body>
</html>
