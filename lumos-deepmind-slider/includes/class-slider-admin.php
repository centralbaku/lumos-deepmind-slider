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
		// Listed as "Slider" under the shared LUMOS menu. The other screens are reached
		// through the tabs on the slider page and keep "Slider" highlighted.
		lumos_menu_add_product(
			'lumos-deepmind-slider',
			__( 'Slider', 'lumos-deepmind-slider' ),
			array( $this, 'render_slides_page' ),
			__( 'Full-screen hero slider with a block and shortcode.', 'lumos-deepmind-slider' ),
			'manage_options',
			'dashicons-slides'
		);

		$hidden = array(
			'lumos-slider-settings'      => array( __( 'Settings & Shortcode', 'lumos-deepmind-slider' ), 'render_settings_page' ),
			'lumos-slider-media-guide'   => array( __( 'Media Guidelines', 'lumos-deepmind-slider' ), 'render_media_guide_page' ),
			'lumos-slider-changelog'     => array( __( 'Changelog', 'lumos-deepmind-slider' ), 'render_changelog_page' ),
			'lumos-slider-import-export' => array( __( 'Import & Export', 'lumos-deepmind-slider' ), 'render_import_export_page' ),
		);

		foreach ( $hidden as $slug => $page ) {
			lumos_menu_add_hidden_page( $slug, $page[0], array( $this, $page[1] ), 'lumos-deepmind-slider' );
		}
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
		$current['slide_height']         = isset( $_POST['slide_height'] ) ? $this->sanitize_css_length( wp_unslash( $_POST['slide_height'] ), 'clamp(520px, 82svh, 800px)' ) : 'clamp(520px, 82svh, 800px)';

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
			$new_data['id'] = $this->generate_slide_id( $slides );
			$slides[]       = $new_data;
		} else {
			$found = false;
			foreach ( $slides as $key => $slide ) {
				if ( (string) $slide['id'] === (string) $slide_id ) {
					$new_data['id'] = $slide['id'];
					// The edit form does not post the on/off state; keep the stored value.
					$new_data['active'] = isset( $slide['active'] ) ? (bool) $slide['active'] : true;
					$slides[ $key ]     = $new_data;
					$found              = true;
					break;
				}
			}
			if ( ! $found ) {
				$new_data['id'] = $this->generate_slide_id( $slides );
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
				$cloned['id']    = $this->generate_slide_id( $slides );
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
	 * Generate a slide ID that is unique within the given slides list.
	 *
	 * @param array $slides Existing slides.
	 * @return string
	 */
	private function generate_slide_id( $slides ) {
		$used = array();
		foreach ( (array) $slides as $slide ) {
			if ( is_array( $slide ) && isset( $slide['id'] ) ) {
				$used[ (string) $slide['id'] ] = true;
			}
		}
		$id = (int) round( microtime( true ) * 1000 );
		while ( isset( $used[ (string) $id ] ) ) {
			$id++;
		}
		return (string) $id;
	}

	/**
	 * Accept only a safe CSS length expression (e.g. "clamp(520px, 82svh, 800px)").
	 *
	 * @param mixed  $value   Raw value.
	 * @param string $default Fallback value.
	 * @return string
	 */
	public function sanitize_css_length( $value, $default ) {
		$value = trim( sanitize_text_field( (string) $value ) );
		if ( '' !== $value && preg_match( '/^[0-9a-z().%\s,+\-*\/]{1,120}$/i', $value ) ) {
			return $value;
		}
		return $default;
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
			'id'              => isset( $data['id'] ) && '' !== (string) $data['id'] ? sanitize_text_field( $data['id'] ) : $this->generate_slide_id( array() ),
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
	 * Export Slides: ZIP archive (slides.json + media/) or standalone JSON backup.
	 */
	public function handle_export_slides() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'lumos-deepmind-slider' ) );
		}

		check_admin_referer( 'lumos_export_slides_nonce', 'nonce' );

		$slides   = get_option( 'lumos_slider_slides', Lumos_Slider_Defaults::get_default_slides() );
		$settings = get_option( 'lumos_slider_settings', Lumos_Slider_Defaults::get_default_settings() );
		$format   = isset( $_GET['format'] ) && 'json' === $_GET['format'] ? 'json' : 'zip';

		// JSON format export (no media files bundled).
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

		// ZIP format export with media.
		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( esc_html__( 'The ZipArchive PHP extension is not available on this server. Please contact your host or export as JSON instead.', 'lumos-deepmind-slider' ) );
		}

		// Build the archive in the system temp directory (never in the public uploads folder).
		$temp_zip = wp_tempnam( 'lumos-export.zip' );
		$zip      = new ZipArchive();
		if ( true !== $zip->open( $temp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			wp_die( esc_html__( 'Failed to create export ZIP archive.', 'lumos-deepmind-slider' ) );
		}

		$upload_dir       = wp_upload_dir();
		$media_map        = array();
		$used_names       = array();
		$processed_slides = array();
		$temp_downloads   = array();

		$media_keys = array( 'video', 'video_webm', 'poster', 'image', 'logo' );

		foreach ( $slides as $slide_idx => $slide ) {
			if ( ! is_array( $slide ) ) {
				continue;
			}

			$processed_slide                   = $slide;
			$processed_slide['_bundled_media'] = array();

			$media_fields = array(
				'video'      => isset( $slide['video_url'] ) ? $slide['video_url'] : '',
				'video_webm' => isset( $slide['video_webm_url'] ) ? $slide['video_webm_url'] : '',
				'poster'     => isset( $slide['poster_url'] ) ? $slide['poster_url'] : '',
				'image'      => isset( $slide['image_url'] ) ? $slide['image_url'] : '',
				'logo'       => isset( $slide['logo_url'] ) ? $slide['logo_url'] : '',
			);

			foreach ( $media_keys as $field_key ) {
				$url = trim( (string) $media_fields[ $field_key ] );
				if ( '' === $url ) {
					continue;
				}

				// Root-relative URLs are made absolute so they can be resolved.
				if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
					$url = home_url( $url );
				}

				$clean_url = strtok( $url, '#' );
				if ( ! preg_match( '#^https?://#i', $clean_url ) ) {
					continue;
				}

				if ( isset( $media_map[ $clean_url ] ) ) {
					$processed_slide['_bundled_media'][ $field_key ] = $media_map[ $clean_url ];
					continue;
				}

				$source = $this->locate_local_media( $clean_url, $upload_dir );
				if ( null === $source ) {
					$source = $this->download_media( $clean_url );
					if ( null === $source ) {
						continue;
					}
					$temp_downloads[] = $source;
				}

				$filename          = $this->unique_media_name( $clean_url, $field_key, $slide_idx, $used_names );
				$zip_relative_path = 'media/' . $filename;

				if ( ! $zip->addFile( $source, $zip_relative_path ) ) {
					continue;
				}

				$media_map[ $clean_url ]                         = $zip_relative_path;
				$processed_slide['_bundled_media'][ $field_key ] = $zip_relative_path;
			}

			$processed_slides[] = $processed_slide;
		}

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

		// Downloaded temp files can only be removed after the archive is closed.
		foreach ( $temp_downloads as $tmp_file ) {
			@unlink( $tmp_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		}

		if ( ! file_exists( $temp_zip ) || 0 === filesize( $temp_zip ) ) {
			@unlink( $temp_zip ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			wp_die( esc_html__( 'Error: Zip export file could not be generated.', 'lumos-deepmind-slider' ) );
		}

		$download_name = 'lumos-slides-export-' . gmdate( 'Y-m-d-His' ) . '.zip';
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $download_name . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $temp_zip ) );
		readfile( $temp_zip ); // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		@unlink( $temp_zip ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		exit;
	}

	/**
	 * Find a media file that already lives on this site.
	 *
	 * @param string $url        Absolute media URL.
	 * @param array  $upload_dir wp_upload_dir() result.
	 * @return string|null Absolute local path or null.
	 */
	private function locate_local_media( $url, $upload_dir ) {
		$post_id = attachment_url_to_postid( $url );
		if ( $post_id ) {
			$attached = get_attached_file( $post_id );
			if ( $attached && is_file( $attached ) ) {
				return $attached;
			}
		}

		$path = (string) wp_parse_url( $url, PHP_URL_PATH );

		if ( 0 === strpos( $url, $upload_dir['baseurl'] ) ) {
			$candidate = $upload_dir['basedir'] . substr( rawurldecode( $path ), strlen( (string) wp_parse_url( $upload_dir['baseurl'], PHP_URL_PATH ) ) );
			if ( $this->is_inside( $candidate, $upload_dir['basedir'] ) ) {
				return $candidate;
			}
		}

		if ( 0 === strpos( $url, home_url() ) ) {
			$candidate = ABSPATH . ltrim( rawurldecode( substr( $path, strlen( (string) wp_parse_url( home_url(), PHP_URL_PATH ) ) ) ), '/' );
			if ( $this->is_inside( $candidate, ABSPATH ) ) {
				return $candidate;
			}
		}

		return null;
	}

	/**
	 * Whether a path resolves to a real file inside a base directory.
	 *
	 * @param string $path Candidate path.
	 * @param string $base Base directory.
	 * @return bool
	 */
	private function is_inside( $path, $base ) {
		$real_path = realpath( $path );
		$real_base = realpath( $base );
		return $real_path && $real_base && is_file( $real_path ) && 0 === strpos( $real_path, rtrim( $real_base, '/\\' ) . DIRECTORY_SEPARATOR );
	}

	/**
	 * Download a remote media file to a temp file (streamed, not held in memory).
	 *
	 * @param string $url Remote URL.
	 * @return string|null Temp file path or null on failure.
	 */
	private function download_media( $url ) {
		$tmp      = wp_tempnam( 'lumos-media' );
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'   => 60,
				'sslverify' => true,
				'stream'    => true,
				'filename'  => $tmp,
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) || ! file_exists( $tmp ) || 0 === filesize( $tmp ) || filesize( $tmp ) > wp_max_upload_size() ) {
			@unlink( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			return null;
		}

		return $tmp;
	}

	/**
	 * Build a unique, safe file name for a media entry inside the ZIP.
	 *
	 * @param string $url        Media URL.
	 * @param string $field_key  Field key (video, poster, ...).
	 * @param int    $slide_idx  Slide index.
	 * @param array  $used_names Names already used (lowercase keys), passed by reference.
	 * @return string
	 */
	private function unique_media_name( $url, $field_key, $slide_idx, &$used_names ) {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$name = $path ? sanitize_file_name( rawurldecode( basename( $path ) ) ) : '';

		if ( '' === $name || false === strpos( $name, '.' ) ) {
			if ( 'video_webm' === $field_key ) {
				$ext = '.webm';
			} elseif ( 'video' === $field_key ) {
				$ext = '.mp4';
			} else {
				$ext = '.jpg';
			}
			$name = 'media_' . $slide_idx . '_' . $field_key . $ext;
		}

		$candidate = $name;
		$counter   = 1;
		while ( isset( $used_names[ strtolower( $candidate ) ] ) ) {
			$candidate = $counter . '_' . $name;
			$counter++;
		}

		$used_names[ strtolower( $candidate ) ] = true;
		return $candidate;
	}

	/**
	 * AJAX: Import (restore) a backup and sideload its media into the Media Library.
	 */
	public function ajax_import_slides() {
		check_ajax_referer( 'lumos_slider_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'lumos-deepmind-slider' ) );
		}

		if ( empty( $_FILES['import_file'] ) || ! is_array( $_FILES['import_file'] ) ) {
			wp_send_json_error( __( 'Please select a valid export file (.zip or .json) to upload.', 'lumos-deepmind-slider' ) );
		}

		$file = $_FILES['import_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			wp_send_json_error( $this->upload_error_message( (int) $file['error'] ) );
		}

		$tmp_path = isset( $file['tmp_name'] ) ? $file['tmp_name'] : '';
		if ( '' === $tmp_path || ! is_uploaded_file( $tmp_path ) ) {
			wp_send_json_error( __( 'The uploaded file could not be read.', 'lumos-deepmind-slider' ) );
		}

		$file_name = sanitize_file_name( (string) $file['name'] );
		$file_ext  = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $file_ext, array( 'zip', 'json' ), true ) ) {
			wp_send_json_error( __( 'Invalid file format. Only .zip and .json backup files are supported.', 'lumos-deepmind-slider' ) );
		}

		$import_mode     = isset( $_POST['import_mode'] ) && 'append' === $_POST['import_mode'] ? 'append' : 'replace'; // phpcs:ignore WordPress.Security.NonceVerification
		$import_settings = ! empty( $_POST['import_settings'] ); // phpcs:ignore WordPress.Security.NonceVerification

		try {
			$result = $this->run_import( $tmp_path, $file_ext, $import_mode, $import_settings );
		} catch ( RuntimeException $e ) {
			wp_send_json_error( $e->getMessage() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Perform a backup import. Separated from the AJAX layer so it can be tested.
	 *
	 * @param string $tmp_path        Path to the uploaded (or test) backup file.
	 * @param string $file_ext        'zip' or 'json'.
	 * @param string $import_mode     'replace' or 'append'.
	 * @param bool   $import_settings Whether to restore the saved settings too.
	 * @return array Result summary.
	 * @throws RuntimeException When the backup is invalid. Nothing is changed in that case.
	 */
	public function run_import( $tmp_path, $file_ext, $import_mode, $import_settings ) {
		$package = 'json' === $file_ext ? $this->read_json_backup( $tmp_path ) : $this->open_zip_backup( $tmp_path );

		// Sideload media (ZIP only), then remap slide media URLs to the new Media Library files.
		$media_url_map        = array();
		$media_imported_count = 0;
		$media_skipped        = 0;
		if ( ! empty( $package['zip'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';

			$media_url_map        = $this->sideload_media( $package['zip'], $package['media'], $media_imported_count );
			$package['zip']->close();
		}

		$slides_data = array();
		foreach ( $package['slides'] as $raw_slide ) {
			if ( ! is_array( $raw_slide ) ) {
				continue;
			}
			if ( isset( $raw_slide['_bundled_media'] ) && is_array( $raw_slide['_bundled_media'] ) ) {
				foreach ( array( 'video' => 'video_url', 'video_webm' => 'video_webm_url', 'poster' => 'poster_url', 'image' => 'image_url', 'logo' => 'logo_url' ) as $key => $field ) {
					$bundled = isset( $raw_slide['_bundled_media'][ $key ] ) ? (string) $raw_slide['_bundled_media'][ $key ] : '';
					if ( '' === $bundled ) {
						continue;
					}
					if ( isset( $media_url_map[ $bundled ] ) ) {
						$raw_slide[ $field ] = $media_url_map[ $bundled ];
					} else {
						// The file was in the backup but could not be restored: never keep a URL
						// that points at the old site.
						$raw_slide[ $field ] = '';
						$media_skipped++;
					}
				}
			} else {
				foreach ( array( 'video_url', 'video_webm_url', 'poster_url', 'image_url', 'logo_url' ) as $field ) {
					if ( ! empty( $raw_slide[ $field ] ) ) {
						$base = basename( (string) wp_parse_url( $raw_slide[ $field ], PHP_URL_PATH ) );
						if ( isset( $media_url_map[ 'media/' . $base ] ) ) {
							$raw_slide[ $field ] = $media_url_map[ 'media/' . $base ];
						}
					}
				}
			}
			unset( $raw_slide['_bundled_media'] );
			$slides_data[] = $raw_slide;
		}

		// Sanitize everything before touching stored options.
		$sanitized_slides = array();
		foreach ( $slides_data as $raw_slide ) {
			$sanitized_slides[] = $this->sanitize_slide_data( $raw_slide );
		}

		if ( empty( $sanitized_slides ) ) {
			throw new RuntimeException( __( 'No valid slide records were found in the backup. Nothing was changed.', 'lumos-deepmind-slider' ) );
		}

		$existing_slides = get_option( 'lumos_slider_slides', Lumos_Slider_Defaults::get_default_slides() );
		if ( ! is_array( $existing_slides ) ) {
			$existing_slides = array();
		}

		// Give every imported slide a unique ID (append mode must not collide with existing IDs).
		$used_ids = array();
		if ( 'append' === $import_mode ) {
			foreach ( $existing_slides as $existing ) {
				$used_ids[ (string) $existing['id'] ] = true;
			}
		}
		$next_id = (int) round( microtime( true ) * 1000 );
		foreach ( $sanitized_slides as $key => $slide ) {
			while ( isset( $used_ids[ (string) $next_id ] ) ) {
				$next_id++;
			}
			$sanitized_slides[ $key ]['id'] = (string) $next_id;
			$used_ids[ (string) $next_id ]  = true;
			$next_id++;
		}

		$final_slides = 'append' === $import_mode ? array_merge( array_values( $existing_slides ), $sanitized_slides ) : $sanitized_slides;
		update_option( 'lumos_slider_slides', array_values( $final_slides ) );

		// Restore settings if requested and present.
		if ( $import_settings && ! empty( $package['settings'] ) ) {
			$settings_data  = $package['settings'];
			$defaults       = Lumos_Slider_Defaults::get_default_settings();
			$clean_settings = array(
				'caption'              => isset( $settings_data['caption'] ) ? sanitize_text_field( $settings_data['caption'] ) : $defaults['caption'],
				'autoplay'             => ! empty( $settings_data['autoplay'] ),
				'autoplay_speed'       => isset( $settings_data['autoplay_speed'] ) ? max( 1000, absint( $settings_data['autoplay_speed'] ) ) : $defaults['autoplay_speed'],
				'loop'                 => ! empty( $settings_data['loop'] ),
				'advance_on_video_end' => ! empty( $settings_data['advance_on_video_end'] ),
				'slide_width'          => isset( $settings_data['slide_width'] ) ? sanitize_text_field( $settings_data['slide_width'] ) : $defaults['slide_width'],
				'slide_height'         => isset( $settings_data['slide_height'] ) ? $this->sanitize_css_length( $settings_data['slide_height'], $defaults['slide_height'] ) : $defaults['slide_height'],
				'theme'                => isset( $settings_data['theme'] ) && in_array( $settings_data['theme'], array( 'dark', 'light' ), true ) ? $settings_data['theme'] : $defaults['theme'],
			);
			update_option( 'lumos_slider_settings', $clean_settings );
		}

		return array(
			'message'      => sprintf(
				/* translators: 1: number of slides, 2: number of media files */
				__( 'Success! Imported %1$d slide(s) and %2$d media file(s).', 'lumos-deepmind-slider' ),
				count( $sanitized_slides ),
				$media_imported_count
			) . ( $media_skipped > 0 ? ' ' . sprintf(
				/* translators: %d: number of media references that could not be restored */
				__( '%d media reference(s) could not be restored and were cleared.', 'lumos-deepmind-slider' ),
				$media_skipped
			) : '' ),
			'slides_count' => count( $sanitized_slides ),
			'media_count'  => $media_imported_count,
			'media_skipped' => $media_skipped,
		);
	}

	/**
	 * Read and validate a JSON backup.
	 *
	 * @param string $path Uploaded temp path.
	 * @return array{slides: array, settings: array|null, zip: null, media: array}
	 * @throws RuntimeException On invalid input.
	 */
	private function read_json_backup( $path ) {
		if ( filesize( $path ) > 10 * 1024 * 1024 ) {
			throw new RuntimeException( __( 'The JSON backup is too large (max 10 MB).', 'lumos-deepmind-slider' ) );
		}

		$decoded = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return $this->validate_backup_manifest( $decoded );
	}

	/**
	 * Open a ZIP backup WITHOUT extracting it to disk, and validate slides.json.
	 *
	 * @param string $path Uploaded temp path.
	 * @return array{slides: array, settings: array|null, zip: ZipArchive, media: array}
	 * @throws RuntimeException On invalid input.
	 */
	private function open_zip_backup( $path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			throw new RuntimeException( __( 'ZipArchive PHP extension is not available on this server.', 'lumos-deepmind-slider' ) );
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $path ) ) {
			throw new RuntimeException( __( 'Failed to open the uploaded ZIP archive.', 'lumos-deepmind-slider' ) );
		}

		if ( $zip->numFiles > 2000 ) {
			$zip->close();
			throw new RuntimeException( __( 'The ZIP archive contains too many files.', 'lumos-deepmind-slider' ) );
		}

		$max_size   = wp_max_upload_size();
		$json_index = null;
		$media      = array();

		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$stat = $zip->statIndex( $i );
			if ( ! $stat ) {
				continue;
			}

			$name = str_replace( '\\', '/', (string) $stat['name'] );
			if ( $this->is_unsafe_zip_path( $name ) ) {
				$zip->close();
				throw new RuntimeException( __( 'The archive contains an unsafe file path. Import was cancelled.', 'lumos-deepmind-slider' ) );
			}

			if ( '/' === substr( $name, -1 ) ) {
				continue;
			}

			if ( 'slides.json' === $name ) {
				$json_index = $i;
			} elseif ( null === $json_index && preg_match( '#(^|/)slides\.json$#', $name ) ) {
				$json_index = $i;
			} elseif ( preg_match( '#(^|/)media/([^/]+)$#', $name, $m ) ) {
				if ( (int) $stat['size'] > $max_size ) {
					continue;
				}
				$media[ 'media/' . $m[2] ] = $i;
			}
		}

		if ( null === $json_index ) {
			$zip->close();
			throw new RuntimeException( __( 'Invalid backup: slides.json was not found in the ZIP archive.', 'lumos-deepmind-slider' ) );
		}

		$stat = $zip->statIndex( $json_index );
		if ( ! $stat || (int) $stat['size'] > 10 * 1024 * 1024 ) {
			$zip->close();
			throw new RuntimeException( __( 'slides.json is too large or unreadable.', 'lumos-deepmind-slider' ) );
		}

		$decoded = json_decode( (string) $zip->getFromIndex( $json_index ), true );
		try {
			$manifest = $this->validate_backup_manifest( $decoded );
		} catch ( RuntimeException $e ) {
			$zip->close();
			throw $e;
		}

		$manifest['zip']   = $zip;
		$manifest['media'] = $media;
		return $manifest;
	}

	/**
	 * Validate the decoded backup manifest.
	 *
	 * @param mixed $decoded Decoded JSON.
	 * @return array{slides: array, settings: array|null, zip: null, media: array}
	 * @throws RuntimeException When no slides are present.
	 */
	private function validate_backup_manifest( $decoded ) {
		if ( ! is_array( $decoded ) || empty( $decoded['slides'] ) || ! is_array( $decoded['slides'] ) ) {
			throw new RuntimeException( __( 'Invalid backup: no slide data found.', 'lumos-deepmind-slider' ) );
		}

		return array(
			'slides'   => array_values( $decoded['slides'] ),
			'settings' => isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ? $decoded['settings'] : null,
			'zip'      => null,
			'media'    => array(),
		);
	}

	/**
	 * Reject absolute paths and any path traversal inside a ZIP entry name.
	 *
	 * @param string $name Entry name.
	 * @return bool
	 */
	private function is_unsafe_zip_path( $name ) {
		if ( '' === $name || false !== strpos( $name, "\0" ) || '/' === $name[0] || false !== strpos( $name, ':' ) ) {
			return true;
		}
		foreach ( explode( '/', $name ) as $segment ) {
			if ( '..' === $segment ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Sideload media files from an open backup ZIP into the Media Library.
	 * Files already imported earlier (same content hash) are reused, not duplicated.
	 *
	 * @param ZipArchive $zip             Open archive.
	 * @param array      $media           Map of 'media/<name>' => entry index.
	 * @param int        $imported_count  Incremented for each media file made available (by reference).
	 * @return array Map of 'media/<name>' => new public URL.
	 */
	private function sideload_media( $zip, $media, &$imported_count ) {
		$url_map = array();

		$allow_svg = function ( $mimes ) {
			$mimes['svg'] = 'image/svg+xml';
			return $mimes;
		};
		add_filter( 'upload_mimes', $allow_svg );

		foreach ( $media as $entry_name => $index ) {
			$contents = $zip->getFromIndex( $index );
			if ( false === $contents || '' === $contents ) {
				continue;
			}

			$file_name = sanitize_file_name( basename( $entry_name ) );
			$ext       = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
			if ( '' === $file_name || '' === $ext ) {
				continue;
			}

			// SVG is only accepted when it contains no active content.
			if ( 'svg' === $ext && ! $this->is_safe_svg( $contents ) ) {
				continue;
			}

			$hash     = sha1( $contents );
			$existing = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => '_lumos_import_sha1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => $hash, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'no_found_rows'  => true,
				)
			);

			if ( ! empty( $existing ) ) {
				$url = wp_get_attachment_url( (int) $existing[0] );
				if ( $url ) {
					$url_map[ $entry_name ] = $url;
					$imported_count++;
				}
				continue;
			}

			$upload = wp_upload_bits( $file_name, null, $contents );
			if ( ! empty( $upload['error'] ) ) {
				continue;
			}

			$wp_filetype = wp_check_filetype( $upload['file'] );
			$attach_id   = wp_insert_attachment(
				array(
					'post_mime_type' => $wp_filetype['type'] ? $wp_filetype['type'] : 'application/octet-stream',
					'post_title'     => sanitize_text_field( pathinfo( $file_name, PATHINFO_FILENAME ) ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				),
				$upload['file']
			);

			if ( ! is_wp_error( $attach_id ) && $attach_id > 0 ) {
				wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $upload['file'] ) );
				update_post_meta( $attach_id, '_lumos_import_sha1', $hash );
			}

			$url_map[ $entry_name ] = $upload['url'];
			$imported_count++;
		}

		remove_filter( 'upload_mimes', $allow_svg );

		return $url_map;
	}

	/**
	 * Basic SVG safety check: no scripts, event handlers, foreign content or external references.
	 *
	 * @param string $svg SVG markup.
	 * @return bool
	 */
	private function is_safe_svg( $svg ) {
		if ( false === strpos( $svg, '<svg' ) ) {
			return false;
		}
		return ! preg_match( '/<\s*(script|foreignobject|iframe|embed|object|use)\b|\son[a-z]+\s*=|javascript:|data:text\/html/i', $svg );
	}

	/**
	 * Human-readable message for an upload error code.
	 *
	 * @param int $code PHP upload error code.
	 * @return string
	 */
	private function upload_error_message( $code ) {
		switch ( $code ) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return __( 'The backup file is larger than the server upload limit.', 'lumos-deepmind-slider' );
			case UPLOAD_ERR_PARTIAL:
				return __( 'The upload was interrupted. Please try again.', 'lumos-deepmind-slider' );
			case UPLOAD_ERR_NO_FILE:
				return __( 'Please select a valid export file (.zip or .json) to upload.', 'lumos-deepmind-slider' );
			default:
				return __( 'The server could not receive the uploaded file.', 'lumos-deepmind-slider' );
		}
	}
}
