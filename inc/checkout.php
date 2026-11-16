<?php
/**
 * Simple checkout for Bangladesh: name, phone, address and a delivery-area choice.
 *
 * What it does when "Simple checkout" is on (Customizer → Lihan Theme → Shop, default on):
 * - Checkout fields are reduced to Full name, Phone and Full address. Country stays a
 *   normal, enabled field (default: the store country) hidden with CSS, so shipping zones
 *   keep matching.
 * - Email, last name, company, city, district, postcode, the second address line, order
 *   notes, "ship to a different address" and account creation are removed.
 * - The delivery options (shipping rates) are shown as large selectable boxes under the
 *   address instead of small radios in the order summary. No area is pre-selected, so a
 *   customer outside Dhaka cannot check out at the Dhaka rate by accident; WooCommerce's
 *   own "No shipping method has been selected" check blocks the order until one is chosen.
 * - No terms checkbox and no privacy-policy paragraph above the order button.
 * - Bangladeshi phone numbers are validated (01XXXXXXXXX; +880 / 880 prefixes are accepted
 *   and normalised).
 * - Free delivery: when the cart reaches the "Free delivery threshold" setting, every
 *   delivery area becomes free (so the area is still recorded on the order).
 *
 * Works with the classic [woocommerce_checkout] shortcode and with CartFlows checkout steps
 * (both use the woocommerce_checkout_fields filter). The WooCommerce Checkout *block* does
 * not support removing core fields, so use the shortcode on the checkout page.
 *
 * Filters:
 * - lihan_simple_checkout_enabled (bool)   Turn the whole module off/on in code.
 * - lihan_checkout_fields (array)          Final billing field definitions.
 * - lihan_valid_phone (bool, string $phone) Override the phone check.
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defaults for the checkout settings.
 *
 * @param array<string,mixed> $defaults Defaults.
 * @return array<string,mixed>
 */
function lihan_checkout_option_defaults( array $defaults ): array {
	$defaults['wc_simple_checkout']      = true;
	$defaults['wc_delivery_area_notes'] = "Inside Dhaka|Delivered in 1–2 days\nOutside Dhaka|Delivered in 2–5 days by courier";
	return $defaults;
}
add_filter( 'lihan_defaults', 'lihan_checkout_option_defaults' );

/**
 * Whether the simple checkout is active.
 */
function lihan_simple_checkout_enabled(): bool {
	$enabled = function_exists( 'lihan_get_option' ) ? (bool) lihan_get_option( 'wc_simple_checkout' ) : true;
	return (bool) apply_filters( 'lihan_simple_checkout_enabled', $enabled );
}

/**
 * Customizer settings (added to the theme's Shop section).
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function lihan_checkout_customize_register( WP_Customize_Manager $wp_customize ): void {
	if ( ! $wp_customize->get_section( 'lihan_woocommerce' ) ) {
		return;
	}
	$defaults = lihan_checkout_option_defaults( [] );

	$wp_customize->add_setting(
		'wc_simple_checkout',
		[
			'default'           => $defaults['wc_simple_checkout'],
			'sanitize_callback' => function_exists( 'lihan_sanitize_checkbox' ) ? 'lihan_sanitize_checkbox' : 'wp_validate_boolean',
		]
	);
	$wp_customize->add_control(
		'wc_simple_checkout',
		[
			'label'       => __( 'Simple checkout (name, phone, address + delivery area)', 'lihan' ),
			'description' => __( 'Needs the classic checkout: the Checkout page should contain the [woocommerce_checkout] shortcode.', 'lihan' ),
			'section'     => 'lihan_woocommerce',
			'type'        => 'checkbox',
		]
	);

	$wp_customize->add_setting(
		'wc_delivery_area_notes',
		[
			'default'           => $defaults['wc_delivery_area_notes'],
			'sanitize_callback' => 'sanitize_textarea_field',
		]
	);
	$wp_customize->add_control(
		'wc_delivery_area_notes',
		[
			'label'       => __( 'Delivery area notes', 'lihan' ),
			'description' => __( 'One per line: delivery method name|short note. Shown under each delivery area at checkout.', 'lihan' ),
			'section'     => 'lihan_woocommerce',
			'type'        => 'textarea',
		]
	);
}
add_action( 'customize_register', 'lihan_checkout_customize_register', 31 );

/**
 * Whether the current checkout (page load or AJAX refresh) is a CartFlows checkout step.
 */
