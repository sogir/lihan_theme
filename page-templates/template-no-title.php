<?php
/**
 * Template Name: Lihan Container, No Title
 * Template Post Type: page, post
 *
 * Theme header and footer, content inside the theme container, without the
 * page title and breadcrumbs. Elementor content is not given extra padding,
 * so its own section spacing applies.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="lh-main lh-main--no-title">
	<?php
	while ( have_posts() ) :
		the_post();

		$lihan_is_builder = function_exists( 'lihan_is_elementor_page' ) && lihan_is_elementor_page();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'lh-container' . ( $lihan_is_builder ? '' : ' lh-section' ) ); ?>>
			<h1 class="lh-visually-hidden"><?php the_title(); ?></h1>
			<div class="entry-content<?php echo $lihan_is_builder ? '' : ' lh-rte'; ?>">
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
		</article>
		<?php
		if ( ! $lihan_is_builder && ( comments_open() || get_comments_number() ) ) {
			echo '<div class="lh-container lh-container--narrow lh-comments-wrap">';
			comments_template();
			echo '</div>';
		}
	endwhile;
	?>
</main>
<?php
get_footer();
