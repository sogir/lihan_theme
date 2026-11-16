<?php
/**
 * Template tags and small rendering helpers shared by all templates.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Branding
 * ---------------------------------------------------------------------- */

/**
 * Echo the site logo linked to the home page.
 *
 * Uses the Custom Logo when set, otherwise the bundled Lihan logo.
 *
 * @param array{variant?:string,class?:string,link?:bool} $args variant 'dark' (default, for light
 *                                                              backgrounds) or 'light' (white logo).
 */
function lihan_logo( array $args = [] ): void {
	$args = wp_parse_args(
		$args,
		[
			'variant' => 'dark',
			'class'   => '',
			'link'    => true,
		]
	);

	$name = get_bloginfo( 'name', 'display' );
	$img  = '';

	if ( 'light' === $args['variant'] ) {
		$footer_logo = absint( lihan_get_option( 'footer_logo' ) );
		if ( $footer_logo && wp_attachment_is_image( $footer_logo ) ) {
			$img = wp_get_attachment_image(
				$footer_logo,
				'medium',
				false,
				[
					'class'   => 'lh-logo__img',
					'alt'     => $name,
					'loading' => 'lazy',
				]
			);
		} else {
			$img = sprintf(
				'<img class="lh-logo__img" src="%1$s" width="720" height="287" alt="%2$s" loading="lazy" decoding="async">',
				esc_url( LIHAN_URI . '/assets/images/logo-white.png' ),
				esc_attr( $name )
			);
		}
	} else {
		$logo_id = absint( get_theme_mod( 'custom_logo' ) );
		if ( $logo_id && wp_attachment_is_image( $logo_id ) ) {
			$alt = trim( (string) get_post_meta( $logo_id, '_wp_attachment_image_alt', true ) );
			$img = wp_get_attachment_image(
				$logo_id,
				'full',
				false,
				[
					'class'         => 'lh-logo__img custom-logo',
					'alt'           => '' !== $alt ? $alt : $name,
					'loading'       => 'eager',
					'fetchpriority' => 'high',
					'sizes'         => '240px',
				]
			);
		} else {
			$img = sprintf(
				'<img class="lh-logo__img" src="%1$s" width="720" height="287" alt="%2$s" decoding="async" fetchpriority="high">',
				esc_url( LIHAN_URI . '/assets/images/logo.png' ),
				esc_attr( $name )
			);
		}
	}

	$classes = trim( 'lh-logo lh-logo--' . sanitize_html_class( (string) $args['variant'] ) . ' ' . $args['class'] );

	if ( $args['link'] ) {
		printf(
			'<a class="%1$s" href="%2$s" rel="home">%3$s</a>',
			esc_attr( $classes ),
			esc_url( home_url( '/' ) ),
			$img // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above / core image markup.
		);
	} else {
		printf( '<span class="%1$s">%2$s</span>', esc_attr( $classes ), $img ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/* -------------------------------------------------------------------------
 * Contact & social
 * ---------------------------------------------------------------------- */

/**
 * Social profiles from the customizer.
 *
 * @return array<string,array{label:string,url:string}>
 */
function lihan_social_profiles(): array {
	$profiles = [
		'facebook'  => __( 'Facebook', 'lihan' ),
		'instagram' => __( 'Instagram', 'lihan' ),
		'youtube'   => __( 'YouTube', 'lihan' ),
		'tiktok'    => __( 'TikTok', 'lihan' ),
	];
	$out = [];
	foreach ( $profiles as $key => $label ) {
		$url = trim( (string) lihan_get_option( 'social_' . $key ) );
		if ( '' !== $url ) {
			$out[ $key ] = [
				'label' => $label,
				'url'   => $url,
			];
		}
	}
	return (array) apply_filters( 'lihan_social_profiles', $out );
}

/**
 * Echo a list of social icon links. Empty profiles are skipped.
 *
 * @param string $class Extra list classes.
 * @param int    $size  Icon size.
 */
function lihan_social_links( string $class = '', int $size = 16 ): void {
	$profiles = lihan_social_profiles();
	if ( ! $profiles ) {
		return;
	}
	echo '<ul class="' . esc_attr( trim( 'lh-social ' . $class ) ) . '">';
	foreach ( $profiles as $key => $profile ) {
		printf(
			'<li><a href="%1$s" target="_blank" rel="noopener noreferrer me">%2$s<span class="lh-visually-hidden">%3$s</span></a></li>',
			esc_url( $profile['url'] ),
			lihan_icon( $key, $size ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG.
			esc_html(
				sprintf(
					/* translators: %s: social network name. */
					__( '%s (opens in a new tab)', 'lihan' ),
					$profile['label']
				)
			)
		);
	}
	echo '</ul>';
}

/**
 * WhatsApp chat URL for a number and optional pre-filled message.
 *
 * @param string $message Optional message text.
 */
function lihan_whatsapp_url( string $message = '' ): string {
	$number = preg_replace( '/\D+/', '', (string) lihan_get_option( 'contact_whatsapp' ) );
	if ( '' === $number ) {
		return '';
	}
	$url = 'https://wa.me/' . $number;
	if ( '' !== $message ) {
		$url .= '?text=' . rawurlencode( $message );
	}
	return $url;
}

/**
 * Contact channels (phone, WhatsApp, Messenger) for headers, drawers and the footer.
 *
 * @return array<int,array{key:string,icon:string,label:string,url:string,value:string}>
 */
function lihan_contact_items(): array {
	$items = [];

	$phone = trim( (string) lihan_get_option( 'contact_phone' ) );
	if ( '' !== $phone ) {
		$items[] = [
			'key'   => 'phone',
			'icon'  => 'phone',
			'label' => __( 'Call us', 'lihan' ),
			'url'   => 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ),
			'value' => $phone,
		];
	}

	$whatsapp = lihan_whatsapp_url();
	if ( '' !== $whatsapp ) {
		$items[] = [
			'key'   => 'whatsapp',
			'icon'  => 'whatsapp',
			'label' => __( 'WhatsApp', 'lihan' ),
			'url'   => $whatsapp,
			'value' => __( 'Chat on WhatsApp', 'lihan' ),
		];
	}

	$messenger = trim( (string) lihan_get_option( 'contact_messenger' ) );
	if ( '' !== $messenger ) {
		$items[] = [
			'key'   => 'messenger',
			'icon'  => 'messenger',
			'label' => __( 'Messenger', 'lihan' ),
			'url'   => $messenger,
			'value' => __( 'Message us on Messenger', 'lihan' ),
		];
	}

	/**
	 * Filters the contact items.
	 *
	 * @param array $items Items with icon, label, url, value and key.
	 */
	return (array) apply_filters( 'lihan_contact_items', $items );
}

/**
 * Echo the contact items as a list (used in the mobile drawer and the footer).
 *
 * @param string $class         Extra list class.
 * @param bool   $include_email Also list the email address when set.
 */
function lihan_contact_list( string $class = '', bool $include_email = false ): void {
	$items = lihan_contact_items();
	$email = $include_email ? sanitize_email( (string) lihan_get_option( 'contact_email' ) ) : '';
	if ( $email ) {
		$items[] = [
			'key'   => 'email',
			'icon'  => 'mail',
			'label' => __( 'Email', 'lihan' ),
			'url'   => 'mailto:' . $email,
			'value' => antispambot( $email ),
		];
	}
	if ( ! $items ) {
		return;
	}
	echo '<ul class="' . esc_attr( trim( 'lh-contact ' . $class ) ) . '">';
	foreach ( $items as $item ) {
		$external = str_starts_with( (string) $item['url'], 'http' );
		printf(
			'<li class="lh-contact__item lh-contact__item--%1$s"><a href="%2$s"%3$s>%4$s<span class="lh-contact__text"><span class="lh-visually-hidden">%5$s: </span>%6$s</span></a></li>',
			esc_attr( (string) $item['key'] ),
			esc_url( (string) $item['url'], [ 'http', 'https', 'tel', 'mailto' ] ),
			$external ? ' target="_blank" rel="noopener noreferrer"' : '',
			lihan_icon( (string) $item['icon'], 18 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG.
			esc_html( (string) $item['label'] ),
			esc_html( (string) $item['value'] )
		);
	}
	echo '</ul>';
}

/* -------------------------------------------------------------------------
 * Store helpers
 * ---------------------------------------------------------------------- */

/**
 * Wishlist page URL when a supported wishlist plugin (YITH, TI) is active, else ''.
 */
function lihan_wishlist_url(): string {
	$url = '';
	if ( function_exists( 'YITH_WCWL' ) ) {
		$wcwl = YITH_WCWL();
		if ( is_object( $wcwl ) && method_exists( $wcwl, 'get_wishlist_url' ) ) {
			$url = (string) $wcwl->get_wishlist_url();
		}
	} elseif ( function_exists( 'tinv_url_wishlist_default' ) ) {
		$url = (string) tinv_url_wishlist_default();
	}
	return (string) apply_filters( 'lihan_wishlist_url', $url );
}

/**
 * Account URL: WooCommerce My Account page, else profile / login.
 */
function lihan_account_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'myaccount' );
		if ( $url ) {
			return $url;
		}
	}
	return is_user_logged_in() ? admin_url( 'profile.php' ) : wp_login_url();
}

/**
 * Number of items in the WooCommerce cart (0 without WooCommerce or outside the front end).
 */
function lihan_cart_count(): int {
	if ( ! function_exists( 'WC' ) ) {
		return 0;
	}
	$wc = WC();
	if ( ! isset( $wc->cart ) || ! is_object( $wc->cart ) ) {
		return 0;
	}
	return (int) $wc->cart->get_cart_contents_count();
}

/**
 * Cart count badge markup. WooCommerce fragments should replace `.lh-cart-count` with this output.
 *
 * @param int|null $count Count to show (defaults to the current cart).
 */
function lihan_cart_count_markup( ?int $count = null ): string {
	$count = null === $count ? lihan_cart_count() : max( 0, $count );
	return sprintf(
		'<span class="lh-cart-count%1$s" data-count="%2$d"><span class="lh-cart-count__num" aria-hidden="true">%2$d</span><span class="lh-visually-hidden">%3$s</span></span>',
		0 === $count ? ' is-empty' : '',
		$count,
		esc_html(
			sprintf(
				/* translators: %d: number of items in the cart. */
				_n( '%d item', '%d items', $count, 'lihan' ),
				$count
			)
		)
	);
}

/* -------------------------------------------------------------------------
 * Header & footer partials (also used as selective-refresh callbacks)
 * ---------------------------------------------------------------------- */

/**
 * Announcement messages as an array of non-empty lines.
 *
 * @return array<int,string>
 */
function lihan_announcement_messages(): array {
	$raw = (string) lihan_get_option( 'announcement_messages' );
	return array_values( array_filter( array_map( 'trim', preg_split( '/\R/u', $raw ) ?: [] ), 'strlen' ) );
}

/**
 * Echo the rotating announcement list.
 */
function lihan_render_announcements(): void {
	$messages = lihan_announcement_messages();
	if ( ! $messages ) {
		return;
	}
	echo '<ul class="lh-announce" data-lh-announce aria-label="' . esc_attr__( 'Announcements', 'lihan' ) . '">';
	foreach ( $messages as $i => $message ) {
		printf(
			'<li class="lh-announce__item%1$s">%2$s</li>',
			0 === $i ? ' is-active' : '',
			esc_html( $message )
		);
	}
	echo '</ul>';
}

/**
 * Echo the footer about text.
 */
function lihan_render_footer_about(): void {
	$about = (string) lihan_get_option( 'footer_about' );
	if ( '' !== trim( $about ) ) {
		echo wp_kses_post( wpautop( $about ) );
	}
}

/**
 * Echo the copyright line.
 */
function lihan_render_copyright(): void {
	$text = (string) lihan_get_option( 'footer_copyright' );
	$text = str_replace( [ '{year}', '{site}' ], [ wp_date( 'Y' ), get_bloginfo( 'name' ) ], $text );
	echo wp_kses_post( $text );
}

/**
 * Echo the opening-hours heading and table.
 */
function lihan_render_opening_hours(): void {
	$raw  = (string) lihan_get_option( 'opening_hours' );
	$rows = [];
	foreach ( preg_split( '/\R/u', $raw ) ?: [] as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts  = array_map( 'trim', explode( '|', $line, 2 ) );
		$rows[] = [ $parts[0], $parts[1] ?? '' ];
	}
	if ( ! $rows ) {
		return;
	}
	echo '<h3 class="lh-footer__subtitle">' . esc_html__( 'Opening Hours', 'lihan' ) . '</h3>';
	echo '<table class="lh-hours"><tbody>';
	foreach ( $rows as [ $days, $hours ] ) {
		printf( '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>', esc_html( $days ), esc_html( $hours ) );
	}
	echo '</tbody></table>';
}

/**
 * SVG badge for a payment method (generic marks, not official logos).
 *
 * @param string $slug Payment method slug.
 */
function lihan_payment_badge( string $slug ): string {
	$choices = lihan_payment_method_choices();
	if ( ! isset( $choices[ $slug ] ) ) {
		return '';
	}

	$text = static fn( string $label, string $color, float $size, string $extra = '' ): string => sprintf(
		'<text x="19" y="15.5" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="%1$s" fill="%2$s"%3$s>%4$s</text>',
		$size,
		$color,
		$extra,
		$label
	);

	$marks = [
		'cod'        => $text( 'COD', '#0b2f29', 9.5 ),
		'bkash'      => $text( 'bKash', '#e2136e', 8.5 ),
		'nagad'      => $text( 'Nagad', '#ec5a24', 8.5 ),
		'rocket'     => $text( 'Rocket', '#8c3494', 8 ),
		'visa'       => $text( 'VISA', '#1a1f71', 10, ' font-style="italic"' ),
		'mastercard' => '<circle cx="15.5" cy="12" r="6.5" fill="#eb001b"/><circle cx="22.5" cy="12" r="6.5" fill="#f79e1b" fill-opacity=".85"/>',
	];

	return sprintf(
		'<svg class="lh-payment-badge lh-payment-badge--%1$s" width="38" height="24" viewBox="0 0 38 24" role="img" aria-label="%2$s" xmlns="http://www.w3.org/2000/svg"><title>%2$s</title><rect width="38" height="24" rx="3" fill="#fff"/>%3$s</svg>',
		esc_attr( $slug ),
		esc_attr( $choices[ $slug ] ),
		$marks[ $slug ]
	);
}

/**
 * Echo the payment badges list.
 */
function lihan_render_payment_badges(): void {
	$slugs = array_filter( explode( ',', (string) lihan_get_option( 'payment_methods' ) ) );
	$items = '';
	foreach ( $slugs as $slug ) {
		$badge = lihan_payment_badge( sanitize_key( $slug ) );
		if ( '' !== $badge ) {
			$items .= '<li>' . $badge . '</li>';
		}
	}
	if ( '' === $items ) {
		return;
	}
	echo '<ul class="lh-payments" aria-label="' . esc_attr__( 'Payment methods we accept', 'lihan' ) . '">' . $items . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped static SVG.
}

/**
 * Footer column heading text.
 *
 * @param int $column Column number (2–4).
 */
function lihan_footer_col_title( int $column ): string {
	return (string) lihan_get_option( 'footer_col' . $column . '_title' );
}

/** Selective-refresh callback. */
function lihan_render_footer_col2_title(): void {
	echo esc_html( lihan_footer_col_title( 2 ) );
}

/** Selective-refresh callback. */
function lihan_render_footer_col3_title(): void {
	echo esc_html( lihan_footer_col_title( 3 ) );
}

/** Selective-refresh callback. */
function lihan_render_footer_col4_title(): void {
	echo esc_html( lihan_footer_col_title( 4 ) );
}

/**
 * Whether the floating chat button should render on this request.
 */
function lihan_show_floating_chat(): bool {
	$show = (bool) lihan_get_option( 'show_floating_chat' );
	if ( $show && ( lihan_is_cartflows_step() || ( function_exists( 'is_checkout' ) && is_checkout() ) ) ) {
		$show = false;
	}
	if ( $show && '' === lihan_whatsapp_url() && '' === trim( (string) lihan_get_option( 'contact_messenger' ) ) ) {
		$show = false;
	}
	return (bool) apply_filters( 'lihan_show_floating_chat', $show );
}

/* -------------------------------------------------------------------------
 * Menus
 * ---------------------------------------------------------------------- */

/**
 * Button that expands a sub-menu in the mobile drawer.
 *
 * @param string $title Parent item title (for the accessible name).
 * @param bool   $open  Whether the sub-menu starts expanded.
 */
function lihan_submenu_toggle( string $title, bool $open = false ): string {
	return sprintf(
		'<button type="button" class="lh-submenu-toggle" aria-expanded="%1$s"><span class="lh-visually-hidden">%2$s</span>%3$s</button>',
		$open ? 'true' : 'false',
		esc_html(
			sprintf(
				/* translators: %s: menu item title. */
				__( 'Show submenu for %s', 'lihan' ),
				wp_strip_all_tags( $title )
			)
		),
		lihan_icon( 'plus', 16, 'lh-submenu-toggle__plus' ) . lihan_icon( 'minus', 16, 'lh-submenu-toggle__minus' )
	);
}

/**
 * Add sub-menu toggles in the mobile drawer and a flyout chevron for deep desktop items.
 *
 * @param string   $output Item output.
 * @param WP_Post  $item   Menu item.
 * @param int      $depth  Depth.
 * @param stdClass $args   wp_nav_menu() arguments.
 */
function lihan_nav_menu_start_el( $output, $item, $depth, $args ) {
	$context = is_object( $args ) && isset( $args->lihan_context ) ? (string) $args->lihan_context : '';
	if ( '' === $context ) {
		return $output;
	}
	$classes      = is_array( $item->classes ?? null ) ? $item->classes : [];
	$has_children = in_array( 'menu-item-has-children', $classes, true );
	if ( ! $has_children ) {
		return $output;
	}
	if ( 'mobile' === $context ) {
		$open    = (bool) array_intersect( [ 'current-menu-ancestor', 'current-menu-parent', 'current_page_ancestor' ], $classes );
		$output .= lihan_submenu_toggle( (string) $item->title, $open );
	}
	return $output;
}
add_filter( 'walker_nav_menu_start_el', 'lihan_nav_menu_start_el', 10, 4 );

/**
 * Chevron for desktop items that open a flyout (depth ≥ 1 with children).
 *
 * @param string   $title Item title.
 * @param WP_Post  $item  Menu item.
 * @param stdClass $args  wp_nav_menu() arguments.
 * @param int      $depth Depth.
 */
function lihan_nav_menu_item_title( $title, $item, $args, $depth ) {
	if ( is_object( $args ) && 'primary' === ( $args->lihan_context ?? '' ) && $depth >= 1 && in_array( 'menu-item-has-children', (array) ( $item->classes ?? [] ), true ) ) {
		$title .= lihan_icon( 'chevron-right', 12, 'lh-menu__flyout-icon' );
	}
	return $title;
}
add_filter( 'nav_menu_item_title', 'lihan_nav_menu_item_title', 10, 4 );

/**
 * Avoid duplicate element IDs when the same menu renders in the desktop nav and the mobile drawer.
 *
 * @param string   $id    Item ID attribute.
 * @param WP_Post  $item  Menu item.
 * @param stdClass $args  wp_nav_menu() arguments.
 */
function lihan_nav_menu_item_id( $id, $item, $args ) {
	if ( is_object( $args ) && 'mobile' === ( $args->lihan_context ?? '' ) ) {
		return '';
	}
	return $id;
}
add_filter( 'nav_menu_item_id', 'lihan_nav_menu_item_id', 10, 3 );

/**
 * Fallback for the main/mobile menu: top-level product categories (WooCommerce) or pages,
 * so the header never looks empty before a menu is assigned.
 *
 * @param array<string,mixed> $args wp_nav_menu() arguments.
 * @return string|void
 */
function lihan_menu_fallback( $args = [] ) {
	$args    = (array) $args;
	$context = (string) ( $args['lihan_context'] ?? 'primary' );
	$items   = [];

	if ( taxonomy_exists( 'product_cat' ) ) {
		$exclude = array_filter( [ (int) get_option( 'default_product_cat' ) ] );
		$terms   = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => false,
				'orderby'    => 'menu_order',
				'number'     => 8,
				'exclude'    => $exclude,
			]
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$children = get_terms(
					[
						'taxonomy'   => 'product_cat',
						'parent'     => $term->term_id,
						'hide_empty' => false,
						'orderby'    => 'menu_order',
						'number'     => 12,
					]
				);
				$link     = get_term_link( $term );
				$items[]  = [
					'title'    => $term->name,
					'url'      => is_wp_error( $link ) ? '' : $link,
					'current'  => is_tax( 'product_cat', $term->term_id ),
					'children' => is_wp_error( $children ) ? [] : array_map(
						static function ( WP_Term $child ): array {
							$child_link = get_term_link( $child );
							return [
								'title'   => $child->name,
								'url'     => is_wp_error( $child_link ) ? '' : $child_link,
								'current' => is_tax( 'product_cat', $child->term_id ),
							];
						},
						$children
					),
				];
			}
		}
	}

	if ( ! $items ) {
		$pages = get_pages(
			[
				'parent'      => 0,
				'sort_column' => 'menu_order,post_title',
				'number'      => 8,
			]
		);
		foreach ( $pages as $page ) {
			$items[] = [
				'title'    => get_the_title( $page ),
				'url'      => get_permalink( $page ),
				'current'  => is_page( $page->ID ),
				'children' => [],
			];
		}
	}

	$menu_class = (string) ( $args['menu_class'] ?? 'menu' );
	$menu_id    = (string) ( $args['menu_id'] ?? '' );
	$html       = '<ul' . ( $menu_id ? ' id="' . esc_attr( $menu_id ) . '"' : '' ) . ' class="' . esc_attr( $menu_class ) . '">';

	foreach ( $items as $item ) {
		if ( '' === $item['url'] ) {
			continue;
		}
		$has_children = ! empty( $item['children'] );
		$classes      = 'menu-item' . ( $has_children ? ' menu-item-has-children' : '' ) . ( $item['current'] ? ' current-menu-item' : '' );
		$html        .= '<li class="' . esc_attr( $classes ) . '">';
		$html        .= '<a href="' . esc_url( $item['url'] ) . '"' . ( $item['current'] ? ' aria-current="page"' : '' ) . '>' . esc_html( $item['title'] ) . '</a>';
		if ( $has_children ) {
			if ( 'mobile' === $context ) {
				$html .= lihan_submenu_toggle( $item['title'] );
			}
			$html .= '<ul class="sub-menu">';
			foreach ( $item['children'] as $child ) {
				if ( '' === $child['url'] ) {
					continue;
				}
				$html .= '<li class="menu-item' . ( $child['current'] ? ' current-menu-item' : '' ) . '"><a href="' . esc_url( $child['url'] ) . '"' . ( $child['current'] ? ' aria-current="page"' : '' ) . '>' . esc_html( $child['title'] ) . '</a></li>';
			}
			$html .= '</ul>';
		}
		$html .= '</li>';
	}
	$html .= '</ul>';

	if ( isset( $args['echo'] ) && ! $args['echo'] ) {
		return $html;
	}
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
}

