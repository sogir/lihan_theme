<?php
/**
 * Built-in newsletter sign-up: form, handler, subscriber list and privacy tools.
 *
 * Subscribers are stored in the `lihan_newsletter_subscribers` option (array of emails).
 *
 * @package Lihan
 */

defined( 'ABSPATH' ) || exit;

const LIHAN_NEWSLETTER_OPTION = 'lihan_newsletter_subscribers';

/**
 * Echo the newsletter heading (selective-refresh callback).
 */
function lihan_render_newsletter_heading(): void {
	echo esc_html( (string) lihan_get_option( 'newsletter_heading' ) );
}

/**
 * Echo the newsletter text (selective-refresh callback).
 */
function lihan_render_newsletter_text(): void {
	echo esc_html( (string) lihan_get_option( 'newsletter_text' ) );
}

/**
 * Status messages shown after a sign-up attempt.
 *
 * @return array<string,array{0:string,1:string}> status => [ type, message ].
 */
function lihan_newsletter_messages(): array {
	return [
		'ok'      => [ 'success', __( 'Thank you! You are on the list.', 'lihan' ) ],
		'exists'  => [ 'info', __( 'You are already subscribed. Thank you!', 'lihan' ) ],
		'invalid' => [ 'error', __( 'Please enter a valid email address.', 'lihan' ) ],
		'error'   => [ 'error', __( 'Something went wrong. Please try again.', 'lihan' ) ],
	];
}

/**
 * Echo the newsletter form (or the configured shortcode).
 *
 * @param array{id?:string,class?:string} $args Optional form id and extra classes.
 */
