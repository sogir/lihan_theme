<?php
/**
 * Customizer: "Lihan Theme" panel, option defaults and the lihan_get_option() accessor.
 *
 * Other modules (homepage, WooCommerce) add their own sections to the `lihan_theme`
 * panel and their defaults through the `lihan_defaults` filter.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default values for every theme option.
 *
 * @return array<string,mixed>
 */
function lihan_defaults(): array {
	$defaults = [
		// Colours.
		'color_primary'         => '#0b2f29',
		'color_accent'          => '#e8d3a6',
		'color_gold'            => '#aa791b',
		'color_bg_alt'          => '#f2f3f6',
		'color_bg_warm'         => '#f5efe6',
		'color_text'            => '#111111',

		// Header.
		'announcement_messages' => implode(
			"\n",
			[
				__( 'FREE DELIVERY ON ORDERS OVER ৳5,000', 'lihan' ),
				__( 'CASH ON DELIVERY ALL OVER BANGLADESH', 'lihan' ),
				__( 'EXCHANGE WITHIN 7 DAYS', 'lihan' ),
			]
		),
		'header_sticky'         => true,

		// Contact & social.
		'contact_phone'         => '01707629095',
		'contact_whatsapp'      => '8801707629095',
		'contact_messenger'     => 'https://m.me/lihanbd',
		'contact_email'         => '',
		'social_facebook'       => 'https://www.facebook.com/share/19Zhisj6gf/',
		'social_instagram'      => '',
		'social_youtube'        => '',
		'social_tiktok'         => '',
		'show_floating_chat'    => true,

		// Footer.
		'footer_logo'           => 0,
		'footer_about'          => __( 'Lihan Lifestyle brings you modest fashion made with care: abayas, hijabs, niqabs and more, designed for everyday elegance and delivered across Bangladesh.', 'lihan' ),
		'footer_col2_title'     => __( 'Information', 'lihan' ),
		'footer_col3_title'     => __( 'Customer Care', 'lihan' ),
		'footer_col4_title'     => __( 'Get in Touch', 'lihan' ),
		'opening_hours'         => __( "Sat - Thu|10:00 - 20:00\nFriday|Closed", 'lihan' ),
		/* translators: {year} is replaced with the current year. Keep the placeholder. */
		'footer_copyright'      => __( '© {year} Lihan Lifestyle. All rights reserved.', 'lihan' ),
		'payment_methods'       => 'cod,bkash,nagad,rocket,visa,mastercard',

		// Newsletter.
		'newsletter_heading'    => __( 'READY TO ELEVATE YOUR MODEST STYLE?', 'lihan' ),
		'newsletter_text'       => __( 'Be the first to hear about new arrivals, exclusive offers and restocks.', 'lihan' ),
		'newsletter_shortcode'  => '',
	];

	/**
	 * Filters the theme option defaults. Modules add their own keys here.
	 *
	 * @param array $defaults Option defaults.
	 */
	return (array) apply_filters( 'lihan_defaults', $defaults );
}

/**
 * Get a theme option (theme mod) with its registered default.
 *
 * @param string $key Option key.
 * @return mixed
 */
function lihan_get_option( string $key ) {
	$defaults = lihan_defaults();
	return get_theme_mod( $key, $defaults[ $key ] ?? null );
}

/**
 * Colour options mapped to the CSS custom properties they control.
 *
 * @return array<string,array{var:string,label:string,contrast?:string}>
 */
function lihan_color_settings(): array {
	return [
		'color_primary' => [
			'var'      => '--lh-primary',
			'contrast' => '--lh-primary-contrast',
			'label'    => __( 'Primary (top bar, footer, buttons)', 'lihan' ),
		],
		'color_accent'  => [
			'var'      => '--lh-accent',
			'contrast' => '--lh-accent-contrast',
			'label'    => __( 'Accent (sign-up button, cart badge)', 'lihan' ),
		],
		'color_gold'    => [
			'var'   => '--lh-gold',
			'label' => __( 'Gold (small highlights, ratings)', 'lihan' ),
		],
		'color_bg_alt'  => [
			'var'   => '--lh-bg-alt',
			'label' => __( 'Light grey background', 'lihan' ),
		],
		'color_bg_warm' => [
			'var'   => '--lh-bg-warm',
			'label' => __( 'Warm background', 'lihan' ),
		],
		'color_text'    => [
			'var'   => '--lh-text',
			'label' => __( 'Text', 'lihan' ),
		],
	];
}