/**
 * Fallback for the footer menus until menus are assigned.
 *
 * footer-1 lists a few pages; footer-2 lists the store's account/cart pages (or the privacy page).
 *
 * @param array<string,mixed> $args wp_nav_menu() arguments.
 * @return string|void
 */
function lihan_footer_menu_fallback( $args = [] ) {
	$args  = (array) $args;
	$links = [];

	if ( 'footer-2' === ( $args['theme_location'] ?? '' ) ) {
		if ( function_exists( 'wc_get_page_id' ) ) {
			foreach ( [
				'myaccount' => __( 'My Account', 'lihan' ),
				'shop'      => __( 'Shop', 'lihan' ),
				'cart'      => __( 'Cart', 'lihan' ),
				'checkout'  => __( 'Checkout', 'lihan' ),
			] as $page => $label ) {
				$id = wc_get_page_id( $page );
				if ( $id > 0 && 'publish' === get_post_status( $id ) ) {
					$links[ get_permalink( $id ) ] = $label;
				}
			}
		}
		$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
		if ( $privacy && 'publish' === get_post_status( $privacy ) ) {
			$links[ get_permalink( $privacy ) ] = get_the_title( $privacy );
		}
	} else {
		$exclude = [ (int) get_option( 'page_on_front' ) ];
		if ( function_exists( 'wc_get_page_id' ) ) {
			foreach ( [ 'shop', 'cart', 'checkout', 'myaccount' ] as $page ) {
				$exclude[] = wc_get_page_id( $page );
			}
		}
		$pages = get_pages(
			[
				'parent'      => 0,
				'sort_column' => 'menu_order,post_title',
				'exclude'     => array_filter( $exclude, static fn( int $id ): bool => $id > 0 ),
				'number'      => 6,
			]
		);
		foreach ( $pages as $page ) {
			$links[ get_permalink( $page ) ] = get_the_title( $page );
		}
	}

	if ( ! $links ) {
		return isset( $args['echo'] ) && ! $args['echo'] ? '' : null;
	}

	$html = '<ul class="' . esc_attr( (string) ( $args['menu_class'] ?? 'menu' ) ) . '">';
	foreach ( $links as $url => $label ) {
		$html .= '<li class="menu-item"><a href="' . esc_url( (string) $url ) . '">' . esc_html( (string) $label ) . '</a></li>';
	}
	$html .= '</ul>';

	if ( isset( $args['echo'] ) && ! $args['echo'] ) {
		return $html;
	}
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
}