function lihan_checkout_in_cartflows(): bool {
	if ( function_exists( 'lihan_is_cartflows_step' ) && lihan_is_cartflows_step() ) {
		return true;
	}
	return function_exists( '_get_wcf_checkout_id' ) && (int) _get_wcf_checkout_id() > 0;
}

/**
 * CartFlows' modern checkout adds its own "Customer information" email field outside the
 * WooCommerce field list; drop it so the step asks only for name, phone and address.
 */
function lihan_checkout_cartflows_remove_email(): void {
	if ( ! lihan_simple_checkout_enabled() || ! class_exists( 'Cartflows_Modern_Checkout' ) ) {
		return;
	}
	remove_action( 'woocommerce_checkout_billing', [ Cartflows_Modern_Checkout::get_instance(), 'add_custom_billing_email_field' ], 9 );
}
add_action( 'cartflows_checkout_form_before', 'lihan_checkout_cartflows_remove_email', 20 );

/**
 * Body class so the CSS can adapt the layout.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function lihan_checkout_body_class( array $classes ): array {
	if ( lihan_simple_checkout_enabled() && function_exists( 'is_checkout' ) && is_checkout() ) {
		$classes[] = 'lh-simple-checkout';
	}
	return $classes;
}
add_filter( 'body_class', 'lihan_checkout_body_class' );

/* -------------------------------------------------------------------------
 * Fields
 * ---------------------------------------------------------------------- */

/**
 * Reduce the checkout to name, phone and address.
 *
 * Runs late so it wins over defaults, but CartFlows' own layout classes are kept.
 *
 * @param array<string,array<string,array<string,mixed>>> $fields Checkout fields.
 * @return array<string,array<string,array<string,mixed>>>
 */
function lihan_checkout_fields( array $fields ): array {
	if ( ! lihan_simple_checkout_enabled() ) {
		return $fields;
	}

	$country = WC()->countries ? WC()->countries->get_base_country() : 'BD';

	$billing = [
		'billing_first_name' => [
			'label'        => __( 'Full name', 'lihan' ),
			'placeholder'  => __( 'Your full name', 'lihan' ),
			'required'     => true,
			'class'        => [ 'form-row-wide' ],
			'autocomplete' => 'name',
			'priority'     => 10,
		],
		'billing_phone'      => [
			'label'             => __( 'Phone number', 'lihan' ),
			'placeholder'       => __( '01XXXXXXXXX', 'lihan' ),
			'type'              => 'tel',
			'required'          => true,
			'class'             => [ 'form-row-wide' ],
			'validate'          => [ 'phone' ],
			'autocomplete'      => 'tel',
			'priority'          => 20,
			'custom_attributes' => [
				'inputmode' => 'tel',
				'maxlength' => '16',
			],
		],
		'billing_address_1'  => [
			'label'             => __( 'Full address', 'lihan' ),
			'placeholder'       => __( 'House, road, area, thana, district', 'lihan' ),
			'type'              => 'textarea',
			'required'          => true,
			'class'             => [ 'form-row-wide' ],
			'autocomplete'      => 'street-address',
			'priority'          => 30,
			'custom_attributes' => [ 'rows' => '3' ],
		],
		// A normal, enabled country field (so shipping zones, taxes and the order address
		// keep working), hidden with CSS (.lh-hidden-field) because the store ships within
		// Bangladesh only.
		'billing_country'    => [
			'label'    => __( 'Country / Region', 'lihan' ),
			'type'     => 'country',
			'default'  => $country,
			'required' => true,
			'class'    => [ 'form-row-wide', 'lh-hidden-field', 'update_totals_on_change' ],
			'autocomplete' => 'country',
			'priority' => 90,
		],
	];

	// Keep other classes plugins added to the fields we keep, but make every field full width
	// (CartFlows puts the address in a half-width column).
	foreach ( $billing as $key => $def ) {
		if ( isset( $fields['billing'][ $key ]['class'] ) && 'billing_country' !== $key ) {
			$extra = array_filter(
				(array) $fields['billing'][ $key ]['class'],
				static fn( $c ) => ! in_array( $c, [ 'form-row-first', 'form-row-last', 'form-row-wide', 'address-field' ], true ) && ! str_starts_with( (string) $c, 'wcf-column-' )
			);
			$billing[ $key ]['class'] = array_values( array_unique( array_merge( $def['class'], $extra ) ) );
		}
		if ( 'billing_country' !== $key && lihan_checkout_in_cartflows() ) {
			$billing[ $key ]['class'][] = 'wcf-column-100';
		}
	}

	$fields['billing'] = apply_filters( 'lihan_checkout_fields', $billing );

	// Shipping mirrors billing (WooCommerce copies billing → shipping in "billing only" mode).
	$fields['shipping'] = [
		'shipping_first_name' => $billing['billing_first_name'],
		'shipping_address_1'  => $billing['billing_address_1'],
		'shipping_country'    => $billing['billing_country'],
	];

	unset( $fields['order']['order_comments'] );
	unset( $fields['account'] );

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'lihan_checkout_fields', 100 );

