<?php
/**
 * Template Name: Lihan Full Width
 * Template Post Type: page, post
 *
 * Theme header and footer, no title or breadcrumbs, content edge to edge.
 * Made for Elementor (or block) layouts that bring their own sections.
 * Classic content without a builder keeps the theme gutters so text never
 * touches the screen edge.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="lh-main lh-main--full lh-main--full-width">
	<?php
	while ( have_posts() ) :
		the_post();

		$lihan_is_builder = function_exists( 'lihan_is_elementor_page' ) && lihan_is_elementor_page();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'lh-full-width' ); ?>>
			<h1 class="lh-visually-hidden"><?php the_title(); ?></h1>
			<?php if ( $lihan_is_builder ) : ?>
				<?php the_content(); ?>
			<?php else : ?>
				<div class="lh-container lh-section">
					<div class="entry-content lh-rte">
						<?php
						the_content();

						wp_link_pages(
							[
								'before' => '<nav class="lh-page-links" aria-label="' . esc_attr__( 'Page', 'lihan' ) . '">',
								'after'  => '</nav>',
							]
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		</article>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
