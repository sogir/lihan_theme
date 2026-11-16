<?php
/**
 * Main template: blog posts index and fallback for every other view.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( is_home() && ! is_front_page() ) {
	$lihan_title = single_post_title( '', false );
} elseif ( is_home() ) {
	$lihan_title = __( 'Latest stories', 'lihan' );
} else {
	$lihan_title = get_the_archive_title();
}
?>
<main id="primary" class="lh-main lh-blog">
	<div class="lh-container">
		<?php
		lihan_breadcrumbs();
		lihan_page_header( (string) $lihan_title );
		?>
		<div class="lh-blog__layout">
			<div class="lh-blog__content">
				<?php if ( have_posts() ) : ?>
					<div class="lh-post-grid">
						<?php
						while ( have_posts() ) :
							the_post();
							get_template_part( 'template-parts/content', get_post_type() );
						endwhile;
						?>
					</div>
					<?php lihan_pagination(); ?>
				<?php else : ?>
					<?php get_template_part( 'template-parts/content', 'none' ); ?>
				<?php endif; ?>
			</div>
			<?php get_sidebar(); ?>
		</div>
	</div>
</main>
<?php
get_footer();
