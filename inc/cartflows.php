<?php
/**
 * CartFlows compatibility.
 *
 * CartFlows step pages (checkout, landing, upsell, thank-you…) are focused,
 * distraction-free funnels, so the theme switches off its own extras there.
 *
 * Filters this file hooks into (priority 20). Each returns false on step pages
 * and passes the incoming value through everywhere else. The theme code that
 * applies them is noted on the right:
 *
 *   lihan_show_floating_chat  (bool) Floating WhatsApp/Messenger button.
 *                             A: lihan_show_floating_chat() in inc/template-tags.php.
 *   lihan_show_cart_drawer    (bool) Off-canvas mini cart #lh-cart-drawer.
 *                             C: inc/woocommerce.php must wrap the drawer markup in
 *                             apply_filters( 'lihan_show_cart_drawer', true ).
 *   lihan_header_sticky       (bool) Sticky main header + nav.
 *                             A: lihan_is_header_sticky() in inc/setup.php.
 *   lihan_sticky_header       (bool) Alias of lihan_header_sticky, for templates that use it.
 *   lihan_show_breadcrumbs    (bool) lihan_breadcrumbs() output (A: inc/template-tags.php).
 *   lihan_show_page_title     (bool) Page title in template-parts/content-page.php (A).
 *                             inc/elementor.php also returns false here when an
 *                             Elementor page has "Hide Title" switched on.
 *
 * Filters this file applies:
 *
 *   lihan_is_cartflows_step   (bool $is_step) Override the step-page detection.
 *
 * WordPress filters also hooked on step pages (safety net for templates that read
 * the options directly): theme_mod_header_sticky and theme_mod_show_floating_chat
 * return false.
 *
 * Detection: lihan_cartflows_is_step() (canonical, reads the main query so product
 * loops cannot change the answer, and also covers CartFlows' global checkout
 * override, which swaps the step post into the WooCommerce checkout page at wp:0).
 * inc/setup.php defines the shared lihan_is_cartflows_step(); the copy below is
 * only a fallback when that file has not defined it.
 *
 * Body classes on step pages: lh-cf-step, lh-cf-template-{cartflows-default|
 * cartflows-canvas|instant-checkout|theme}, and lh-cf-themed when the step (or its
 * flow) does not use CartFlows' own design settings. assets/css/cartflows.css is
 * scoped to these classes and loaded only on step pages.
 *
 * Options: the theme writes no CartFlows option (the checkout primary colour comes
 * from cartflows.css through --wcf-primary-color under .lh-cf-themed), so the
 * user's CartFlows settings are never overwritten.
 *
 * Template notes (CartFlows 3.3, modules/flow/classes/class-cartflows-step-post-type.php):
 * - 'cartflows-default' ("CartFlows — Boxed"), 'cartflows-canvas' ("Template for
 *   Page Builders") and 'instant-checkout' (the instant checkout layout, set through
 *   the cartflows_page_template filter) render their own HTML document WITHOUT the
 *   theme header/footer,
 *   and CartFlows dequeues every stylesheet/script whose URL is inside the theme
 *   (Cartflows_Frontend::remove_theme_styles at wp_enqueue_scripts:9999). wp_footer()
 *   still fires, which is why footer extras must respect the filters above.
 * - Any other value (the theme's default template) loads the theme's page.php, i.e.
 *   the normal theme header and footer.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether CartFlows is active.
 */
function lihan_cartflows_active(): bool {
	return defined( 'CARTFLOWS_VER' ) && function_exists( 'wcf' );
}

/**
 * Whether the current request renders a CartFlows step.
 *
 * Also true on the WooCommerce checkout page when CartFlows' "global checkout"
 * override swaps in a step post (it replaces the main query's post at `wp`:0).
 * The result is cached once `template_redirect` has run, when the answer is final.
 */
