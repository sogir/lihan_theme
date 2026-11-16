<?php
/**
 * Footer bottom bar: copyright and payment badges.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="lh-footer-bottom">
	<div class="lh-container lh-footer-bottom__inner">
		<p class="lh-footer__copyright"><?php lihan_render_copyright(); ?></p>
		<div class="lh-footer__payments"><?php lihan_render_payment_badges(); ?></div>
	</div>
</div>
