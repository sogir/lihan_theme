<?php
/**
 * Demo content importer.
 *
 * Sets up a realistic Lihan store from the bundled data in THEME/demo/:
 * WooCommerce settings for Bangladesh, shipping zones, Cash on Delivery,
 * product categories, attributes, products (simple and variable), reviews,
 * pages, blog posts, menus, logo, site icon and homepage image theme mods.
 *
 * Idempotent: every item is looked up first (pages, posts and products by
 * slug or SKU, terms by slug, attachments by their demo source path, menu
 * items by target), so running it again adds only what is missing and never
 * duplicates. Existing published content is left untouched.
 *
 * Entry points:
 *   lihan_run_demo_import( array $args = [] ): array   (CLI, admin page)
 *   Appearance → Lihan Demo Import                     (admin page)
 *
 * The theme does not need to be active: theme mods are written straight to
 * the `theme_mods_lihan` option (merged, never overwritten).
 *
 * @package Lihan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Lihan_Demo_Importer' ) ) :

	/**
	 * Runs the demo import.
	 */
	class Lihan_Demo_Importer {

		/** Meta key that marks attachments created by the importer (value: path relative to demo/images). */
		const SOURCE_META = '_lihan_demo_source';

		/** Meta key that marks posts, pages and products created by the importer. */
		const DEMO_META = '_lihan_demo';

		/** Theme slug whose theme mods are written. */
		const THEME = 'lihan';

		/**
		 * Options.
		 *
		 * @var array{update:bool,images:bool}
		 */
		private array $args;

		/** Absolute path of THEME/demo. */
		private string $dir;

		/** Absolute path of the theme. */
		private string $theme_dir;

		/**
		 * Log lines.
		 *
		 * @var string[]
		 */
		private array $log = array();

		/**
		 * Counters: created / existing per item type.
		 *
		 * @var array<string,array{created:int,existing:int}>
		 */
		private array $counts = array();

		/**
		 * Errors.
		 *
		 * @var string[]
		 */
		private array $errors = array();

		/**
		 * Page IDs by slug.
		 *
		 * @var array<string,int>
		 */
		private array $pages = array();

		/**
		 * Product category term IDs by slug.
		 *
		 * @var array<string,int>
		 */
		private array $cats = array();

		/**
		 * Product IDs by slug.
		 *
		 * @var array<string,int>
		 */
		private array $products = array();

		/**
		 * Constructor.
		 *
		 * @param array $args {
		 *     @type bool $update Re-sync existing demo products with the bundled data (prices, texts, images). Default false.
		 * }
		 */
		public function __construct( array $args = array() ) {
			$this->args      = array(
				'update' => ! empty( $args['update'] ),
			);
			$this->theme_dir = dirname( __DIR__ );
			$this->dir       = $this->theme_dir . '/demo';
		}

		/**
		 * Run every step.
		 *
		 * @return array{ok:bool,log:string[],errors:string[],counts:array}
		 */
		public function run(): array {
			if ( function_exists( 'set_time_limit' ) ) {
				set_time_limit( 0 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- image processing can take a while.
			}
			wp_raise_memory_limit( 'admin' );
			$this->load_admin_includes();

			$data    = $this->read_json( 'products.json' );
			$content = $this->read_json( 'content.json' );
			if ( null === $data || null === $content ) {
				return $this->result();
			}

			$woo = class_exists( 'WooCommerce' );

			$this->step_site_settings();
			if ( $woo ) {
				$this->step_woocommerce_settings();
				$this->step_shipping();
				$this->step_checkout_page();
				$this->step_payments();
			} else {
				$this->note( __( 'WooCommerce is not active: store settings, categories and products were skipped.', 'lihan' ) );
			}
			$this->register_image_sizes();
			$this->step_pages( $content['pages'] ?? array() );
			if ( $woo ) {
				$this->step_categories( $data['categories'] ?? array() );
				$this->step_attributes( $data['attributes'] ?? array() );
				$this->step_products( $data['products'] ?? array() );
			}
			$this->step_posts( $content['posts'] ?? array() );
			$this->step_menus();
			$this->step_branding();
			$this->step_theme_mods();
			$this->step_cleanup_defaults();

			flush_rewrite_rules( false );
			$this->note( __( 'Rewrite rules flushed.', 'lihan' ) );

			return $this->result();
		}

		/* ------------------------------------------------------------------
		 * Helpers
		 * --------------------------------------------------------------- */

		/** Load the admin APIs the importer needs (media, menus, WooCommerce admin). */
		private function load_admin_includes(): void {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}

		/**
		 * Decode a JSON file from the demo folder.
		 *
		 * @param string $file File name relative to demo/.
		 * @return array|null
		 */
		private function read_json( string $file ): ?array {
			$path = $this->dir . '/' . $file;
			if ( ! is_readable( $path ) ) {
				/* translators: %s: file path */
				$this->error( sprintf( __( 'Demo data file is missing: %s', 'lihan' ), $path ) );
				return null;
			}
			$json = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
			if ( ! is_array( $json ) ) {
				/* translators: %s: file path */
				$this->error( sprintf( __( 'Demo data file is not valid JSON: %s', 'lihan' ), $path ) );
				return null;
			}
			return $json;
		}

		/**
		 * Add a log line.
		 *
		 * @param string $message Message.
		 */
		private function note( string $message ): void {
			$this->log[] = $message;
		}

		/**
		 * Add an error line.
		 *
		 * @param string $message Message.
		 */
		private function error( string $message ): void {
			$this->errors[] = $message;
			$this->log[]    = '! ' . $message;
		}

		/**
		 * Count a created or existing item.
		 *
		 * @param string $type    Item type.
		 * @param bool   $created True when created in this run.
		 */
		private function count( string $type, bool $created ): void {
			if ( ! isset( $this->counts[ $type ] ) ) {
				$this->counts[ $type ] = array(
					'created'  => 0,
					'existing' => 0,
				);
			}
			++$this->counts[ $type ][ $created ? 'created' : 'existing' ];
		}

		/**
		 * Result array.
		 *
		 * @return array{ok:bool,log:string[],errors:string[],counts:array}
		 */
		private function result(): array {
			return array(
				'ok'     => empty( $this->errors ),
				'log'    => $this->log,
				'errors' => $this->errors,
				'counts' => $this->counts,
			);
		}

		/**
		 * Find a post of a type by slug, in any status except trash.
		 *
		 * @param string $slug      Post name.
		 * @param string $post_type Post type.
		 * @return WP_Post|null
		 */
		private function find_post( string $slug, string $post_type ): ?WP_Post {
			$posts = get_posts(
				array(
					'name'                   => $slug,
					'post_type'              => $post_type,
					'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
					'numberposts'            => 1,
					'suppress_filters'       => true,
					'update_post_term_cache' => false,
				)
			);
			return $posts ? $posts[0] : null;
		}

		/**
		 * Import an image from demo/images (or any theme-relative path) as an attachment, once.
		 *
		 * @param string $rel    Path relative to demo/images, or to the theme when prefixed with "theme:".
		 * @param string $title  Attachment title and alt text.
		 * @param int    $parent Parent post ID.
		 * @return int Attachment ID, 0 on failure.
		 */
		private function import_image( string $rel, string $title, int $parent = 0 ): int {
			$existing = get_posts(
				array(
					'post_type'        => 'attachment',
					'post_status'      => 'inherit',
					'meta_key'         => self::SOURCE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-off admin task.
					'meta_value'       => $rel, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- one-off admin task.
					'fields'           => 'ids',
					'numberposts'      => 1,
					'suppress_filters' => true,
				)
			);
			if ( $existing && get_attached_file( (int) $existing[0] ) && file_exists( (string) get_attached_file( (int) $existing[0] ) ) ) {
				$this->count( 'images', false );
				return (int) $existing[0];
			}

			$source = str_starts_with( $rel, 'theme:' )
				? $this->theme_dir . '/' . substr( $rel, 6 )
				: $this->dir . '/images/' . $rel;
			if ( ! is_readable( $source ) ) {
				/* translators: %s: file path */
				$this->error( sprintf( __( 'Image not found: %s', 'lihan' ), $source ) );
				return 0;
			}

			$tmp = wp_tempnam( basename( $source ) );
			if ( ! $tmp || ! copy( $source, $tmp ) ) {
				/* translators: %s: file path */
				$this->error( sprintf( __( 'Could not copy image: %s', 'lihan' ), $source ) );
				return 0;
			}

			$name = str_starts_with( $rel, 'theme:' ) ? 'lihan-' . basename( $source ) : str_replace( array( 'products/', '/' ), array( '', '-' ), $rel );
			$file = array(
				'name'     => sanitize_file_name( $name ),
				'tmp_name' => $tmp,
			);
			$id   = media_handle_sideload( $file, $parent, $title );
			if ( is_wp_error( $id ) ) {
				wp_delete_file( $tmp );
				/* translators: 1: file name, 2: error message */
				$this->error( sprintf( __( 'Could not import image %1$s: %2$s', 'lihan' ), $rel, $id->get_error_message() ) );
				return 0;
			}
			update_post_meta( $id, self::SOURCE_META, $rel );
			update_post_meta( $id, '_wp_attachment_image_alt', $title );
			$this->count( 'images', true );
			return (int) $id;
		}

		/**
		 * Make sure the theme image sizes exist while importing, so the
		 * generated sub-sizes match the theme even when it is not active yet.
		 */
		private function register_image_sizes(): void {
			$sizes = array(
				'lihan-portrait' => array( 600, 900 ),
				'lihan-square'   => array( 600, 600 ),
				'lihan-hero'     => array( 1920, 1000 ),
				'lihan-reel'     => array( 540, 960 ),
			);
			foreach ( $sizes as $name => $dim ) {
				if ( ! has_image_size( $name ) ) {
					add_image_size( $name, $dim[0], $dim[1], true );
				}
			}
			if ( function_exists( 'WC' ) && method_exists( WC(), 'add_image_sizes' ) ) {
				WC()->add_image_sizes(); // Re-register with the thumbnail settings saved above.
			}
		}

		/**
		 * Replace content tokens with live URLs.
		 *
		 * @param string $text Text with tokens.
		 * @return string
		 */
		private function tokens( string $text ): string {
			$phone     = '01707629095';
			$whatsapp  = '8801707629095';
			$messenger = 'https://m.me/lihanbd';
			$facebook  = 'https://www.facebook.com/share/19Zhisj6gf/';
			if ( function_exists( 'lihan_get_option' ) ) {
				$phone     = (string) ( lihan_get_option( 'contact_phone' ) ? lihan_get_option( 'contact_phone' ) : $phone );
				$whatsapp  = (string) ( lihan_get_option( 'contact_whatsapp' ) ? lihan_get_option( 'contact_whatsapp' ) : $whatsapp );
				$messenger = (string) ( lihan_get_option( 'contact_messenger' ) ? lihan_get_option( 'contact_messenger' ) : $messenger );
				$facebook  = (string) ( lihan_get_option( 'social_facebook' ) ? lihan_get_option( 'social_facebook' ) : $facebook );
			}
			$shop    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
			$account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();

			$text = strtr(
				$text,
				array(
					'{phone}'         => $phone,
					'{whatsapp_url}'  => 'https://wa.me/' . preg_replace( '/\D+/', '', $whatsapp ),
					'{messenger_url}' => $messenger,
					'{facebook_url}'  => $facebook,
					'{shop_url}'      => $shop,
					'{myaccount_url}' => $account,
				)
			);

			return (string) preg_replace_callback(
				'/\{(page|cat|product):([a-z0-9-]+)\}/',
				function ( array $m ): string {
					if ( 'page' === $m[1] ) {
						$id = $this->pages[ $m[2] ] ?? 0;
						return $id ? (string) get_permalink( $id ) : home_url( '/' . $m[2] . '/' );
					}
					if ( 'cat' === $m[1] ) {
						$term = get_term_by( 'slug', $m[2], 'product_cat' );
						$link = $term ? get_term_link( $term ) : '';
						return is_string( $link ) && '' !== $link ? $link : home_url( '/product-category/' . $m[2] . '/' );
					}
					$post = $this->find_post( $m[2], 'product' );
					return $post ? (string) get_permalink( $post ) : home_url( '/product/' . $m[2] . '/' );
				},
				$text
			);
		}

		/**
		 * Convert the compact block list from content.json into block markup.
		 *
		 * @param array $blocks List of [type, value].
		 * @return string
		 */
		private function blocks( array $blocks ): string {
			$out = array();
			foreach ( $blocks as $block ) {
				if ( ! is_array( $block ) || count( $block ) < 2 ) {
					continue;
				}
				[ $type, $value ] = $block;
				switch ( $type ) {
					case 'h2':
					case 'h3':
						$level = (int) substr( $type, 1 );
						$attrs = 2 === $level ? '' : ' {"level":' . $level . '}';
						$out[] = "<!-- wp:heading$attrs -->\n<$type class=\"wp-block-heading\">" . wp_kses_post( $this->tokens( (string) $value ) ) . "</$type>\n<!-- /wp:heading -->";
						break;
					case 'p':
						$out[] = "<!-- wp:paragraph -->\n<p>" . wp_kses_post( $this->tokens( (string) $value ) ) . "</p>\n<!-- /wp:paragraph -->";
						break;
					case 'ul':
						$items = '';
						foreach ( (array) $value as $item ) {
							$items .= "<!-- wp:list-item -->\n<li>" . wp_kses_post( $this->tokens( (string) $item ) ) . "</li>\n<!-- /wp:list-item -->\n";
						}
						$out[] = "<!-- wp:list -->\n<ul class=\"wp-block-list\">" . $items . "</ul>\n<!-- /wp:list -->";
						break;
					case 'table':
						$head = '';
						foreach ( (array) ( $value['head'] ?? array() ) as $cell ) {
							$head .= '<th>' . esc_html( (string) $cell ) . '</th>';
						}
						$rows = '';
						foreach ( (array) ( $value['rows'] ?? array() ) as $row ) {
							$rows .= '<tr>';
							foreach ( (array) $row as $cell ) {
								$rows .= '<td>' . esc_html( (string) $cell ) . '</td>';
							}
							$rows .= '</tr>';
						}
						$out[] = "<!-- wp:table -->\n<figure class=\"wp-block-table\"><table class=\"has-fixed-layout\"><thead><tr>$head</tr></thead><tbody>$rows</tbody></table></figure>\n<!-- /wp:table -->";
						break;
					case 'buttons':
						$buttons = '';
						foreach ( (array) $value as $button ) {
							$label    = $this->tokens( (string) ( $button[0] ?? '' ) );
							$url      = $this->tokens( (string) ( $button[1] ?? '' ) );
							$buttons .= "<!-- wp:button -->\n<div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\" href=\"" . esc_url( $url, array( 'http', 'https', 'tel', 'mailto' ) ) . '">' . esc_html( $label ) . "</a></div>\n<!-- /wp:button -->\n";
						}
						$out[] = "<!-- wp:buttons -->\n<div class=\"wp-block-buttons\">" . $buttons . "</div>\n<!-- /wp:buttons -->";
						break;
				}
			}
			return implode( "\n\n", $out );
		}

		/* ------------------------------------------------------------------
		 * Steps
		 * --------------------------------------------------------------- */

		/** Site name, tagline, permalinks. */
		private function step_site_settings(): void {
			update_option( 'blogname', 'Lihan' );
			update_option( 'blogdescription', 'Modest fashion, made with care' );
			if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
				global $wp_rewrite;
				$wp_rewrite->set_permalink_structure( '/%postname%/' );
			}
			$this->note( __( 'Site title, tagline and permalinks set.', 'lihan' ) );
		}

		/** Store location, currency and catalogue settings; skip the onboarding wizard. */
		private function step_woocommerce_settings(): void {
			$options = array(
				'woocommerce_default_country'                 => 'BD:BD-13',
				'woocommerce_store_city'                      => 'Dhaka',
				'woocommerce_allowed_countries'               => 'specific',
				'woocommerce_specific_allowed_countries'      => array( 'BD' ),
				'woocommerce_ship_to_countries'               => '',
				'woocommerce_currency'                        => 'BDT',
				'woocommerce_currency_pos'                    => 'left',
				'woocommerce_price_thousand_sep'              => ',',
				'woocommerce_price_decimal_sep'               => '.',
				'woocommerce_price_num_decimals'              => 0,
				'woocommerce_calc_taxes'                      => 'no',
				'woocommerce_enable_reviews'                  => 'yes',
				'woocommerce_review_rating_required'          => 'yes',
				'woocommerce_enable_ajax_add_to_cart'         => 'yes',
				'woocommerce_cart_redirect_after_add'         => 'no',
				// Product images: 600px wide, 2:3 portrait thumbnails to match the theme's product cards.
				'woocommerce_thumbnail_image_width'           => 600,
				'woocommerce_single_image_width'              => 900,
				'woocommerce_thumbnail_cropping'              => 'custom',
				'woocommerce_thumbnail_cropping_custom_width' => '2',
				'woocommerce_thumbnail_cropping_custom_height' => '3',
				// The store is live (WooCommerce starts new stores in "coming soon" mode).
				'woocommerce_coming_soon'                     => 'no',
				'woocommerce_onboarding_profile'              => array( 'skipped' => true ),
				'woocommerce_show_marketplace_suggestions'    => 'no',
			);
			foreach ( $options as $key => $value ) {
				update_option( $key, $value );
			}

			// Hide the setup task lists (the old "woocommerce_task_list_hidden" option maps to this list).
			$hidden = get_option( 'woocommerce_task_list_hidden_lists', array() );
			$hidden = is_array( $hidden ) ? $hidden : array();
			update_option( 'woocommerce_task_list_hidden_lists', array_values( array_unique( array_merge( $hidden, array( 'setup', 'extended' ) ) ) ) );
			// Legacy option name: WooCommerce maps it onto the list above when its task lists are loaded.
			if ( 'yes' !== get_option( 'woocommerce_task_list_hidden' ) ) {
				update_option( 'woocommerce_task_list_hidden', 'yes' );
			}

			if ( class_exists( 'WC_Install' ) ) {
				WC_Install::create_pages();
			}
			$this->note( __( 'WooCommerce: Bangladesh, BDT (৳, no decimals), onboarding skipped, store pages checked.', 'lihan' ) );
		}

		/**
		 * One "Bangladesh" zone with two delivery areas the customer picks at checkout:
		 * Inside Dhaka (৳70) and Outside Dhaka (৳130). Free delivery over the theme's
		 * threshold (default ৳5,000) is applied by the theme to whichever area is chosen.
		 *
		 * Older imports used two zones matched by district; those are replaced.
		 */
		private function step_shipping(): void {
			if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
				return;
			}
			$areas = array(
				array(
					'title' => 'Inside Dhaka',
					'cost'  => 70,
				),
				array(
					'title' => 'Outside Dhaka',
					'cost'  => 130,
				),
			);

			$zone_id = 0;
			foreach ( WC_Shipping_Zones::get_zones( 'admin' ) as $z ) {
				$name = (string) $z['zone_name'];
				if ( 'Bangladesh' === $name ) {
					$zone_id = (int) $z['id'];
				} elseif ( in_array( $name, array( 'Inside Dhaka', 'Outside Dhaka' ), true ) ) {
					// Legacy zones from earlier versions of this importer.
					$legacy = new WC_Shipping_Zone( (int) $z['id'] );
					$legacy->delete();
					$this->note( sprintf( 'Shipping: removed old zone "%s".', $name ) );
				}
			}

			$created = 0 === $zone_id;
			$zone    = new WC_Shipping_Zone( $created ? null : $zone_id );
			if ( $created ) {
				$zone->set_zone_name( 'Bangladesh' );
				$zone->set_zone_order( 0 );
				$zone->set_locations(
					array(
						array(
							'code' => 'BD',
							'type' => 'country',
						),
					)
				);
				$zone->save();
			}
			$this->count( 'shipping zones', $created );

			$existing = array();
			foreach ( $zone->get_shipping_methods() as $method ) {
				if ( 'flat_rate' === $method->id ) {
					$existing[] = strtolower( (string) $method->get_option( 'title' ) );
				}
			}
			foreach ( $areas as $area ) {
				if ( in_array( strtolower( $area['title'] ), $existing, true ) ) {
					continue;
				}
				$instance = $zone->add_shipping_method( 'flat_rate' );
				update_option(
					'woocommerce_flat_rate_' . $instance . '_settings',
					array(
						'title'      => $area['title'],
						'tax_status' => 'none',
						'cost'       => (string) $area['cost'],
					)
				);
			}
			WC_Cache_Helper::get_transient_version( 'shipping', true );
			$this->note( __( 'Shipping: zone Bangladesh with Inside Dhaka ৳70 and Outside Dhaka ৳130 (free delivery over the theme threshold).', 'lihan' ) );
		}

		/**
		 * Use the classic checkout shortcode: the theme's simple checkout (name, phone,
		 * address, delivery area) needs it, because the Checkout block cannot drop core fields.
		 * Only the untouched default block page is converted.
		 */
		private function step_checkout_page(): void {
			if ( ! function_exists( 'wc_get_page_id' ) ) {
				return;
			}
			$page = get_post( wc_get_page_id( 'checkout' ) );
			if ( ! $page instanceof WP_Post ) {
				return;
			}
			if ( has_shortcode( $page->post_content, 'woocommerce_checkout' ) ) {
				return;
			}
			if ( '' !== trim( $page->post_content ) && ! str_contains( $page->post_content, '<!-- wp:woocommerce/checkout ' ) && ! str_contains( $page->post_content, '<!-- wp:woocommerce/checkout -->' ) ) {
				$this->note( 'Checkout page has custom content; left unchanged.' );
				return;
			}
			wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_content' => "<!-- wp:shortcode -->\n[woocommerce_checkout]\n<!-- /wp:shortcode -->",
				)
			);
			$this->note( 'Checkout page switched to the classic [woocommerce_checkout] shortcode.' );
		}

		/** Enable Cash on Delivery. */
		private function step_payments(): void {
			$cod = get_option( 'woocommerce_cod_settings', array() );
			$cod = is_array( $cod ) ? $cod : array();
			$cod = array_merge(
				array(
					'title'              => 'Cash on Delivery',
					'description'        => 'Pay in cash when your order arrives. Check the product first, then pay.',
					'instructions'       => 'Please keep the exact amount ready. Our courier will call you before delivery.',
					'enable_for_methods' => array(),
					'enable_for_virtual' => 'yes',
				),
				$cod
			);
			$cod['enabled'] = 'yes';
			$cod['title']   = 'Cash on Delivery';
			update_option( 'woocommerce_cod_settings', $cod );

			$order = get_option( 'woocommerce_gateway_order', array() );
			$order = is_array( $order ) ? $order : array();
			if ( ! isset( $order['cod'] ) ) {
				$order['cod'] = 0;
				update_option( 'woocommerce_gateway_order', $order );
			}
			$this->note( __( 'Payments: Cash on Delivery enabled.', 'lihan' ) );
		}

		/**
		 * Pages, front page, posts page, privacy and terms pages.
		 *
		 * @param array $pages Page definitions.
		 */
		private function step_pages( array $pages ): void {
			// Store pages first so tokens can link to them.
			if ( function_exists( 'wc_get_page_id' ) ) {
				foreach ( array( 'shop', 'cart', 'checkout', 'myaccount' ) as $key ) {
					$id = wc_get_page_id( $key );
					if ( $id > 0 ) {
						$this->pages[ 'wc-' . $key ] = $id;
					}
				}
			}

			// Pass 1: make sure every page exists, so links between pages resolve in pass 2.
			$fresh = array();
			foreach ( $pages as $def ) {
				$slug = sanitize_title( (string) ( $def['slug'] ?? '' ) );
				if ( '' === $slug ) {
					continue;
				}
				$post = $this->find_post( $slug, 'page' );
				// WordPress creates a draft privacy page on install; claim drafts, keep published pages.
				// Update mode only re-syncs pages the importer created; other published pages are never overwritten.
				if ( $post && 'publish' === $post->post_status && ( ! $this->args['update'] || ! get_post_meta( $post->ID, self::DEMO_META, true ) ) ) {
					$this->pages[ $slug ] = $post->ID;
					$this->count( 'pages', false );
					continue;
				}
				if ( ! $post ) {
					$id = wp_insert_post(
						array(
							'post_type'    => 'page',
							'post_status'  => 'publish',
							'post_name'    => $slug,
							'post_title'   => (string) $def['title'],
							'post_content' => '',
						),
						true
					);
					if ( is_wp_error( $id ) ) {
						/* translators: 1: page title, 2: error message */
						$this->error( sprintf( __( 'Could not create page %1$s: %2$s', 'lihan' ), $def['title'], $id->get_error_message() ) );
						continue;
					}
					update_post_meta( $id, self::DEMO_META, 1 );
					$this->count( 'pages', true );
				} else {
					$id = $post->ID;
					$this->count( 'pages', false );
				}
				$this->pages[ $slug ] = (int) $id;
				$fresh[ $slug ]       = $def;
			}

			// Pass 2: fill content of pages created (or claimed) in this run.
			foreach ( $fresh as $slug => $def ) {
				wp_update_post(
					array(
						'ID'           => $this->pages[ $slug ],
						'post_title'   => (string) $def['title'],
						'post_name'    => $slug,
						'post_status'  => 'publish',
						'post_content' => $this->blocks( (array) ( $def['blocks'] ?? array() ) ),
					)
				);
			}

			// Roles.
			foreach ( $pages as $def ) {
				$slug = sanitize_title( (string) ( $def['slug'] ?? '' ) );
				$id   = $this->pages[ $slug ] ?? 0;
				if ( ! $id || empty( $def['role'] ) ) {
					continue;
				}
				switch ( $def['role'] ) {
					case 'front':
						update_option( 'show_on_front', 'page' );
						update_option( 'page_on_front', $id );
						break;
					case 'posts':
						update_option( 'page_for_posts', $id );
						break;
					case 'privacy':
						update_option( 'wp_page_for_privacy_policy', $id );
						break;
					case 'terms':
						update_option( 'woocommerce_terms_page_id', $id );
						break;
				}
			}
			$this->note( __( 'Pages ready; Home is the static front page and Blog the posts page.', 'lihan' ) );
		}

		/**
		 * Product categories with thumbnails.
		 *
		 * @param array $cats Category definitions.
		 */
		private function step_categories( array $cats ): void {
			foreach ( $cats as $def ) {
				$slug = sanitize_title( (string) $def['slug'] );
				$term = get_term_by( 'slug', $slug, 'product_cat' );
				if ( $term ) {
					$term_id = (int) $term->term_id;
					$this->count( 'categories', false );
				} else {
					$parent = 0;
					if ( ! empty( $def['parent'] ) && isset( $this->cats[ $def['parent'] ] ) ) {
						$parent = $this->cats[ $def['parent'] ];
					}
					$res = wp_insert_term(
						(string) $def['name'],
						'product_cat',
						array(
							'slug'        => $slug,
							'description' => (string) ( $def['description'] ?? '' ),
							'parent'      => $parent,
						)
					);
					if ( is_wp_error( $res ) ) {
						/* translators: 1: category name, 2: error message */
						$this->error( sprintf( __( 'Could not create category %1$s: %2$s', 'lihan' ), $def['name'], $res->get_error_message() ) );
						continue;
					}
					$term_id = (int) $res['term_id'];
					update_term_meta( $term_id, 'order', (int) ( $def['menu_order'] ?? 0 ) );
					$this->count( 'categories', true );
				}
				$this->cats[ $slug ] = $term_id;

				if ( ! empty( $def['image'] ) && ! get_term_meta( $term_id, 'thumbnail_id', true ) ) {
					$img = $this->import_image( (string) $def['image'], (string) $def['name'] );
					if ( $img ) {
						update_term_meta( $term_id, 'thumbnail_id', $img );
					}
				}
			}
			$this->note( __( 'Product categories ready.', 'lihan' ) );
		}

		/**
		 * Global attributes (pa_size, pa_color) and their terms.
		 *
		 * @param array $attributes Attribute definitions keyed by slug.
		 */
		private function step_attributes( array $attributes ): void {
			foreach ( $attributes as $slug => $def ) {
				$taxonomy = wc_attribute_taxonomy_name( $slug );
				if ( ! wc_attribute_taxonomy_id_by_name( $taxonomy ) ) {
					$res = wc_create_attribute(
						array(
							'name'         => (string) $def['name'],
							'slug'         => $slug,
							'type'         => 'select',
							'order_by'     => 'menu_order',
							'has_archives' => false,
						)
					);
					if ( is_wp_error( $res ) ) {
						/* translators: 1: attribute name, 2: error message */
						$this->error( sprintf( __( 'Could not create attribute %1$s: %2$s', 'lihan' ), $def['name'], $res->get_error_message() ) );
						continue;
					}
					$this->count( 'attributes', true );
				} else {
					$this->count( 'attributes', false );
				}
				// The taxonomy is registered on init; register it now if it was just created.
				if ( ! taxonomy_exists( $taxonomy ) ) {
					register_taxonomy(
						$taxonomy,
						array( 'product' ),
						array(
							'hierarchical' => false,
							'show_ui'      => false,
							'query_var'    => true,
							'rewrite'      => false,
						)
					);
				}
				foreach ( (array) $def['terms'] as $order => $t ) {
					$name = (string) $t['name'];
					$term = get_term_by( 'slug', sanitize_title( $name ), $taxonomy );
					if ( ! $term ) {
						$res = wp_insert_term( $name, $taxonomy, array( 'slug' => sanitize_title( $name ) ) );
						if ( is_wp_error( $res ) ) {
							$this->error( $res->get_error_message() );
							continue;
						}
						$term_id = (int) $res['term_id'];
						update_term_meta( $term_id, 'order', (int) $order );
					} else {
						$term_id = (int) $term->term_id;
					}
					if ( ! empty( $t['hex'] ) && ! get_term_meta( $term_id, 'lihan_swatch_color', true ) ) {
						update_term_meta( $term_id, 'lihan_swatch_color', sanitize_hex_color( (string) $t['hex'] ) );
					}
				}
			}
			delete_transient( 'wc_attribute_taxonomies' );
			$this->note( __( 'Attributes ready: Size and Color (with swatch colours).', 'lihan' ) );
		}

		/**
		 * Products.
		 *
		 * @param array $products Product definitions.
		 */
		private function step_products( array $products ): void {
			$tag_ids = array();
			$now     = time();
			foreach ( $products as $index => $def ) {
				$sku = (string) $def['sku'];
				$id  = (int) wc_get_product_id_by_sku( $sku );
				if ( ! $id ) {
					$post = $this->find_post( (string) $def['slug'], 'product' );
					$id   = $post ? (int) $post->ID : 0;
				}
				$created = 0 === $id;

				if ( ! $created && ! $this->args['update'] ) {
					$this->products[ $def['slug'] ] = $id;
					$this->count( 'products', false );
					$product = wc_get_product( $id );
					if ( $product instanceof WC_Product ) {
						// Recover from an interrupted earlier run: attach the photos if the product has none.
						if ( ! $product->get_image_id() ) {
							$this->attach_product_images( $product, $def );
						}
						if ( $product instanceof WC_Product_Variable ) {
							$this->ensure_variations( $product, $def );
						}
					}
					$this->ensure_reviews( $id, (array) ( $def['reviews'] ?? array() ) );
					continue;
				}

				try {
					$id = $this->save_product( $def, $id, $index, $now, $tag_ids );
				} catch ( Exception $e ) {
					/* translators: 1: product name, 2: error message */
					$this->error( sprintf( __( 'Could not save product %1$s: %2$s', 'lihan' ), $def['name'], $e->getMessage() ) );
					continue;
				}
				if ( ! $id ) {
					continue;
				}
				$this->products[ $def['slug'] ] = $id;
				$this->count( 'products', $created );
			}
			if ( function_exists( 'wc_delete_product_transients' ) ) {
				wc_delete_product_transients();
			}
			$this->note( __( 'Products ready.', 'lihan' ) );
		}

		/**
		 * Create (or, in update mode, re-sync) one product with its images, variations and reviews.
		 *
		 * @param array $def      Product definition.
		 * @param int   $id       Existing product ID, 0 to create.
		 * @param int   $index    Position in the data file.
		 * @param int   $now      Import timestamp.
		 * @param array $tag_ids  Product tag IDs by slug (cache, filled as tags are created).
		 * @return int Product ID, 0 on failure.
		 * @throws WC_Data_Exception When WooCommerce rejects a value (for example a duplicate SKU).
		 */
		private function save_product( array $def, int $id, int $index, int $now, array &$tag_ids ): int {
			$sku     = (string) $def['sku'];
			$created = 0 === $id;

			$is_variable = 'variable' === $def['type'];
			if ( $created ) {
				$product = $is_variable ? new WC_Product_Variable() : new WC_Product_Simple();
			} else {
				$product = wc_get_product( $id );
				if ( ! $product ) {
					return 0;
				}
			}

			$product->set_name( (string) $def['name'] );
			$product->set_slug( (string) $def['slug'] );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$description = wp_kses_post( (string) $def['description'] );
			// Mark Bangla copy (Bengali Unicode block) so screen readers and fonts pick the right language.
			if ( preg_match( '/[\x{0980}-\x{09FF}]/u', $description ) ) {
				$description = '<div lang="bn">' . $description . '</div>';
			}
			$product->set_description( $description );
			$product->set_short_description( wp_kses_post( (string) $def['short_description'] ) );
			$product->set_featured( ! empty( $def['featured'] ) );
			$product->set_menu_order( (int) ( $def['menu_order'] ?? $index ) );
			$product->set_reviews_allowed( true );
			$product->set_manage_stock( false );
			$product->set_stock_status( 'instock' );
			if ( $created ) {
				$product->set_sku( $sku );
				// Newest first in the order of the data file, so "latest" sorting shows the abaya sets first.
				$product->set_date_created( $now - ( $index * DAY_IN_SECONDS ) );
			}

			if ( ! $is_variable ) {
				$product->set_regular_price( (string) $def['regular_price'] );
				$product->set_sale_price( null !== $def['sale_price'] ? (string) $def['sale_price'] : '' );
			}

			// Category and tags.
			if ( isset( $this->cats[ $def['category'] ] ) ) {
				$product->set_category_ids( array( $this->cats[ $def['category'] ] ) );
			}
			$tags = array();
			foreach ( (array) $def['tags'] as $tag ) {
				if ( ! isset( $tag_ids[ $tag ] ) ) {
					$term = get_term_by( 'slug', $tag, 'product_tag' );
					if ( ! $term ) {
						$res = wp_insert_term( ucfirst( $tag ), 'product_tag', array( 'slug' => $tag ) );
						$tag_ids[ $tag ] = is_wp_error( $res ) ? 0 : (int) $res['term_id'];
					} else {
						$tag_ids[ $tag ] = (int) $term->term_id;
					}
				}
				if ( $tag_ids[ $tag ] ) {
					$tags[] = $tag_ids[ $tag ];
				}
			}
			$product->set_tag_ids( $tags );

			// Attributes.
			$product->set_attributes( $this->build_attributes( $def ) );

			$id = $product->save();
			if ( ! $id ) {
				/* translators: %s: product name */
				$this->error( sprintf( __( 'Could not save product %s.', 'lihan' ), $def['name'] ) );
				return 0;
			}
			if ( $created ) {
				update_post_meta( $id, self::DEMO_META, 1 );
			}

			// Images (after save, so attachments get the product as parent).
			$this->attach_product_images( $product, $def );

			if ( $product instanceof WC_Product_Variable ) {
				$this->ensure_variations( $product, $def );
			}
			$this->ensure_reviews( $id, (array) ( $def['reviews'] ?? array() ) );

			return (int) $id;
		}

		/**
		 * Import a product's photos (once) and set the main image and gallery.
		 *
		 * @param WC_Product $product Saved product.
		 * @param array      $def     Product definition.
		 */
		private function attach_product_images( WC_Product $product, array $def ): void {
			$image_ids = array();
			foreach ( array_values( (array) ( $def['images'] ?? array() ) ) as $n => $rel ) {
				/* translators: 1: product name, 2: image number */
				$alt = 0 === $n ? (string) $def['name'] : sprintf( __( '%1$s, view %2$d', 'lihan' ), $def['name'], $n + 1 );
				$img = $this->import_image( (string) $rel, $alt, $product->get_id() );
				if ( $img ) {
					$image_ids[] = $img;
				}
			}
			if ( $image_ids ) {
				$product->set_image_id( array_shift( $image_ids ) );
				$product->set_gallery_image_ids( $image_ids );
				$product->save();
			}
		}

		/**
		 * Build WC_Product_Attribute objects for a product definition.
		 *
		 * @param array $def Product definition.
		 * @return WC_Product_Attribute[]
		 */
		private function build_attributes( array $def ): array {
			$attributes = array();
			$position   = 0;
			// Colour first so swatches read colour → size.
			$defs = (array) $def['attributes'];
			uksort(
				$defs,
				static function ( $a, $b ): int {
					return ( 'color' === $b ) <=> ( 'color' === $a );
				}
			);
			foreach ( $defs as $slug => $values ) {
				$taxonomy = wc_attribute_taxonomy_name( (string) $slug );
				$term_ids = array();
				foreach ( (array) $values as $value ) {
					$term = get_term_by( 'slug', sanitize_title( (string) $value ), $taxonomy );
					if ( $term ) {
						$term_ids[] = (int) $term->term_id;
					}
				}
				if ( ! $term_ids ) {
					continue;
				}
				$attr = new WC_Product_Attribute();
				$attr->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
				$attr->set_name( $taxonomy );
				$attr->set_options( $term_ids );
				$attr->set_position( $position++ );
				$attr->set_visible( true );
				$attr->set_variation( 'variable' === $def['type'] );
				$attributes[] = $attr;
			}
			if ( ! empty( $def['fabric'] ) ) {
				$attr = new WC_Product_Attribute();
				$attr->set_id( 0 );
				$attr->set_name( 'Fabric' );
				$attr->set_options( array( (string) $def['fabric'] ) );
				$attr->set_position( $position );
				$attr->set_visible( true );
				$attr->set_variation( false );
				$attributes[] = $attr;
			}
			return $attributes;
		}

		/**
		 * Create missing variations (one per attribute combination) for a variable product.
		 *
		 * @param WC_Product_Variable $product Product.
		 * @param array               $def     Product definition.
		 */
		private function ensure_variations( WC_Product_Variable $product, array $def ): void {
			$axes = array();
			foreach ( (array) $def['attributes'] as $slug => $values ) {
				$axes[ wc_attribute_taxonomy_name( (string) $slug ) ] = array_map( 'sanitize_title', (array) $values );
			}
			if ( ! $axes ) {
				return;
			}
			// Cartesian product of the attribute values.
			$combos = array( array() );
			foreach ( $axes as $taxonomy => $values ) {
				$next = array();
				foreach ( $combos as $combo ) {
					foreach ( $values as $value ) {
						$next[] = $combo + array( $taxonomy => $value );
					}
				}
				$combos = $next;
			}

			$existing = array();
			foreach ( $product->get_children() as $child_id ) {
				$child = wc_get_product( $child_id );
				if ( $child instanceof WC_Product_Variation ) {
					$attrs = $child->get_attributes();
					ksort( $attrs );
					$existing[ wp_json_encode( $attrs ) ] = $child;
				}
			}

			$sold_out = array();
			foreach ( (array) ( $def['out_of_stock'] ?? array() ) as $slug => $value ) {
				$sold_out[ wc_attribute_taxonomy_name( (string) $slug ) ] = sanitize_title( (string) $value );
			}

			$changed = false;
			foreach ( $combos as $i => $combo ) {
				ksort( $combo );
				$key = wp_json_encode( $combo );
				if ( isset( $existing[ $key ] ) && ! $this->args['update'] ) {
					continue;
				}
				$variation = $existing[ $key ] ?? new WC_Product_Variation();
				$variation->set_parent_id( $product->get_id() );
				$variation->set_attributes( $combo );
				$variation->set_regular_price( (string) $def['regular_price'] );
				$variation->set_sale_price( null !== $def['sale_price'] ? (string) $def['sale_price'] : '' );
				$variation->set_menu_order( $i );
				$variation->set_manage_stock( false );
				$out = $sold_out && array_intersect_assoc( $sold_out, $combo ) === $sold_out;
				$variation->set_stock_status( $out ? 'outofstock' : 'instock' );
				if ( ! $variation->get_id() ) {
					$variation->set_sku( $def['sku'] . '-' . implode( '-', array_map( 'strtoupper', $combo ) ) );
				}
				$variation->save();
				$changed = true;
			}
			if ( $changed ) {
				WC_Product_Variable::sync( $product->get_id() );
				wc_delete_product_transients( $product->get_id() );
			}
			// Default selection: none, so shoppers pick a size consciously.
		}

		/**
		 * Add approved 5-star reviews once.
		 *
		 * @param int   $product_id Product ID.
		 * @param array $reviews    Reviews (author, rating, content).
		 */
		private function ensure_reviews( int $product_id, array $reviews ): void {
			if ( ! $reviews ) {
				return;
			}
			$added = false;
			foreach ( $reviews as $n => $review ) {
				$author = (string) $review['author'];
				$found  = get_comments(
					array(
						'post_id'      => $product_id,
						'type'         => 'review',
						'status'       => 'all',
						'count'        => true,
						'search'       => $author,
						'meta_key'     => self::DEMO_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-off admin task.
					)
				);
				if ( $found ) {
					$this->count( 'reviews', false );
					continue;
				}
				$date = gmdate( 'Y-m-d H:i:s', time() - ( ( $n + 1 ) * 6 * DAY_IN_SECONDS ) );
				$id   = wp_insert_comment(
					array(
						'comment_post_ID'      => $product_id,
						'comment_author'       => $author,
						'comment_author_email' => sanitize_title( $author ) . '@example.com',
						'comment_content'      => (string) $review['content'],
						'comment_type'         => 'review',
						'comment_approved'     => 1,
						'comment_date'         => get_date_from_gmt( $date ),
						'comment_date_gmt'     => $date,
						'user_id'              => 0,
					)
				);
				if ( $id ) {
					update_comment_meta( $id, 'rating', max( 1, min( 5, (int) $review['rating'] ) ) );
					update_comment_meta( $id, 'verified', 1 );
					update_comment_meta( $id, self::DEMO_META, 1 );
					$this->count( 'reviews', true );
					$added = true;
				}
			}
			if ( $added && class_exists( 'WC_Comments' ) ) {
				WC_Comments::clear_transients( $product_id );
			}
		}

		/**
		 * Blog posts with featured images.
		 *
		 * @param array $posts Post definitions.
		 */
		private function step_posts( array $posts ): void {
			$cat = get_term_by( 'slug', 'styling-tips', 'category' );
			if ( ! $cat ) {
				$res    = wp_insert_term( 'Styling Tips', 'category', array( 'slug' => 'styling-tips' ) );
				$cat_id = is_wp_error( $res ) ? 0 : (int) $res['term_id'];
			} else {
				$cat_id = (int) $cat->term_id;
			}

			foreach ( $posts as $i => $def ) {
				$slug = sanitize_title( (string) $def['slug'] );
				if ( $this->find_post( $slug, 'post' ) ) {
					$this->count( 'posts', false );
					continue;
				}
				$date = gmdate( 'Y-m-d H:i:s', time() - ( ( $i + 1 ) * 9 * DAY_IN_SECONDS ) );
				$id   = wp_insert_post(
					array(
						'post_type'     => 'post',
						'post_status'   => 'publish',
						'post_name'     => $slug,
						'post_title'    => (string) $def['title'],
						'post_excerpt'  => (string) ( $def['excerpt'] ?? '' ),
						'post_content'  => $this->blocks( (array) $def['blocks'] ),
						'post_category' => $cat_id ? array( $cat_id ) : array(),
						'post_date'     => get_date_from_gmt( $date ),
						'post_date_gmt' => $date,
					),
					true
				);
				if ( is_wp_error( $id ) ) {
					$this->error( $id->get_error_message() );
					continue;
				}
				update_post_meta( $id, self::DEMO_META, 1 );
				if ( ! empty( $def['image'] ) ) {
					$img = $this->import_image( (string) $def['image'], (string) $def['title'], $id );
					if ( $img ) {
						set_post_thumbnail( $id, $img );
					}
				}
				$this->count( 'posts', true );
			}
			$this->note( __( 'Blog posts ready.', 'lihan' ) );
		}

		/** Main, footer information and footer customer care menus. */
		private function step_menus(): void {
			$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
			$cat      = fn( string $slug, array $children = array() ): array => array(
				'type'     => 'cat',
				'slug'     => $slug,
				'children' => $children,
			);
			$page     = fn( string $slug, string $title = '' ): array => array(
				'type'  => 'page',
				'slug'  => $slug,
				'title' => $title,
			);

			$menus = array(
				'main'     => array(
					'name'      => 'Main menu',
					'locations' => array( 'primary', 'mobile' ),
					'items'     => array(
						array(
							'type'  => 'custom',
							'title' => 'New Arrivals',
							'url'   => add_query_arg( 'orderby', 'date', $shop_url ),
						),
						$cat( 'abaya-set' ),
						$cat( 'hijab' ),
						$cat( 'niqab', array( $cat( 'niqab-combo' ) ) ),
						$cat( 'inner-cap' ),
						$cat( 'hat-moja' ),
						$page( 'contact' ),
					),
				),
				'footer-1' => array(
					'name'      => 'Footer Information',
					'locations' => array( 'footer-1' ),
					'items'     => array( $page( 'about-us' ), $page( 'contact' ), $page( 'blog' ), $page( 'size-guide' ), $page( 'faq' ) ),
				),
				'footer-2' => array(
					'name'      => 'Footer Customer Care',
					'locations' => array( 'footer-2' ),
					'items'     => array(
						$page( 'wc-myaccount', 'My Account' ),
						$page( 'order-procedure' ),
						$page( 'delivery-information' ),
						$page( 'return-exchange-policy', 'Return & Exchange' ),
						$page( 'privacy-policy' ),
						$page( 'terms-conditions' ),
					),
				),
			);

			$locations = array();
			foreach ( $menus as $def ) {
				$menu = wp_get_nav_menu_object( $def['name'] );
				if ( $menu ) {
					$menu_id = (int) $menu->term_id;
					$this->count( 'menus', false );
				} else {
					$menu_id = wp_create_nav_menu( $def['name'] );
					if ( is_wp_error( $menu_id ) ) {
						$this->error( $menu_id->get_error_message() );
						continue;
					}
					$this->count( 'menus', true );
				}
				$this->add_menu_items( (int) $menu_id, $def['items'], 0 );
				foreach ( $def['locations'] as $location ) {
					$locations[ $location ] = (int) $menu_id;
				}
			}

			// Assign locations in the lihan theme mods (merge; keep valid user assignments).
			$mods    = $this->get_theme_mods();
			$current = isset( $mods['nav_menu_locations'] ) && is_array( $mods['nav_menu_locations'] ) ? $mods['nav_menu_locations'] : array();
			foreach ( $locations as $location => $menu_id ) {
				if ( empty( $current[ $location ] ) || ! wp_get_nav_menu_object( (int) $current[ $location ] ) ) {
					$current[ $location ] = $menu_id;
				}
			}
			$mods['nav_menu_locations'] = $current;
			$this->save_theme_mods( $mods );
			$this->note( __( 'Menus ready and assigned (primary, mobile, footer-1, footer-2).', 'lihan' ) );
		}

		/**
		 * Add menu items that are not in the menu yet.
		 *
		 * @param int   $menu_id Menu ID.
		 * @param array $items   Item definitions.
		 * @param int   $parent  Parent menu item ID.
		 */
		private function add_menu_items( int $menu_id, array $items, int $parent ): void {
			$existing = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
			$existing = is_array( $existing ) ? $existing : array();

			foreach ( $items as $item ) {
				// No explicit position: WordPress appends each new item after the last one, so
				// sub-items land right after their parent (depth-first, as the menu editor expects).
				$args = array(
					'menu-item-status'    => 'publish',
					'menu-item-parent-id' => $parent,
				);
				switch ( $item['type'] ) {
					case 'cat':
						$term_id = $this->cats[ $item['slug'] ] ?? 0;
						if ( ! $term_id ) {
							$term    = get_term_by( 'slug', $item['slug'], 'product_cat' );
							$term_id = $term ? (int) $term->term_id : 0;
						}
						if ( ! $term_id ) {
							continue 2;
						}
						$args += array(
							'menu-item-type'      => 'taxonomy',
							'menu-item-object'    => 'product_cat',
							'menu-item-object-id' => $term_id,
						);
						$match = fn( $e ) => 'taxonomy' === $e->type && (int) $e->object_id === $term_id;
						break;
					case 'page':
						$page_id = $this->pages[ $item['slug'] ] ?? 0;
						if ( ! $page_id ) {
							continue 2;
						}
						$args += array(
							'menu-item-type'      => 'post_type',
							'menu-item-object'    => 'page',
							'menu-item-object-id' => $page_id,
						);
						if ( ! empty( $item['title'] ) ) {
							$args['menu-item-title'] = $item['title'];
						}
						$match = fn( $e ) => 'post_type' === $e->type && (int) $e->object_id === $page_id;
						break;
					default:
						$args += array(
							'menu-item-type'  => 'custom',
							'menu-item-title' => $item['title'],
							'menu-item-url'   => $item['url'],
						);
						$title = $item['title'];
						$match = fn( $e ) => 'custom' === $e->type && $e->title === $title;
				}

				$found = null;
				foreach ( $existing as $e ) {
					if ( (int) $e->menu_item_parent === $parent && $match( $e ) ) {
						$found = $e;
						break;
					}
				}
				if ( $found ) {
					$item_id = (int) $found->ID;
				} else {
					$item_id = wp_update_nav_menu_item( $menu_id, 0, $args );
					if ( is_wp_error( $item_id ) ) {
						$this->error( $item_id->get_error_message() );
						continue;
					}
				}
				if ( ! empty( $item['children'] ) ) {
					$this->add_menu_items( $menu_id, $item['children'], (int) $item_id );
				}
			}
		}

		/** Custom logo and site icon. */
		private function step_branding(): void {
			$mods = $this->get_theme_mods();
			if ( empty( $mods['custom_logo'] ) || ! wp_attachment_is_image( (int) $mods['custom_logo'] ) ) {
				$logo = $this->import_image( 'theme:assets/images/logo.png', 'Lihan Lifestyle' );
				if ( $logo ) {
					$mods['custom_logo'] = $logo;
					$this->save_theme_mods( $mods );
				}
			}
			if ( ! get_option( 'site_icon' ) || ! wp_attachment_is_image( (int) get_option( 'site_icon' ) ) ) {
				$icon = $this->import_image( 'theme:assets/images/favicon.png', 'Lihan site icon' );
				if ( $icon ) {
					update_option( 'site_icon', $icon );
				}
			}
			$this->note( __( 'Logo and site icon set.', 'lihan' ) );
		}

		/** Homepage images (hero, promo, image with text): set only when empty. */
		private function step_theme_mods(): void {
			$mods   = $this->get_theme_mods();
			$images = array(
				'home_hero_1_image'        => array( 'hero-1.jpg', 'The new abaya edit' ),
				'home_hero_1_image_mobile' => array( 'hero-1-mobile.jpg', 'The new abaya edit' ),
				'home_hero_2_image'        => array( 'hero-2.jpg', 'Hijabs for every day' ),
				'home_hero_2_image_mobile' => array( 'hero-2-mobile.jpg', 'Hijabs for every day' ),
				'home_promo_image'         => array( 'promo.jpg', 'Lihan Lifestyle' ),
				'home_about_image'         => array( 'about.jpg', 'Our promise' ),
			);
			/**
			 * Filter the theme mods that receive demo images: theme mod => [ file under demo/images, alt text ].
			 *
			 * @param array $images Map.
			 */
			$images = (array) apply_filters( 'lihan_demo_image_mods', $images );
			foreach ( $images as $mod => $spec ) {
				if ( ! empty( $mods[ $mod ] ) ) {
					continue;
				}
				$id = $this->import_image( (string) $spec[0], (string) $spec[1] );
				if ( $id ) {
					$mods[ $mod ] = $id;
				}
			}
			// The second slide is about hijabs: point its button at the Hijab category.
			if ( empty( $mods['home_hero_2_url'] ) && isset( $this->cats['hijab'] ) ) {
				$link = get_term_link( $this->cats['hijab'], 'product_cat' );
				if ( is_string( $link ) ) {
					$mods['home_hero_2_url'] = $link;
				}
			}
			if ( empty( $mods['home_hero_1_url'] ) && isset( $this->cats['abaya-set'] ) ) {
				$link = get_term_link( $this->cats['abaya-set'], 'product_cat' );
				if ( is_string( $link ) ) {
					$mods['home_hero_1_url'] = $link;
				}
			}
			$this->save_theme_mods( $mods );
			$this->note( __( 'Homepage images set in the theme options (only where empty).', 'lihan' ) );
		}

		/** Move WordPress's untouched sample content to the trash (recoverable). */
		private function step_cleanup_defaults(): void {
			$sample = $this->find_post( 'sample-page', 'page' );
			if ( $sample && str_contains( $sample->post_content, 'This is an example page' ) ) {
				wp_trash_post( $sample->ID );
				$this->note( __( 'Moved the WordPress "Sample Page" to the trash.', 'lihan' ) );
			}
			$hello = $this->find_post( 'hello-world', 'post' );
			if ( $hello && str_contains( $hello->post_content, 'Welcome to WordPress' ) ) {
				wp_trash_post( $hello->ID );
				$this->note( __( 'Moved the WordPress "Hello world!" post to the trash.', 'lihan' ) );
			}
		}

		/**
		 * Theme mods of the Lihan theme (works whether or not it is active).
		 *
		 * @return array
		 */
		private function get_theme_mods(): array {
			$mods = get_option( 'theme_mods_' . self::THEME, array() );
			return is_array( $mods ) ? $mods : array();
		}

		/**
		 * Save theme mods (merged into what is stored now).
		 *
		 * @param array $mods Theme mods.
		 */
		private function save_theme_mods( array $mods ): void {
			update_option( 'theme_mods_' . self::THEME, array_merge( $this->get_theme_mods(), $mods ) );
		}
	}

