<?php
/**
 * Theme updates from GitHub Releases + automatic updates.
 *
 * How it works
 * - The repo comes from the "Update URI" header in style.css
 *   (https://github.com/OWNER/REPO) or the LIHAN_UPDATE_REPO constant.
 * - WordPress checks for updates twice a day. This asks GitHub for the
 *   latest release; if its tag (e.g. v2.4.0) is newer than the installed
 *   Version, it shows up in Dashboard → Updates like any other theme.
 * - The release must have an asset named "<theme-folder>.zip" (bin/release.sh
 *   builds and uploads it).
 * - Auto-updates are on for this theme by default (turn off with
 *   define( 'LIHAN_AUTO_UPDATE', false ); in wp-config.php).
 * - Private repo: add define( 'LIHAN_GITHUB_TOKEN', 'github_pat_…' );
 *   to wp-config.php (a fine-grained token with read access to the repo).
 */

defined( 'ABSPATH' ) || exit;

final class Lihan_Theme_Updater {

    const CACHE_KEY = 'lihan_theme_update_release';

    private $slug;
    private $repo;

    public function __construct() {
        $this->slug = get_template();
        $this->repo = $this->resolve_repo();
        if ( ! $this->repo ) {
            return;
        }

        add_filter( 'pre_set_site_transient_update_themes', [ $this, 'inject_update' ] );
        add_filter( 'http_request_args', [ $this, 'auth_download' ], 10, 2 );
        add_filter( 'upgrader_source_selection', [ $this, 'fix_folder_name' ], 10, 4 );
        add_filter( 'auto_update_theme', [ $this, 'auto_update' ], 10, 2 );
        add_action( 'upgrader_process_complete', [ $this, 'flush_cache' ], 10, 0 );
        add_action( 'load-update-core.php', [ $this, 'maybe_force_check' ], 1 ); // before core's wp_update_themes (priority 10)
        add_action( 'admin_notices', [ $this, 'admin_notice' ] );
    }

    private function resolve_repo() {
        if ( defined( 'LIHAN_UPDATE_REPO' ) && LIHAN_UPDATE_REPO ) {
            return trim( LIHAN_UPDATE_REPO, '/' );
        }
        $uri = wp_get_theme( $this->slug )->get( 'UpdateURI' );
        if ( $uri && preg_match( '#github\.com/([^/\s]+/[^/\s]+?)(?:\.git)?/?$#', $uri, $m ) ) {
            return $m[1];
        }
        return '';
    }

    private function token() {
        return defined( 'LIHAN_GITHUB_TOKEN' ) ? LIHAN_GITHUB_TOKEN : '';
    }

    private function api_headers() {
        $h = [
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => 'lihan-theme-updater',
        ];
        if ( $this->token() ) {
            $h['Authorization'] = 'Bearer ' . $this->token();
        }
        return $h;
    }

