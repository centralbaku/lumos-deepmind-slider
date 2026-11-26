<?php
/**
 * Plugin Name: Lumos DeepMind Hero Slider
 * Description: Pixel-perfect, responsive hero carousel for WordPress inspired by Google DeepMind Gemini Robotics. Supports video and image slide backgrounds, customizable scrim overlay, hero top-center and card top-left placements, touch/swipe gestures, and WordPress Media Library integration.
 * Version: 1.0.1
 * Author: Lumos
 * Text Domain: lumos-deepmind-slider
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Update URI: https://github.com/centralbaku/lumos-deepmind-slider
 *
 * @package LumosDeepMindSlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'LUMOS_SLIDER_VERSION', '1.0.1' );
define( 'LUMOS_SLIDER_FILE', __FILE__ );
define( 'LUMOS_SLIDER_PATH', plugin_dir_path( __FILE__ ) );
define( 'LUMOS_SLIDER_URL', plugin_dir_url( __FILE__ ) );

// Load classes.
// Shared LUMOS admin menu (identical copy ships in every Lumos plugin)
require_once LUMOS_SLIDER_PATH . 'includes/lumos-menu.php';

// Shared GitHub updater: every version pushed to main is offered as a WordPress update.
require_once LUMOS_SLIDER_PATH . 'includes/lumos-updater.php';
new Lumos_GitHub_Updater( LUMOS_SLIDER_FILE, 'centralbaku/lumos-deepmind-slider', LUMOS_SLIDER_VERSION );

require_once LUMOS_SLIDER_PATH . 'includes/class-slider-defaults.php';
require_once LUMOS_SLIDER_PATH . 'includes/class-slider-shortcode.php';
require_once LUMOS_SLIDER_PATH . 'includes/class-slider-admin.php';
require_once LUMOS_SLIDER_PATH . 'includes/class-slider-block.php';

/**
 * Main plugin singleton class
 */
final class Lumos_DeepMind_Slider_Plugin {

	/**
	 * Single instance of the class
	 *
	 * @var Lumos_DeepMind_Slider_Plugin
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Lumos_DeepMind_Slider_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Activation hook
		register_activation_hook( LUMOS_SLIDER_FILE, array( $this, 'activate' ) );

		// Enqueue scripts & styles
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );

		// Ensure upload replacement normalizes directory slug so WordPress cleanly replaces the existing install
		add_filter( 'upgrader_source_selection', array( $this, 'normalize_plugin_source_directory' ), 10, 4 );

		// Standalone live preview handler (empty page showing only the slider)
		add_action( 'template_redirect', array( $this, 'handle_standalone_preview' ) );

		// Initialize modules
		Lumos_Slider_Shortcode::instance();
		new Lumos_Slider_Admin();
		new Lumos_Slider_Block();
	}

	/**
	 * Normalize plugin folder name upon upload or upgrade
	 * Ensures that zip archives named lumos-deepmind-slider-v.1.0.1 or extracted from GitHub
	 * are mapped directly to lumos-deepmind-slider so WordPress cleanly replaces the existing install.
	 *
	 * @param string      $source        File source location.
	 * @param string      $remote_source Remote file source location.
	 * @param WP_Upgrader $upgrader      WP_Upgrader instance.
	 * @param array       $hook_extra    Extra arguments passed to hook.
	 * @return string
	 */
	public function normalize_plugin_source_directory( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;

		if ( is_wp_error( $source ) || empty( $wp_filesystem ) ) {
			return $source;
		}

		// Check if lumos-deepmind-slider.php exists in the extracted directory
		$plugin_file = trailingslashit( $source ) . 'lumos-deepmind-slider.php';
		if ( ! $wp_filesystem->exists( $plugin_file ) ) {
			return $source;
		}

		$desired_dir = trailingslashit( $remote_source ) . 'lumos-deepmind-slider';

		// If extracted folder is named differently (e.g. lumos-deepmind-slider-v.1.0.1), rename it to the canonical slug
		if ( untrailingslashit( $source ) !== untrailingslashit( $desired_dir ) ) {
			if ( $wp_filesystem->move( $source, $desired_dir, true ) ) {
				return $desired_dir;
			}
		}

		return $source;
	}

