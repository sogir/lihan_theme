<?php
/**
 * Single post (and fallback for custom post types).
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="lh-main lh-single">
	<div class="lh-container">
		<?php
		lihan_breadcrumbs();

		while ( have_posts() ) :
			the_post();

			if ( lihan_is_elementor_page() ) {
				the_content();
				continue;
			}

			get_template_part( 'template-parts/content', 'single' );

			if ( 'post' === get_post_type() ) {
				echo '<div class="lh-container--narrow">';
				the_post_navigation(
					[
						'class'     => 'lh-post-nav',
						'prev_text' => '<span class="lh-post-nav__label">' . lihan_icon( 'chevron-left', 14 ) . esc_html__( 'Previous', 'lihan' ) . '</span><span class="lh-post-nav__title">%title</span>',
						'next_text' => '<span class="lh-post-nav__label">' . esc_html__( 'Next', 'lihan' ) . lihan_icon( 'chevron-right', 14 ) . '</span><span class="lh-post-nav__title">%title</span>',
					]
				);
				echo '</div>';
			}

			if ( comments_open() || get_comments_number() ) {
				echo '<div class="lh-container--narrow lh-comments-wrap">';
				comments_template();
				echo '</div>';
			}
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();
