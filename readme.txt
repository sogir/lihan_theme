=== Lihan ===
Contributors: lihanlifestyle
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.1
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: e-commerce, custom-logo, custom-menu, featured-images, threaded-comments, translation-ready, theme-options, full-width-template, block-styles, wide-blocks, accessibility-ready

A clean, fast modest-fashion store theme for Lihan Lifestyle, built for WooCommerce, Elementor and CartFlows.

== Description ==

Lihan is a classic WordPress theme for Lihan Lifestyle (lihanbd.com), a Bangladeshi modest-fashion store.

* Store header with a rotating announcement bar, product search, account and cart icons, and an optional sticky header.
* Off-canvas mobile menu with accordion sub-menus, a mobile search panel and an off-canvas cart.
* Homepage sections (hero slider, USP bar, category grid, styling inspiration reels, trending products, reviews, promo banner, image + text, read-more text, rich text, FAQ with FAQPage schema), each editable in the Customizer and available as Elementor widgets and the [lihan_section] shortcode.
* WooCommerce: product cards with a hover image, variation swatches, sticky buy box, AJAX cart drawer with a free-delivery progress bar, styled cart/checkout (classic and block versions).
* Elementor: header/footer theme locations, full-width templates and a "Lihan" widget category.
* CartFlows: distraction-free step pages and a checkout styled with the theme colours.
* Footer with a built-in newsletter sign-up (stored subscribers, CSV export, privacy exporter/eraser), menus, contact details, opening hours and payment badges.
* Floating WhatsApp / Messenger chat button.
* Vanilla JavaScript (no jQuery needed by the theme), inline SVG icons, accessible markup (skip link, labelled toggles, focus styles, reduced-motion support).

== Installation ==

1. Upload the `lihan` folder to `/wp-content/themes/`, or upload the zip in Appearance → Themes → Add New.
2. Activate the theme in Appearance → Themes.
3. Optional: run Appearance → Lihan Demo Import to create the demo categories, products, pages and menus.
4. Set your logo, colours, contact details and homepage sections in Appearance → Customize → Lihan Theme.
5. Assign menus in Appearance → Menus: Main menu, Mobile menu, Footer: Information, Footer: Customer care, Top bar links.

== Updates ==

The theme updates itself from GitHub Releases of https://github.com/sogir/lihan_theme (set by the "Update URI" header in style.css).

1. Make your changes, then run `bin/release.sh patch` (or `minor`, `major`, or an exact version such as `1.2.0`).
   It bumps the version, commits, tags vX.Y.Z, pushes, builds lihan.zip and publishes it as a GitHub Release (needs the GitHub CLI, logged in).
2. Every site running the theme installs the update automatically. WordPress checks about every 12 hours; Dashboard → Updates → Check again pulls it at once.

* Turn off auto-updates on a site: `define( 'LIHAN_AUTO_UPDATE', false );` in wp-config.php.
* If the repository is made private: `define( 'LIHAN_GITHUB_TOKEN', 'github_pat_…' );` in wp-config.php (fine-grained token, read-only Contents).
* Use a different repository: `define( 'LIHAN_UPDATE_REPO', 'owner/repo' );`.

== Frequently Asked Questions ==

= Where do I change the phone, WhatsApp and Messenger links? =

Appearance → Customize → Lihan Theme → Contact & Social.

= How do I use my own newsletter service? =

Paste its shortcode in Customize → Lihan Theme → Newsletter. Without a shortcode the built-in form stores subscribers under Tools → Newsletter subscribers.

= How do I build a page with Elementor? =

Edit the page with Elementor and choose the "Lihan Full Width" (or Elementor Full Width / Canvas) template. The Lihan widgets are in the "Lihan" category.

== Developer notes ==

* Design tokens are CSS custom properties on `:root` in `assets/css/main.css` (`--lh-primary`, `--lh-accent`, …); Customizer colours override them inline.
* Shared JavaScript API in `assets/js/main.js`: `[data-lh-carousel]`, `[data-lh-open="ID"]` / `[data-lh-close]` drawers, `[data-lh-readmore]`, `window.Lihan.init( root )`, `window.Lihan.openDrawer( id )`, `window.Lihan.closeDrawer()` and the `lihan:init` document event.
* Filters: `lihan_defaults`, `lihan_header_sticky`, `lihan_show_floating_chat`, `lihan_show_breadcrumbs`, `lihan_show_page_title`, `lihan_contact_items`, `lihan_social_profiles`, `lihan_icons`, `lihan_home_sections`, `lihan_google_fonts_url`.

== Changelog ==

= 1.0.1 =
* Updater falls back to github.com when the GitHub API rate limit is hit (common on shared hosting) and explains rate-limit errors.
* Updater stays off in git checkouts so a development copy is never overwritten.

= 1.0.0 =
* Initial release.

== Copyright ==

Lihan WordPress Theme, (C) 2026 Lihan Lifestyle.
Lihan is distributed under the terms of the GNU GPL v2 or later.

This theme bundles the following resources:

* Montserrat font, by Julieta Ulanovsky and contributors — SIL Open Font License 1.1 (loaded from Google Fonts).
* Icons: original inline SVG paths drawn for this theme — GPL v2 or later.
* Lihan logo and favicon — (C) Lihan Lifestyle, used with permission.
* screenshot.png — composed from the theme's own design, GPL v2 or later.