function lihan_cartflows_is_step(): bool {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached;
	}

	$is_step = false;

	if ( lihan_cartflows_active() && ! is_admin() && did_action( 'wp' ) ) {
		$step_type = defined( 'CARTFLOWS_STEP_POST_TYPE' ) ? CARTFLOWS_STEP_POST_TYPE : 'cartflows_step';

		if ( is_singular( $step_type ) ) {
			$is_step = true;
		} else {
			// Global checkout override: CartFlows replaces the main query's post.
			// Read the main query (not the loop global $post) so product loops cannot flip the result.
			$main_post = isset( $GLOBALS['wp_the_query'] ) && $GLOBALS['wp_the_query'] instanceof WP_Query ? $GLOBALS['wp_the_query']->post : null;

			if ( $main_post instanceof WP_Post ) {
				$is_step = $step_type === $main_post->post_type;
			}
		}
	}

	/**
	 * Filters whether the current request is a CartFlows step page.
	 *
	 * @param bool $is_step Detected value.
	 */
	$is_step = (bool) apply_filters( 'lihan_is_cartflows_step', $is_step );

	if ( did_action( 'template_redirect' ) ) {
		$cached = $is_step;
	}

	return $is_step;
}

if ( ! function_exists( 'lihan_is_cartflows_step' ) ) {
	/**
	 * Shared alias (inc/setup.php may define its own version first).
	 */
	function lihan_is_cartflows_step(): bool {
		return lihan_cartflows_is_step();
	}
}

/**
 * ID of the step being rendered (0 when none).
 */
function lihan_cartflows_step_id(): int {
	if ( ! lihan_cartflows_is_step() ) {
		return 0;
	}

	$main_post = isset( $GLOBALS['wp_the_query'] ) && $GLOBALS['wp_the_query'] instanceof WP_Query ? $GLOBALS['wp_the_query']->post : null;

	return $main_post instanceof WP_Post ? (int) $main_post->ID : (int) get_queried_object_id();
}

/**
 * The step's page template: 'cartflows-default', 'cartflows-canvas',
 * 'instant-checkout' (CartFlows' instant layout, also its own document) or 'theme'.
 */
function lihan_cartflows_step_template(): string {
	$step_id = lihan_cartflows_step_id();

	if ( ! $step_id ) {
		return '';
	}

	$template = class_exists( 'Cartflows_Helper' ) && method_exists( 'Cartflows_Helper', 'get_current_page_template' )
		? (string) Cartflows_Helper::get_current_page_template( $step_id )
		: (string) get_post_meta( $step_id, '_wp_page_template', true );

	return in_array( $template, [ 'cartflows-default', 'cartflows-canvas', 'instant-checkout' ], true ) ? $template : 'theme';
}

/**
 * Whether the step uses CartFlows' own design settings (then the theme does not
 * set CartFlows' colour variables, so the user's choices always win).
 */
function lihan_cartflows_step_has_own_design(): bool {
	$step_id = lihan_cartflows_step_id();

	if ( ! $step_id ) {
		return false;
	}

	$enabled = get_post_meta( $step_id, 'wcf-enable-design-settings', true );

	if ( '' === $enabled ) {
		// Same fallback CartFlows uses (Cartflows_Default_Meta::get_show_design_meta_value()).
		$enabled = get_option( 'cartflows-legacy-meta-show-design-options', false ) ? 'yes' : 'no';
	}

	if ( 'yes' === $enabled ) {
		return true;
	}

	$flow_id = (int) get_post_meta( $step_id, 'wcf-flow-id', true );

	return $flow_id && 'yes' === get_post_meta( $flow_id, 'wcf-enable-gcp-styling', true );
}

/**
 * Returns false on step pages; used for every "show theme extra" filter.
 *
 * @param mixed $value Incoming value.
 * @return mixed
 */
function lihan_cartflows_disable_on_step( $value ) {
	return lihan_cartflows_is_step() ? false : $value;
}

if ( ! lihan_cartflows_active() ) {
	return;
}

