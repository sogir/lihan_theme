<?php
/**
 * Mobile search panel that slides down under the header.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="lh-search-drawer" class="lh-search-drawer" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'lihan' ); ?>">
	<div class="lh-container lh-search-drawer__inner">
		<?php
		get_search_form(
			[
				'lihan_products' => true,
				'lihan_variant'  => 'drawer',
				'aria_label'     => __( 'Mobile site search', 'lihan' ),
			]
		);
		?>
		<button type="button" class="lh-header-icon lh-search-drawer__close" data-lh-close>
			<?php echo lihan_icon( 'close', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="lh-visually-hidden"><?php esc_html_e( 'Close search', 'lihan' ); ?></span>
		</button>
	</div>
</div>
