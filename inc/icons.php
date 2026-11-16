<?php
/**
 * Inline SVG icon set (24×24). Stroke icons use currentColor; brand marks are simple filled shapes.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icon definitions: name => [ 'type' => 'stroke'|'fill', 'body' => inner SVG markup ].
 *
 * @return array<string,array{type:string,body:string}>
 */
function lihan_icon_definitions(): array {
	static $icons = null;
	if ( null !== $icons ) {
		return $icons;
	}

	$stroke = static fn( string $body ): array => [
		'type' => 'stroke',
		'body' => $body,
	];
	$fill   = static fn( string $body ): array => [
		'type' => 'fill',
		'body' => $body,
	];

	$star = '12 3 14.8 8.8 21 9.6 16.5 14 17.6 20.2 12 17.3 6.4 20.2 7.5 14 3 9.6 9.2 8.8';

	$icons = [
		// Interface.
		'search'        => $stroke( '<circle cx="11" cy="11" r="7"/><path d="M16.2 16.2 21 21"/>' ),
		'user'          => $stroke( '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.2 3.6-7 8-7s8 2.8 8 7"/>' ),
		'heart'         => $stroke( '<path d="M12 20.3s-7.4-4.5-9.1-9.3C1.7 7.5 4 4 7.4 4c2 0 3.6 1.1 4.6 2.7C13 5.1 14.6 4 16.6 4 20 4 22.3 7.5 21.1 11c-1.7 4.8-9.1 9.3-9.1 9.3z"/>' ),
		'bag'           => $stroke( '<path d="M5 8h14l-1.1 13H6.1L5 8z"/><path d="M9 10.5V6.5a3 3 0 0 1 6 0v4"/>' ),
		'menu'          => $stroke( '<path d="M3 6h18M3 12h18M3 18h18"/>' ),
		'close'         => $stroke( '<path d="M6 6l12 12M18 6 6 18"/>' ),
		'chevron-left'  => $stroke( '<path d="m15 18-6-6 6-6"/>' ),
		'chevron-right' => $stroke( '<path d="m9 6 6 6-6 6"/>' ),
		'chevron-down'  => $stroke( '<path d="m6 9 6 6 6-6"/>' ),
		'chevron-up'    => $stroke( '<path d="m6 15 6-6 6 6"/>' ),
		'plus'          => $stroke( '<path d="M12 5v14M5 12h14"/>' ),
		'minus'         => $stroke( '<path d="M5 12h14"/>' ),
		'arrow-right'   => $stroke( '<path d="M5 12h14M13 6l6 6-6 6"/>' ),
		'arrow-left'    => $stroke( '<path d="M19 12H5M11 6l-6 6 6 6"/>' ),
		'check'         => $stroke( '<path d="m5 12.5 4.5 4.5L19 7"/>' ),
		'grid'          => $stroke( '<rect x="4" y="4" width="6.5" height="6.5"/><rect x="13.5" y="4" width="6.5" height="6.5"/><rect x="4" y="13.5" width="6.5" height="6.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5"/>' ),
		'filter'        => $stroke( '<path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/>' ),
		'play'          => $stroke( '<path d="M8 5.5v13l10.5-6.5L8 5.5z"/>' ),
		'refresh'       => $stroke( '<path d="M20 11a8 8 0 0 0-14.6-4.4L4 8"/><path d="M4 4v4h4"/><path d="M4 13a8 8 0 0 0 14.6 4.4L20 16"/><path d="M20 20v-4h-4"/>' ),
		'share'         => $stroke( '<circle cx="18" cy="5.5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="18.5" r="2.5"/><path d="m8.2 10.8 7.6-4.1M8.2 13.2l7.6 4.1"/>' ),
		'eye'           => $stroke( '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>' ),
		'ruler'         => $stroke( '<path d="m3 16.5 13.5-13.5 4.5 4.5L7.5 21 3 16.5z"/><path d="m7 12.5 2 2M10 9.5l2 2M13 6.5l2 2"/>' ),
		'info'          => $stroke( '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5v.01"/>' ),

		// Contact.
		'phone'         => $stroke( '<path d="M21 16.5v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 1.1 3.8 2 2 0 0 1 3.1 1.5h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L7.1 9.4a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2.1z"/>' ),
		'mail'          => $stroke( '<rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5"/>' ),
		'clock'         => $stroke( '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/>' ),
		'map-pin'       => $stroke( '<path d="M12 21s-7-6.1-7-11.5a7 7 0 0 1 14 0C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>' ),

		// Ratings.
		'star'          => $stroke( '<polygon points="' . $star . '"/>' ),
		'star-filled'   => [
			'type' => 'stroke',
			'body' => '<polygon points="' . $star . '" fill="currentColor"/>',
		],

		// Store promises.
		'truck'         => $stroke( '<path d="M2 6h12v11H2zM14 10h4.2l2.8 3.4V17h-7"/><circle cx="6" cy="18" r="1.8"/><circle cx="17.5" cy="18" r="1.8"/>' ),
		'fabric'        => $stroke( '<path d="M6 3.5a2.5 2.5 0 0 0-2.5 2.5v12A2.5 2.5 0 0 0 6 20.5h0A2.5 2.5 0 0 0 8.5 18V6A2.5 2.5 0 0 0 6 3.5z"/><path d="M6 3.5h12.5v13l-4 4H6"/><path d="M14.5 20.5v-4h4"/><path d="M11.5 8h4M11.5 11.5h4"/>' ),
		'design'        => $stroke( '<path d="M4 20V4l16 16H4z"/><path d="M8 16v-3.8l3.8 3.8H8z"/><path d="M4 8h2M4 12h2"/>' ),
		'hand-heart'    => $stroke( '<path d="M12 10.5S8.5 8.4 8.5 6a2 2 0 0 1 3.5-1.3A2 2 0 0 1 15.5 6c0 2.4-3.5 4.5-3.5 4.5z"/><path d="M2.5 14h3.2l3.6 1.3h2.9a1.5 1.5 0 0 1 0 3H9"/><path d="m12.2 18.3 5.4-2.8a1.6 1.6 0 0 1 2 2.4L14.5 21.5H6.5l-4-1.5"/>' ),
		'reviews'       => $stroke( '<path d="M4 4.5h16v11.5H9.5L4 20.5v-16z"/><path d="m12 7.3.9 1.9 2 .3-1.5 1.4.4 2-1.8-1-1.8 1 .4-2-1.5-1.4 2-.3.9-1.9z"/>' ),
		'shield'        => $stroke( '<path d="M12 3 20 6v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6l8-3z"/><path d="m9 12 2 2 4-4"/>' ),
		'gift'          => $stroke( '<rect x="3" y="8" width="18" height="4"/><path d="M5 12v9h14v-9M12 8v13"/><path d="M12 8S10.5 3.5 8 3.8C6.3 4 6.4 8 12 8zM12 8s1.5-4.5 4-4.2c1.7.2 1.6 4.2-4 4.2z"/>' ),
		'cod'           => $stroke( '<rect x="2" y="6" width="20" height="12" rx="1.5"/><circle cx="12" cy="12" r="2.6"/><path d="M5.5 9.5v5M18.5 9.5v5"/>' ),

		// Brands (generic, simplified marks).
		'facebook'      => $fill( '<path d="M13.6 21.5v-8h2.7l.4-3.2h-3.1V8.3c0-.9.3-1.6 1.6-1.6h1.7V3.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.1 1.5-4.1 4.2v2.3H7.5v3.2h2.8v8h3.3z"/>' ),
		'instagram'     => $stroke( '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r=".6" fill="currentColor"/>' ),
		'youtube'       => $fill( '<path fill-rule="evenodd" d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4a2.5 2.5 0 0 0-1.8 1.8C2 8.8 2 12 2 12s0 3.2.4 4.8a2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8c.4-1.6.4-4.8.4-4.8s0-3.2-.4-4.8zM10 15V9l5.2 3-5.2 3z"/>' ),
		'tiktok'        => $fill( '<path d="M16.6 3c.3 2.2 1.7 3.8 3.9 4v3.2a7.4 7.4 0 0 1-3.9-1.2v6.4a5.7 5.7 0 1 1-5.7-5.7l.6.1v3.3a2.5 2.5 0 1 0 1.9 2.4V3h3.2z"/>' ),
		'x'             => $fill( '<path d="M17.8 3h3.1l-6.8 7.7 8 10.3h-6.2l-4.9-6.3L5.4 21H2.3l7.2-8.2L1.8 3h6.4l4.4 5.8L17.8 3zm-1.1 16.2h1.7L7.4 4.7H5.6l11.1 14.5z"/>' ),
		'whatsapp'      => [
			'type' => 'fill',
			'body' => '<path d="M3.6 20.4 4.9 16A8.5 8.5 0 1 1 8.1 19.2L3.6 20.4z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9.3 7.7c-.2-.4-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.3.3-.9.9-.9 2.2s.9 2.5 1 2.7c.1.2 1.8 2.9 4.5 3.9 2.2.9 2.7.7 3.2.7.5-.1 1.5-.6 1.8-1.2.2-.6.2-1.1.1-1.2l-.5-.3-1.7-.8c-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1-.3-.1-1.1-.4-2-1.3-.8-.7-1.3-1.5-1.4-1.8-.2-.3 0-.4.1-.5l.4-.5.3-.4v-.5l-.8-1.9z"/>',
		],
		'messenger'     => $fill( '<path d="M12 2.5c-5.3 0-9.5 3.9-9.5 8.9 0 2.8 1.3 5.2 3.4 6.8v3.3l3.1-1.7c.9.2 1.9.4 3 .4 5.3 0 9.5-3.9 9.5-8.8S17.3 2.5 12 2.5zm1 11.9-2.4-2.6-4.7 2.6 5.2-5.5 2.5 2.6 4.6-2.6-5.2 5.5z"/>' ),
	];

	/**
	 * Filters the icon set. Add entries as name => [ 'type' => 'stroke'|'fill', 'body' => '<path …/>' ].
	 *
	 * @param array $icons Icon definitions.
	 */
	$icons = (array) apply_filters( 'lihan_icons', $icons );

	return $icons;
}