endif;

if ( ! function_exists( 'lihan_run_demo_import' ) ) {
	/**
	 * Run the demo import.
	 *
	 * @param array $args See Lihan_Demo_Importer::__construct().
	 * @return array{ok:bool,log:string[],errors:string[],counts:array}
	 */
	function lihan_run_demo_import( array $args = array() ): array {
		$importer = new Lihan_Demo_Importer( $args );
		$result   = $importer->run();
		/**
		 * Fires after the demo import.
		 *
		 * @param array $result Import result.
		 */
		do_action( 'lihan_demo_imported', $result );
		return $result;
	}
}

/* -------------------------------------------------------------------------
 * Admin page: Appearance → Lihan Demo Import
 * ---------------------------------------------------------------------- */

/** Register the admin page. */
function lihan_demo_import_menu(): void {
	add_theme_page(
		__( 'Lihan Demo Import', 'lihan' ),
		__( 'Lihan Demo Import', 'lihan' ),
		'manage_options',
		'lihan-demo-import',
		'lihan_demo_import_page'
	);
}
add_action( 'admin_menu', 'lihan_demo_import_menu' );

/** Handle the import form (POST → run → redirect back with the result). */
function lihan_demo_import_handle(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to import demo content.', 'lihan' ), 403 );
	}
	check_admin_referer( 'lihan_demo_import' );

	$result = lihan_run_demo_import(
		array(
			'update' => ! empty( $_POST['lihan_update'] ),
		)
	);
	set_transient( 'lihan_demo_import_result_' . get_current_user_id(), $result, HOUR_IN_SECONDS );

	wp_safe_redirect( add_query_arg( array( 'page' => 'lihan-demo-import', 'imported' => 1 ), admin_url( 'themes.php' ) ) );
	exit;
}
add_action( 'admin_post_lihan_demo_import', 'lihan_demo_import_handle' );