/**
 * Stop WooCommerce's address script from relabelling our fields when the (hidden) country
 * field initialises: it would turn "Full name"/"Full address" back into "First name" /
 * "Street address" and change their placeholders and required state.
 *
 * @param array<string,string> $selectors Locale field selectors.
 * @return array<string,string>
 */
function lihan_checkout_locale_selectors( $selectors ) {
	if ( lihan_simple_checkout_enabled() && is_array( $selectors ) ) {
		unset( $selectors['first_name'], $selectors['address_1'] );
	}
	return $selectors;
}
add_filter( 'woocommerce_country_locale_field_selectors', 'lihan_checkout_locale_selectors', 20 );

/**
 * Ship to the billing address only (removes "Ship to a different address").
 *
 * @param mixed $value Option value.
 * @return mixed
 */
function lihan_checkout_ship_to_billing_only( $value ) {
	return lihan_simple_checkout_enabled() ? 'billing_only' : $value;
}
add_filter( 'option_woocommerce_ship_to_destination', 'lihan_checkout_ship_to_billing_only' );

/**
 * No order notes field.
 *
 * @param bool $enabled Enabled.
 */
function lihan_checkout_order_notes( $enabled ): bool {
	return lihan_simple_checkout_enabled() ? false : (bool) $enabled;
}
add_filter( 'woocommerce_enable_order_notes_field', 'lihan_checkout_order_notes', 100 );

/**
 * No account creation at checkout (there is no email field to create one with).
 *
 * @param bool $enabled Enabled.
 */
function lihan_checkout_registration( $enabled ): bool {
	return lihan_simple_checkout_enabled() ? false : (bool) $enabled;
}
add_filter( 'woocommerce_checkout_registration_enabled', 'lihan_checkout_registration', 100 );
add_filter(
	'woocommerce_checkout_registration_required',
	static fn( $required ) => lihan_simple_checkout_enabled() ? false : $required,
	100
);

/**
 * Plainer checkout wording.
 *
 * @param string $translation Translated text.
 * @param string $text        Original text.
 * @param string $domain      Text domain.
 */
function lihan_checkout_gettext( $translation, $text, $domain ) {
	if ( 'woocommerce' !== $domain || ! lihan_simple_checkout_enabled() ) {
		return $translation;
	}
	switch ( $text ) {
		case 'Billing &amp; Shipping':
		case 'Billing details':
			return __( 'Delivery details', 'lihan' );
	}
	return $translation;
}
add_filter( 'gettext', 'lihan_checkout_gettext', 20, 3 );

/**
 * "Confirm order" instead of "Place order".
 *
 * @param string $text Button text.
 */
function lihan_checkout_button_text( $text ): string {
	return lihan_simple_checkout_enabled() ? __( 'Confirm order', 'lihan' ) : (string) $text;
}
add_filter( 'woocommerce_order_button_text', 'lihan_checkout_button_text', 20 );

/**
 * No "I have read and agree to the terms" checkbox (and no check for it on submit).
 *
 * @param bool $show Show the checkbox.
 */
