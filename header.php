<?php
/**
 * Site header.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="lh-skip-link" href="#primary"><?php esc_html_e( 'Skip to content', 'lihan' ); ?></a>

<?php
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'header' ) ) {
	get_template_part( 'template-parts/header/site-header' );
}
?>
<div class="lh-overlay" data-lh-close aria-hidden="true"></div>

<div id="content" class="lh-site-content">
