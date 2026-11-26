<?php
/**
 * GitHub updater: WordPress shows "Update available" when a newer release is published
 * at github.com/centralbaku/lumos-deepmind-slider, and installs its zip on "Update now".
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class Lumos_Slider_Updater {

    const REPO      = 'centralbaku/lumos-deepmind-slider';
    const CACHE_KEY = 'lumos_slider_github_release';
    const SLUG      = 'lumos-deepmind-slider'; // plugin folder name

    private static $file;     // lumos-post-cover/lumos-post-cover.php
    private static $version;

    public static function init( $main_file, $version ) {
        self::$file    = plugin_basename( $main_file );
        self::$version = $version;
        add_filter( 'pre_set_site_transient_update_plugins', [ __CLASS__, 'check' ] );
        add_filter( 'plugins_api', [ __CLASS__, 'info' ], 20, 3 );
        add_filter( 'upgrader_source_selection', [ __CLASS__, 'fix_folder' ], 10, 4 );
        add_action( 'upgrader_process_complete', function () { delete_site_transient( self::CACHE_KEY ); } );
        // "Check Again" on Dashboard → Updates also refreshes our cache.
        add_action( 'load-update-core.php', function () {
            if ( isset( $_GET['force-check'] ) ) delete_site_transient( self::CACHE_KEY );
        } );
    }

    /** Latest release as [version, zip, url, notes, published] or null. Cached 6 hours. */
    public static function release( $force = false ) {
        $r = $force ? false : get_site_transient( self::CACHE_KEY );
        if ( false !== $r ) return $r ?: null;

        $headers = [ 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) ];
        if ( defined( 'LUMOS_SLIDER_GITHUB_TOKEN' ) && LUMOS_SLIDER_GITHUB_TOKEN ) $headers['Authorization'] = 'Bearer ' . LUMOS_SLIDER_GITHUB_TOKEN;
        $res = wp_remote_get( 'https://api.github.com/repos/' . self::REPO . '/releases/latest', [ 'timeout' => 10, 'headers' => $headers ] );

        $r = [];
        if ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) ) {
            $j = json_decode( wp_remote_retrieve_body( $res ), true );
            if ( ! empty( $j['tag_name'] ) ) {
                $zip = '';
                foreach ( (array) ( $j['assets'] ?? [] ) as $a ) {
                    if ( substr( $a['name'], -4 ) === '.zip' ) { $zip = $a['browser_download_url']; break; }
                }
                $r = [
                    'version'   => ltrim( $j['tag_name'], 'vV' ),
                    'zip'       => $zip ?: $j['zipball_url'],
                    'url'       => $j['html_url'],
                    'notes'     => (string) ( $j['body'] ?? '' ),
                    'published' => (string) ( $j['published_at'] ?? '' ),
                ];
            }
        }
        // Cache failures briefly so a GitHub outage doesn't slow admin pages.
        set_site_transient( self::CACHE_KEY, $r, $r ? 6 * HOUR_IN_SECONDS : 30 * MINUTE_IN_SECONDS );
        return $r ?: null;
    }

    public static function check( $transient ) {
        if ( ! is_object( $transient ) ) $transient = new stdClass();
        $r = self::release();
        $item = (object) [
            'id'          => 'github.com/' . self::REPO,
            'slug'        => self::SLUG,
            'plugin'      => self::$file,
            'new_version' => $r ? $r['version'] : self::$version,
            'url'         => 'https://github.com/' . self::REPO,
            'package'     => $r ? $r['zip'] : '',
            'icons'       => [],
            'banners'     => [],
            'tested'      => get_bloginfo( 'version' ),
            'requires_php'=> '7.4',
        ];
        if ( $r && version_compare( $r['version'], self::$version, '>' ) ) {
            $transient->response[ self::$file ] = $item;
        } else {
            $transient->no_update[ self::$file ] = $item; // enables the auto-update toggle
        }
        return $transient;
    }

    /** "View details" popup on the Plugins / Updates screens. */
    public static function info( $res, $action, $args ) {
        if ( 'plugin_information' !== $action || empty( $args->slug ) || self::SLUG !== $args->slug ) return $res;
        $r = self::release();
        return (object) [
            'name'          => 'Lumos DeepMind Hero Slider',
            'slug'          => self::SLUG,
            'version'       => $r ? $r['version'] : self::$version,
            'author'        => 'Lumos',
            'homepage'      => 'https://github.com/' . self::REPO,
            'download_link' => $r ? $r['zip'] : '',
            'last_updated'  => $r ? $r['published'] : '',
            'requires_php'  => '7.4',
            'sections'      => [
                'description' => 'Responsive hero carousel with video and image slides.',
                'changelog'   => $r && $r['notes'] ? wpautop( esc_html( $r['notes'] ) ) : 'See GitHub releases.',
            ],
        ];
    }

    /** Make sure the unpacked folder is named lumos-deepmind-slider (GitHub zipballs use owner-repo-hash). */
    public static function fix_folder( $source, $remote_source, $upgrader, $extra = [] ) {
        if ( empty( $extra['plugin'] ) || self::$file !== $extra['plugin'] ) return $source;
        $want = trailingslashit( $remote_source ) . self::SLUG . '/';
        if ( untrailingslashit( $source ) === untrailingslashit( $want ) ) return $source;
        global $wp_filesystem;
        return $wp_filesystem && $wp_filesystem->move( $source, $want, true ) ? $want : $source;
    }
}