/* -------------------------------------------------------------------------
 * Breadcrumbs
 * ---------------------------------------------------------------------- */

/**
 * Echo the breadcrumb trail (WooCommerce's when active, else a simple built-in one).
 */
function lihan_breadcrumbs(): void {
	$show = ! is_front_page() && ! lihan_is_cartflows_step();
	if ( ! apply_filters( 'lihan_show_breadcrumbs', $show ) ) {
		return;
	}

	$separator = '<span class="lh-breadcrumbs__sep" aria-hidden="true">/</span>';

	if ( function_exists( 'woocommerce_breadcrumb' ) ) {
		woocommerce_breadcrumb(
			[
				'delimiter'   => $separator,
				'wrap_before' => '<nav class="lh-breadcrumbs woocommerce-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'lihan' ) . '">',
				'wrap_after'  => '</nav>',
				'home'        => _x( 'Home', 'breadcrumb', 'lihan' ),
			]
		);
		return;
	}

	$crumbs = [ [ _x( 'Home', 'breadcrumb', 'lihan' ), home_url( '/' ) ] ];

	if ( is_home() ) {
		$crumbs[] = [ get_the_title( (int) get_option( 'page_for_posts' ) ) ?: __( 'Blog', 'lihan' ), '' ];
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			if ( 'post' === $post->post_type ) {
				$posts_page = (int) get_option( 'page_for_posts' );
				if ( $posts_page ) {
					$crumbs[] = [ get_the_title( $posts_page ), get_permalink( $posts_page ) ];
				}
				$cats = get_the_category( $post->ID );
				if ( $cats ) {
					$crumbs[] = [ $cats[0]->name, get_category_link( $cats[0] ) ];
				}
			} elseif ( 'page' !== $post->post_type ) {
				$type = get_post_type_object( $post->post_type );
				if ( $type && $type->has_archive ) {
					$crumbs[] = [ $type->labels->name, get_post_type_archive_link( $post->post_type ) ];
				}
			}
			foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor ) {
				$crumbs[] = [ get_the_title( $ancestor ), get_permalink( $ancestor ) ];
			}
			$crumbs[] = [ get_the_title( $post ), '' ];
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, $term->taxonomy );
				if ( $ancestor instanceof WP_Term ) {
					$link     = get_term_link( $ancestor );
					$crumbs[] = [ $ancestor->name, is_wp_error( $link ) ? '' : $link ];
				}
			}
			$crumbs[] = [ $term->name, '' ];
		}
	} elseif ( is_post_type_archive() ) {
		$crumbs[] = [ post_type_archive_title( '', false ), '' ];
	} elseif ( is_author() ) {
		$crumbs[] = [ get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) ), '' ];
	} elseif ( is_date() ) {
		$crumbs[] = [ get_the_archive_title(), '' ];
	} elseif ( is_search() ) {
		/* translators: %s: search query. */
		$crumbs[] = [ sprintf( __( 'Search results for “%s”', 'lihan' ), get_search_query( false ) ), '' ];
	} elseif ( is_404() ) {
		$crumbs[] = [ __( 'Page not found', 'lihan' ), '' ];
	}

	$crumbs = (array) apply_filters( 'lihan_breadcrumb_items', $crumbs );
	$parts  = [];
	$last   = count( $crumbs ) - 1;
	foreach ( array_values( $crumbs ) as $i => [ $label, $url ] ) {
		$parts[] = ( $url && $i !== $last )
			? '<a href="' . esc_url( (string) $url ) . '">' . esc_html( (string) $label ) . '</a>'
			: '<span' . ( $i === $last ? ' aria-current="page"' : '' ) . '>' . esc_html( (string) $label ) . '</span>';
	}

	echo '<nav class="lh-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'lihan' ) . '">' . implode( $separator, $parts ) . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
}