function lihan_newsletter_form( array $args = [] ): void {
	$shortcode = trim( (string) lihan_get_option( 'newsletter_shortcode' ) );
	if ( '' !== $shortcode ) {
		echo '<div class="lh-newsletter__embed">' . do_shortcode( $shortcode ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output.
		return;
	}

	$id       = $args['id'] ?? wp_unique_id( 'lh-newsletter-' );
	$class    = trim( 'lh-newsletter-form ' . ( $args['class'] ?? '' ) );
	$status   = isset( $_GET['lh_nl'] ) ? sanitize_key( wp_unslash( $_GET['lh_nl'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
	$messages = lihan_newsletter_messages();
	$message  = $messages[ $status ] ?? null;
	$redirect = remove_query_arg( 'lh_nl' ); // Current request path + query (validated again on submit).
	?>
	<form id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $class ); ?>" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" novalidate>
		<input type="hidden" name="action" value="lihan_newsletter">
		<input type="hidden" name="lh_redirect" value="<?php echo esc_url( $redirect ); ?>">
		<?php wp_nonce_field( 'lihan_newsletter', 'lh_nl_nonce', false ); ?>
		<div class="lh-newsletter-form__hp" aria-hidden="true">
			<label for="<?php echo esc_attr( $id ); ?>-website"><?php esc_html_e( 'Leave this field empty', 'lihan' ); ?></label>
			<input type="text" id="<?php echo esc_attr( $id ); ?>-website" name="lh_website" value="" tabindex="-1" autocomplete="off">
		</div>
		<div class="lh-newsletter-form__row">
			<label class="lh-visually-hidden" for="<?php echo esc_attr( $id ); ?>-email"><?php esc_html_e( 'Email address', 'lihan' ); ?></label>
			<input class="lh-newsletter-form__input" type="email" id="<?php echo esc_attr( $id ); ?>-email" name="lh_email" required autocomplete="email" inputmode="email" placeholder="<?php esc_attr_e( 'Enter your email address', 'lihan' ); ?>"<?php echo $message && 'error' === $message[0] ? ' aria-invalid="true"' : ''; ?> aria-describedby="<?php echo esc_attr( $id ); ?>-msg">
			<button class="lh-btn lh-btn--accent lh-newsletter-form__submit" type="submit"><?php esc_html_e( 'Sign up', 'lihan' ); ?></button>
		</div>
		<p id="<?php echo esc_attr( $id ); ?>-msg" class="lh-newsletter-form__msg<?php echo $message ? ' is-' . esc_attr( $message[0] ) : ''; ?>" role="status" aria-live="polite"><?php echo $message ? esc_html( $message[1] ) : ''; ?></p>
	</form>
	<?php
}

/**
 * Handle a sign-up submission (logged-in and logged-out).
 */
function lihan_newsletter_handle(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified below; honeypot checked first.
	$redirect = isset( $_POST['lh_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['lh_redirect'] ) ) : '';
	$redirect = wp_validate_redirect( $redirect, home_url( '/' ) );

	$done = static function ( string $status ) use ( $redirect ): void {
		wp_safe_redirect( add_query_arg( 'lh_nl', $status, remove_query_arg( 'lh_nl', $redirect ) ) . '#lh-newsletter' );
		exit;
	};

	// Bots fill every field: pretend success, store nothing.
	if ( ! empty( $_POST['lh_website'] ) ) {
		$done( 'ok' );
	}

	$nonce = isset( $_POST['lh_nl_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['lh_nl_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'lihan_newsletter' ) ) {
		$done( 'error' );
	}

	$email = isset( $_POST['lh_email'] ) ? sanitize_email( wp_unslash( $_POST['lh_email'] ) ) : '';
	// phpcs:enable
	if ( '' === $email || ! is_email( $email ) ) {
		$done( 'invalid' );
	}

	// Light rate limit per visitor: 5 attempts per 10 minutes.
	$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rate_key = 'lihan_nl_' . md5( $ip . wp_salt( 'nonce' ) );
	$attempts = (int) get_transient( $rate_key );
	if ( $attempts >= 5 ) {
		$done( 'error' );
	}
	set_transient( $rate_key, $attempts + 1, 10 * MINUTE_IN_SECONDS );

	$email = strtolower( $email );
	$list  = lihan_newsletter_subscribers();
	if ( in_array( $email, $list, true ) ) {
		$done( 'exists' );
	}

	$list[] = $email;
	update_option( LIHAN_NEWSLETTER_OPTION, array_values( array_unique( $list ) ), false );

	wp_mail(
		get_option( 'admin_email' ),
		sprintf(
			/* translators: %s: site name. */
			__( '[%s] New newsletter subscriber', 'lihan' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		),
		sprintf(
			/* translators: 1: email address, 2: admin URL of the subscriber list. */
			__( "A new visitor subscribed to the newsletter:\n\n%1\$s\n\nAll subscribers: %2\$s", 'lihan' ),
			$email,
			admin_url( 'tools.php?page=lihan-newsletter' )
		)
	);

	/**
	 * Fires after a new newsletter subscriber is stored (hook up an email service here).
	 *
	 * @param string $email Subscriber email.
	 */
	do_action( 'lihan_newsletter_subscribed', $email );

	$done( 'ok' );
}
add_action( 'admin_post_nopriv_lihan_newsletter', 'lihan_newsletter_handle' );
add_action( 'admin_post_lihan_newsletter', 'lihan_newsletter_handle' );

/**
 * Stored subscribers.
 *
 * @return array<int,string>
 */
function lihan_newsletter_subscribers(): array {
	$list = get_option( LIHAN_NEWSLETTER_OPTION, [] );
	return is_array( $list ) ? array_values( array_filter( array_map( 'strval', $list ) ) ) : [];
}

/**
 * Tools → Newsletter subscribers (read-only list with a CSV download).
 */
function lihan_newsletter_admin_menu(): void {
	add_management_page(
		__( 'Newsletter subscribers', 'lihan' ),
		__( 'Newsletter subscribers', 'lihan' ),
		'manage_options',
		'lihan-newsletter',
		'lihan_newsletter_admin_page'
	);
}
add_action( 'admin_menu', 'lihan_newsletter_admin_menu' );

/**
 * Render the subscribers admin page.
 */
function lihan_newsletter_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$list = lihan_newsletter_subscribers();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Newsletter subscribers', 'lihan' ); ?></h1>
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %d: number of subscribers. */
					_n( '%d subscriber collected by the footer sign-up form.', '%d subscribers collected by the footer sign-up form.', count( $list ), 'lihan' ),
					count( $list )
				)
			);
			?>
		</p>
		<?php if ( $list ) : ?>
			<p><a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lihan_newsletter_export' ), 'lihan_newsletter_export' ) ); ?>"><?php esc_html_e( 'Download CSV', 'lihan' ); ?></a></p>
			<label for="lh-nl-list" class="screen-reader-text"><?php esc_html_e( 'Subscriber emails', 'lihan' ); ?></label>
			<textarea id="lh-nl-list" class="large-text code" rows="12" readonly><?php echo esc_textarea( implode( "\n", $list ) ); ?></textarea>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Download the subscriber list as CSV.
 */
function lihan_newsletter_export(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to export subscribers.', 'lihan' ), '', [ 'response' => 403 ] );
	}
	check_admin_referer( 'lihan_newsletter_export' );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=lihan-newsletter-' . gmdate( 'Y-m-d' ) . '.csv' );
	echo "email\n";
	foreach ( lihan_newsletter_subscribers() as $email ) {
		// Prevent spreadsheet formula injection.
		$cell = preg_match( '/^[=+\-@]/', $email ) ? "'" . $email : $email;
		echo '"' . str_replace( '"', '""', $cell ) . "\"\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV, not HTML; addresses passed is_email().
	}
	exit;
}
add_action( 'admin_post_lihan_newsletter_export', 'lihan_newsletter_export' );

/**
 * Privacy: personal data exporter.
 *
 * @param array<string,array<string,mixed>> $exporters Exporters.
 * @return array<string,array<string,mixed>>
 */
function lihan_newsletter_register_exporter( array $exporters ): array {
	$exporters['lihan-newsletter'] = [
		'exporter_friendly_name' => __( 'Lihan newsletter', 'lihan' ),
		'callback'               => static function ( string $email ): array {
			$data = [];
			if ( in_array( strtolower( $email ), lihan_newsletter_subscribers(), true ) ) {
				$data[] = [
					'group_id'    => 'lihan-newsletter',
					'group_label' => __( 'Newsletter', 'lihan' ),
					'item_id'     => 'lihan-newsletter-' . md5( $email ),
					'data'        => [
						[
							'name'  => __( 'Subscribed email', 'lihan' ),
							'value' => $email,
						],
					],
				];
			}
			return [
				'data' => $data,
				'done' => true,
			];
		},
	];
	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'lihan_newsletter_register_exporter' );

/**
 * Privacy: personal data eraser.
 *
 * @param array<string,array<string,mixed>> $erasers Erasers.
 * @return array<string,array<string,mixed>>
 */
function lihan_newsletter_register_eraser( array $erasers ): array {
	$erasers['lihan-newsletter'] = [
		'eraser_friendly_name' => __( 'Lihan newsletter', 'lihan' ),
		'callback'             => static function ( string $email ): array {
			$list    = lihan_newsletter_subscribers();
			$kept    = array_values( array_diff( $list, [ strtolower( $email ) ] ) );
			$removed = count( $kept ) !== count( $list );
			if ( $removed ) {
				update_option( LIHAN_NEWSLETTER_OPTION, $kept, false );
			}
			return [
				'items_removed'  => $removed,
				'items_retained' => false,
				'messages'       => [],
				'done'           => true,
			];
		},
	];
	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'lihan_newsletter_register_eraser' );