/**
 * Readable text colour (#ffffff or #111111) for a background colour.
 *
 * @param string $hex Background colour.
 */
function lihan_contrast_color( string $hex ): string {
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return '#ffffff';
	}
	$channels = array_map(
		static function ( string $c ): float {
			$v = hexdec( $c ) / 255;
			return $v <= 0.03928 ? $v / 12.92 : ( ( $v + 0.055 ) / 1.055 ) ** 2.4;
		},
		str_split( $hex, 2 )
	);
	$luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
	// Pick whichever of white (L=1) or near-black (L≈0.0056) gives the higher contrast ratio.
	return ( 1.05 / ( $luminance + 0.05 ) ) >= ( ( $luminance + 0.05 ) / 0.0556 ) ? '#ffffff' : '#111111';
}

/**
 * Inline CSS overriding the design tokens with customizer colours (only changed values).
 */
function lihan_customizer_css(): string {
	$defaults = lihan_defaults();
	$vars     = [];

	foreach ( lihan_color_settings() as $key => $setting ) {
		$value = sanitize_hex_color( (string) lihan_get_option( $key ) );
		if ( ! $value || strtolower( $value ) === strtolower( (string) ( $defaults[ $key ] ?? '' ) ) ) {
			continue;
		}
		$vars[] = $setting['var'] . ':' . $value;
		if ( ! empty( $setting['contrast'] ) ) {
			$vars[] = $setting['contrast'] . ':' . lihan_contrast_color( $value );
		}
	}

	return $vars ? ':root{' . implode( ';', $vars ) . '}' : '';
}

/**
 * Payment methods the footer can show.
 *
 * @return array<string,string>
 */
function lihan_payment_method_choices(): array {
	return [
		'cod'        => __( 'Cash on Delivery', 'lihan' ),
		'bkash'      => __( 'bKash', 'lihan' ),
		'nagad'      => __( 'Nagad', 'lihan' ),
		'rocket'     => __( 'Rocket', 'lihan' ),
		'visa'       => __( 'Visa', 'lihan' ),
		'mastercard' => __( 'Mastercard', 'lihan' ),
	];
}

/* -------------------------------------------------------------------------
 * Sanitizers
 * ---------------------------------------------------------------------- */

/**
 * Sanitize a checkbox value.
 *
 * @param mixed $value Raw value.
 */
function lihan_sanitize_checkbox( $value ): bool {
	return ! empty( $value ) && 'false' !== $value;
}

/**
 * Keep digits only (WhatsApp numbers in international format).
 *
 * @param mixed $value Raw value.
 */
function lihan_sanitize_digits( $value ): string {
	return (string) preg_replace( '/\D+/', '', (string) $value );
}

/**
 * Phone numbers: digits, spaces, plus, dashes and brackets.
 *
 * @param mixed $value Raw value.
 */
function lihan_sanitize_phone( $value ): string {
	return trim( (string) preg_replace( '/[^0-9+\-\s()]/', '', sanitize_text_field( (string) $value ) ) );
}

/**
 * Short rich text (links and emphasis only).
 *
 * @param mixed $value Raw value.
 */
function lihan_sanitize_inline_html( $value ): string {
	return wp_kses(
		(string) $value,
		[
			'a'      => [
				'href'   => true,
				'target' => true,
				'rel'    => true,
			],
			'strong' => [],
			'em'     => [],
			'br'     => [],
		]
	);
}

/**
 * Payment methods: comma list restricted to known slugs.
 *
 * @param mixed $value Raw value (string or array).
 */
function lihan_sanitize_payment_methods( $value ): string {
	$list    = is_array( $value ) ? $value : explode( ',', (string) $value );
	$allowed = array_keys( lihan_payment_method_choices() );
	$clean   = array_values( array_intersect( array_map( 'sanitize_key', array_map( 'trim', $list ) ), $allowed ) );
	return implode( ',', array_unique( $clean ) );
}

/* -------------------------------------------------------------------------
 * Registration
 * ---------------------------------------------------------------------- */

