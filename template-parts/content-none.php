<?php
/**
 * Message when there is nothing to show.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="lh-no-results lh-container--narrow">
	<h2 class="lh-h2"><?php esc_html_e( 'Nothing found', 'lihan' ); ?></h2>
	<?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>
		<p>
			<?php
			printf(
				wp_kses(
					/* translators: %s: link to the new post screen. */
					__( 'Ready to publish your first post? <a href="%s">Get started here</a>.', 'lihan' ),
					[ 'a' => [ 'href' => [] ] ]
				),
				esc_url( admin_url( 'post-new.php' ) )
			);
			?>
		</p>
	<?php elseif ( is_search() ) : ?>
		<p><?php esc_html_e( 'Sorry, nothing matched your search. Please try different keywords.', 'lihan' ); ?></p>
	<?php else : ?>
		<p><?php esc_html_e( 'It seems we can’t find what you’re looking for. Perhaps searching can help.', 'lihan' ); ?></p>
		<?php get_search_form(); ?>
	<?php endif; ?>
</section>