function lihan_checkout_show_terms( $show ): bool {
	return lihan_simple_checkout_enabled() ? false : (bool) $show;
}
add_filter( 'woocommerce_checkout_show_terms', 'lihan_checkout_show_terms', 100 );

/**
 * No privacy-policy paragraph or terms text above the order button.
 */
function lihan_checkout_remove_legal_text(): void {
	if ( ! lihan_simple_checkout_enabled() ) {
		return;
	}
	remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20 );
	remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30 );
}
add_action( 'wp', 'lihan_checkout_remove_legal_text' );
add_action( 'woocommerce_checkout_update_order_review', 'lihan_checkout_remove_legal_text' );

/* -------------------------------------------------------------------------
 * Phone validation
 * ---------------------------------------------------------------------- */

/**
 * Normalise a Bangladeshi mobile number to 01XXXXXXXXX (other input is returned trimmed).
 *
 * @param string $phone Raw input.
 */
function lihan_normalize_bd_phone( string $phone ): string {
	$digits = preg_replace( '/\D+/', '', $phone );
	if ( str_starts_with( $digits, '880' ) ) {
		$digits = substr( $digits, 2 );
	} elseif ( str_starts_with( $digits, '88' ) && 13 === strlen( $digits ) ) {
		$digits = substr( $digits, 2 );
	}
	return preg_match( '/^01[3-9]\d{8}$/', $digits ) ? $digits : trim( $phone );
}

/**
 * Save the phone in the normalised form.
 *
 * @param string $value Posted value.
 */
function lihan_checkout_process_phone( $value ): string {
	return lihan_simple_checkout_enabled() ? lihan_normalize_bd_phone( (string) $value ) : (string) $value;
}
add_filter( 'woocommerce_process_checkout_field_billing_phone', 'lihan_checkout_process_phone' );

/**
 * Reject phone numbers that are not Bangladeshi mobile numbers (only for Bangladesh stores).
 *
 * @param array<string,mixed> $data   Posted data.
 * @param WP_Error            $errors Errors.
 */
function lihan_checkout_validate_phone( array $data, WP_Error $errors ): void {
	if ( ! lihan_simple_checkout_enabled() || empty( $data['billing_phone'] ) ) {
		return;
	}
	if ( 'BD' !== ( $data['billing_country'] ?? 'BD' ) ) {
		return;
	}
	$phone = (string) $data['billing_phone'];
	$valid = (bool) preg_match( '/^01[3-9]\d{8}$/', lihan_normalize_bd_phone( $phone ) );
	if ( ! apply_filters( 'lihan_valid_phone', $valid, $phone ) ) {
		$errors->add(
			'billing_phone_validation',
			__( 'Please enter a valid Bangladeshi mobile number, for example 01712345678.', 'lihan' ),
			[ 'id' => 'billing_phone' ]
		);
	}
}
add_action( 'woocommerce_after_checkout_validation', 'lihan_checkout_validate_phone', 10, 2 );

/* -------------------------------------------------------------------------
 * Delivery area
 * ---------------------------------------------------------------------- */

/**
 * Don't pre-select a delivery area when there is a real choice to make.
 *
 * @param string                $default Default rate ID.
 * @param WC_Shipping_Rate[]    $rates   Rates.
 * @param string|false          $chosen  Previously chosen rate.
 */
function lihan_checkout_no_default_area( $default, $rates, $chosen ) {
	if ( ! lihan_simple_checkout_enabled() || ! is_array( $rates ) || count( $rates ) < 2 ) {
		return $default;
	}
	if ( $chosen && isset( $rates[ $chosen ] ) ) {
		return $chosen;
	}
	return '';
}
add_filter( 'woocommerce_shipping_chosen_method', 'lihan_checkout_no_default_area', 20, 3 );

/**
 * The cart total that counts towards free delivery (subtotal after discounts).
 */
function lihan_checkout_free_delivery_total(): float {
	$cart = WC()->cart;
	if ( ! $cart ) {
		return 0.0;
	}
	$total = (float) $cart->get_displayed_subtotal() - (float) $cart->get_discount_total();
	if ( $cart->display_prices_including_tax() ) {
		$total -= (float) $cart->get_discount_tax();
	}
	return max( 0.0, $total );
}