/**
 * Register the "Lihan Theme" panel, sections, settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function lihan_customize_register( WP_Customize_Manager $wp_customize ): void {
	lihan_register_customizer_controls();

	$defaults = lihan_defaults();

	$wp_customize->add_panel(
		'lihan_theme',
		[
			'title'       => __( 'Lihan Theme', 'lihan' ),
			'description' => __( 'Colours, header, footer and contact details for the Lihan theme.', 'lihan' ),
			'priority'    => 30,
		]
	);

	$sections = [
		'lihan_colors'     => __( 'Colors', 'lihan' ),
		'lihan_header'     => __( 'Header & Announcement', 'lihan' ),
		'lihan_contact'    => __( 'Contact & Social', 'lihan' ),
		'lihan_footer'     => __( 'Footer', 'lihan' ),
		'lihan_newsletter' => __( 'Newsletter', 'lihan' ),
	];
	$priority = 10;
	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section(
			$id,
			[
				'title'    => $title,
				'panel'    => 'lihan_theme',
				'priority' => $priority,
			]
		);
		$priority += 10;
	}

	$add = static function ( string $key, array $setting, array $control ) use ( $wp_customize, $defaults ): void {
		$wp_customize->add_setting(
			$key,
			array_merge(
				[
					'default'    => $defaults[ $key ] ?? '',
					'transport'  => 'refresh',
					'capability' => 'edit_theme_options',
				],
				$setting
			)
		);
		if ( isset( $control['class'] ) ) {
			$class = $control['class'];
			unset( $control['class'] );
			$wp_customize->add_control( new $class( $wp_customize, $key, $control ) );
		} else {
			$wp_customize->add_control( $key, $control );
		}
	};

	// Colors.
	foreach ( lihan_color_settings() as $key => $color ) {
		$add(
			$key,
			[
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			],
			[
				'class'   => 'WP_Customize_Color_Control',
				'label'   => $color['label'],
				'section' => 'lihan_colors',
			]
		);
	}

	// Header & announcement.
	$add(
		'announcement_messages',
		[
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'postMessage',
		],
		[
			'type'        => 'textarea',
			'label'       => __( 'Announcement messages', 'lihan' ),
			'description' => __( 'One message per line. They rotate in the top bar. Leave empty to hide the bar text.', 'lihan' ),
			'section'     => 'lihan_header',
		]
	);
	$add(
		'header_sticky',
		[ 'sanitize_callback' => 'lihan_sanitize_checkbox' ],
		[
			'type'    => 'checkbox',
			'label'   => __( 'Keep the header visible while scrolling', 'lihan' ),
			'section' => 'lihan_header',
		]
	);

	// Contact & social.
	$contact_fields = [
		'contact_phone'     => [ __( 'Phone / hotline', 'lihan' ), 'lihan_sanitize_phone', 'tel', '' ],
		'contact_whatsapp'  => [ __( 'WhatsApp number', 'lihan' ), 'lihan_sanitize_digits', 'text', __( 'International format, digits only (for example 8801707629095).', 'lihan' ) ],
		'contact_messenger' => [ __( 'Messenger link', 'lihan' ), 'esc_url_raw', 'url', __( 'For example https://m.me/yourpage', 'lihan' ) ],
		'contact_email'     => [ __( 'Email address', 'lihan' ), 'sanitize_email', 'email', __( 'Leave empty to hide it.', 'lihan' ) ],
		'social_facebook'   => [ __( 'Facebook URL', 'lihan' ), 'esc_url_raw', 'url', '' ],
		'social_instagram'  => [ __( 'Instagram URL', 'lihan' ), 'esc_url_raw', 'url', '' ],
		'social_youtube'    => [ __( 'YouTube URL', 'lihan' ), 'esc_url_raw', 'url', '' ],
		'social_tiktok'     => [ __( 'TikTok URL', 'lihan' ), 'esc_url_raw', 'url', '' ],
	];
	foreach ( $contact_fields as $key => [ $label, $sanitize, $type, $description ] ) {
		$add(
			$key,
			[ 'sanitize_callback' => $sanitize ],
			[
				'type'        => $type,
				'label'       => $label,
				'description' => $description,
				'section'     => 'lihan_contact',
			]
		);
	}
	$add(
		'show_floating_chat',
		[ 'sanitize_callback' => 'lihan_sanitize_checkbox' ],
		[
			'type'        => 'checkbox',
			'label'       => __( 'Show the floating WhatsApp / Messenger button', 'lihan' ),
			'description' => __( 'Hidden automatically on checkout pages.', 'lihan' ),
			'section'     => 'lihan_contact',
		]
	);

	// Footer.
	$add(
		'footer_logo',
		[ 'sanitize_callback' => 'absint' ],
		[
			'class'       => 'WP_Customize_Media_Control',
			'mime_type'   => 'image',
			'label'       => __( 'Footer logo (light version)', 'lihan' ),
			'description' => __( 'Shown on the dark footer. Defaults to the bundled light Lihan logo.', 'lihan' ),
			'section'     => 'lihan_footer',
		]
	);
	$add(
		'footer_about',
		[
			'sanitize_callback' => 'lihan_sanitize_inline_html',
			'transport'         => 'postMessage',
		],
		[
			'type'    => 'textarea',
			'label'   => __( 'About text', 'lihan' ),
			'section' => 'lihan_footer',
		]
	);
	foreach ( [
		'footer_col2_title' => __( 'Column 2 heading (Footer: Information menu)', 'lihan' ),
		'footer_col3_title' => __( 'Column 3 heading (Footer: Customer care menu)', 'lihan' ),
		'footer_col4_title' => __( 'Column 4 heading (contact details)', 'lihan' ),
	] as $key => $label ) {
		$add(
			$key,
			[
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'postMessage',
			],
			[
				'type'    => 'text',
				'label'   => $label,
				'section' => 'lihan_footer',
			]
		);
	}
	$add(
		'opening_hours',
		[
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'postMessage',
		],
		[
			'type'        => 'textarea',
			'label'       => __( 'Opening hours', 'lihan' ),
			'description' => __( 'One row per line as "Days|Hours", for example "Friday|Closed". Leave empty to hide.', 'lihan' ),
			'section'     => 'lihan_footer',
		]
	);
	$add(
		'footer_copyright',
		[
			'sanitize_callback' => 'lihan_sanitize_inline_html',
			'transport'         => 'postMessage',
		],
		[
			'type'        => 'text',
			'label'       => __( 'Copyright text', 'lihan' ),
			'description' => __( '{year} is replaced with the current year.', 'lihan' ),
			'section'     => 'lihan_footer',
		]
	);
	$add(
		'payment_methods',
		[
			'sanitize_callback' => 'lihan_sanitize_payment_methods',
			'transport'         => 'postMessage',
		],
		[
			'class'   => 'Lihan_Customize_Checkbox_Multiple_Control',
			'label'   => __( 'Payment badges', 'lihan' ),
			'choices' => lihan_payment_method_choices(),
			'section' => 'lihan_footer',
		]
	);

	// Newsletter.
	$add(
		'newsletter_heading',
		[
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		],
		[
			'type'    => 'text',
			'label'   => __( 'Heading', 'lihan' ),
			'section' => 'lihan_newsletter',
		]
	);
	$add(
		'newsletter_text',
		[
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'postMessage',
		],
		[
			'type'    => 'textarea',
			'label'   => __( 'Text', 'lihan' ),
			'section' => 'lihan_newsletter',
		]
	);
	$add(
		'newsletter_shortcode',
		[ 'sanitize_callback' => 'sanitize_text_field' ],
		[
			'type'        => 'text',
			'label'       => __( 'Form shortcode (optional)', 'lihan' ),
			'description' => __( 'Paste a shortcode from your email marketing plugin to replace the built-in form. The built-in form stores addresses under Tools → Newsletter subscribers.', 'lihan' ),
			'section'     => 'lihan_newsletter',
		]
	);

	// Selective refresh for text that has a cheap render callback.
	if ( isset( $wp_customize->selective_refresh ) ) {
		$partials = [
			'announcement_messages' => [ '.lh-topbar__center', 'lihan_render_announcements' ],
			'footer_about'          => [ '.lh-footer__about', 'lihan_render_footer_about' ],
			'footer_copyright'      => [ '.lh-footer__copyright', 'lihan_render_copyright' ],
			'opening_hours'         => [ '.lh-footer__hours', 'lihan_render_opening_hours' ],
			'payment_methods'       => [ '.lh-footer__payments', 'lihan_render_payment_badges' ],
			'newsletter_heading'    => [ '.lh-newsletter__heading', 'lihan_render_newsletter_heading' ],
			'newsletter_text'       => [ '.lh-newsletter__text', 'lihan_render_newsletter_text' ],
			'footer_col2_title'     => [ '.lh-footer__col--2 .lh-footer__title-text', 'lihan_render_footer_col2_title' ],
			'footer_col3_title'     => [ '.lh-footer__col--3 .lh-footer__title-text', 'lihan_render_footer_col3_title' ],
			'footer_col4_title'     => [ '.lh-footer__col--4 .lh-footer__title-text', 'lihan_render_footer_col4_title' ],
		];
		foreach ( $partials as $setting => [ $selector, $callback ] ) {
			$wp_customize->selective_refresh->add_partial(
				$setting,
				[
					'selector'            => $selector,
					'settings'            => [ $setting ],
					'container_inclusive' => false,
					'render_callback'     => static function () use ( $callback ): void {
						if ( function_exists( $callback ) ) {
							$callback();
						}
					},
					'fallback_refresh'    => true,
				]
			);
		}
	}

	// Core settings that benefit from live preview.
	$wp_customize->get_setting( 'blogname' )->transport = 'postMessage';
}
add_action( 'customize_register', 'lihan_customize_register' );

/**
 * Declare the custom control classes (only once WP_Customize_Control is available).
 */
