<?php
/**
 * Front page.
 *
 * - A static front page built with Elementor renders its own content, full width.
 * - Otherwise the enabled homepage sections render in order (filter
 *   lihan_home_sections); any content typed into the static front page is
 *   shown after them. "Your latest posts" as the front page also gets the sections.
 *
 * @package Lihan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$lihan_front_id  = (int) get_option( 'page_on_front' );
$lihan_is_static = 'page' === get_option( 'show_on_front' ) && $lihan_front_id > 0;
$lihan_elementor = false;

if ( $lihan_is_static && class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->documents ) ) {
	$lihan_document  = \Elementor\Plugin::$instance->documents->get( $lihan_front_id );
	$lihan_elementor = $lihan_document && $lihan_document->is_built_with_elementor();
}

if ( $lihan_elementor ) :
	?>
	<main id="primary" class="lh-main lh-main--elementor lh-home">
		<?php
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>
	</main>
	<?php
else :
	?>
	<main id="primary" class="lh-main lh-home">
		<?php
		foreach ( lihan_home_sections() as $lihan_section ) {
			lihan_render_section( $lihan_section );
		}

		// Content typed into the static front page is shown after the sections.
		if ( $lihan_is_static ) {
			while ( have_posts() ) :
				the_post();
				if ( '' !== trim( (string) get_the_content() ) ) :
					?>
					<section class="lh-section lh-home-content">
						<div class="lh-container lh-container--narrow lh-rte entry-content">
							<?php the_content(); ?>
						</div>
					</section>
					<?php
				endif;
			endwhile;
		}
		?>
	</main>
	<?php
endif;

get_footer();