	/**
	 * Standalone live preview handler
	 * Intercepts ?lumos_slider_preview=1 and renders a clean, distraction-free page
	 * containing only the slider (no theme header, footer, or sidebar).
	 */
	public function handle_standalone_preview() {
		if ( ! isset( $_GET['lumos_slider_preview'] ) || '1' !== sanitize_text_field( wp_unslash( $_GET['lumos_slider_preview'] ) ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to preview the slider. Please log in as an administrator or editor.', 'lumos-deepmind-slider' ) );
		}

		// Clean headers
		status_header( 200 );
		nocache_headers();

		$shortcode_handler = Lumos_Slider_Shortcode::instance();
		$slider_html       = $shortcode_handler->render_shortcode( array() );
		$version           = LUMOS_SLIDER_VERSION;
		$css_url           = LUMOS_SLIDER_URL . 'assets/css/deepmind-slider.css?ver=' . $version;
		$js_url            = LUMOS_SLIDER_URL . 'assets/js/deepmind-slider.js?ver=' . $version;
		$admin_url         = admin_url( 'admin.php?page=lumos-deepmind-slider' );

		?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php esc_html_e( 'DeepMind Hero Slider — Live Preview', 'lumos-deepmind-slider' ); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Google+Sans+Flex:opsz,wght@6..144,400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>">
	<style>
		*, *::before, *::after { box-sizing: border-box; }
		html, body {
			margin: 0;
			padding: 0;
			background-color: #0e0f14;
			color: #ffffff;
			min-height: 100vh;
			overflow-x: hidden;
			font-family: 'Google Sans Flex', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
		}
		.lumos-preview-bar {
			position: sticky;
			top: 0;
			left: 0;
			right: 0;
			z-index: 99999;
			background: rgba(14, 15, 20, 0.92);
			backdrop-filter: blur(12px);
			-webkit-backdrop-filter: blur(12px);
			border-bottom: 1px solid rgba(255, 255, 255, 0.1);
			padding: 12px 24px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 16px;
			font-size: 13px;
		}
		.lumos-preview-bar-title {
			display: flex;
			align-items: center;
			gap: 10px;
			font-weight: 600;
			letter-spacing: -0.01em;
		}
		.lumos-preview-badge {
			background: #1a73e8;
			color: #ffffff;
			font-size: 11px;
			font-weight: 600;
			padding: 2px 8px;
			border-radius: 9999px;
			letter-spacing: 0.02em;
		}
		.lumos-preview-bar-actions {
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.lumos-preview-btn {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 7px 14px;
			border-radius: 6px;
			font-size: 13px;
			font-weight: 500;
			text-decoration: none;
			cursor: pointer;
			transition: all 0.15s ease;
			border: 1px solid rgba(255, 255, 255, 0.2);
			background: rgba(255, 255, 255, 0.08);
			color: #ffffff;
		}
		.lumos-preview-btn:hover {
			background: rgba(255, 255, 255, 0.18);
			border-color: rgba(255, 255, 255, 0.35);
			color: #ffffff;
		}
		.lumos-preview-wrapper {
			width: 100%;
			margin: 0 auto;
			padding: 10px 0 60px;
		}
	</style>
</head>
<body>
	<header class="lumos-preview-bar">
		<div class="lumos-preview-bar-title">
			<span><?php esc_html_e( 'DeepMind Hero Slider', 'lumos-deepmind-slider' ); ?></span>
			<span class="lumos-preview-badge"><?php esc_html_e( 'LIVE PREVIEW', 'lumos-deepmind-slider' ); ?></span>
		</div>
		<div class="lumos-preview-bar-actions">
			<a href="<?php echo esc_url( $admin_url ); ?>" class="lumos-preview-btn">
				&larr; <?php esc_html_e( 'Back to Slider Manager', 'lumos-deepmind-slider' ); ?>
			</a>
		</div>
	</header>

	<main class="lumos-preview-wrapper">
		<?php echo $slider_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</main>

	<script src="<?php echo esc_url( $js_url ); ?>"></script>
</body>
</html><?php
		exit;
	}

	/**
	 * Plugin activation hook
	 */
	public function activate() {
		// Populate default settings if not exists
		if ( false === get_option( 'lumos_slider_settings' ) ) {
			update_option( 'lumos_slider_settings', Lumos_Slider_Defaults::get_default_settings() );
		}

		// Populate default demo slides if not exists
		if ( false === get_option( 'lumos_slider_slides' ) ) {
			update_option( 'lumos_slider_slides', Lumos_Slider_Defaults::get_default_slides() );
		}
	}

	/**
	 * Register frontend assets
	 */
	public function register_frontend_assets() {
		wp_register_style(
			'lumos-deepmind-slider',
			LUMOS_SLIDER_URL . 'assets/css/deepmind-slider.css',
			array(),
			LUMOS_SLIDER_VERSION
		);

		wp_register_script(
			'lumos-deepmind-slider',
			LUMOS_SLIDER_URL . 'assets/js/deepmind-slider.js',
			array(),
			LUMOS_SLIDER_VERSION,
			true
		);
	}
}

// Initialize the plugin
Lumos_DeepMind_Slider_Plugin::get_instance();

/**
 * Global helper function to output the slider in PHP theme templates
 *
 * @param array $args Optional custom attributes.
 */
function lumos_deepmind_slider( $args = array() ) {
	$renderer = Lumos_Slider_Shortcode::instance();
	echo $renderer->render_shortcode( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