function lihan_register_customizer_controls(): void {
	if ( class_exists( 'Lihan_Customize_Checkbox_Multiple_Control' ) || ! class_exists( 'WP_Customize_Control' ) ) {
		return;
	}

	/**
	 * A list of checkboxes stored as a comma-separated string.
	 */
	class Lihan_Customize_Checkbox_Multiple_Control extends WP_Customize_Control {
		/**
		 * Control type.
		 *
		 * @var string
		 */
		public $type = 'lihan-checkbox-multiple';

		/**
		 * Render the control.
		 */
		protected function render_content(): void {
			if ( empty( $this->choices ) ) {
				return;
			}
			$values = array_filter( explode( ',', (string) $this->value() ) );
			?>
			<fieldset class="lh-checkbox-multiple">
				<?php if ( ! empty( $this->label ) ) : ?>
					<legend class="customize-control-title"><?php echo esc_html( $this->label ); ?></legend>
				<?php endif; ?>
				<?php if ( ! empty( $this->description ) ) : ?>
					<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
				<?php endif; ?>
				<?php foreach ( $this->choices as $value => $label ) : ?>
					<label style="display:block;margin:4px 0;">
						<input type="checkbox" value="<?php echo esc_attr( (string) $value ); ?>" <?php checked( in_array( (string) $value, $values, true ) ); ?>>
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
				<input type="hidden" <?php $this->link(); ?> value="<?php echo esc_attr( implode( ',', $values ) ); ?>">
			</fieldset>
			<?php
		}
	}
}

