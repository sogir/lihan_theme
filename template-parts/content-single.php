<?php
/**
 * Single post content.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'lh-entry lh-entry--single' ); ?>>
	<header class="lh-page-header lh-entry__header">
		<?php if ( 'post' === get_post_type() ) : ?>
			<?php $lihan_cats = get_the_category(); ?>
			<?php if ( $lihan_cats ) : ?>
				<p class="lh-entry__eyebrow"><a href="<?php echo esc_url( get_category_link( $lihan_cats[0] ) ); ?>"><?php echo esc_html( $lihan_cats[0]->name ); ?></a></p>
			<?php endif; ?>
		<?php endif; ?>
		<?php
		if ( apply_filters( 'lihan_show_page_title', true ) ) {
			the_title( '<h1 class="lh-page-title lh-h2 entry-title">', '</h1>' );
		} else {
			the_title( '<h1 class="lh-visually-hidden entry-title">', '</h1>' );
		}
		?>
		<?php if ( 'post' === get_post_type() ) : ?>
			<div class="lh-meta lh-meta--center">
				<?php
				lihan_posted_on();
				lihan_posted_by();
				?>
			</div>
		<?php endif; ?>
	</header>

	<?php lihan_post_thumbnail( 'large' ); ?>

	<div class="lh-entry__content lh-rte lh-container--narrow">
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

	<footer class="lh-entry__footer lh-container--narrow">
		<?php lihan_entry_footer(); ?>
	</footer>
</article>
