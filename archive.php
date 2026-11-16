<?php
/**
 * Archive template (categories, tags, dates, authors, custom post type archives).
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="lh-main lh-blog lh-archive">
	<div class="lh-container">
		<?php
		lihan_breadcrumbs();
		lihan_page_header( get_the_archive_title(), (string) get_the_archive_description() );
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