/* -------------------------------------------------------------------------
 * Blog
 * ---------------------------------------------------------------------- */

/**
 * Echo the post date.
 */
function lihan_posted_on(): void {
	$time = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
	if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
		$time = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated lh-visually-hidden" datetime="%3$s">%4$s</time>';
	}
	$time = sprintf(
		$time,
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date() ),
		esc_attr( get_the_modified_date( DATE_W3C ) ),
		esc_html( get_the_modified_date() )
	);
	echo '<span class="lh-meta__date posted-on"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $time . '</a></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
}

/**
 * Echo the post author.
 */
function lihan_posted_by(): void {
	printf(
		'<span class="lh-meta__author byline"><span class="lh-visually-hidden">%1$s </span><span class="author vcard"><a class="url fn n" href="%2$s">%3$s</a></span></span>',
		esc_html__( 'By', 'lihan' ),
		esc_url( get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ),
		esc_html( get_the_author() )
	);
}

/**
 * Echo categories, tags and the edit link for a single post.
 */
function lihan_entry_footer(): void {
	if ( 'post' === get_post_type() ) {
		$cats = get_the_category_list( ', ' );
		if ( $cats ) {
			echo '<p class="lh-entry-footer__cats"><span class="lh-entry-footer__label">' . esc_html__( 'Posted in', 'lihan' ) . '</span> ' . $cats . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core markup.
		}
		$tags = get_the_tag_list( '<ul class="lh-tags"><li>', '</li><li>', '</li></ul>' );
		if ( $tags && ! is_wp_error( $tags ) ) {
			echo $tags; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core markup.
		}
	}
	edit_post_link(
		sprintf(
			/* translators: %s: post title. */
			esc_html__( 'Edit %s', 'lihan' ),
			'<span class="lh-visually-hidden">' . esc_html( get_the_title() ) . '</span>'
		),
		'<p class="lh-edit-link">',
		'</p>'
	);
}

