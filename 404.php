<?php
/**
 * 404 page.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lihan_shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
?>
<main id="primary" class="lh-main lh-404">
	<div class="lh-container lh-container--narrow lh-404__inner">
		<p class="lh-404__code" aria-hidden="true">404</p>
		<h1 class="lh-h2 lh-404__title"><?php esc_html_e( 'Page not found', 'lihan' ); ?></h1>
		<p class="lh-404__text"><?php esc_html_e( 'Sorry, the page you are looking for has moved or no longer exists. Try a search, or continue shopping below.', 'lihan' ); ?></p>
		<div class="lh-404__search">
			<?php
			get_search_form(
				[
					'lihan_products' => true,
					'aria_label'     => __( 'Search the store', 'lihan' ),
				]
			);
			?>
		</div>
		<div class="lh-404__actions">
			<a class="lh-btn lh-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'lihan' ); ?></a>
			<?php if ( $lihan_shop ) : ?>
				<a class="lh-btn lh-btn--outline" href="<?php echo esc_url( $lihan_shop ); ?>"><?php esc_html_e( 'Shop all', 'lihan' ); ?></a>
			<?php endif; ?>
		</div>
		<?php
		if ( taxonomy_exists( 'product_cat' ) ) {
			lihan_menu_fallback(
				[
					'menu_class'    => 'lh-404__cats',
					'lihan_context' => 'plain',
				]
			);
		}
		?>
	</div>
</main>
<?php
get_footer();
