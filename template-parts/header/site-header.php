<?php
/**
 * Theme header: top bar, sticky masthead + navigation, and the mobile drawers.
 *
 * The <header> is a direct child of <body> so `position: sticky` works for the whole page.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

$lihan_sticky = function_exists( 'lihan_is_header_sticky' ) && lihan_is_header_sticky();

get_template_part( 'template-parts/header/topbar' );
?>
<div class="lh-header-sentinel" aria-hidden="true"></div>
<header id="masthead" class="lh-site-header<?php echo $lihan_sticky ? ' lh-site-header--sticky' : ''; ?>" data-lh-header>
	<?php
	get_template_part( 'template-parts/header/masthead' );
	get_template_part( 'template-parts/header/navigation' );
	get_template_part( 'template-parts/header/search-drawer' );
	?>
</header>
<?php
get_template_part( 'template-parts/header/mobile-menu' );