/**
 * Make every delivery area free once the cart reaches the free-delivery threshold.
 *
 * @param WC_Shipping_Rate[]  $rates   Rates.
 * @param array<string,mixed> $package Package.
 * @return WC_Shipping_Rate[]
 */
function lihan_checkout_free_delivery( $rates, $package ) {
	if ( ! function_exists( 'lihan_get_option' ) || ! is_array( $rates ) ) {
		return $rates;
	}
	$threshold = (float) apply_filters( 'lihan_free_shipping_threshold', (float) lihan_get_option( 'wc_free_shipping_threshold' ) );
	if ( $threshold <= 0 || lihan_checkout_free_delivery_total() < $threshold ) {
		return $rates;
	}
	foreach ( $rates as $rate ) {
		if ( $rate instanceof WC_Shipping_Rate && 'local_pickup' !== $rate->get_method_id() ) {
			$rate->set_cost( 0 );
			$rate->set_taxes( [] );
		}
	}
	return $rates;
}
add_filter( 'woocommerce_package_rates', 'lihan_checkout_free_delivery', 20, 2 );

/**
 * Include the free-delivery state in the shipping cache key, so rates refresh when the
 * cart crosses the threshold.
 *
 * @param array<string,mixed> $packages Packages.
 * @return array<string,mixed>
 */
function lihan_checkout_package_cache_key( $packages ) {
	if ( ! is_array( $packages ) || ! function_exists( 'lihan_get_option' ) ) {
		return $packages;
	}
	$threshold = (float) lihan_get_option( 'wc_free_shipping_threshold' );
	$free      = $threshold > 0 && lihan_checkout_free_delivery_total() >= $threshold;
	foreach ( $packages as &$package ) {
		$package['lihan_free_delivery'] = $free ? 1 : 0;
	}
	return $packages;
}
add_filter( 'woocommerce_cart_shipping_packages', 'lihan_checkout_package_cache_key' );

/**
 * Notes per delivery method, from the customizer ("Name|note" lines).
 *
 * @return array<string,string> Lower-case method label → note.
 */
function lihan_checkout_area_notes(): array {
	$raw   = function_exists( 'lihan_get_option' ) ? (string) lihan_get_option( 'wc_delivery_area_notes' ) : '';
	$notes = [];
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( 2 === count( $parts ) && '' !== $parts[0] ) {
			$notes[ strtolower( $parts[0] ) ] = $parts[1];
		}
	}
	return $notes;
}

/**
 * Markup for the delivery-area boxes.
 */
function lihan_checkout_delivery_area_html(): string {
	if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
		return '<div id="lh-delivery-area" class="lh-delivery-area" hidden></div>';
	}

	$packages = WC()->shipping()->get_packages();
	if ( empty( $packages ) ) {
		WC()->cart->calculate_shipping();
		$packages = WC()->shipping()->get_packages();
	}
	$chosen = (array) WC()->session->get( 'chosen_shipping_methods', [] );
	$notes  = lihan_checkout_area_notes();

	ob_start();
	?>
	<div id="lh-delivery-area" class="lh-delivery-area">
		<h3 class="lh-delivery-area__title"><?php esc_html_e( 'Delivery area', 'lihan' ); ?></h3>
		<?php foreach ( $packages as $i => $package ) : ?>
			<?php
			$rates = $package['rates'] ?? [];
			if ( empty( $rates ) ) :
				?>
				<p class="lh-delivery-area__empty"><?php esc_html_e( 'Sorry, we can’t deliver to this address yet. Please call or message us and we’ll help.', 'lihan' ); ?></p>
				<?php
				continue;
			endif;
			$current = $chosen[ $i ] ?? '';
			if ( 1 === count( $rates ) ) {
				$current = (string) array_key_first( $rates );
			}
			?>
			<fieldset class="lh-delivery-options">
				<legend class="screen-reader-text"><?php esc_html_e( 'Choose your delivery area', 'lihan' ); ?></legend>
				<?php
				foreach ( $rates as $rate_id => $rate ) :
					/** @var WC_Shipping_Rate $rate */
					$label   = $rate->get_label();
					$cost    = (float) $rate->get_cost() + array_sum( (array) $rate->get_taxes() );
					$note    = $notes[ strtolower( $label ) ] ?? '';
					$input   = 'shipping_method_' . $i . '_' . sanitize_title( $rate_id );
					$checked = (string) $rate_id === (string) $current;
					?>
					<label class="lh-delivery-option<?php echo $checked ? ' is-selected' : ''; ?>" for="<?php echo esc_attr( $input ); ?>">
						<input type="radio" class="shipping_method lh-delivery-option__input" name="shipping_method[<?php echo esc_attr( (string) $i ); ?>]" data-index="<?php echo esc_attr( (string) $i ); ?>" id="<?php echo esc_attr( $input ); ?>" value="<?php echo esc_attr( $rate_id ); ?>" <?php checked( $checked ); ?> />
						<span class="lh-delivery-option__radio" aria-hidden="true"></span>
						<span class="lh-delivery-option__body">
							<span class="lh-delivery-option__name"><?php echo esc_html( $label ); ?></span>
							<?php if ( '' !== $note ) : ?>
								<span class="lh-delivery-option__note"><?php echo esc_html( $note ); ?></span>
							<?php endif; ?>
						</span>
						<span class="lh-delivery-option__price">
							<?php echo $cost > 0 ? wp_kses_post( wc_price( $cost ) ) : esc_html__( 'Free', 'lihan' ); ?>
						</span>
					</label>
				<?php endforeach; ?>
			</fieldset>
			<?php if ( '' === $current ) : ?>
				<p class="lh-delivery-area__hint"><?php esc_html_e( 'Please choose where we should deliver your order.', 'lihan' ); ?></p>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Print the delivery-area boxes right under the address fields.
 */
