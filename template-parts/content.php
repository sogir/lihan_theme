<?php
/**
 * Post card used in the blog grid and archives.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'lh-card' ); ?>>
	<?php lihan_post_thumbnail( 'lihan-square' ); ?>
	<div class="lh-card__body">
		<?php if ( 'post' === get_post_type() ) : ?>
			<div class="lh-meta">
				<?php
				lihan_posted_on();
				$lihan_cats = get_the_category();
				if ( $lihan_cats ) {
					echo '<span class="lh-meta__cat"><a href="' . esc_url( get_category_link( $lihan_cats[0] ) ) . '">' . esc_html( $lihan_cats[0]->name ) . '</a></span>';
				}
				?>
			</div>
		<?php endif; ?>
		<?php the_title( '<h2 class="lh-card__title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' ); ?>
		<div class="lh-card__excerpt"><?php the_excerpt(); ?></div>
		<a class="lh-card__more" href="<?php the_permalink(); ?>">
			<?php esc_html_e( 'Read more', 'lihan' ); ?><span class="lh-visually-hidden"> <?php echo esc_html( get_the_title() ); ?></span>
			<?php echo lihan_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	</div>
</article>