/**
 * Echo the post thumbnail (linked on archives).
 *
 * @param string $size Image size.
 */
function lihan_post_thumbnail( string $size = 'large' ): void {
	if ( post_password_required() || is_attachment() || ! has_post_thumbnail() ) {
		return;
	}
	if ( is_singular() && in_the_loop() && is_main_query() && get_queried_object_id() === get_the_ID() ) {
		echo '<figure class="lh-entry__media">';
		the_post_thumbnail( $size, [ 'loading' => 'eager' ] );
		echo '</figure>';
		return;
	}
	echo '<a class="lh-card__media" href="' . esc_url( get_permalink() ) . '" aria-hidden="true" tabindex="-1">';
	the_post_thumbnail(
		$size,
		[
			'alt'     => the_title_attribute( [ 'echo' => false ] ),
			'loading' => 'lazy',
		]
	);
	echo '</a>';
}

/**
 * Echo numbered pagination for archives.
 */
function lihan_pagination(): void {
	the_posts_pagination(
		[
			'class'              => 'lh-pagination',
			'mid_size'           => 1,
			'prev_text'          => lihan_icon( 'chevron-left', 16 ) . '<span class="lh-visually-hidden">' . esc_html__( 'Previous page', 'lihan' ) . '</span>',
			'next_text'          => '<span class="lh-visually-hidden">' . esc_html__( 'Next page', 'lihan' ) . '</span>' . lihan_icon( 'chevron-right', 16 ),
			'before_page_number' => '<span class="lh-visually-hidden">' . esc_html__( 'Page', 'lihan' ) . ' </span>',
		]
	);
}

/**
 * Archive/page header title + optional description.
 *
 * @param string $title       Title HTML-safe text.
 * @param string $description Optional description HTML.
 */
function lihan_page_header( string $title, string $description = '' ): void {
	echo '<header class="lh-page-header">';
	echo '<h1 class="lh-page-title lh-h2 entry-title">' . wp_kses_post( $title ) . '</h1>';
	if ( '' !== trim( $description ) ) {
		echo '<div class="lh-page-header__desc lh-rte">' . wp_kses_post( $description ) . '</div>';
	}
	echo '</header>';
}

/**
 * Whether a post is built with Elementor (its content is then rendered full-width).
 *
 * @param int $post_id Post ID (defaults to the current post).
 */
function lihan_is_elementor_page( int $post_id = 0 ): bool {
	$post_id = $post_id ?: (int) get_the_ID();
	if ( ! $post_id || ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
		return false;
	}
	$plugin = \Elementor\Plugin::$instance;
	if ( ! isset( $plugin->documents ) ) {
		return false;
	}
	$document = $plugin->documents->get( $post_id );
	return $document && method_exists( $document, 'is_built_with_elementor' ) && $document->is_built_with_elementor();
}
