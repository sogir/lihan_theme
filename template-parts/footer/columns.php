<?php
/**
 * Footer columns: brand, two menus and contact details.
 *
 * Below 768px the two menu columns collapse into accordions; contact details stay open.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

/**
 * Column heading. Collapsible columns get a toggle button that is only shown below 768px;
 * on larger screens a plain heading is shown instead (the hidden one is display:none, so
 * assistive technology never meets a button that does nothing).
 *
 * @param int    $column Column number.
 * @param string $panel  ID of the collapsible panel ('' = not collapsible).
 */
$lihan_heading = static function ( int $column, string $panel = '' ): void {
	$title = esc_html( lihan_footer_col_title( $column ) );
	if ( '' === $panel ) {
		printf( '<h2 class="lh-footer__title"><span class="lh-footer__title-text">%s</span></h2>', $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
		return;
	}
	printf(
		'<h2 class="lh-footer__title"><span class="lh-footer__title-text lh-footer__title-static">%1$s</span><button type="button" class="lh-footer__toggle" aria-expanded="false" aria-controls="%2$s"><span class="lh-footer__title-text">%1$s</span>%3$s</button></h2>',
		$title, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
		esc_attr( $panel ),
		lihan_icon( 'plus', 16, 'lh-footer__toggle-icon' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG.
	);
};
?>
<div class="lh-footer-main">
	<div class="lh-container lh-footer__grid">
		<div class="lh-footer__col lh-footer__col--1 lh-footer__brand">
			<?php lihan_logo( [ 'variant' => 'light' ] ); ?>
			<div class="lh-footer__about"><?php lihan_render_footer_about(); ?></div>
		</div>

		<div class="lh-footer__col lh-footer__col--2" data-lh-footer-col>
			<?php $lihan_heading( 2, 'lh-footer-panel-2' ); ?>
			<div id="lh-footer-panel-2" class="lh-footer__panel">
				<?php
				wp_nav_menu(
					[
						'theme_location' => 'footer-1',
						'container'      => false,
						'menu_class'     => 'lh-footer__menu',
						'depth'          => 1,
						'fallback_cb'    => 'lihan_footer_menu_fallback',
					]
				);
				?>
			</div>
		</div>

		<div class="lh-footer__col lh-footer__col--3" data-lh-footer-col>
			<?php $lihan_heading( 3, 'lh-footer-panel-3' ); ?>
			<div id="lh-footer-panel-3" class="lh-footer__panel">
				<?php
				wp_nav_menu(
					[
						'theme_location' => 'footer-2',
						'container'      => false,
						'menu_class'     => 'lh-footer__menu',
						'depth'          => 1,
						'fallback_cb'    => 'lihan_footer_menu_fallback',
					]
				);
				?>
			</div>
		</div>

		<div class="lh-footer__col lh-footer__col--4">
			<?php $lihan_heading( 4 ); ?>
			<div class="lh-footer__panel lh-footer__panel--static">
				<?php
				lihan_contact_list( 'lh-contact--footer', true );
				lihan_social_links( 'lh-social--footer', 18 );
				?>
				<div class="lh-footer__hours"><?php lihan_render_opening_hours(); ?></div>
			</div>
		</div>
	</div>
</div>