/** Render the admin page. */
function lihan_demo_import_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$result = null;
	if ( isset( $_GET['imported'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		$key    = 'lihan_demo_import_result_' . get_current_user_id();
		$result = get_transient( $key );
		delete_transient( $key );
	}
	$woo = class_exists( 'WooCommerce' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Lihan Demo Import', 'lihan' ); ?></h1>

		<?php if ( is_array( $result ) ) : ?>
			<div class="notice <?php echo ! empty( $result['ok'] ) ? 'notice-success' : 'notice-warning'; ?>">
				<p><strong>
					<?php
					echo ! empty( $result['ok'] )
						? esc_html__( 'Demo content imported.', 'lihan' )
						: esc_html__( 'Demo import finished with some problems. Details below.', 'lihan' );
					?>
				</strong></p>
			</div>
			<?php if ( ! empty( $result['counts'] ) ) : ?>
				<table class="widefat striped" style="max-width:520px">
					<thead><tr><th><?php esc_html_e( 'Item', 'lihan' ); ?></th><th><?php esc_html_e( 'Created', 'lihan' ); ?></th><th><?php esc_html_e( 'Already there', 'lihan' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( (array) $result['counts'] as $type => $c ) : ?>
						<tr><td><?php echo esc_html( ucfirst( (string) $type ) ); ?></td><td><?php echo esc_html( (string) ( $c['created'] ?? 0 ) ); ?></td><td><?php echo esc_html( (string) ( $c['existing'] ?? 0 ) ); ?></td></tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<details style="margin:1em 0">
				<summary><?php esc_html_e( 'Import log', 'lihan' ); ?></summary>
				<ul style="list-style:disc;padding-left:20px">
					<?php foreach ( (array) $result['log'] as $line ) : ?>
						<li><?php echo esc_html( (string) $line ); ?></li>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endif; ?>

		<p><?php esc_html_e( 'Set up a ready-to-browse Lihan store: Bangladesh store settings (BDT, Cash on Delivery, Inside/Outside Dhaka delivery), product categories, about 24 products with photos, reviews, pages, blog posts, menus, logo and homepage images.', 'lihan' ); ?></p>
		<p><?php esc_html_e( 'It is safe to run more than once: items that already exist are kept and nothing is duplicated. Your published pages and theme settings are never overwritten.', 'lihan' ); ?></p>
		<?php if ( ! $woo ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'WooCommerce is not active, so only pages, posts, menus and branding will be imported. Activate WooCommerce first for the full store.', 'lihan' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lihan_demo_import">
			<?php wp_nonce_field( 'lihan_demo_import' ); ?>
			<p>
				<label>
					<input type="checkbox" name="lihan_update" value="1">
					<?php esc_html_e( 'Also reset existing demo products (prices, texts, images) to the demo data', 'lihan' ); ?>
				</label>
			</p>
			<?php submit_button( __( 'Import demo content', 'lihan' ), 'primary', 'submit', false ); ?>
			<p class="description"><?php esc_html_e( 'Importing the images can take a minute. Please keep this page open.', 'lihan' ); ?></p>
		</form>
	</div>
	<?php
}
