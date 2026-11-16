<?php
/**
 * Single page.
 *
 * Elementor-built pages render their content full-width; WooCommerce pages (cart, checkout,
 * account) use the full container; other pages use a narrow reading column.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	if ( lihan_is_elementor_page() ) :
		?>
		<main id="primary" class="lh-main lh-main--elementor">
			<?php the_content(); ?>
		</main>
		<?php
	else :
		$lihan_is_store = lihan_is_woocommerce_page();
		?>
		<main id="primary" class="lh-main lh-page<?php echo $lihan_is_store ? ' lh-page--store' : ''; ?>">
			<div class="lh-container">
				<?php
				lihan_breadcrumbs();
				get_template_part( 'template-parts/content', 'page', [ 'wide' => $lihan_is_store ] );

				if ( comments_open() || get_comments_number() ) {
					echo '<div class="lh-container--narrow lh-comments-wrap">';
					comments_template();
					echo '</div>';
				}
				?>
			</div>
		</main>
		<?php
	endif;
endwhile;

get_footer();
