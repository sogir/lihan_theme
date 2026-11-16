<?php
/**
 * Page content.
 *
 * $args['wide'] (bool): use the full container instead of the narrow reading column.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

$lihan_wide = ! empty( $args['wide'] );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'lh-entry lh-entry--page' ); ?>>
	<?php if ( apply_filters( 'lihan_show_page_title', true ) ) : ?>
		<header class="lh-page-header">
			<?php the_title( '<h1 class="lh-page-title lh-h2 entry-title">', '</h1>' ); ?>
		</header>
	<?php endif; ?>

	<?php lihan_post_thumbnail( 'large' ); ?>

	<div class="lh-entry__content lh-rte<?php echo $lihan_wide ? '' : ' lh-container--narrow'; ?>">
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

	<?php if ( get_edit_post_link() ) : ?>
		<footer class="lh-entry__footer<?php echo $lihan_wide ? '' : ' lh-container--narrow'; ?>">
			<?php
			edit_post_link(
				sprintf(
					/* translators: %s: page title. */
					esc_html__( 'Edit %s', 'lihan' ),
					'<span class="lh-visually-hidden">' . esc_html( get_the_title() ) . '</span>'
				),
				'<p class="lh-edit-link">',
				'</p>'
			);
			?>
		</footer>
	<?php endif; ?>
</article>
