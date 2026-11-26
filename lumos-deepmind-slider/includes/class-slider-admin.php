<?php
/**
 * Admin Panel & Slide Manager
 *
 * @package LumosDeepMindSlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lumos_Slider_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX handlers
		add_action( 'wp_ajax_lumos_save_slide', array( $this, 'ajax_save_slide' ) );
		add_action( 'wp_ajax_lumos_delete_slide', array( $this, 'ajax_delete_slide' ) );
		add_action( 'wp_ajax_lumos_duplicate_slide', array( $this, 'ajax_duplicate_slide' ) );
		add_action( 'wp_ajax_lumos_toggle_slide_status', array( $this, 'ajax_toggle_slide_status' ) );
		add_action( 'wp_ajax_lumos_save_slide_order', array( $this, 'ajax_save_slide_order' ) );
		add_action( 'wp_ajax_lumos_reset_demo_slides', array( $this, 'ajax_reset_demo_slides' ) );

		// Export & Import handlers
		add_action( 'admin_post_lumos_export_slides', array( $this, 'handle_export_slides' ) );
		add_action( 'wp_ajax_lumos_import_slides', array( $this, 'ajax_import_slides' ) );

		// Settings save handler
		add_action( 'admin_init', array( $this, 'handle_save_settings' ) );
	}

	/**
	 * Register admin menu items
	 */
	public function register_admin_menus() {
		add_menu_page(
			__( 'DeepMind Slider', 'lumos-deepmind-slider' ),
			__( 'DeepMind Slider', 'lumos-deepmind-slider' ),
			'manage_options',
			'lumos-deepmind-slider',
			array( $this, 'render_slides_page' ),
			'dashicons-slides',
			30
		);

		add_submenu_page(
			'lumos-deepmind-slider',
			__( 'Manage Slides', 'lumos-deepmind-slider' ),
			__( 'Manage Slides', 'lumos-deepmind-slider' ),
			'manage_options',
			'lumos-deepmind-slider',
			array( $this, 'render_slides_page' )
		);

		add_submenu_page(
			'lumos-deepmind-slider',
			__( 'Settings & Shortcode', 'lumos-deepmind-slider' ),
			__( 'Settings & Shortcode', 'lumos-deepmind-slider' ),
			'manage_options',
			'lumos-slider-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'lumos-deepmind-slider',
			__( 'Media Guidelines', 'lumos-deepmind-slider' ),
			__( 'Media Guidelines', 'lumos-deepmind-slider' ),
			'manage_options',
			'lumos-slider-media-guide',
			array( $this, 'render_media_guide_page' )
		);

		add_submenu_page(
			'lumos-deepmind-slider',
			__( 'Changelog', 'lumos-deepmind-slider' ),
			__( 'Changelog', 'lumos-deepmind-slider' ),
			'manage_options',
			'lumos-slider-changelog',
			array( $this, 'render_changelog_page' )
		);

		add_submenu_page(
			'lumos-deepmind-slider',
			__( 'Import & Export', 'lumos-deepmind-slider' ),
			__( 'Import & Export', 'lumos-deepmind-slider' ),
			'manage_options',
			'lumos-slider-import-export',
			array( $this, 'render_import_export_page' )
		);
	}

	/**
	 * Enqueue admin scripts & styles
	 *
	 * @param string $hook Current admin screen hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( false === strpos( $hook, 'lumos-deepmind-slider' ) && false === strpos( $hook, 'lumos-slider-settings' ) && false === strpos( $hook, 'lumos-slider-media-guide' ) && false === strpos( $hook, 'lumos-slider-changelog' ) && false === strpos( $hook, 'lumos-slider-import-export' ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_style(
			'lumos-slider-admin',
			LUMOS_SLIDER_URL . 'assets/css/admin-slider.css',
			array(),
			LUMOS_SLIDER_VERSION
		);

		wp_enqueue_script(
			'lumos-slider-admin',
			LUMOS_SLIDER_URL . 'assets/js/admin-slider.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			LUMOS_SLIDER_VERSION,
			true
		);

		wp_localize_script(
			'lumos-slider-admin',
			'lumosSliderAdmin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'lumos_slider_admin_nonce' ),
			)
		);
	}

	/**
	 * Render the main Slides Manager page
	 */
	public function render_slides_page() {
		$slides = get_option( 'lumos_slider_slides', Lumos_Slider_Defaults::get_default_slides() );
		?>
		<div class="wrap lumos-admin-wrap">
			<div class="lumos-admin-header">
				<div class="lumos-admin-header-text">
					<h1>
						<span><?php esc_html_e( 'DeepMind Hero Slider', 'lumos-deepmind-slider' ); ?></span>
						<span class="lumos-badge">v<?php echo esc_html( LUMOS_SLIDER_VERSION ); ?></span>
					</h1>
					<p><?php esc_html_e( 'Configure header layouts, customize text, toggle buttons, change video/image backgrounds, and manage slides.', 'lumos-deepmind-slider' ); ?></p>
				</div>
				<div class="lumos-admin-actions">
					<a href="<?php echo esc_url( add_query_arg( 'lumos_slider_preview', '1', home_url( '/' ) ) ); ?>" class="button button-secondary" target="_blank" id="btn-preview-slider" title="<?php esc_attr_e( 'Preview slider on a clean distraction-free page', 'lumos-deepmind-slider' ); ?>">
						<span class="dashicons dashicons-visibility"></span>
						<span><?php esc_html_e( 'Preview Slider', 'lumos-deepmind-slider' ); ?></span>
					</a>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lumos_export_slides' ), 'lumos_export_slides_nonce', 'nonce' ) ); ?>" class="button button-secondary" id="btn-quick-export" title="<?php esc_attr_e( 'Export all slides and media as a downloadable ZIP package', 'lumos-deepmind-slider' ); ?>">
						<span class="dashicons dashicons-download"></span>
						<span><?php esc_html_e( 'Export (.zip)', 'lumos-deepmind-slider' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-import-export' ) ); ?>" class="button button-secondary" id="btn-quick-import" title="<?php esc_attr_e( 'Import slides and media package', 'lumos-deepmind-slider' ); ?>">
						<span class="dashicons dashicons-upload"></span>
						<span><?php esc_html_e( 'Import', 'lumos-deepmind-slider' ); ?></span>
					</a>
					<button type="button" class="button button-primary" id="btn-add-slide">
						<span class="dashicons dashicons-plus-alt2"></span>
						<span><?php esc_html_e( 'Add New Slide', 'lumos-deepmind-slider' ); ?></span>
					</button>
					<button type="button" class="button button-secondary" id="btn-reset-demo">
						<span class="dashicons dashicons-update"></span>
						<span><?php esc_html_e( 'Restore Demo Slides', 'lumos-deepmind-slider' ); ?></span>
					</button>
				</div>
			</div>

			<nav class="lumos-nav-tabs">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-deepmind-slider' ) ); ?>" class="lumos-nav-tab is-active"><?php esc_html_e( 'Manage Slides', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-settings' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Settings & Shortcode', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-media-guide' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Media Guidelines', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-changelog' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Changelog', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-import-export' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Import & Export', 'lumos-deepmind-slider' ); ?></a>
			</nav>

			<!-- Slides List with Drag-and-Drop Sort -->
			<div class="lumos-slides-list" id="lumos-slides-container">
				<?php if ( empty( $slides ) ) : ?>
					<div class="lumos-settings-box">
						<p><?php esc_html_e( 'No slides found. Click "Add New Slide" or "Restore Demo Slides" to get started.', 'lumos-deepmind-slider' ); ?></p>
					</div>
				<?php else : ?>
					<?php foreach ( $slides as $index => $slide ) : ?>
						<?php
						$is_active  = ! isset( $slide['active'] ) || true === $slide['active'] || '1' === $slide['active'] || 1 === $slide['active'];
						$media_type = isset( $slide['media_type'] ) ? $slide['media_type'] : 'video';
						$thumb_url  = 'video' === $media_type ? ( ! empty( $slide['poster_url'] ) ? $slide['poster_url'] : '' ) : ( ! empty( $slide['image_url'] ) ? $slide['image_url'] : '' );
						$layout     = isset( $slide['layout'] ) ? $slide['layout'] : 'top-center';
						?>
						<div class="lumos-slide-card <?php echo $is_active ? 'is-active' : 'is-disabled'; ?>" data-id="<?php echo esc_attr( $slide['id'] ); ?>" data-slide="<?php echo esc_attr( wp_json_encode( $slide ) ); ?>">
							<div class="lumos-slide-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'lumos-deepmind-slider' ); ?>">
								<span class="dashicons dashicons-menu"></span>
							</div>

							<!-- Slide Status Toggle Switch (Turn On / Off) -->
							<div class="lumos-slide-toggle-wrap" title="<?php esc_attr_e( 'Turn slide ON or OFF', 'lumos-deepmind-slider' ); ?>">
								<label class="lumos-switch">
									<input type="checkbox" class="btn-toggle-slide-active" data-id="<?php echo esc_attr( $slide['id'] ); ?>" <?php checked( $is_active ); ?>>
									<span class="lumos-slider-round"></span>
								</label>
								<span class="lumos-status-label"><?php echo $is_active ? esc_html__( 'Active', 'lumos-deepmind-slider' ) : esc_html__( 'Off', 'lumos-deepmind-slider' ); ?></span>
							</div>

							<div class="lumos-slide-thumb">
								<?php if ( ! empty( $thumb_url ) ) : ?>
									<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $slide['title'] ); ?>">
								<?php else : ?>
									<span class="thumb-placeholder dashicons dashicons-format-image"></span>
								<?php endif; ?>
								<span class="lumos-media-badge"><?php echo esc_html( strtoupper( $media_type ) ); ?></span>
							</div>

							<div class="lumos-slide-details">
								<h3><?php echo esc_html( $slide['title'] ); ?></h3>
								<p class="lumos-slide-desc"><?php echo esc_html( $slide['description'] ); ?></p>
								<div class="lumos-slide-tags">
									<span class="lumos-tag lumos-tag-primary">
										<?php echo 'top-center' === $layout ? esc_html__( 'Hero (Top-Center)', 'lumos-deepmind-slider' ) : esc_html__( 'Card (Top-Left)', 'lumos-deepmind-slider' ); ?>
									</span>
									<?php if ( ! empty( $slide['logo_enable'] ) && ! empty( $slide['logo_url'] ) ) : ?>
										<span class="lumos-tag lumos-tag-logo">🏷️ <?php echo 'bottom' === ( isset( $slide['logo_position'] ) ? $slide['logo_position'] : 'top' ) ? esc_html__( 'Logo: Bottom', 'lumos-deepmind-slider' ) : esc_html__( 'Logo: Top', 'lumos-deepmind-slider' ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $slide['button_1_enable'] ) && ! empty( $slide['button_1_text'] ) ) : ?>
										<span class="lumos-tag lumos-tag-btn">🔘 <?php echo esc_html( $slide['button_1_text'] ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $slide['button_2_enable'] ) && ! empty( $slide['button_2_text'] ) ) : ?>
										<span class="lumos-tag lumos-tag-btn">🔘 <?php echo esc_html( $slide['button_2_text'] ); ?></span>
									<?php endif; ?>
								</div>
							</div>

							<div class="lumos-slide-actions">
								<button class="button button-small btn-edit-slide"><?php esc_html_e( 'Edit', 'lumos-deepmind-slider' ); ?></button>
								<button class="button button-small btn-duplicate-slide"><?php esc_html_e( 'Duplicate', 'lumos-deepmind-slider' ); ?></button>
								<button class="button button-small button-link-delete btn-delete-slide"><?php esc_html_e( 'Delete', 'lumos-deepmind-slider' ); ?></button>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<!-- Quick Media Guidelines Summary Card -->
			<div class="lumos-settings-box lumos-media-guide-quick-card">
				<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
					<div>
						<h2 style="margin:0 0 4px;display:flex;align-items:center;gap:8px;">
							<span class="dashicons dashicons-format-gallery" style="color:#2271b1;"></span>
							<?php esc_html_e( 'Recommended Media Sizes & Formats', 'lumos-deepmind-slider' ); ?>
						</h2>
						<p style="margin:0;color:#646970;font-size:13px;"><?php esc_html_e( 'Follow these recommendations for crisp visuals, fast loading, and smooth autoplay across all devices.', 'lumos-deepmind-slider' ); ?></p>
					</div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-media-guide' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'View Full Media Guide & Tools', 'lumos-deepmind-slider' ); ?> &rarr;
					</a>
				</div>

				<div class="lumos-guide-cards-grid">
					<!-- Video Card -->
					<div class="lumos-guide-card">
						<div class="lumos-guide-card-icon" style="background:#e8f0fe;color:#1a73e8;">🎬</div>
						<h3><?php esc_html_e( 'Background Video', 'lumos-deepmind-slider' ); ?></h3>
						<div class="lumos-guide-card-stat"><code>1920 &times; 1080</code> (16:9)</div>
						<ul>
							<li><strong><?php esc_html_e( 'Formats:', 'lumos-deepmind-slider' ); ?></strong> MP4 (H.264), WebM</li>
							<li><strong><?php esc_html_e( 'Target Size:', 'lumos-deepmind-slider' ); ?></strong> &lt; 10 MB (ideal: 3&ndash;8 MB)</li>
							<li><strong><?php esc_html_e( 'Audio:', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( 'Muted / Strip audio track', 'lumos-deepmind-slider' ); ?></li>
							<li><strong><?php esc_html_e( 'Frame Rate:', 'lumos-deepmind-slider' ); ?></strong> 24 or 30 fps (avoid 60fps)</li>
						</ul>
					</div>

					<!-- Fallback Poster Card -->
					<div class="lumos-guide-card">
						<div class="lumos-guide-card-icon" style="background:#e6f4ea;color:#137333;">🖼️</div>
						<h3><?php esc_html_e( 'Fallback Poster Image', 'lumos-deepmind-slider' ); ?></h3>
						<div class="lumos-guide-card-stat"><code>1920 &times; 1080</code> (16:9)</div>
						<ul>
							<li><strong><?php esc_html_e( 'Formats:', 'lumos-deepmind-slider' ); ?></strong> WebP (best) or JPG</li>
							<li><strong><?php esc_html_e( 'Target Size:', 'lumos-deepmind-slider' ); ?></strong> &lt; 300 KB</li>
							<li><strong><?php esc_html_e( 'Source:', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( '1st video frame (0.0s)', 'lumos-deepmind-slider' ); ?></li>
							<li><strong><?php esc_html_e( 'Role:', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( 'Zero-flicker instant loading', 'lumos-deepmind-slider' ); ?></li>
						</ul>
					</div>

					<!-- Background Image Card -->
					<div class="lumos-guide-card">
						<div class="lumos-guide-card-icon" style="background:#fef7e0;color:#b06000;">🖼️</div>
						<h3><?php esc_html_e( 'Background Image', 'lumos-deepmind-slider' ); ?></h3>
						<div class="lumos-guide-card-stat"><code>1920 &times; 1080</code> (16:9)</div>
						<ul>
							<li><strong><?php esc_html_e( 'Formats:', 'lumos-deepmind-slider' ); ?></strong> WebP, JPG, AVIF</li>
							<li><strong><?php esc_html_e( 'Target Size:', 'lumos-deepmind-slider' ); ?></strong> 150 &ndash; 400 KB (max 600KB)</li>
							<li><strong><?php esc_html_e( 'Retina 2K:', 'lumos-deepmind-slider' ); ?></strong> 2560 &times; 1440 max</li>
							<li><strong><?php esc_html_e( 'Contrast:', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( 'Enable Dark Scrim for readability', 'lumos-deepmind-slider' ); ?></li>
						</ul>
					</div>
				</div>
			</div>

			<!-- Slide Editor Modal Window -->
			<div class="lumos-modal-backdrop" id="lumos-slide-modal">
				<div class="lumos-modal-window">
					<form id="lumos-slide-form">
						<div class="lumos-modal-header">
							<h2 id="modal-title-text"><?php esc_html_e( 'Add New Slide', 'lumos-deepmind-slider' ); ?></h2>
							<button type="button" class="lumos-modal-close">&times;</button>
						</div>

						<div class="lumos-modal-body">
							<input type="hidden" name="id" id="slide-id" value="new">
							<input type="hidden" name="media_type" id="slide-media-type-input" value="video">
							<input type="hidden" name="layout" id="slide-layout-input" value="top-center">

							<!-- 1. Slide Status Toggle (Active / Inactive) -->
							<div class="lumos-form-section" style="background:#eef3f8;border-color:#c8d7e6;">
								<div style="display:flex;align-items:center;justify-content:space-between;">
									<div>
										<strong style="font-size:15px;color:#1d2327;"><?php esc_html_e( 'Slide Status (Turn Slide On or Off)', 'lumos-deepmind-slider' ); ?></strong>
										<p style="margin:2px 0 0;color:#646970;font-size:13px;"><?php esc_html_e( 'When turned off, this slide is kept in your drafts but hidden from website visitors.', 'lumos-deepmind-slider' ); ?></p>
									</div>
									<label class="lumos-switch">
										<input type="checkbox" name="active" id="slide-active-checkbox" value="1" checked>
										<span class="lumos-slider-round"></span>
									</label>
								</div>
							</div>

							<!-- 2. Header Text Layout Selection -->
							<div class="lumos-form-section">
								<h3 class="lumos-form-section-title">
									<span class="dashicons dashicons-layout"></span>
									<?php esc_html_e( 'Header Text Layout', 'lumos-deepmind-slider' ); ?>
								</h3>
								<p style="margin:-6px 0 14px;color:#646970;font-size:13px;"><?php esc_html_e( 'Select the placement of title, subtitle, and buttons matching DeepMind Gemini Robotics styles:', 'lumos-deepmind-slider' ); ?></p>

								<div class="lumos-layout-cards">
									<div class="lumos-layout-card is-selected" data-layout="top-center">
										<div class="lumos-layout-card-header">
											<div class="lumos-layout-title"><?php esc_html_e( 'Hero Style (Centered)', 'lumos-deepmind-slider' ); ?></div>
											<span class="lumos-layout-card-badge"><?php esc_html_e( 'Slide 1 Style', 'lumos-deepmind-slider' ); ?></span>
										</div>
										<div class="lumos-layout-preview layout-preview--hero">
											<div class="preview-line title-center"></div>
											<div class="preview-line desc-center"></div>
											<div class="preview-btn btn-bottom-center"></div>
										</div>
										<small><?php esc_html_e( 'Headline in center of slide + bottom centered CTA button', 'lumos-deepmind-slider' ); ?></small>
									</div>

									<div class="lumos-layout-card" data-layout="top-left">
										<div class="lumos-layout-card-header">
											<div class="lumos-layout-title"><?php esc_html_e( 'Card Style (Top-Left)', 'lumos-deepmind-slider' ); ?></div>
											<span class="lumos-layout-card-badge"><?php esc_html_e( 'Slide 2 & 3 Style', 'lumos-deepmind-slider' ); ?></span>
										</div>
										<div class="lumos-layout-preview layout-preview--card">
											<div class="preview-line title-left"></div>
											<div class="preview-line desc-left"></div>
											<div class="preview-btn btn-left"></div>
										</div>
										<small><?php esc_html_e( 'Headline anchored top-left + action buttons grouped directly below', 'lumos-deepmind-slider' ); ?></small>
									</div>
								</div>
							</div>

							<!-- 3. Edit Header Text & Subtitle -->
							<div class="lumos-form-section">
								<h3 class="lumos-form-section-title">
									<span class="dashicons dashicons-editor-textcolor"></span>
									<?php esc_html_e( 'Edit Header Text & Subtitle', 'lumos-deepmind-slider' ); ?>
								</h3>

								<div class="lumos-form-row">
									<div class="lumos-form-group">
										<label for="slide-title"><?php esc_html_e( 'Slide Title / Headline', 'lumos-deepmind-slider' ); ?> *</label>
										<input type="text" name="title" id="slide-title" required placeholder="<?php esc_attr_e( 'e.g. Gemini Robotics 2', 'lumos-deepmind-slider' ); ?>">
									</div>

									<div class="lumos-form-group">
										<label for="slide-title-tag"><?php esc_html_e( 'Heading Tag', 'lumos-deepmind-slider' ); ?></label>
										<select name="title_tag" id="slide-title-tag">
											<option value="h1">H1 (Recommended for Hero)</option>
											<option value="h2" selected>H2 (Standard Card)</option>
											<option value="h3">H3</option>
										</select>
									</div>
								</div>

								<div class="lumos-form-group">
									<label for="slide-desc"><?php esc_html_e( 'Subtitle / Description Text', 'lumos-deepmind-slider' ); ?></label>
									<textarea name="description" id="slide-desc" rows="3" placeholder="<?php esc_attr_e( 'The intelligence layer to power any kind of robot', 'lumos-deepmind-slider' ); ?>"></textarea>
								</div>
							</div>

							<!-- 3b. Custom Slide Logo (SVG or PNG only) -->
							<div class="lumos-form-section">
								<h3 class="lumos-form-section-title">
									<span class="dashicons dashicons-art"></span>
									<?php esc_html_e( 'Custom Slide Logo (SVG or PNG only)', 'lumos-deepmind-slider' ); ?>
								</h3>
								<p class="description" style="margin-bottom:12px;">
									<?php esc_html_e( 'Add an optional brand mark or logo to display alongside the slide text (e.g. DeepMind 4-point sparkle). Accepted formats: SVG (.svg) or PNG (.png) only.', 'lumos-deepmind-slider' ); ?>
								</p>

								<div class="lumos-form-group" style="margin-bottom:12px;">
									<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
										<input type="checkbox" name="logo_enable" id="slide-logo-enable" value="1">
										<strong><?php esc_html_e( 'Display Custom Logo on this slide', 'lumos-deepmind-slider' ); ?></strong>
									</label>
								</div>

								<div id="slide-logo-fields" style="display:none;">
									<div class="lumos-form-row">
										<div class="lumos-form-group" style="flex:2;">
											<label for="slide-logo-url"><?php esc_html_e( 'Logo Image URL (.svg or .png)', 'lumos-deepmind-slider' ); ?></label>
											<div class="lumos-media-picker-group">
												<input type="url" name="logo_url" id="slide-logo-url" placeholder="https://.../logo.svg" accept=".svg,.png,image/svg+xml,image/png">
												<button type="button" class="button" id="btn-select-logo">
													<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Choose SVG / PNG', 'lumos-deepmind-slider' ); ?>
												</button>
												<button type="button" class="button button-link-delete" id="btn-clear-logo" style="display:none;" title="<?php esc_attr_e( 'Remove Logo', 'lumos-deepmind-slider' ); ?>">
													<span class="dashicons dashicons-trash"></span>
												</button>
											</div>
											<small class="description"><?php esc_html_e( 'Only SVG and PNG files are accepted. Max recommended height: 48px.', 'lumos-deepmind-slider' ); ?></small>
										</div>

										<div class="lumos-form-group" style="flex:1;">
											<label for="slide-logo-position"><?php esc_html_e( 'Logo Position', 'lumos-deepmind-slider' ); ?></label>
											<select name="logo_position" id="slide-logo-position">
												<option value="top"><?php esc_html_e( 'On Top of Text (Above Headline)', 'lumos-deepmind-slider' ); ?></option>
												<option value="bottom"><?php esc_html_e( 'Bottom of Text (Below Description & Buttons)', 'lumos-deepmind-slider' ); ?></option>
											</select>
										</div>
									</div>

									<div class="lumos-form-group" style="margin-top:10px;">
										<label for="slide-logo-alt"><?php esc_html_e( 'Logo Alt / Title Text', 'lumos-deepmind-slider' ); ?></label>
										<input type="text" name="logo_alt" id="slide-logo-alt" placeholder="<?php esc_attr_e( 'e.g. DeepMind Sparkle Logo', 'lumos-deepmind-slider' ); ?>">
									</div>

									<div id="slide-logo-preview" class="lumos-logo-preview-box" style="display:none;margin-top:10px;">
										<div class="lumos-logo-preview-inner">
											<img id="slide-logo-preview-img" src="" alt="Logo Preview">
											<span id="slide-logo-preview-meta" class="lumos-logo-preview-meta"></span>
										</div>
									</div>
								</div>
							</div>

							<!-- 4. Background Media (Change Video & Images) -->
							<div class="lumos-form-section">
								<h3 class="lumos-form-section-title">
									<span class="dashicons dashicons-format-video"></span>
									<?php esc_html_e( 'Background Media (Video or Image)', 'lumos-deepmind-slider' ); ?>
								</h3>

								<!-- Media Specs Callout Banner -->
								<div class="lumos-media-specs-callout">
									<div class="lumos-specs-callout-header">
										<div class="lumos-specs-callout-title">
											<span class="dashicons dashicons-info" style="color:#2271b1;"></span>
											<strong><?php esc_html_e( 'Recommended Media Sizes & Formats', 'lumos-deepmind-slider' ); ?></strong>
										</div>
										<button type="button" class="button button-small" id="btn-toggle-modal-specs" style="font-size:12px;height:26px;line-height:24px;">
											<?php esc_html_e( 'View Specifications', 'lumos-deepmind-slider' ); ?> &darr;
										</button>
									</div>
									<div class="lumos-specs-summary-pills">
										<span class="lumos-spec-pill pill-video"><span class="dashicons dashicons-video-alt3"></span> <strong>Video:</strong> 1920&times;1080 (16:9) &bull; MP4/WebM &bull; &lt; 10 MB &bull; Muted</span>
										<span class="lumos-spec-pill pill-poster"><span class="dashicons dashicons-format-image"></span> <strong>Poster:</strong> 1920&times;1080 &bull; WebP/JPG &bull; &lt; 300 KB</span>
										<span class="lumos-spec-pill pill-image"><span class="dashicons dashicons-format-image"></span> <strong>Image:</strong> 1920&times;1080 (or 2560&times;1440) &bull; 150&ndash;400 KB</span>
									</div>
									<div class="lumos-specs-details" id="modal-specs-details" style="display:none;margin-top:10px;padding-top:10px;border-top:1px solid #dcdcde;">
										<table class="lumos-specs-mini-table">
											<thead>
												<tr>
													<th><?php esc_html_e( 'Type', 'lumos-deepmind-slider' ); ?></th>
													<th><?php esc_html_e( 'Resolution', 'lumos-deepmind-slider' ); ?></th>
													<th><?php esc_html_e( 'Ratio', 'lumos-deepmind-slider' ); ?></th>
													<th><?php esc_html_e( 'Formats', 'lumos-deepmind-slider' ); ?></th>
													<th><?php esc_html_e( 'File Size', 'lumos-deepmind-slider' ); ?></th>
													<th><?php esc_html_e( 'Notes', 'lumos-deepmind-slider' ); ?></th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td><strong>🎬 Video</strong></td>
													<td><code>1920 &times; 1080</code></td>
													<td>16:9</td>
													<td>MP4 (H.264), WebM</td>
													<td><strong>&lt; 10 MB</strong> (target 3&ndash;8 MB)</td>
													<td>Muted audio track, 24&ndash;30 fps, FastStart enabled</td>
												</tr>
												<tr>
													<td><strong>🖼️ Poster</strong></td>
													<td><code>1920 &times; 1080</code></td>
													<td>16:9</td>
													<td>WebP, JPG</td>
													<td><strong>&lt; 300 KB</strong></td>
													<td>1st frame of video for instant seamless loading</td>
												</tr>
												<tr>
													<td><strong>🖼️ Image</strong></td>
													<td><code>1920 &times; 1080</code> or <code>2560 &times; 1440</code></td>
													<td>16:9</td>
													<td>WebP, JPG, AVIF</td>
													<td><strong>150 &ndash; 400 KB</strong></td>
													<td>Max 600 KB; use Dark Scrim for optimal contrast</td>
												</tr>
											</tbody>
										</table>
										<div style="margin-top:8px;font-size:12px;color:#50575e;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
											<span>💡 <strong>Tip:</strong> Strip the audio track from videos to ensure browsers allow instant autoplay and reduce file size by 20%.</span>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-media-guide' ) ); ?>" target="_blank" style="font-weight:600;text-decoration:none;">
												<?php esc_html_e( 'Open Full Media Guide & Free Tools', 'lumos-deepmind-slider' ); ?> &rarr;
											</a>
										</div>
									</div>
								</div>

								<div class="lumos-radio-cards">
									<div class="lumos-radio-card is-selected" data-type="video">
										<div class="lumos-radio-card-title"><?php esc_html_e( '🎬 Video Background', 'lumos-deepmind-slider' ); ?></div>
										<small><?php esc_html_e( 'Looping autoplay MP4/WebM with fallback poster', 'lumos-deepmind-slider' ); ?></small>
									</div>
									<div class="lumos-radio-card" data-type="image">
										<div class="lumos-radio-card-title"><?php esc_html_e( '🖼️ Image Background', 'lumos-deepmind-slider' ); ?></div>
										<small><?php esc_html_e( 'High-resolution photo or illustration', 'lumos-deepmind-slider' ); ?></small>
									</div>
								</div>

								<!-- Video fields -->
								<div class="lumos-video-fields">
									<div class="lumos-form-group">
										<label for="slide-video-url"><?php esc_html_e( 'Video URL (MP4 / WebM)', 'lumos-deepmind-slider' ); ?></label>
										<div class="lumos-media-picker-group">
											<input type="url" name="video_url" id="slide-video-url" placeholder="https://example.com/video.mp4">
											<button type="button" class="button" id="btn-select-video">
												<span class="dashicons dashicons-upload" style="vertical-align:middle;margin-right:2px;"></span>
												<?php esc_html_e( 'Choose / Upload Video', 'lumos-deepmind-slider' ); ?>
											</button>
										</div>
										<small class="lumos-field-hint">
											<span class="dashicons dashicons-yes-alt" style="color:#00a32a;font-size:15px;line-height:1;vertical-align:text-bottom;"></span>
											<strong><?php esc_html_e( 'Recommended:', 'lumos-deepmind-slider' ); ?></strong>
											<?php esc_html_e( '1920×1080 (16:9), MP4 (H.264) or WebM, under 10 MB (target 3–8 MB), muted audio track, 24–30 fps.', 'lumos-deepmind-slider' ); ?>
										</small>
									</div>

									<div class="lumos-form-group">
										<label for="slide-poster-url"><?php esc_html_e( 'Fallback Poster Image (Shown while video loads)', 'lumos-deepmind-slider' ); ?></label>
										<div class="lumos-media-picker-group">
											<input type="url" name="poster_url" id="slide-poster-url" placeholder="https://example.com/poster.jpg">
											<button type="button" class="button" id="btn-select-poster">
												<span class="dashicons dashicons-format-image" style="vertical-align:middle;margin-right:2px;"></span>
												<?php esc_html_e( 'Choose / Upload Poster', 'lumos-deepmind-slider' ); ?>
											</button>
										</div>
										<small class="lumos-field-hint">
											<span class="dashicons dashicons-yes-alt" style="color:#00a32a;font-size:15px;line-height:1;vertical-align:text-bottom;"></span>
											<strong><?php esc_html_e( 'Recommended:', 'lumos-deepmind-slider' ); ?></strong>
											<?php esc_html_e( '1920×1080 px WebP or JPG, under 300 KB. Export the 1st frame of the video for zero-flicker loading.', 'lumos-deepmind-slider' ); ?>
										</small>
										<div class="lumos-media-preview-box" id="box-preview-poster">
											<img id="preview-poster" src="" alt="Poster preview">
										</div>
									</div>
								</div>

								<!-- Image fields -->
								<div class="lumos-image-fields" style="display:none;">
									<div class="lumos-form-group">
										<label for="slide-image-url"><?php esc_html_e( 'Background Image URL', 'lumos-deepmind-slider' ); ?></label>
										<div class="lumos-media-picker-group">
											<input type="url" name="image_url" id="slide-image-url" placeholder="https://example.com/slide-bg.jpg">
											<button type="button" class="button" id="btn-select-image">
												<span class="dashicons dashicons-format-image" style="vertical-align:middle;margin-right:2px;"></span>
												<?php esc_html_e( 'Choose / Upload Image', 'lumos-deepmind-slider' ); ?>
											</button>
										</div>
										<small class="lumos-field-hint">
											<span class="dashicons dashicons-yes-alt" style="color:#00a32a;font-size:15px;line-height:1;vertical-align:text-bottom;"></span>
											<strong><?php esc_html_e( 'Recommended:', 'lumos-deepmind-slider' ); ?></strong>
											<?php esc_html_e( '1920×1080 (or 2560×1440 for Retina displays), 16:9 aspect ratio, WebP or JPG, 150 KB – 400 KB (max 600 KB).', 'lumos-deepmind-slider' ); ?>
										</small>
										<div class="lumos-media-preview-box" id="box-preview-image">
											<img id="preview-image" src="" alt="Image preview">
										</div>
									</div>
								</div>

								<div class="lumos-form-group" style="margin-top:14px;">
									<label for="slide-scrim-type"><?php esc_html_e( 'Background Scrim Overlay (For Text Contrast)', 'lumos-deepmind-slider' ); ?></label>
									<select name="scrim_type" id="slide-scrim-type">
										<option value="scrim-dark"><?php esc_html_e( 'Dark Scrim (Default DeepMind contrast gradient)', 'lumos-deepmind-slider' ); ?></option>
										<option value="scrim-light"><?php esc_html_e( 'Light Scrim', 'lumos-deepmind-slider' ); ?></option>
										<option value="none"><?php esc_html_e( 'None (No overlay)', 'lumos-deepmind-slider' ); ?></option>
									</select>
								</div>
							</div>

							<!-- 5. Turn On and Off Buttons & Customize Links -->
							<div class="lumos-form-section">
								<h3 class="lumos-form-section-title">
									<span class="dashicons dashicons-button"></span>
									<?php esc_html_e( 'Action Buttons (Turn On / Off & Edit)', 'lumos-deepmind-slider' ); ?>
								</h3>

								<!-- Button 1 (Primary) -->
								<div class="lumos-btn-card">
									<div class="lumos-btn-card-header">
										<div>
											<strong><?php esc_html_e( 'Button 1 (Primary CTA)', 'lumos-deepmind-slider' ); ?></strong>
										</div>
										<label class="lumos-switch">
											<input type="checkbox" name="button_1_enable" id="slide-btn1-enable" class="btn-toggle-options" data-target="#btn1-options-box" value="1" checked>
											<span class="lumos-slider-round"></span>
										</label>
									</div>

									<div id="btn1-options-box" class="lumos-btn-options-content">
										<div class="lumos-form-row">
											<div class="lumos-form-group">
												<label for="slide-btn1-text"><?php esc_html_e( 'Button Label Text', 'lumos-deepmind-slider' ); ?></label>
												<input type="text" name="button_1_text" id="slide-btn1-text" placeholder="<?php esc_attr_e( 'e.g. Try Gemini Robotics ER 2', 'lumos-deepmind-slider' ); ?>">
											</div>
											<div class="lumos-form-group">
												<label for="slide-btn1-url"><?php esc_html_e( 'Link URL', 'lumos-deepmind-slider' ); ?></label>
												<input type="text" name="button_1_url" id="slide-btn1-url" placeholder="https://... or #anchor">
											</div>
										</div>

										<div class="lumos-form-row">
											<div class="lumos-form-group">
												<label for="slide-btn1-style"><?php esc_html_e( 'Style', 'lumos-deepmind-slider' ); ?></label>
												<select name="button_1_style" id="slide-btn1-style">
													<option value="primary"><?php esc_html_e( 'Primary (Solid White Pill)', 'lumos-deepmind-slider' ); ?></option>
													<option value="protected"><?php esc_html_e( 'Protected (Frosted Glass Pill)', 'lumos-deepmind-slider' ); ?></option>
												</select>
											</div>
											<div class="lumos-form-group">
												<label for="slide-btn1-icon"><?php esc_html_e( 'Icon', 'lumos-deepmind-slider' ); ?></label>
												<select name="button_1_icon" id="slide-btn1-icon">
													<option value="ai_studio"><?php esc_html_e( 'Google AI Studio Ribbon', 'lumos-deepmind-slider' ); ?></option>
													<option value="arrow_right"><?php esc_html_e( 'Arrow Right', 'lumos-deepmind-slider' ); ?></option>
													<option value="external"><?php esc_html_e( 'External Link', 'lumos-deepmind-slider' ); ?></option>
													<option value="none"><?php esc_html_e( 'None', 'lumos-deepmind-slider' ); ?></option>
												</select>
											</div>
										</div>

										<div class="lumos-form-group">
											<label>
												<input type="checkbox" name="button_1_target" id="slide-btn1-target" value="_blank">
												<?php esc_html_e( 'Open in new tab', 'lumos-deepmind-slider' ); ?>
											</label>
										</div>
									</div>
								</div>

								<!-- Button 2 (Secondary) -->
								<div class="lumos-btn-card" style="margin-top:14px;">
									<div class="lumos-btn-card-header">
										<div>
											<strong><?php esc_html_e( 'Button 2 (Secondary / Text Link)', 'lumos-deepmind-slider' ); ?></strong>
										</div>
										<label class="lumos-switch">
											<input type="checkbox" name="button_2_enable" id="slide-btn2-enable" class="btn-toggle-options" data-target="#btn2-options-box" value="1">
											<span class="lumos-slider-round"></span>
										</label>
									</div>

									<div id="btn2-options-box" class="lumos-btn-options-content" style="display:none;">
										<div class="lumos-form-row">
											<div class="lumos-form-group">
												<label for="slide-btn2-text"><?php esc_html_e( 'Button Label Text', 'lumos-deepmind-slider' ); ?></label>
												<input type="text" name="button_2_text" id="slide-btn2-text" placeholder="<?php esc_attr_e( 'e.g. Learn more', 'lumos-deepmind-slider' ); ?>">
											</div>
											<div class="lumos-form-group">
												<label for="slide-btn2-url"><?php esc_html_e( 'Link URL', 'lumos-deepmind-slider' ); ?></label>
												<input type="text" name="button_2_url" id="slide-btn2-url" placeholder="https://... or #anchor">
											</div>
										</div>

										<div class="lumos-form-row">
											<div class="lumos-form-group">
												<label for="slide-btn2-style"><?php esc_html_e( 'Style', 'lumos-deepmind-slider' ); ?></label>
												<select name="button_2_style" id="slide-btn2-style">
													<option value="protected"><?php esc_html_e( 'Protected (Frosted Glass Pill)', 'lumos-deepmind-slider' ); ?></option>
													<option value="text"><?php esc_html_e( 'Text Link with Chevron Arrow', 'lumos-deepmind-slider' ); ?></option>
												</select>
											</div>
											<div class="lumos-form-group">
												<label for="slide-btn2-icon"><?php esc_html_e( 'Icon', 'lumos-deepmind-slider' ); ?></label>
												<select name="button_2_icon" id="slide-btn2-icon">
													<option value="arrow_right"><?php esc_html_e( 'Chevron Arrow Right', 'lumos-deepmind-slider' ); ?></option>
													<option value="external"><?php esc_html_e( 'External Link', 'lumos-deepmind-slider' ); ?></option>
													<option value="none"><?php esc_html_e( 'None', 'lumos-deepmind-slider' ); ?></option>
												</select>
											</div>
										</div>

										<div class="lumos-form-group">
											<label>
												<input type="checkbox" name="button_2_target" id="slide-btn2-target" value="_blank">
												<?php esc_html_e( 'Open in new tab', 'lumos-deepmind-slider' ); ?>
											</label>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="lumos-modal-footer">
							<button type="button" class="button button-secondary" id="btn-modal-cancel"><?php esc_html_e( 'Cancel', 'lumos-deepmind-slider' ); ?></button>
							<button type="submit" class="button button-primary" id="btn-save-slide"><?php esc_html_e( 'Save Slide', 'lumos-deepmind-slider' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Settings & Shortcode page
	 */
	public function render_settings_page() {
		$settings = get_option( 'lumos_slider_settings', Lumos_Slider_Defaults::get_default_settings() );
		?>
		<div class="wrap lumos-admin-wrap">
			<div class="lumos-admin-header">
				<div>
					<h1>
						<span><?php esc_html_e( 'DeepMind Hero Slider Settings', 'lumos-deepmind-slider' ); ?></span>
					</h1>
					<p><?php esc_html_e( 'Global settings, shortcode generator, and developer usage guide.', 'lumos-deepmind-slider' ); ?></p>
				</div>
				<div class="lumos-admin-actions">
					<a href="<?php echo esc_url( add_query_arg( 'lumos_slider_preview', '1', home_url( '/' ) ) ); ?>" class="button button-secondary" target="_blank" title="<?php esc_attr_e( 'Preview slider on a clean distraction-free page', 'lumos-deepmind-slider' ); ?>">
						<span class="dashicons dashicons-visibility"></span>
						<span><?php esc_html_e( 'Preview Slider', 'lumos-deepmind-slider' ); ?></span>
					</a>
				</div>
			</div>

			<nav class="lumos-nav-tabs">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-deepmind-slider' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Manage Slides', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-settings' ) ); ?>" class="lumos-nav-tab is-active"><?php esc_html_e( 'Settings & Shortcode', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-media-guide' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Media Guidelines', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-changelog' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Changelog', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-import-export' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Import & Export', 'lumos-deepmind-slider' ); ?></a>
			</nav>

			<!-- Shortcode Helper Box -->
			<div class="lumos-settings-box">
				<h2><?php esc_html_e( 'Shortcode & Embedding', 'lumos-deepmind-slider' ); ?></h2>
				<p><?php esc_html_e( 'Copy and paste this shortcode into any post, page, widget, or page builder (Elementor, Gutenberg, Divi, WPBakery):', 'lumos-deepmind-slider' ); ?></p>
				<div class="lumos-code-snippet">
					<span>[deepmind_slider]</span>
					<button type="button" class="button button-secondary button-small" id="btn-copy-shortcode"><?php esc_html_e( 'Copy Shortcode', 'lumos-deepmind-slider' ); ?></button>
				</div>

				<h3 style="margin-top:20px;"><?php esc_html_e( 'PHP Template Tag (Theme Developers)', 'lumos-deepmind-slider' ); ?></h3>
				<div class="lumos-code-snippet">
					<code>&lt;?php if ( function_exists( 'lumos_deepmind_slider' ) ) { lumos_deepmind_slider(); } ?&gt;</code>
				</div>
			</div>

			<!-- Settings Form -->
			<form method="post" action="">
				<?php wp_nonce_field( 'lumos_save_slider_settings', 'lumos_settings_nonce' ); ?>
				<div class="lumos-settings-box">
					<h2><?php esc_html_e( 'Global Carousel Options', 'lumos-deepmind-slider' ); ?></h2>

					<div class="lumos-form-group">
						<label for="setting-caption"><?php esc_html_e( 'Bottom Caption Text', 'lumos-deepmind-slider' ); ?></label>
						<input type="text" name="caption" id="setting-caption" value="<?php echo esc_attr( isset( $settings['caption'] ) ? $settings['caption'] : 'Explore the latest' ); ?>">
						<small><?php esc_html_e( 'Displayed in the bottom left corner next to the arrow controls (e.g. "Explore the latest"). Leave blank to hide.', 'lumos-deepmind-slider' ); ?></small>
					</div>

					<div class="lumos-form-group">
						<label>
							<input type="checkbox" name="autoplay" id="setting-autoplay-toggle" value="1" <?php checked( ! empty( $settings['autoplay'] ) ); ?>>
							<?php esc_html_e( 'Enable Auto-slide / Autoplay', 'lumos-deepmind-slider' ); ?>
						</label>
						<small><?php esc_html_e( 'Automatically advances to the next slide after a set duration.', 'lumos-deepmind-slider' ); ?></small>
					</div>

					<div class="lumos-form-group" id="box-autoplay-speed" style="<?php echo empty( $settings['autoplay'] ) ? 'display:none;' : ''; ?>">
						<label for="setting-autoplay-speed"><?php esc_html_e( 'Auto-slide Time / Duration (Seconds)', 'lumos-deepmind-slider' ); ?></label>
						<div style="display:flex;align-items:center;gap:10px;">
							<input type="number" name="autoplay_speed_sec" id="setting-autoplay-speed" value="<?php echo esc_attr( isset( $settings['autoplay_speed'] ) ? round( $settings['autoplay_speed'] / 1000, 1 ) : 6 ); ?>" min="1" max="60" step="0.5" style="max-width:120px;">
							<span style="font-weight:500;color:#50575e;"><?php esc_html_e( 'seconds (e.g. 6sec)', 'lumos-deepmind-slider' ); ?></span>
						</div>
						<small><?php esc_html_e( 'Manually adjust how long each slide stays visible before advancing to the next slide.', 'lumos-deepmind-slider' ); ?></small>
					</div>

					<div class="lumos-form-group">
						<label>
							<input type="checkbox" name="loop" value="1" <?php checked( isset( $settings['loop'] ) ? $settings['loop'] : true ); ?>>
							<?php esc_html_e( 'Infinite Loop Mode (wraps around seamlessly)', 'lumos-deepmind-slider' ); ?>
						</label>
					</div>

					<div class="lumos-form-group">
						<label>
							<input type="checkbox" name="advance_on_video_end" value="1" <?php checked( ! isset( $settings['advance_on_video_end'] ) || ! empty( $settings['advance_on_video_end'] ) ); ?>>
							<?php esc_html_e( 'Auto-Advance on Video End (Automatically transition to next slide when background video finishes playing)', 'lumos-deepmind-slider' ); ?>
						</label>
						<small><?php esc_html_e( 'When enabled, videos play to their natural end without looping, and the carousel automatically advances to the next slide.', 'lumos-deepmind-slider' ); ?></small>
					</div>

					<div class="lumos-form-group">
						<label for="setting-slide-height"><?php esc_html_e( 'Slide Height CSS Rule', 'lumos-deepmind-slider' ); ?></label>
						<input type="text" name="slide_height" id="setting-slide-height" value="<?php echo esc_attr( isset( $settings['slide_height'] ) ? $settings['slide_height'] : 'clamp(520px, 82svh, 800px)' ); ?>">
						<small><?php esc_html_e( 'Default is clamp(520px, 82svh, 800px) matching DeepMind. You can also use 16:9 aspect ratios or custom pixel heights (e.g. 700px).', 'lumos-deepmind-slider' ); ?></small>
					</div>

					<p style="margin-top:20px;">
						<button type="submit" name="lumos_save_settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'lumos-deepmind-slider' ); ?></button>
					</p>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle settings post save
	 */
	public function handle_save_settings() {
		if ( ! isset( $_POST['lumos_save_settings'] ) ) {
			return;
		}

		if ( ! isset( $_POST['lumos_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lumos_settings_nonce'] ) ), 'lumos_save_slider_settings' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current = get_option( 'lumos_slider_settings', Lumos_Slider_Defaults::get_default_settings() );

		$current['caption']        = isset( $_POST['caption'] ) ? sanitize_text_field( wp_unslash( $_POST['caption'] ) ) : '';
		$current['autoplay']       = ! empty( $_POST['autoplay'] );
		if ( isset( $_POST['autoplay_speed_sec'] ) ) {
			$sec = floatval( $_POST['autoplay_speed_sec'] );
			$current['autoplay_speed'] = intval( max( 1, $sec ) * 1000 );
		} elseif ( isset( $_POST['autoplay_speed'] ) ) {
			$current['autoplay_speed'] = intval( $_POST['autoplay_speed'] );
		}
		$current['loop']                 = ! empty( $_POST['loop'] );
		$current['advance_on_video_end'] = ! empty( $_POST['advance_on_video_end'] );
		$current['slide_height']         = isset( $_POST['slide_height'] ) ? sanitize_text_field( wp_unslash( $_POST['slide_height'] ) ) : 'clamp(520px, 82svh, 800px)';

		update_option( 'lumos_slider_settings', $current );
	}

	/**
	 * Render the Media Guidelines & Best Practices page
	 */
	public function render_media_guide_page() {
		?>
		<div class="wrap lumos-admin-wrap">
			<div class="lumos-admin-header">
				<div>
					<h1>
						<span><?php esc_html_e( 'Media Guidelines & Sizing Specs', 'lumos-deepmind-slider' ); ?></span>
						<span class="lumos-badge"><?php esc_html_e( 'Best Practices', 'lumos-deepmind-slider' ); ?></span>
					</h1>
					<p><?php esc_html_e( 'Recommended image dimensions, video formats, file sizes, and encoding parameters for the DeepMind Hero Slider.', 'lumos-deepmind-slider' ); ?></p>
				</div>
				<div class="lumos-admin-actions">
					<a href="<?php echo esc_url( add_query_arg( 'lumos_slider_preview', '1', home_url( '/' ) ) ); ?>" class="button button-secondary" target="_blank" title="<?php esc_attr_e( 'Preview slider on a clean distraction-free page', 'lumos-deepmind-slider' ); ?>">
						<span class="dashicons dashicons-visibility"></span>
						<span><?php esc_html_e( 'Preview Slider', 'lumos-deepmind-slider' ); ?></span>
					</a>
				</div>
			</div>

			<nav class="lumos-nav-tabs">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-deepmind-slider' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Manage Slides', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-settings' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Settings & Shortcode', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-media-guide' ) ); ?>" class="lumos-nav-tab is-active"><?php esc_html_e( 'Media Guidelines', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-changelog' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Changelog', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-import-export' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Import & Export', 'lumos-deepmind-slider' ); ?></a>
			</nav>

			<!-- 1. Quick Summary Cards Grid -->
			<div class="lumos-guide-cards-grid" style="margin-bottom:24px;">
				<div class="lumos-guide-card">
					<div class="lumos-guide-card-icon" style="background:#e8f0fe;color:#1a73e8;">🎬</div>
					<h3><?php esc_html_e( 'Background Video', 'lumos-deepmind-slider' ); ?></h3>
					<div class="lumos-guide-card-stat"><code>1920 &times; 1080</code> (16:9)</div>
					<ul>
						<li><strong><?php esc_html_e( 'Formats:', 'lumos-deepmind-slider' ); ?></strong> MP4 (H.264 codec) or WebM</li>
						<li><strong><?php esc_html_e( 'Target Size:', 'lumos-deepmind-slider' ); ?></strong> &lt; 10 MB (optimal: 3&ndash;8 MB)</li>
						<li><strong><?php esc_html_e( 'Bitrate:', 'lumos-deepmind-slider' ); ?></strong> 1,500 &ndash; 3,000 kbps (1.5&ndash;3 Mbps)</li>
						<li><strong><?php esc_html_e( 'Frame Rate:', 'lumos-deepmind-slider' ); ?></strong> 24 fps or 30 fps (avoid 60 fps)</li>
						<li><strong><?php esc_html_e( 'Audio:', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( 'Must be muted / audio track removed', 'lumos-deepmind-slider' ); ?></li>
						<li><strong><?php esc_html_e( 'Streaming:', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( 'Enable FastStart (moov atom at start)', 'lumos-deepmind-slider' ); ?></li>
					</ul>
				</div>

				<div class="lumos-guide-card">
					<div class="lumos-guide-card-icon" style="background:#e6f4ea;color:#137333;">🖼️</div>
					<h3><?php esc_html_e( 'Fallback Poster Image', 'lumos-deepmind-slider' ); ?></h3>
					<div class="lumos-guide-card-stat"><code>1920 &times; 1080</code> (16:9)</div>
					<ul>
						<li><strong><?php esc_html_e( 'Formats:', 'lumos-deepmind-slider' ); ?></strong> WebP (recommended) or JPG</li>
						<li><strong><?php esc_html_e( 'Target Size:', 'lumos-deepmind-slider' ); ?></strong> &lt; 200 &ndash; 300 KB</li>
						<li><strong><?php esc_html_e( 'Quality:', 'lumos-deepmind-slider' ); ?></strong> 80% &ndash; 85% compression</li>
						<li><strong><?php esc_html_e( 'Source:', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( 'First frame of video (0.0s)', 'lumos-deepmind-slider' ); ?></li>
						<li><strong><?php esc_html_e( 'Role:', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( 'Prevents dark flashes while video streams', 'lumos-deepmind-slider' ); ?></li>
					</ul>
				</div>

				<div class="lumos-guide-card">
					<div class="lumos-guide-card-icon" style="background:#fef7e0;color:#b06000;">🖼️</div>
					<h3><?php esc_html_e( 'Background Image', 'lumos-deepmind-slider' ); ?></h3>
					<div class="lumos-guide-card-stat"><code>1920 &times; 1080</code> (16:9)</div>
					<ul>
						<li><strong><?php esc_html_e( 'Formats:', 'lumos-deepmind-slider' ); ?></strong> WebP, JPG, or AVIF</li>
						<li><strong><?php esc_html_e( 'Target Size:', 'lumos-deepmind-slider' ); ?></strong> 150 KB &ndash; 400 KB (max 600 KB)</li>
						<li><strong><?php esc_html_e( 'Retina 2K:', 'lumos-deepmind-slider' ); ?></strong> 2560 &times; 1440 px maximum</li>
						<li><strong><?php esc_html_e( 'Contrast:', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( 'Use Dark Scrim overlay for light text', 'lumos-deepmind-slider' ); ?></li>
						<li><strong><?php esc_html_e( 'Focal Point:', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( 'Center / right-weighted for typography', 'lumos-deepmind-slider' ); ?></li>
					</ul>
				</div>
			</div>

			<!-- 2. Master Specification Comparison Table -->
			<div class="lumos-settings-box">
				<h2><?php esc_html_e( 'Complete Technical Specifications', 'lumos-deepmind-slider' ); ?></h2>
				<p><?php esc_html_e( 'Compare technical criteria side-by-side to ensure maximum visual fidelity with minimal page load latency:', 'lumos-deepmind-slider' ); ?></p>

				<div style="overflow-x:auto;">
					<table class="lumos-specs-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Parameter', 'lumos-deepmind-slider' ); ?></th>
								<th><?php esc_html_e( 'Background Video', 'lumos-deepmind-slider' ); ?></th>
								<th><?php esc_html_e( 'Video Poster Image', 'lumos-deepmind-slider' ); ?></th>
								<th><?php esc_html_e( 'Background Image', 'lumos-deepmind-slider' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><strong><?php esc_html_e( 'Optimal Dimensions', 'lumos-deepmind-slider' ); ?></strong></td>
								<td><code>1920 &times; 1080 px</code> (1080p)</td>
								<td><code>1920 &times; 1080 px</code></td>
								<td><code>1920 &times; 1080 px</code> (or <code>2560 &times; 1440 px</code>)</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Aspect Ratio', 'lumos-deepmind-slider' ); ?></strong></td>
								<td><code>16:9</code> widescreen</td>
								<td><code>16:9</code> widescreen</td>
								<td><code>16:9</code> widescreen</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Recommended Formats', 'lumos-deepmind-slider' ); ?></strong></td>
								<td><code>.mp4</code> (H.264), <code>.webm</code> (VP9/AV1)</td>
								<td><code>.webp</code>, <code>.jpg</code></td>
								<td><code>.webp</code>, <code>.jpg</code>, <code>.avif</code></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Ideal File Size', 'lumos-deepmind-slider' ); ?></strong></td>
								<td><strong style="color:#00a32a;">3 MB &ndash; 8 MB</strong></td>
								<td><strong style="color:#00a32a;">100 KB &ndash; 250 KB</strong></td>
								<td><strong style="color:#00a32a;">150 KB &ndash; 350 KB</strong></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Maximum Safe Size', 'lumos-deepmind-slider' ); ?></strong></td>
								<td>15 MB (avoid files &gt; 20 MB)</td>
								<td>400 KB</td>
								<td>600 KB (avoid uncompressed files &gt; 1 MB)</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Video Bitrate / Quality', 'lumos-deepmind-slider' ); ?></strong></td>
								<td>1,500 &ndash; 3,000 kbps (CRF 22&ndash;24)</td>
								<td>80% &ndash; 85% WebP quality</td>
								<td>80% &ndash; 85% WebP quality</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Audio Requirement', 'lumos-deepmind-slider' ); ?></strong></td>
								<td><strong><?php esc_html_e( 'Strictly Muted / No Audio', 'lumos-deepmind-slider' ); ?></strong></td>
								<td>N/A</td>
								<td>N/A</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Autoplay Support', 'lumos-deepmind-slider' ); ?></strong></td>
								<td><?php esc_html_e( 'Universal (iOS, Safari, Android, Chrome)', 'lumos-deepmind-slider' ); ?></td>
								<td><?php esc_html_e( 'Instant preview while buffering', 'lumos-deepmind-slider' ); ?></td>
								<td><?php esc_html_e( 'Instant responsive display', 'lumos-deepmind-slider' ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- 3. Video Compression & Optimization Tools -->
			<div class="lumos-settings-box">
				<h2><span class="dashicons dashicons-video-alt3" style="color:#2271b1;vertical-align:middle;margin-right:6px;"></span><?php esc_html_e( 'Video Optimization & Compression Guide', 'lumos-deepmind-slider' ); ?></h2>
				<p><?php esc_html_e( 'High-resolution hero videos look stunning when optimized properly. Use these tested settings to achieve crisp 1080p video under 8 MB:', 'lumos-deepmind-slider' ); ?></p>

				<h3 style="margin-top:18px;"><?php esc_html_e( 'Option A: Free HandBrake GUI (Recommended for Most Users)', 'lumos-deepmind-slider' ); ?></h3>
				<p><?php esc_html_e( 'Download HandBrake (free for Mac & Windows at handbrake.fr) and use these settings:', 'lumos-deepmind-slider' ); ?></p>
				<ul class="lumos-checklist">
					<li><strong><?php esc_html_e( 'Preset:', 'lumos-deepmind-slider' ); ?></strong> Fast 1080p30</li>
					<li><strong><?php esc_html_e( 'Format:', 'lumos-deepmind-slider' ); ?></strong> MP4, with <strong>Web Optimized</strong> checked.</li>
					<li><strong><?php esc_html_e( 'Dimensions:', 'lumos-deepmind-slider' ); ?></strong> 1920 &times; 1080 (16:9).</li>
					<li><strong><?php esc_html_e( 'Video Tab:', 'lumos-deepmind-slider' ); ?></strong> Video Codec H.264 (x264), Framerate 30 fps (Peak Framerate), Constant Quality RF 22 to 24.</li>
					<li><strong><?php esc_html_e( 'Audio Tab:', 'lumos-deepmind-slider' ); ?></strong> Click &ldquo;Clear Tracks&rdquo; to remove audio completely.</li>
				</ul>

				<h3 style="margin-top:24px;"><?php esc_html_e( 'Option B: 1-Line FFmpeg Terminal Command (For Developers)', 'lumos-deepmind-slider' ); ?></h3>
				<p><?php esc_html_e( 'Run this command in terminal to convert any raw video into a web-optimized, streaming-ready MP4:', 'lumos-deepmind-slider' ); ?></p>
				<div class="lumos-code-snippet">
					<code>ffmpeg -i input.mov -c:v libx264 -crf 23 -preset slow -pix_fmt yuv420p -an -movflags +faststart output.mp4</code>
				</div>
				<ul style="margin:10px 0 0 20px;color:#50575e;font-size:13px;line-height:1.7;">
					<li><code>-c:v libx264</code>: <?php esc_html_e( 'Encodes with H.264, universally compatible with Apple Safari, iOS, Chrome, and Android.', 'lumos-deepmind-slider' ); ?></li>
					<li><code>-crf 23</code>: <?php esc_html_e( 'Constant Rate Factor 23 delivers pristine visual quality with lightweight file size.', 'lumos-deepmind-slider' ); ?></li>
					<li><code>-an</code>: <?php esc_html_e( 'Strips audio track entirely, cutting file size by ~20% and guaranteeing browser autoplay compliance.', 'lumos-deepmind-slider' ); ?></li>
					<li><code>-movflags +faststart</code>: <?php esc_html_e( 'Places metadata atom at the beginning so playback begins immediately without waiting to download the whole file.', 'lumos-deepmind-slider' ); ?></li>
				</ul>
			</div>

			<!-- 4. Image Optimization & Autoplay Best Practices -->
			<div class="lumos-settings-box">
				<h2><span class="dashicons dashicons-format-image" style="color:#2271b1;vertical-align:middle;margin-right:6px;"></span><?php esc_html_e( 'Image Optimization & Browser Autoplay Rules', 'lumos-deepmind-slider' ); ?></h2>

				<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:20px;margin-top:16px;">
					<div style="background:#f6f7f7;padding:18px 20px;border-radius:8px;border:1px solid #dcdcde;">
						<h3 style="margin-top:0;"><?php esc_html_e( '🖼️ Why WebP Format is Recommended', 'lumos-deepmind-slider' ); ?></h3>
						<p style="font-size:13px;color:#50575e;line-height:1.6;margin-bottom:10px;">
							<?php esc_html_e( 'WebP provides 25%–35% higher compression than standard JPEG at identical visual fidelity. It is natively supported in WordPress and modern browsers.', 'lumos-deepmind-slider' ); ?>
						</p>
						<p style="font-size:13px;color:#50575e;line-height:1.6;margin-bottom:0;">
							<strong><?php esc_html_e( 'Free Compression Tools:', 'lumos-deepmind-slider' ); ?></strong><br>
							&bull; <a href="https://squoosh.app" target="_blank" rel="noopener">Squoosh.app</a> <?php esc_html_e( '(Official Google Chrome compression web app)', 'lumos-deepmind-slider' ); ?><br>
							&bull; <a href="https://tinypng.com" target="_blank" rel="noopener">TinyPNG / TinyJPG</a> <?php esc_html_e( '(Drag-and-drop instant optimizer)', 'lumos-deepmind-slider' ); ?>
						</p>
					</div>

					<div style="background:#f6f7f7;padding:18px 20px;border-radius:8px;border:1px solid #dcdcde;">
						<h3 style="margin-top:0;"><?php esc_html_e( '🔇 Browser Autoplay Policies (Chrome & Safari)', 'lumos-deepmind-slider' ); ?></h3>
						<p style="font-size:13px;color:#50575e;line-height:1.6;margin-bottom:10px;">
							<?php esc_html_e( 'Modern browsers (Chrome, Safari, Firefox, Edge, iOS Safari, Android Chrome) block video autoplay with sound unless the visitor has clicked the page first.', 'lumos-deepmind-slider' ); ?>
						</p>
						<p style="font-size:13px;color:#50575e;line-height:1.6;margin-bottom:0;">
							<?php esc_html_e( 'DeepMind Hero Slider automatically includes `muted playsinline autoplay loop` attributes. Always ensure your video has no loud audio track for seamless loading.', 'lumos-deepmind-slider' ); ?>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Changelog page
	 */
	public function render_changelog_page() {
		?>
		<div class="wrap lumos-admin-wrap">
			<div class="lumos-admin-header">
				<div class="lumos-admin-header-text">
					<h1>
						<span><?php esc_html_e( 'DeepMind Hero Slider', 'lumos-deepmind-slider' ); ?></span>
						<span class="lumos-badge">v<?php echo esc_html( LUMOS_SLIDER_VERSION ); ?></span>
					</h1>
					<p><?php esc_html_e( 'Version history, release notes, and WordPress upgrade guidelines.', 'lumos-deepmind-slider' ); ?></p>
				</div>
				<div class="lumos-admin-actions">
					<a href="<?php echo esc_url( add_query_arg( 'lumos_slider_preview', '1', home_url( '/' ) ) ); ?>" class="button button-secondary" target="_blank" title="<?php esc_attr_e( 'Preview slider on a clean distraction-free page', 'lumos-deepmind-slider' ); ?>">
						<span class="dashicons dashicons-visibility"></span>
						<span><?php esc_html_e( 'Preview Slider', 'lumos-deepmind-slider' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-deepmind-slider' ) ); ?>" class="button button-primary">
						<span class="dashicons dashicons-slides"></span>
						<span><?php esc_html_e( 'Manage Slides', 'lumos-deepmind-slider' ); ?></span>
					</a>
				</div>
			</div>

			<nav class="lumos-nav-tabs">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-deepmind-slider' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Manage Slides', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-settings' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Settings & Shortcode', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-media-guide' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Media Guidelines', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-changelog' ) ); ?>" class="lumos-nav-tab is-active"><?php esc_html_e( 'Changelog', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-import-export' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Import & Export', 'lumos-deepmind-slider' ); ?></a>
			</nav>

			<!-- In-Place Upgrade Notice -->
			<div class="lumos-settings-box" style="border-left: 4px solid #1a73e8; background: #f8faff;">
				<div style="display: flex; align-items: flex-start; gap: 14px;">
					<span class="dashicons dashicons-update" style="color: #1a73e8; font-size: 28px; width: 28px; height: 28px; margin-top: 2px;"></span>
					<div>
						<h3 style="margin: 0 0 6px; color: #0f172a; font-size: 15px; font-weight: 600;">
							<?php esc_html_e( 'Seamless WordPress Plugin Replacement', 'lumos-deepmind-slider' ); ?>
						</h3>
						<p style="margin: 0; color: #475569; font-size: 13px; line-height: 1.6;">
							<?php esc_html_e( 'When uploading a new version zip (e.g. lumos-deepmind-slider-v.1.0.1.zip) via Plugins > Add New > Upload Plugin, WordPress automatically detects the existing installation and offers "Replace current with uploaded". The plugin directory slug is automatically normalized so no duplicate folders are created, and all your existing slides and custom settings remain 100% intact in the database.', 'lumos-deepmind-slider' ); ?>
						</p>
					</div>
				</div>
			</div>

			<!-- Version Timeline -->
			<div class="lumos-settings-box">
				<div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 18px;">
					<div style="display: flex; align-items: center; gap: 10px;">
						<h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">Version 1.0.1</h2>
						<span style="background: #e7f5ea; color: #185a25; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 9999px;">Latest Release</span>
					</div>
					<span style="color: #64748b; font-size: 13px;">October 5, 2026</span>
				</div>

				<ul style="list-style: disc; margin: 0 0 24px 20px; color: #334155; line-height: 1.7; font-size: 13.5px;">
					<li><strong>Custom Slide Logo (SVG or PNG only):</strong> Add an optional brand logo or vector mark directly above the headline or below description/buttons per slide (e.g. Google DeepMind 4-point sparkle). Strict validation allows SVG (<code>.svg</code>) and PNG (<code>.png</code>) only. Fully integrated with slide export/import packages.</li>
					<li><strong>Video Loading &gt; 50% Buffer Crossfade &amp; Looping:</strong> While video is loading, fallback poster image is visible. When video buffer reaches &gt; 50% (or readyState 4), it triggers an immediate smooth 0.75s crossfade from poster to video, video starts playback immediately as the crossfade begins, and continues into continuous loop playback without reverting to poster.</li>
					<li><strong>Slide & Media Export & Import Package:</strong> Complete 1-click export bundling all slide data (<code>slides.json</code>) and full media assets (<code>media/</code>) into a portable <code>.zip</code> archive. Smart import with automatic sideloading into the WordPress Media Library and slide URL remapping.</li>
					<li><strong>Auto Slide Transition on Video End:</strong> The carousel now automatically advances to the next slide immediately upon background video completion (HTML5 <code>ended</code> event).</li>
					<li><strong>Configurable Autoplay Timer:</strong> Added manual interval settings (e.g. 6 seconds) with validation and shortcode support.</li>
					<li><strong>Calibrated Drag & Touch Physics:</strong> Fixed issue where drag gesture bounced back to the previous slide by lowering drag threshold (15% slide width / 60px minimum distance) and adding swipe velocity momentum detection.</li>
					<li><strong>Media Guidelines Documentation:</strong> Added comprehensive sizing specifications for 16:9 WebM/MP4 videos, WebP/JPEG images, audio track stripping, and fast-start flags.</li>
					<li><strong>Full-Width Slide Admin & Overhauled Button Styles:</strong> Redesigned admin header and buttons with modern DeepMind blue styling, soft shadows, and full-width layout usage.</li>
					<li><strong>Modal Layout Cards Fix:</strong> Resolved overlapping layout badge glitch by wrapping headline title and badge in a dedicated flex header.</li>
					<li><strong>Support for Hash `#` Anchors & Relative URLs:</strong> Converted button URL inputs to text fields, enabling `#`, `#anchor`, and `/relative-path` links with page jump prevention.</li>
					<li><strong>Standalone Slider Preview Mode:</strong> Added dedicated <em>Preview Slider</em> button opening a clean, distraction-free page with only the slider rendered.</li>
					<li><strong>WordPress In-Place Replacement:</strong> Ensured versioned archives (<code>lumos-deepmind-slider-v.1.0.1.zip</code>) map to the canonical <code>lumos-deepmind-slider</code> directory for seamless WordPress updates.</li>
				</ul>

				<div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 18px; margin-top: 28px;">
					<div style="display: flex; align-items: center; gap: 10px;">
						<h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">Version 1.0.0</h2>
						<span style="background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 9999px;">Initial Release</span>
					</div>
					<span style="color: #64748b; font-size: 13px;">October 3, 2026</span>
				</div>

				<ul style="list-style: disc; margin: 0 0 10px 20px; color: #334155; line-height: 1.7; font-size: 13.5px;">
					<li><strong>DeepMind Gemini Robotics Slider:</strong> Full recreation of the official Google DeepMind hero carousel with 93.5% desktop width peeking.</li>
					<li><strong>Hero Centered & Card Top-Left Layouts:</strong> Dynamic headline and action button placements.</li>
					<li><strong>Video & Image Backgrounds:</strong> Support for MP4/WebM video with poster fallbacks and responsive picture elements.</li>
					<li><strong>WordPress Media Library:</strong> Integrated media modal pickers.</li>
					<li><strong>Shortcode & Block:</strong> <code>[deepmind_slider]</code> and Gutenberg block support.</li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX: Save or Create Slide
	 */
	public function ajax_save_slide() {
		check_ajax_referer( 'lumos_slider_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied', 'lumos-deepmind-slider' ) );
		}

		$slides   = get_option( 'lumos_slider_slides', Lumos_Slider_Defaults::get_default_slides() );
		$slide_id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : 'new';

		$new_data = $this->sanitize_slide_data( wp_unslash( $_POST ) );

		if ( 'new' === $slide_id || empty( $slide_id ) ) {
			$new_data['id'] = time();
			$slides[]       = $new_data;
		} else {
			$found = false;
			foreach ( $slides as $key => $slide ) {
				if ( (string) $slide['id'] === (string) $slide_id ) {
					$new_data['id'] = $slide['id'];
					$slides[ $key ] = $new_data;
					$found          = true;
					break;
				}
			}
			if ( ! $found ) {
				$new_data['id'] = time();
				$slides[]       = $new_data;
			}
		}

		update_option( 'lumos_slider_slides', $slides );
		wp_send_json_success( array( 'slides' => $slides ) );
	}

	/**
	 * AJAX: Toggle slide active/inactive status
	 */
	public function ajax_toggle_slide_status() {
		check_ajax_referer( 'lumos_slider_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied', 'lumos-deepmind-slider' ) );
		}

		$slide_id = isset( $_POST['slide_id'] ) ? sanitize_text_field( wp_unslash( $_POST['slide_id'] ) ) : '';
		$slides   = get_option( 'lumos_slider_slides', Lumos_Slider_Defaults::get_default_slides() );
		$new_active = false;

		foreach ( $slides as $key => $slide ) {
			if ( (string) $slide['id'] === (string) $slide_id ) {
				$current_active = ! isset( $slide['active'] ) || true === $slide['active'] || '1' === $slide['active'] || 1 === $slide['active'];
				$new_active     = ! $current_active;
				$slides[ $key ]['active'] = $new_active;
				break;
			}
		}

		update_option( 'lumos_slider_slides', $slides );
		wp_send_json_success( array( 'active' => $new_active ) );
	}

	/**
	 * AJAX: Delete Slide
	 */
	public function ajax_delete_slide() {
		check_ajax_referer( 'lumos_slider_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied', 'lumos-deepmind-slider' ) );
		}

		$slide_id = isset( $_POST['slide_id'] ) ? sanitize_text_field( wp_unslash( $_POST['slide_id'] ) ) : '';
		$slides   = get_option( 'lumos_slider_slides', Lumos_Slider_Defaults::get_default_slides() );

		$filtered = array();
		foreach ( $slides as $s ) {
			if ( (string) $s['id'] !== (string) $slide_id ) {
				$filtered[] = $s;
			}
		}

		update_option( 'lumos_slider_slides', $filtered );
		wp_send_json_success();
	}

	/**
	 * AJAX: Duplicate Slide
	 */
	public function ajax_duplicate_slide() {
		check_ajax_referer( 'lumos_slider_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied', 'lumos-deepmind-slider' ) );
		}

		$slide_id = isset( $_POST['slide_id'] ) ? sanitize_text_field( wp_unslash( $_POST['slide_id'] ) ) : '';
		$slides   = get_option( 'lumos_slider_slides', Lumos_Slider_Defaults::get_default_slides() );

		$new_slides = array();
		foreach ( $slides as $s ) {
			$new_slides[] = $s;
			if ( (string) $s['id'] === (string) $slide_id ) {
				$cloned          = $s;
				$cloned['id']    = time();
				$cloned['title'] = $s['title'] . ' (Copy)';
				$new_slides[]    = $cloned;
			}
		}

		update_option( 'lumos_slider_slides', $new_slides );
		wp_send_json_success();
	}

	/**
	 * AJAX: Save slide order
	 */
	public function ajax_save_slide_order() {
		check_ajax_referer( 'lumos_slider_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied', 'lumos-deepmind-slider' ) );
		}

		$order  = isset( $_POST['order'] ) && is_array( $_POST['order'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['order'] ) ) : array();
		$slides = get_option( 'lumos_slider_slides', Lumos_Slider_Defaults::get_default_slides() );

		$slide_map = array();
		foreach ( $slides as $s ) {
			$slide_map[ (string) $s['id'] ] = $s;
		}

		$ordered_slides = array();
		foreach ( $order as $id ) {
			if ( isset( $slide_map[ $id ] ) ) {
				$ordered_slides[] = $slide_map[ $id ];
				unset( $slide_map[ $id ] );
			}
		}

		// Append any remaining
		foreach ( $slide_map as $remaining ) {
			$ordered_slides[] = $remaining;
		}

		update_option( 'lumos_slider_slides', $ordered_slides );
		wp_send_json_success();
	}

	/**
	 * AJAX: Restore default demo slides
	 */
	public function ajax_reset_demo_slides() {
		check_ajax_referer( 'lumos_slider_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied', 'lumos-deepmind-slider' ) );
		}

		$demo_slides = Lumos_Slider_Defaults::get_default_slides();
		update_option( 'lumos_slider_slides', $demo_slides );
		wp_send_json_success();
	}

	/**
	 * Helper: Sanitize slide data array
	 *
	 * @param array $data Raw slide input data.
	 * @return array Sanitized slide data.
	 */
	public function sanitize_slide_data( $data ) {
		// Strict SVG or PNG validation for custom logo
		$logo_url    = isset( $data['logo_url'] ) ? esc_url_raw( $data['logo_url'] ) : '';
		$logo_enable = ! empty( $data['logo_enable'] );

		if ( ! empty( $logo_url ) ) {
			$clean_logo = strtok( $logo_url, '?#' );
			$ext        = strtolower( pathinfo( $clean_logo, PATHINFO_EXTENSION ) );
			$is_valid   = in_array( $ext, array( 'svg', 'png' ), true ) ||
			              0 === strpos( $logo_url, 'data:image/svg+xml' ) ||
			              0 === strpos( $logo_url, 'data:image/png' );
			if ( ! $is_valid ) {
				$logo_url    = '';
				$logo_enable = false;
			}
		} else {
			$logo_enable = false;
		}

		$logo_position = isset( $data['logo_position'] ) && in_array( $data['logo_position'], array( 'top', 'bottom' ), true ) ? $data['logo_position'] : 'top';
		$logo_alt      = isset( $data['logo_alt'] ) ? sanitize_text_field( $data['logo_alt'] ) : '';

		return array(
			'id'              => isset( $data['id'] ) ? sanitize_text_field( $data['id'] ) : time(),
			'active'          => ! isset( $data['active'] ) || true === $data['active'] || '1' === $data['active'] || 1 === $data['active'],
			'title'           => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
			'title_tag'       => isset( $data['title_tag'] ) ? sanitize_text_field( $data['title_tag'] ) : 'h2',
			'layout'          => isset( $data['layout'] ) ? sanitize_text_field( $data['layout'] ) : 'top-center',
			'description'     => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
			'media_type'      => isset( $data['media_type'] ) ? sanitize_text_field( $data['media_type'] ) : 'video',
			'video_url'       => isset( $data['video_url'] ) ? esc_url_raw( $data['video_url'] ) : '',
			'video_webm_url'  => isset( $data['video_webm_url'] ) ? esc_url_raw( $data['video_webm_url'] ) : '',
			'poster_url'      => isset( $data['poster_url'] ) ? esc_url_raw( $data['poster_url'] ) : '',
			'image_url'       => isset( $data['image_url'] ) ? esc_url_raw( $data['image_url'] ) : '',
			'scrim_type'      => isset( $data['scrim_type'] ) ? sanitize_text_field( $data['scrim_type'] ) : 'scrim-dark',
			'scrim_opacity'   => isset( $data['scrim_opacity'] ) ? sanitize_text_field( $data['scrim_opacity'] ) : '0.45',
			'button_1_enable' => ! empty( $data['button_1_enable'] ),
			'button_1_text'   => isset( $data['button_1_text'] ) ? sanitize_text_field( $data['button_1_text'] ) : '',
			'button_1_url'    => isset( $data['button_1_url'] ) ? ( ( 0 === strpos( trim( $data['button_1_url'] ), '#' ) || 0 === strpos( trim( $data['button_1_url'] ), '/' ) ) ? sanitize_text_field( $data['button_1_url'] ) : esc_url_raw( $data['button_1_url'] ) ) : '',
			'button_1_target' => isset( $data['button_1_target'] ) ? sanitize_text_field( $data['button_1_target'] ) : '_self',
			'button_1_style'  => isset( $data['button_1_style'] ) ? sanitize_text_field( $data['button_1_style'] ) : 'primary',
			'button_1_icon'   => isset( $data['button_1_icon'] ) ? sanitize_text_field( $data['button_1_icon'] ) : 'none',
			'button_2_enable' => ! empty( $data['button_2_enable'] ),
			'button_2_text'   => isset( $data['button_2_text'] ) ? sanitize_text_field( $data['button_2_text'] ) : '',
			'button_2_url'    => isset( $data['button_2_url'] ) ? ( ( 0 === strpos( trim( $data['button_2_url'] ), '#' ) || 0 === strpos( trim( $data['button_2_url'] ), '/' ) ) ? sanitize_text_field( $data['button_2_url'] ) : esc_url_raw( $data['button_2_url'] ) ) : '',
			'button_2_target' => isset( $data['button_2_target'] ) ? sanitize_text_field( $data['button_2_target'] ) : '_self',
			'button_2_style'  => isset( $data['button_2_style'] ) ? sanitize_text_field( $data['button_2_style'] ) : 'protected',
			'button_2_icon'   => isset( $data['button_2_icon'] ) ? sanitize_text_field( $data['button_2_icon'] ) : 'none',
			'logo_enable'     => $logo_enable,
			'logo_url'        => $logo_url,
			'logo_position'   => $logo_position,
			'logo_alt'        => $logo_alt,
		);
	}

	/**
	 * Helper: Recursively delete a directory
	 *
	 * @param string $dir Path to directory.
	 */
	private function recursive_delete_dir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$items = scandir( $dir );
		if ( false === $items ) {
			return;
		}
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if ( is_dir( $path ) ) {
				$this->recursive_delete_dir( $path );
			} else {
				@unlink( $path );
			}
		}
		@rmdir( $dir );
	}

	/**
	 * Render the Import & Export Slides page
	 */
	public function render_import_export_page() {
		$slides   = get_option( 'lumos_slider_slides', Lumos_Slider_Defaults::get_default_slides() );
		$settings = get_option( 'lumos_slider_settings', Lumos_Slider_Defaults::get_default_settings() );

		$total_slides  = count( $slides );
		$active_slides = 0;
		$media_count   = 0;
		$seen_media    = array();

		foreach ( $slides as $s ) {
			if ( ! isset( $s['active'] ) || true === $s['active'] || '1' === $s['active'] || 1 === $s['active'] ) {
				$active_slides++;
			}
			$urls = array(
				isset( $s['video_url'] ) ? $s['video_url'] : '',
				isset( $s['video_webm_url'] ) ? $s['video_webm_url'] : '',
				isset( $s['poster_url'] ) ? $s['poster_url'] : '',
				isset( $s['image_url'] ) ? $s['image_url'] : '',
			);
			foreach ( $urls as $u ) {
				$u = trim( $u );
				if ( ! empty( $u ) && ! isset( $seen_media[ $u ] ) ) {
					$seen_media[ $u ] = true;
					$media_count++;
				}
			}
		}

		$max_upload_size = ini_get( 'upload_max_filesize' );
		?>
		<div class="wrap lumos-admin-wrap">
			<div class="lumos-admin-header">
				<div class="lumos-admin-header-text">
					<h1>
						<span><?php esc_html_e( 'DeepMind Hero Slider', 'lumos-deepmind-slider' ); ?></span>
						<span class="lumos-badge">v<?php echo esc_html( LUMOS_SLIDER_VERSION ); ?></span>
					</h1>
					<p><?php esc_html_e( 'Export all slides, custom texts, parameters, and media into a self-contained ZIP archive, or import an existing package.', 'lumos-deepmind-slider' ); ?></p>
				</div>
				<div class="lumos-admin-actions">
					<a href="<?php echo esc_url( add_query_arg( 'lumos_slider_preview', '1', home_url( '/' ) ) ); ?>" class="button button-secondary" target="_blank" title="<?php esc_attr_e( 'Preview slider on a clean distraction-free page', 'lumos-deepmind-slider' ); ?>">
						<span class="dashicons dashicons-visibility"></span>
						<span><?php esc_html_e( 'Preview Slider', 'lumos-deepmind-slider' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-deepmind-slider' ) ); ?>" class="button button-primary">
						<span class="dashicons dashicons-slides"></span>
						<span><?php esc_html_e( 'Manage Slides', 'lumos-deepmind-slider' ); ?></span>
					</a>
				</div>
			</div>

			<nav class="lumos-nav-tabs">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-deepmind-slider' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Manage Slides', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-settings' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Settings & Shortcode', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-media-guide' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Media Guidelines', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-changelog' ) ); ?>" class="lumos-nav-tab"><?php esc_html_e( 'Changelog', 'lumos-deepmind-slider' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lumos-slider-import-export' ) ); ?>" class="lumos-nav-tab is-active"><?php esc_html_e( 'Import & Export', 'lumos-deepmind-slider' ); ?></a>
			</nav>

			<div class="lumos-import-export-grid">
				<!-- EXPORT CARD -->
				<div class="lumos-ie-card">
					<div class="lumos-ie-card-header">
						<div class="lumos-ie-card-icon is-export">
							<span class="dashicons dashicons-download"></span>
						</div>
						<div>
							<h2><?php esc_html_e( 'Export Slides & Media Package', 'lumos-deepmind-slider' ); ?></h2>
							<p><?php esc_html_e( 'Download a complete, self-contained backup containing all your slide configurations, texts, button options, slider parameters, and uploaded media files.', 'lumos-deepmind-slider' ); ?></p>
						</div>
					</div>

					<!-- Stats Summary -->
					<div class="lumos-ie-stats">
						<div class="lumos-ie-stat-pill">
							<span class="num"><?php echo esc_html( $total_slides ); ?></span>
							<span class="label"><?php esc_html_e( 'Total Slides', 'lumos-deepmind-slider' ); ?></span>
						</div>
						<div class="lumos-ie-stat-pill">
							<span class="num"><?php echo esc_html( $active_slides ); ?></span>
							<span class="label"><?php esc_html_e( 'Active Slides', 'lumos-deepmind-slider' ); ?></span>
						</div>
						<div class="lumos-ie-stat-pill">
							<span class="num"><?php echo esc_html( $media_count ); ?></span>
							<span class="label"><?php esc_html_e( 'Media Files', 'lumos-deepmind-slider' ); ?></span>
						</div>
					</div>

					<div style="flex-grow:1;margin-bottom:24px;">
						<h4 style="margin:0 0 10px;font-size:13.5px;color:#1e293b;font-weight:600;"><?php esc_html_e( 'What is included in the export:', 'lumos-deepmind-slider' ); ?></h4>
						<ul class="lumos-checklist">
							<li><strong><?php esc_html_e( 'slides.json file:', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( 'Full slider data including titles, headings, tags, descriptions, layouts, links, and button styles.', 'lumos-deepmind-slider' ); ?></li>
							<li><strong><?php esc_html_e( 'Media folder (media/):', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( 'All uploaded background videos (MP4, WebM), poster fallback frames, and image assets.', 'lumos-deepmind-slider' ); ?></li>
							<li><strong><?php esc_html_e( 'Global settings:', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( 'Autoplay speed, advance on video ended, loop mode, scrim opacity, and dimensions.', 'lumos-deepmind-slider' ); ?></li>
							<li><strong><?php esc_html_e( 'Universal compatibility:', 'lumos-deepmind-slider' ); ?></strong> <?php esc_html_e( 'Can be imported into any other WordPress site or the standalone interactive preview demo.', 'lumos-deepmind-slider' ); ?></li>
						</ul>
					</div>

					<div style="display:flex;flex-direction:column;gap:10px;">
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lumos_export_slides&format=zip' ), 'lumos_export_slides_nonce', 'nonce' ) ); ?>" class="button button-primary" style="width:100%;text-align:center;">
							<span class="dashicons dashicons-download"></span>
							<span><?php esc_html_e( 'Download Complete Package (.zip with Media)', 'lumos-deepmind-slider' ); ?></span>
						</a>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lumos_export_slides&format=json' ), 'lumos_export_slides_nonce', 'nonce' ) ); ?>" class="button button-secondary" style="width:100%;text-align:center;">
							<span class="dashicons dashicons-media-code"></span>
							<span><?php esc_html_e( 'Export Config Only (slides.json)', 'lumos-deepmind-slider' ); ?></span>
						</a>
					</div>
				</div>

				<!-- IMPORT CARD -->
				<div class="lumos-ie-card">
					<div class="lumos-ie-card-header">
						<div class="lumos-ie-card-icon is-import">
							<span class="dashicons dashicons-upload"></span>
						</div>
						<div>
							<h2><?php esc_html_e( 'Import Slides & Media Package', 'lumos-deepmind-slider' ); ?></h2>
							<p><?php esc_html_e( 'Upload an exported .zip archive or .json backup file. Media files will be automatically imported into your WordPress Media Library.', 'lumos-deepmind-slider' ); ?></p>
						</div>
					</div>

					<form id="lumos-import-form" enctype="multipart/form-data">
						<!-- Drag & Drop Zone -->
						<div class="lumos-file-dropzone" id="lumos-file-dropzone">
							<span class="dashicons dashicons-cloud-upload"></span>
							<div class="lumos-file-dropzone-title"><?php esc_html_e( 'Click to browse or drag & drop file here', 'lumos-deepmind-slider' ); ?></div>
							<div class="lumos-file-dropzone-sub">
								<?php
								/* translators: %s: maximum upload file size */
								printf( esc_html__( 'Accepts .zip or .json (Max upload size: %s)', 'lumos-deepmind-slider' ), esc_html( $max_upload_size ) );
								?>
							</div>
							<input type="file" name="import_file" id="import_file" accept=".zip,.json" style="display:none;" required>
							<div id="lumos-selected-file-info" style="display:none;">
								<span class="lumos-selected-file-badge">
									<span class="dashicons dashicons-media-default"></span>
									<span id="lumos-selected-filename"></span>
								</span>
							</div>
						</div>

						<!-- Import Mode -->
						<div class="lumos-import-options">
							<label class="lumos-radio-option-card">
								<input type="radio" name="import_mode" value="replace" checked>
								<div>
									<span class="option-title"><?php esc_html_e( 'Replace existing slides (Recommended)', 'lumos-deepmind-slider' ); ?></span>
									<span class="option-desc"><?php esc_html_e( 'Replaces all current slides with the slides from this backup package.', 'lumos-deepmind-slider' ); ?></span>
								</div>
							</label>

							<label class="lumos-radio-option-card">
								<input type="radio" name="import_mode" value="append">
								<div>
									<span class="option-title"><?php esc_html_e( 'Append to existing slides', 'lumos-deepmind-slider' ); ?></span>
									<span class="option-desc"><?php esc_html_e( 'Preserves your current slides and adds the imported slides to the end of the list.', 'lumos-deepmind-slider' ); ?></span>
								</div>
							</label>

							<label style="display:flex;align-items:center;gap:10px;padding:6px 0;font-size:13.5px;color:#1e293b;cursor:pointer;">
								<input type="checkbox" name="import_settings" value="1" checked style="margin:0;">
								<span><strong><?php esc_html_e( 'Import Global Slider Settings', 'lumos-deepmind-slider' ); ?></strong> (<?php esc_html_e( 'autoplay, autoplay interval, loop, video advance, scrim opacity', 'lumos-deepmind-slider' ); ?>)</span>
							</label>
						</div>

						<!-- Status Container -->
						<div id="lumos-import-status" style="display:none;margin-bottom:16px;"></div>

						<!-- Submit Button -->
						<button type="submit" class="button button-primary" id="btn-submit-import" style="width:100%;text-align:center;">
							<span class="dashicons dashicons-upload"></span>
							<span><?php esc_html_e( 'Upload and Import Package', 'lumos-deepmind-slider' ); ?></span>
						</button>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Export Slides: Generates downloadable ZIP archive with slides.json + media/ or standalone JSON
	 */
	public function handle_export_slides() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'lumos-deepmind-slider' ) );
		}

		check_admin_referer( 'lumos_export_slides_nonce', 'nonce' );

		$slides   = get_option( 'lumos_slider_slides', Lumos_Slider_Defaults::get_default_slides() );
		$settings = get_option( 'lumos_slider_settings', Lumos_Slider_Defaults::get_default_settings() );
		$format   = isset( $_GET['format'] ) && 'json' === $_GET['format'] ? 'json' : 'zip';

		// JSON format export
		if ( 'json' === $format ) {
			$data = array(
				'generator'   => 'Lumos DeepMind Hero Slider',
				'version'     => LUMOS_SLIDER_VERSION,
				'exported_at' => gmdate( 'c' ),
				'site_url'    => home_url(),
				'settings'    => $settings,
				'slides'      => $slides,
			);
			$filename = 'lumos-slides-export-' . gmdate( 'Y-m-d-His' ) . '.json';
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Cache-Control: no-cache, no-store, must-revalidate' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
			echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			exit;
		}

		// ZIP format export with media
		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( esc_html__( 'The ZipArchive PHP extension is not available on this server. Please contact your host or export as JSON instead.', 'lumos-deepmind-slider' ) );
		}

		$upload_dir = wp_upload_dir();
		$temp_zip   = trailingslashit( $upload_dir['basedir'] ) . 'lumos-export-' . uniqid() . '.zip';

		$zip = new ZipArchive();
		if ( true !== $zip->open( $temp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			wp_die( esc_html__( 'Failed to create export ZIP archive.', 'lumos-deepmind-slider' ) );
		}

		$media_map        = array();
		$used_names       = array();
		$processed_slides = array();

		foreach ( $slides as $slide_idx => $slide ) {
			$processed_slide = $slide;
			$processed_slide['_bundled_media'] = array();

			$media_fields = array(
				'video'      => isset( $slide['video_url'] ) ? $slide['video_url'] : '',
				'video_webm' => isset( $slide['video_webm_url'] ) ? $slide['video_webm_url'] : '',
				'poster'     => isset( $slide['poster_url'] ) ? $slide['poster_url'] : '',
				'image'      => isset( $slide['image_url'] ) ? $slide['image_url'] : '',
				'logo'       => isset( $slide['logo_url'] ) ? $slide['logo_url'] : '',
			);

			foreach ( $media_fields as $field_key => $url ) {
				$url = trim( $url );
				if ( empty( $url ) ) {
					continue;
				}

				$clean_url = strtok( $url, '#' );

				if ( isset( $media_map[ $clean_url ] ) ) {
					$processed_slide['_bundled_media'][ $field_key ] = $media_map[ $clean_url ];
					continue;
				}

				$file_path = '';
				$file_data = null;

				// 1. Try resolving via attachment ID
				$post_id = attachment_url_to_postid( $clean_url );
				if ( $post_id ) {
					$attached = get_attached_file( $post_id );
					if ( $attached && file_exists( $attached ) ) {
						$file_path = $attached;
					}
				}

				// 2. Try resolving via upload baseurl
				if ( ! $file_path && 0 === strpos( $clean_url, $upload_dir['baseurl'] ) ) {
					$rel = str_replace( $upload_dir['baseurl'], '', $clean_url );
					$candidate = $upload_dir['basedir'] . $rel;
					if ( file_exists( $candidate ) ) {
						$file_path = $candidate;
					}
				}

				// 3. Try resolving via ABSPATH
				if ( ! $file_path && 0 === strpos( $clean_url, home_url() ) ) {
					$rel = str_replace( home_url(), '', $clean_url );
					$candidate = ABSPATH . ltrim( $rel, '/' );
					if ( file_exists( $candidate ) ) {
						$file_path = $candidate;
					}
				}

				// 4. Download remote file if not local
				if ( ! $file_path ) {
					$response = wp_safe_remote_get(
						$clean_url,
						array(
							'timeout'   => 25,
							'sslverify' => false,
						)
					);
					if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
						$file_data = wp_remote_retrieve_body( $response );
					}
				}

				if ( ! $file_path && null === $file_data ) {
					continue;
				}

				// Generate clean unique filename inside media/
				$parsed_path  = parse_url( $clean_url, PHP_URL_PATH );
				$raw_filename = $parsed_path ? basename( $parsed_path ) : 'media_' . $slide_idx . '_' . $field_key;
				$filename     = sanitize_file_name( $raw_filename );
				if ( empty( $filename ) || false === strpos( $filename, '.' ) ) {
					$ext      = ( 'video' === $field_key || 'video_webm' === $field_key ) ? ( 'video_webm' === $field_key ? '.webm' : '.mp4' ) : '.jpg';
					$filename = 'media_' . $slide_idx . '_' . $field_key . $ext;
				}

				if ( isset( $used_names[ $filename ] ) ) {
					$filename = ( $slide_idx + 1 ) . '_' . $filename;
				}
				$used_names[ $filename ] = true;

				$zip_relative_path = 'media/' . $filename;

				if ( $file_path && file_exists( $file_path ) ) {
					$zip->addFile( $file_path, $zip_relative_path );
				} elseif ( null !== $file_data ) {
					$zip->addFromString( $zip_relative_path, $file_data );
				}

				$media_map[ $clean_url ] = $zip_relative_path;
				$processed_slide['_bundled_media'][ $field_key ] = $zip_relative_path;
			}

			$processed_slides[] = $processed_slide;
		}

		// Bundle slides.json manifest
		$manifest = array(
			'generator'   => 'Lumos DeepMind Hero Slider',
			'version'     => LUMOS_SLIDER_VERSION,
			'exported_at' => gmdate( 'c' ),
			'site_url'    => home_url(),
			'settings'    => $settings,
			'slides'      => $processed_slides,
			'media_files' => array_values( $media_map ),
		);

		$zip->addFromString( 'slides.json', wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
		$zip->close();

		if ( ! file_exists( $temp_zip ) ) {
			wp_die( esc_html__( 'Error: Zip export file could not be generated.', 'lumos-deepmind-slider' ) );
		}

		$download_name = 'lumos-slides-export-' . gmdate( 'Y-m-d' ) . '.zip';
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $download_name . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $temp_zip ) );
		readfile( $temp_zip );
		@unlink( $temp_zip );
		exit;
	}

	/**
	 * AJAX: Import Slides and Sideload Media into WP Media Library
	 */
	public function ajax_import_slides() {
		check_ajax_referer( 'lumos_slider_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'lumos-deepmind-slider' ) );
		}

		if ( empty( $_FILES['import_file'] ) || UPLOAD_ERR_OK !== $_FILES['import_file']['error'] ) {
			wp_send_json_error( __( 'Please select a valid export file (.zip or .json) to upload.', 'lumos-deepmind-slider' ) );
		}

		$file_info       = $_FILES['import_file'];
		$file_name       = sanitize_file_name( $file_info['name'] );
		$file_ext        = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
		$import_mode     = isset( $_POST['import_mode'] ) && 'append' === $_POST['import_mode'] ? 'append' : 'replace';
		$import_settings = ! empty( $_POST['import_settings'] );

		if ( ! in_array( $file_ext, array( 'zip', 'json' ), true ) ) {
			wp_send_json_error( __( 'Invalid file format. Only .zip and .json backup files are supported.', 'lumos-deepmind-slider' ) );
		}

		$slides_data          = null;
		$settings_data        = null;
		$media_imported_count = 0;

		if ( 'json' === $file_ext ) {
			$json_content = file_get_contents( $file_info['tmp_name'] );
			$decoded      = json_decode( $json_content, true );
			if ( empty( $decoded ) || ! is_array( $decoded ) || empty( $decoded['slides'] ) ) {
				wp_send_json_error( __( 'Invalid JSON file: No slides data found in backup.', 'lumos-deepmind-slider' ) );
			}
			$slides_data   = $decoded['slides'];
			$settings_data = isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ? $decoded['settings'] : null;
		} else {
			if ( ! class_exists( 'ZipArchive' ) ) {
				wp_send_json_error( __( 'ZipArchive PHP extension is not available on this server.', 'lumos-deepmind-slider' ) );
			}

			$upload_dir       = wp_upload_dir();
			$temp_extract_dir = trailingslashit( $upload_dir['basedir'] ) . 'lumos-import-' . uniqid();
			wp_mkdir_p( $temp_extract_dir );

			$zip = new ZipArchive();
			if ( true !== $zip->open( $file_info['tmp_name'] ) ) {
				$this->recursive_delete_dir( $temp_extract_dir );
				wp_send_json_error( __( 'Failed to open the uploaded ZIP archive.', 'lumos-deepmind-slider' ) );
			}

			$zip->extractTo( $temp_extract_dir );
			$zip->close();

			// Locate slides.json
			$json_path = $temp_extract_dir . '/slides.json';
			if ( ! file_exists( $json_path ) ) {
				$subdirs = glob( $temp_extract_dir . '/*/slides.json' );
				if ( ! empty( $subdirs ) ) {
					$json_path = $subdirs[0];
				}
			}

			if ( ! file_exists( $json_path ) ) {
				$this->recursive_delete_dir( $temp_extract_dir );
				wp_send_json_error( __( 'Invalid backup: slides.json was not found in the ZIP archive.', 'lumos-deepmind-slider' ) );
			}

			$json_content = file_get_contents( $json_path );
			$decoded      = json_decode( $json_content, true );
			if ( empty( $decoded ) || ! is_array( $decoded ) || empty( $decoded['slides'] ) ) {
				$this->recursive_delete_dir( $temp_extract_dir );
				wp_send_json_error( __( 'No slide records found inside slides.json.', 'lumos-deepmind-slider' ) );
			}

			$slides_data   = $decoded['slides'];
			$settings_data = isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ? $decoded['settings'] : null;

			// Sideload media into Media Library
			$base_dir      = dirname( $json_path );
			$media_dir     = $base_dir . '/media';
			$media_url_map = array();

			if ( is_dir( $media_dir ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';

				$media_files = scandir( $media_dir );
				if ( false !== $media_files ) {
					foreach ( $media_files as $mf ) {
						if ( '.' === $mf || '..' === $mf || '.DS_Store' === $mf ) {
							continue;
						}
						$file_path = $media_dir . '/' . $mf;
						if ( ! is_file( $file_path ) ) {
							continue;
						}

						$file_contents = file_get_contents( $file_path );
						if ( false === $file_contents ) {
							continue;
						}

						// Sideload via wp_upload_bits
						$upload = wp_upload_bits( $mf, null, $file_contents );
						if ( ! empty( $upload['error'] ) ) {
							continue;
						}

						$wp_filetype = wp_check_filetype( $upload['file'] );
						$attachment  = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title'     => sanitize_file_name( pathinfo( $mf, PATHINFO_FILENAME ) ),
							'post_content'   => '',
							'post_status'    => 'inherit',
						);
						$attach_id = wp_insert_attachment( $attachment, $upload['file'] );
						if ( ! is_wp_error( $attach_id ) && $attach_id > 0 ) {
							$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
							wp_update_attachment_metadata( $attach_id, $attach_data );
						}

						$new_url = $upload['url'];
						$media_url_map[ 'media/' . $mf ] = $new_url;
						$media_url_map[ $mf ]            = $new_url;
						$media_imported_count++;
					}
				}
			}

			// Remap media URLs in slides
			foreach ( $slides_data as &$s ) {
				if ( isset( $s['_bundled_media'] ) && is_array( $s['_bundled_media'] ) ) {
					if ( ! empty( $s['_bundled_media']['video'] ) && isset( $media_url_map[ $s['_bundled_media']['video'] ] ) ) {
						$s['video_url'] = $media_url_map[ $s['_bundled_media']['video'] ];
					}
					if ( ! empty( $s['_bundled_media']['video_webm'] ) && isset( $media_url_map[ $s['_bundled_media']['video_webm'] ] ) ) {
						$s['video_webm_url'] = $media_url_map[ $s['_bundled_media']['video_webm'] ];
					}
					if ( ! empty( $s['_bundled_media']['poster'] ) && isset( $media_url_map[ $s['_bundled_media']['poster'] ] ) ) {
						$s['poster_url'] = $media_url_map[ $s['_bundled_media']['poster'] ];
					}
					if ( ! empty( $s['_bundled_media']['image'] ) && isset( $media_url_map[ $s['_bundled_media']['image'] ] ) ) {
						$s['image_url'] = $media_url_map[ $s['_bundled_media']['image'] ];
					}
					if ( ! empty( $s['_bundled_media']['logo'] ) && isset( $media_url_map[ $s['_bundled_media']['logo'] ] ) ) {
						$s['logo_url'] = $media_url_map[ $s['_bundled_media']['logo'] ];
					}
				} else {
					$url_keys = array( 'video_url', 'video_webm_url', 'poster_url', 'image_url', 'logo_url' );
					foreach ( $url_keys as $uk ) {
						if ( ! empty( $s[ $uk ] ) ) {
							$base = basename( parse_url( $s[ $uk ], PHP_URL_PATH ) );
							if ( isset( $media_url_map[ $base ] ) ) {
								$s[ $uk ] = $media_url_map[ $base ];
							}
						}
					}
				}
				unset( $s['_bundled_media'] );
			}
			unset( $s );

			// Cleanup temporary extract directory
			$this->recursive_delete_dir( $temp_extract_dir );
		}

		// Sanitize all imported slides
		$sanitized_slides = array();
		$time_base        = time();
		foreach ( $slides_data as $i => $raw_slide ) {
			$sanitized = $this->sanitize_slide_data( $raw_slide );
			if ( 'append' === $import_mode ) {
				$sanitized['id'] = $time_base + ( $i * 10 );
			}
			$sanitized_slides[] = $sanitized;
		}

		// Update slides in options
		if ( 'append' === $import_mode ) {
			$existing_slides = get_option( 'lumos_slider_slides', Lumos_Slider_Defaults::get_default_slides() );
			$final_slides    = array_merge( $existing_slides, $sanitized_slides );
		} else {
			$final_slides = $sanitized_slides;
		}

		update_option( 'lumos_slider_slides', $final_slides );

		// Update settings if requested
		if ( $import_settings && ! empty( $settings_data ) ) {
			$defaults       = Lumos_Slider_Defaults::get_default_settings();
			$clean_settings = array(
				'caption'              => isset( $settings_data['caption'] ) ? sanitize_text_field( $settings_data['caption'] ) : $defaults['caption'],
				'autoplay'             => ! empty( $settings_data['autoplay'] ),
				'autoplay_speed'       => isset( $settings_data['autoplay_speed'] ) ? max( 1000, absint( $settings_data['autoplay_speed'] ) ) : $defaults['autoplay_speed'],
				'loop'                 => ! empty( $settings_data['loop'] ),
				'advance_on_video_end' => ! empty( $settings_data['advance_on_video_end'] ),
				'slide_width'          => isset( $settings_data['slide_width'] ) ? sanitize_text_field( $settings_data['slide_width'] ) : $defaults['slide_width'],
				'slide_height'         => isset( $settings_data['slide_height'] ) ? sanitize_text_field( $settings_data['slide_height'] ) : $defaults['slide_height'],
				'theme'                => isset( $settings_data['theme'] ) ? sanitize_text_field( $settings_data['theme'] ) : $defaults['theme'],
			);
			update_option( 'lumos_slider_settings', $clean_settings );
		}

		wp_send_json_success(
			array(
				'message'      => sprintf(
					/* translators: 1: number of slides, 2: number of media files */
					__( 'Success! Imported %1$d slide(s) and %2$d media file(s).', 'lumos-deepmind-slider' ),
					count( $sanitized_slides ),
					$media_imported_count
				),
				'slides_count' => count( $sanitized_slides ),
				'media_count'  => $media_imported_count,
			)
		);
	}
}
