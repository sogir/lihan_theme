<?php
/**
 * Search results (product searches are handled by WooCommerce's product archive).
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

get_header();

global $wp_query;
$lihan_count = (int) $wp_query->found_posts;
?>
<main id="primary" class="lh-main lh-blog lh-search">
	<div class="lh-container">
		<?php
		lihan_breadcrumbs();
		lihan_page_header(
			sprintf(
				/* translators: %s: search query. */
				esc_html__( 'Results for “%s”', 'lihan' ),
				'<span>' . esc_html( get_search_query( false ) ) . '</span>'
			)
		);
		?>
		<p class="lh-page-header__count">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %d: number of results. */
					_n( '(%d result)', '(%d results)', $lihan_count, 'lihan' ),
					$lihan_count
				)
			);
			?>
		</p>
		<div class="lh-search__form"><?php get_search_form(); ?></div>

		<?php if ( have_posts() ) : ?>
			<div class="lh-post-grid lh-post-grid--search">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', 'search' );
				endwhile;
				?>
			</div>
			<?php lihan_pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
