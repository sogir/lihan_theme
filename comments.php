<?php
/**
 * Comments.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="lh-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="lh-comments__title lh-h2">
			<?php
			$lihan_count = (int) get_comments_number();
			echo esc_html(
				sprintf(
					/* translators: %d: number of comments. */
					_n( '%d comment', '%d comments', $lihan_count, 'lihan' ),
					$lihan_count
				)
			);
			?>
		</h2>

		<ol class="lh-comment-list">
			<?php
			wp_list_comments(
				[
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 48,
				]
			);
			?>
		</ol>

		<?php
		the_comments_navigation(
			[
				'class'     => 'lh-comments-nav',
				'prev_text' => esc_html__( 'Older comments', 'lihan' ),
				'next_text' => esc_html__( 'Newer comments', 'lihan' ),
			]
		);

		if ( ! comments_open() ) :
			?>
			<p class="lh-comments__closed"><?php esc_html_e( 'Comments are closed.', 'lihan' ); ?></p>
			<?php
		endif;
	endif;

	comment_form(
		[
			'class_container'    => 'lh-comment-respond comment-respond',
			'class_form'         => 'lh-comment-form comment-form',
			'title_reply_before' => '<h2 id="reply-title" class="lh-comments__title lh-h2 comment-reply-title">',
			'title_reply_after'  => '</h2>',
			'class_submit'       => 'lh-btn lh-btn--primary submit',
		]
	);
	?>
</section>