    /**
     * Latest release info (cached 6 hours): [ version, package, url ].
     */
    private function latest_release() {
        $cached = get_site_transient( self::CACHE_KEY );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $res = wp_remote_get( "https://api.github.com/repos/{$this->repo}/releases/latest", [
            'timeout' => 15,
            'headers' => $this->api_headers(),
        ] );
        $data = ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) )
            ? json_decode( wp_remote_retrieve_body( $res ), true )
            : null;

        $release = [];
        if ( is_array( $data ) && ! empty( $data['tag_name'] ) ) {
            $package = '';
            foreach ( (array) ( $data['assets'] ?? [] ) as $asset ) {
                if ( $asset['name'] === $this->slug . '.zip' ) {
                    // Private repos need the API asset URL (+ token); public ones can use the browser URL.
                    $package = $this->token() ? $asset['url'] : $asset['browser_download_url'];
                    break;
                }
            }
            $release = [
                'version' => ltrim( $data['tag_name'], 'vV' ),
                'package' => $package,
                'url'     => $data['html_url'] ?? "https://github.com/{$this->repo}/releases",
            ];
        }

        // Remember why a check failed, for the admin notice.
        if ( $release ) {
            delete_site_option( 'lihan_theme_update_error' );
        } else {
            $code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
            if ( is_wp_error( $res ) ) {
                $why = 'Could not connect to GitHub (' . $res->get_error_message() . ').';
            } elseif ( in_array( $code, [ 401, 403, 404 ], true ) ) {
                $why = $this->token()
                    ? "GitHub refused the token (HTTP {$code}). Check LIHAN_GITHUB_TOKEN in wp-config.php: it may be expired, or lack read access to {$this->repo}."
                    : "The repository {$this->repo} is private or not found (HTTP {$code}). Add define( 'LIHAN_GITHUB_TOKEN', '…' ); to wp-config.php.";
            } else {
                $why = "GitHub returned HTTP {$code} or no release yet.";
            }
            update_site_option( 'lihan_theme_update_error', $why );
        }

        // Cache failures briefly so a GitHub hiccup doesn't hammer the API.
        set_site_transient( self::CACHE_KEY, $release, $release ? 6 * HOUR_IN_SECONDS : 30 * MINUTE_IN_SECONDS );
        return $release;
    }

    /**
     * Tell admins (Themes / Updates / Dashboard screens) when update checks fail.
     */
    public function admin_notice() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! current_user_can( 'update_themes' ) || ! $screen || ! in_array( $screen->id, [ 'themes', 'update-core', 'dashboard' ], true ) ) {
            return;
        }
        $why = get_site_option( 'lihan_theme_update_error' );
        if ( $why ) {
            printf(
                '<div class="notice notice-warning"><p><strong>Lihan theme updates:</strong> %s</p></div>',
                esc_html( $why )
            );
        }
    }

    public function inject_update( $transient ) {
        if ( ! is_object( $transient ) ) {
            $transient = new stdClass();
        }
        $installed = wp_get_theme( $this->slug )->get( 'Version' );
        $release   = $this->latest_release();

        $item = [
            'theme'        => $this->slug,
            'new_version'  => $release['version'] ?? $installed,
            'url'          => $release['url'] ?? '',
            'package'      => $release['package'] ?? '',
            'requires'     => wp_get_theme( $this->slug )->get( 'RequiresWP' ),
            'requires_php' => wp_get_theme( $this->slug )->get( 'RequiresPHP' ),
        ];

        if ( $release && $release['package'] && version_compare( $release['version'], $installed, '>' ) ) {
            $transient->response[ $this->slug ] = $item;
            unset( $transient->no_update[ $this->slug ] );
        } else {
            // Listed as "up to date" so the auto-update toggle shows on the Themes screen.
            $transient->no_update[ $this->slug ] = $item;
            unset( $transient->response[ $this->slug ] );
        }
        return $transient;
    }

    /**
     * Private repo downloads: send the token and ask for the binary.
     */
    public function auth_download( $args, $url ) {
        if ( $this->token() && 0 === strpos( $url, "https://api.github.com/repos/{$this->repo}/releases/assets/" ) ) {
            $args['headers']['Authorization'] = 'Bearer ' . $this->token();
            $args['headers']['Accept']        = 'application/octet-stream';
            $args['headers']['User-Agent']    = 'lihan-theme-updater';
        }
        return $args;
    }

    /**
     * Make sure the unzipped folder matches the installed theme folder.
     */
    public function fix_folder_name( $source, $remote_source, $upgrader, $hook_extra = [] ) {
        global $wp_filesystem;
        if ( empty( $hook_extra['theme'] ) || $hook_extra['theme'] !== $this->slug ) {
            return $source;
        }
        $wanted = trailingslashit( $remote_source ) . $this->slug . '/';
        if ( untrailingslashit( $source ) !== untrailingslashit( $wanted ) && $wp_filesystem && $wp_filesystem->move( $source, $wanted, true ) ) {
            return $wanted;
        }
        return $source;
    }

    public function auto_update( $update, $item ) {
        if ( isset( $item->theme ) && $item->theme === $this->slug ) {
            return defined( 'LIHAN_AUTO_UPDATE' ) ? (bool) LIHAN_AUTO_UPDATE : true;
        }
        return $update;
    }

    public function flush_cache() {
        delete_site_transient( self::CACHE_KEY );
    }

    /**
     * Dashboard → Updates → "Check again" also re-asks GitHub.
     */
    public function maybe_force_check() {
        if ( ! empty( $_GET['force-check'] ) && current_user_can( 'update_themes' ) ) {
            $this->flush_cache();
        }
    }
}

new Lihan_Theme_Updater();
