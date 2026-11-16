<?php
/**
 * Lihan theme bootstrap.
 *
 * Each feature area lives in its own file under inc/. Optional modules are loaded
 * only when present so the theme keeps working while parts are developed separately.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

define( 'LIHAN_VERSION', wp_get_theme( get_template() )->get( 'Version' ) ?: '1.0.0' ); // Bumped by bin/release.sh in style.css.
define( 'LIHAN_DIR', get_template_directory() );
define( 'LIHAN_URI', get_template_directory_uri() );

require LIHAN_DIR . '/inc/setup.php';
require LIHAN_DIR . '/inc/icons.php';
require LIHAN_DIR . '/inc/template-tags.php';
require LIHAN_DIR . '/inc/customizer.php';
require LIHAN_DIR . '/inc/newsletter.php';
require LIHAN_DIR . '/inc/updater.php';

foreach ( [ 'home', 'elementor', 'cartflows', 'demo-import' ] as $lihan_module ) {
	if ( file_exists( LIHAN_DIR . "/inc/$lihan_module.php" ) ) {
		require LIHAN_DIR . "/inc/$lihan_module.php";
	}
}
unset( $lihan_module );

if ( class_exists( 'WooCommerce' ) && file_exists( LIHAN_DIR . '/inc/woocommerce.php' ) ) {
	require LIHAN_DIR . '/inc/woocommerce.php';
	if ( file_exists( LIHAN_DIR . '/inc/checkout.php' ) ) {
		require LIHAN_DIR . '/inc/checkout.php';
	}
}
