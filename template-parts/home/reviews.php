<?php
/**
 * Homepage section: customer reviews carousel.
 *
 * Source: approved WooCommerce product reviews rated 4+ (title = product
 * name), falling back to the customizer list.
 *
 * $args (all optional; missing keys fall back to the customizer):
 *   'source'   string        'auto' (product reviews, else items) | 'manual' (items only);
 *                            defaults to 'manual' when 'items' is passed
 *   'items'    array|string  rows of [ 'title', 'text', 'name', 'rating' (1–5, default 5) ],
 *                            or a string with one "Title|Text|Name|Rating" per line
 *   'count'    int           max product reviews (default 8)
 *   'autoplay' int           ms between slides, 0 = off (default 7000)
 *   'heading'  string        optional visually hidden section label (default "Customer reviews")
 *   'id'       string        section HTML id
 *   'class'    string        extra classes
 *
 * @package Lihan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lihan_passed = is_array( $args ?? null ) ? $args : array();

// Items passed directly (e.g. from a page builder) win unless a source is given.
if ( ! empty( $lihan_passed['items'] ) && empty( $lihan_passed['source'] ) ) {
	$lihan_passed['source'] = 'manual';
}

$args = lihan_home_args(
	$lihan_passed,
	array(
		'source'   => (string) lihan_home_opt( 'home_reviews_source' ),
		'items'    => lihan_home_opt( 'home_reviews' ),
		'count'    => 8,
		'autoplay' => 7000,
		'heading'  => __( 'Customer reviews', 'lihan' ),
	)
);

$lihan_reviews = array();

// Product reviews.
if ( 'manual' !== $args['source'] && post_type_exists( 'product' ) ) {
	$lihan_comments = get_comments(
		array(
			'post_type'   => 'product',
			'post_status' => 'publish',
			'status'      => 'approve',
			'type'        => 'review',
			'number'      => max( 1, min( 20, absint( $args['count'] ) ) ),
			'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'rating',
					'value'   => 4,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			),
		)
	);
	foreach ( (array) $lihan_comments as $lihan_comment ) {
		if ( ! $lihan_comment instanceof WP_Comment ) {
			continue;
		}
		$lihan_text = trim( wp_strip_all_tags( (string) $lihan_comment->comment_content ) );
		if ( '' === $lihan_text ) {
			continue;
		}
		// Show the first name and an initial only.
		$lihan_parts = preg_split( '/\s+/', trim( (string) $lihan_comment->comment_author ) );
		$lihan_name  = $lihan_parts[0] ?? '';
		if ( isset( $lihan_parts[1] ) && '' !== $lihan_parts[1] ) {
			$lihan_name .= ' ' . mb_substr( $lihan_parts[1], 0, 1 ) . '.';
		}
		$lihan_reviews[] = array(
			'title'  => get_the_title( (int) $lihan_comment->comment_post_ID ),
			'text'   => wp_trim_words( $lihan_text, 45 ),
			'name'   => $lihan_name,
			'rating' => (int) get_comment_meta( (int) $lihan_comment->comment_ID, 'rating', true ),
			'url'    => (string) get_permalink( (int) $lihan_comment->comment_post_ID ),
		);
	}
}

// Fallback list.
if ( ! $lihan_reviews ) {
	foreach ( lihan_home_parse_rows( $args['items'], array( 'title', 'text', 'name', 'rating' ) ) as $lihan_row ) {
		if ( '' === $lihan_row['text'] ) {
			continue;
		}
		$lihan_reviews[] = array(
			'title'  => '' !== $lihan_row['title'] ? $lihan_row['title'] : wp_trim_words( $lihan_row['text'], 3, '' ),
			'text'   => $lihan_row['text'],
			'name'   => $lihan_row['name'],
			'rating' => '' !== $lihan_row['rating'] ? (int) $lihan_row['rating'] : 5,
			'url'    => '',
		);
	}
}

if ( ! $lihan_reviews ) {
	return;
}

$lihan_total    = count( $lihan_reviews );
$lihan_title_id = wp_unique_id( 'lh-reviews-title-' );
$lihan_label    = trim( (string) $args['heading'] );
?>
<section<?php echo lihan_home_section_attrs( 'reviews', $args, array( 'lh-section', 'lh-section--warm', 'lh-reviews' ), $lihan_label ? $lihan_title_id : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $lihan_label ) : ?>
		<h2 class="lh-visually-hidden" id="<?php echo esc_attr( $lihan_title_id ); ?>"><?php echo esc_html( $lihan_label ); ?></h2>
	<?php endif; ?>
	<div class="lh-container lh-container--narrow">
		<div class="lh-carousel lh-reviews__carousel"<?php echo $lihan_total > 1 ? ' data-lh-carousel data-per-view="1" data-per-view-tablet="1" data-per-view-mobile="1" data-gap="30" data-loop="1" data-autoplay="' . (int) $args['autoplay'] . '"' : ''; ?>>
			<div class="lh-carousel__viewport">
				<div class="lh-carousel__track">
					<?php
					foreach ( $lihan_reviews as $lihan_review ) :
						$lihan_rating = max( 1, min( 5, (int) $lihan_review['rating'] ) );
						?>
						<figure class="lh-carousel__slide lh-review">
							<div class="lh-review__stars" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: star rating. */ __( 'Rated %d out of 5', 'lihan' ), $lihan_rating ) ); ?>">
								<?php
								for ( $lihan_s = 1; $lihan_s <= 5; $lihan_s++ ) {
									echo lihan_home_icon( $lihan_s <= $lihan_rating ? 'star-filled' : 'star', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</div>
							<?php if ( '' !== $lihan_review['title'] ) : ?>
								<p class="lh-h2 lh-review__title">
									<?php if ( $lihan_review['url'] ) : ?>
										<a href="<?php echo esc_url( $lihan_review['url'] ); ?>"><?php echo esc_html( $lihan_review['title'] ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $lihan_review['title'] ); ?>
									<?php endif; ?>
								</p>
							<?php endif; ?>
							<blockquote class="lh-review__text"><p><?php echo esc_html( $lihan_review['text'] ); ?></p></blockquote>
							<?php if ( '' !== $lihan_review['name'] ) : ?>
								<figcaption class="lh-review__author"><?php echo esc_html( $lihan_review['name'] ); ?></figcaption>
							<?php endif; ?>
						</figure>
					<?php endforeach; ?>
				</div>
			</div>
			<?php
			if ( $lihan_total > 1 ) {
				/* translators: 1: current slide, 2: total slides. */
				echo lihan_home_carousel_nav( sprintf( __( '%1$d / %2$d', 'lihan' ), 1, $lihan_total ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
			}
			?>
		</div>
	</div>
</section>