/**
 * Return an inline SVG icon.
 *
 * @param string $name  Icon name (see lihan_icon_definitions()).
 * @param int    $size  Width/height in pixels.
 * @param string $class Extra CSS classes.
 * @return string SVG markup, or '' for an unknown icon.
 */
function lihan_icon( string $name, int $size = 20, string $class = '' ): string {
	$icons = lihan_icon_definitions();
	if ( ! isset( $icons[ $name ]['body'] ) ) {
		return '';
	}

	$icon    = $icons[ $name ];
	$size    = max( 1, $size );
	$classes = trim( 'lh-icon lh-icon--' . sanitize_html_class( $name ) . ' ' . $class );

	if ( 'fill' === ( $icon['type'] ?? 'stroke' ) ) {
		$paint = 'fill="currentColor"';
	} else {
		$paint = 'fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"';
	}

	return sprintf(
		'<svg class="%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" %3$s aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">%4$s</svg>',
		esc_attr( $classes ),
		$size,
		$paint,
		$icon['body'] // Static, trusted markup defined above.
	);
}

/**
 * Allowed SVG tags/attributes for wp_kses when icon markup has to pass through it.
 *
 * @return array<string,array<string,bool>>
 */
function lihan_svg_kses(): array {
	$common = [
		'fill'            => true,
		'stroke'          => true,
		'stroke-width'    => true,
		'stroke-linecap'  => true,
		'stroke-linejoin' => true,
		'fill-rule'       => true,
		'class'           => true,
	];
	return [
		'svg'      => $common + [
			'width'       => true,
			'height'      => true,
			'viewbox'     => true,
			'aria-hidden' => true,
			'focusable'   => true,
			'xmlns'       => true,
			'role'        => true,
			'aria-label'  => true,
		],
		'path'     => $common + [ 'd' => true ],
		'circle'   => $common + [
			'cx' => true,
			'cy' => true,
			'r'  => true,
		],
		'rect'     => $common + [
			'x'      => true,
			'y'      => true,
			'width'  => true,
			'height' => true,
			'rx'     => true,
		],
		'polygon'  => $common + [ 'points' => true ],
		'polyline' => $common + [ 'points' => true ],
		'text'     => $common + [
			'x'           => true,
			'y'           => true,
			'font-size'   => true,
			'font-weight' => true,
			'font-family' => true,
			'font-style'  => true,
			'text-anchor' => true,
		],
		'title'    => [],
	];
}