/**
 * Controls-pane script: keep the multi-checkbox hidden input in sync.
 */
function lihan_customize_controls_scripts(): void {
	wp_add_inline_script(
		'customize-controls',
		"jQuery(document).on('change','.lh-checkbox-multiple input[type=checkbox]',function(){var w=jQuery(this).closest('.lh-checkbox-multiple');var v=w.find('input[type=checkbox]:checked').map(function(){return this.value;}).get();w.find('input[type=hidden]').val(v.join(',')).trigger('change');});"
	);
}
add_action( 'customize_controls_enqueue_scripts', 'lihan_customize_controls_scripts' );

/**
 * Preview script: live colour updates and carousel/rotator re-init after partial refreshes.
 */
function lihan_customize_preview_scripts(): void {
	$map = [];
	foreach ( lihan_color_settings() as $key => $setting ) {
		$map[ $key ] = [
			'var'      => $setting['var'],
			'contrast' => $setting['contrast'] ?? '',
		];
	}

	$script = '(function(api){'
		. 'var map=' . wp_json_encode( $map ) . ';'
		. 'function lum(h){h=(h||"").replace("#","");if(h.length===3){h=h.replace(/(.)/g,"$1$1");}if(h.length!==6){return 0;}var c=[0,2,4].map(function(i){var v=parseInt(h.substr(i,2),16)/255;return v<=0.03928?v/12.92:Math.pow((v+0.055)/1.055,2.4);});return 0.2126*c[0]+0.7152*c[1]+0.0722*c[2];}'
		. 'Object.keys(map).forEach(function(k){api(k,function(s){s.bind(function(to){var r=document.documentElement.style;if(to){r.setProperty(map[k].var,to);}else{r.removeProperty(map[k].var);}if(map[k].contrast){var L=lum(to);r.setProperty(map[k].contrast,(1.05/(L+0.05))>=((L+0.05)/0.0556)?"#ffffff":"#111111");}});});});'
		. 'api("blogname",function(s){s.bind(function(to){document.querySelectorAll(".lh-logo__text").forEach(function(e){e.textContent=to;});});});'
		. 'api.bind("preview-ready",function(){if(api.selectiveRefresh){api.selectiveRefresh.bind("partial-content-rendered",function(p){if(window.Lihan&&p.container&&p.container[0]){window.Lihan.init(p.container[0]);}});}});'
		. '})(wp.customize);';

	wp_add_inline_script( 'customize-preview', $script );
}
add_action( 'customize_preview_init', 'lihan_customize_preview_scripts' );