function lihan_checkout_render_delivery_area(): void {
	// CartFlows steps show their own shipping-method list, so don't add a second one.
	if ( lihan_simple_checkout_enabled() && ! lihan_checkout_in_cartflows() ) {
		echo lihan_checkout_delivery_area_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
	}
}
add_action( 'woocommerce_after_checkout_billing_form', 'lihan_checkout_render_delivery_area', 5 );

/**
 * Refresh the boxes with every checkout update (prices change at the free threshold).
 *
 * @param array<string,string> $fragments Fragments.
 * @return array<string,string>
 */
function lihan_checkout_fragments( $fragments ) {
	if ( lihan_simple_checkout_enabled() && is_array( $fragments ) && ! lihan_checkout_in_cartflows() ) {
		$fragments['#lh-delivery-area'] = lihan_checkout_delivery_area_html();
	}
	return $fragments;
}
add_filter( 'woocommerce_update_order_review_fragments', 'lihan_checkout_fragments' );

/**
 * In the checkout order summary, show the chosen delivery as a plain line (no second set of
 * radios), using the theme's summary template.
 *
 * @param string              $located       Template path.
 * @param string              $template_name Template name.
 * @return string
 */
function lihan_checkout_shipping_summary_template( $located, $template_name ) {
	if ( 'cart/cart-shipping.php' !== $template_name || ! lihan_simple_checkout_enabled() || lihan_checkout_in_cartflows() ) {
		return $located;
	}
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || ( function_exists( 'is_cart' ) && is_cart() ) ) {
		return $located;
	}
	$file = LIHAN_DIR . '/template-parts/checkout/shipping-summary.php';
	return file_exists( $file ) ? $file : $located;
}
add_filter( 'wc_get_template', 'lihan_checkout_shipping_summary_template', 20, 2 );

/**
 * When delivery areas exist but none is chosen yet, say so instead of the alarming
 * "no shipping options available / enter your address" messages (CartFlows' order summary
 * and WooCommerce both use these filters).
 *
 * @param string $html Message HTML.
 */
function lihan_checkout_choose_area_message( $html ) {
	if ( ! lihan_simple_checkout_enabled() || ! WC()->shipping() ) {
		return $html;
	}
	foreach ( WC()->shipping()->get_packages() as $package ) {
		if ( ! empty( $package['rates'] ) ) {
			return esc_html__( 'Choose your delivery area', 'lihan' );
		}
	}
	return $html;
}
add_filter( 'woocommerce_no_shipping_available_html', 'lihan_checkout_choose_area_message', 20 );
add_filter( 'woocommerce_shipping_may_be_available_html', 'lihan_checkout_choose_area_message', 20 );