foreach ( [ 'lihan_show_floating_chat', 'lihan_show_cart_drawer', 'lihan_header_sticky', 'lihan_sticky_header', 'lihan_show_breadcrumbs', 'lihan_show_page_title' ] as $lihan_cf_filter ) {
	add_filter( $lihan_cf_filter, 'lihan_cartflows_disable_on_step', 20 );
}
unset( $lihan_cf_filter );

// Safety net for templates that read the options directly.
add_filter( 'theme_mod_header_sticky', 'lihan_cartflows_disable_on_step', 20 );
add_filter( 'theme_mod_show_floating_chat', 'lihan_cartflows_disable_on_step', 20 );

/**
 * Body classes for step pages.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function lihan_cartflows_body_class( array $classes ): array {
	if ( ! lihan_cartflows_is_step() ) {
		return $classes;
	}

	$classes[] = 'lh-cf-step';
	$classes[] = 'lh-cf-template-' . sanitize_html_class( lihan_cartflows_step_template() );

	if ( ! lihan_cartflows_step_has_own_design() ) {
		$classes[] = 'lh-cf-themed';
	}

	return $classes;
}
add_filter( 'body_class', 'lihan_cartflows_body_class' );

/**
 * Enqueue the CartFlows stylesheet on step pages.
 *
 * Runs after CartFlows' remove_theme_styles (priority 9999), which deregisters
 * every theme asset on its own templates; registering afterwards keeps ours.
 * When main.css was removed, the design tokens and font are provided here.
 */
function lihan_cartflows_enqueue_assets(): void {
	if ( ! lihan_cartflows_is_step() ) {
		return;
	}

	$has_main = wp_style_is( 'lihan-main', 'enqueued' );

	if ( ! $has_main && ! lihan_cartflows_montserrat_enqueued() ) {
		wp_enqueue_style(
			'lihan-cartflows-font',
			'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap',
			[],
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google Fonts URL must stay unversioned.
		);
	}

	$version = defined( 'LIHAN_VERSION' ) ? LIHAN_VERSION : '1.0.0';
	$file    = LIHAN_DIR . '/assets/css/cartflows.css';

	if ( file_exists( $file ) ) {
		$version .= '.' . filemtime( $file );
	}

	wp_enqueue_style( 'lihan-cartflows', LIHAN_URI . '/assets/css/cartflows.css', $has_main ? [ 'lihan-main' ] : [], $version );

	if ( ! $has_main ) {
		$tokens = lihan_cartflows_token_css();

		if ( '' !== $tokens ) {
			wp_add_inline_style( 'lihan-cartflows', $tokens );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'lihan_cartflows_enqueue_assets', 10000 );

/**
 * Whether a Montserrat Google Fonts stylesheet is already queued.
 */
function lihan_cartflows_montserrat_enqueued(): bool {
	$styles = wp_styles();

	foreach ( $styles->queue as $handle ) {
		$src = isset( $styles->registered[ $handle ] ) ? (string) $styles->registered[ $handle ]->src : '';

		if ( '' !== $src && false !== stripos( $src, 'Montserrat' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Customizer colour tokens as CSS, for step templates where main.css is removed.
 */
function lihan_cartflows_token_css(): string {
	if ( function_exists( 'lihan_customizer_css' ) ) {
		return (string) lihan_customizer_css();
	}

	if ( ! function_exists( 'lihan_get_option' ) ) {
		return '';
	}

	$map = [
		'--lh-primary' => 'color_primary',
		'--lh-accent'  => 'color_accent',
		'--lh-gold'    => 'color_gold',
		'--lh-text'    => 'color_text',
		'--lh-bg-alt'  => 'color_bg_alt',
	];

	$declarations = '';

	foreach ( $map as $property => $option ) {
		$color = sanitize_hex_color( (string) lihan_get_option( $option ) );

		if ( $color ) {
			$declarations .= $property . ':' . $color . ';';
		}
	}

	return '' === $declarations ? '' : ':root{' . $declarations . '}';
}
