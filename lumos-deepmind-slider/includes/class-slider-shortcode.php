<?php
/**
 * Slider Shortcode and HTML Renderer
 *
 * @package LumosDeepMindSlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lumos_Slider_Shortcode {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_shortcode( 'deepmind_slider', array( $this, 'render_shortcode' ) );
		add_shortcode( 'lumos_slider', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shortcode( $atts ) {
		$settings = get_option( 'lumos_slider_settings', Lumos_Slider_Defaults::get_default_settings() );
		$slides   = get_option( 'lumos_slider_slides', Lumos_Slider_Defaults::get_default_slides() );

		if ( empty( $slides ) || ! is_array( $slides ) ) {
			return '';
		}

		// Filter active slides (user can turn on and off slides)
		$slides = array_values(
			array_filter(
				$slides,
				function( $slide ) {
					return ! isset( $slide['active'] ) || true === $slide['active'] || '1' === $slide['active'] || 1 === $slide['active'];
				}
			)
		);

		if ( empty( $slides ) ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'id'             => 'deepmind-' . wp_rand( 1000, 9999 ),
				'caption'        => isset( $settings['caption'] ) ? $settings['caption'] : 'Explore the latest',
				'autoplay'       => isset( $settings['autoplay'] ) ? ( $settings['autoplay'] ? 'true' : 'false' ) : 'false',
				'autoplay_speed' => isset( $settings['autoplay_speed'] ) ? $settings['autoplay_speed'] : 6000,
				'loop'                 => isset( $settings['loop'] ) ? ( $settings['loop'] ? 'true' : 'false' ) : 'true',
				'advance_on_video_end' => isset( $settings['advance_on_video_end'] ) ? ( $settings['advance_on_video_end'] ? 'true' : 'false' ) : 'true',
				'height'               => isset( $settings['slide_height'] ) ? $settings['slide_height'] : '',
				'theme'                => isset( $settings['theme'] ) ? $settings['theme'] : 'dark',
			),
			$atts,
			'deepmind_slider'
		);

		// Enqueue scripts and styles
		wp_enqueue_style( 'lumos-deepmind-slider' );
		wp_enqueue_script( 'lumos-deepmind-slider' );

		ob_start();
		$this->render_slider_html( $slides, $atts );
		return ob_get_clean();
	}

	/**
	 * Render HTML output
	 *
	 * @param array $slides Array of slide items.
	 * @param array $config Configuration parameters.
	 */
	public function render_slider_html( $slides, $config ) {
		$unique_id            = esc_attr( $config['id'] );
		$caption              = $config['caption'];
		$loop                 = 'true' === $config['loop'] || true === $config['loop'];
		$raw_speed            = isset( $config['autoplay_speed'] ) ? floatval( $config['autoplay_speed'] ) : 6000;
		$autoplay_speed       = ( $raw_speed > 0 && $raw_speed < 100 ) ? intval( $raw_speed * 1000 ) : intval( $raw_speed );
		$advance_on_video_end = 'false' !== $config['advance_on_video_end'] && false !== $config['advance_on_video_end'];
		$custom_height        = ! empty( $config['height'] ) ? 'style="height:' . esc_attr( $config['height'] ) . ';"' : '';
		$total_slides         = count( $slides );
		?>
		<section class="section--carousel section-default section--no-header spacer-block-md-bottom section-padding-top" id="cover-<?php echo esc_attr( $unique_id ); ?>">
			<div class="section-container">
				<div class="w-block-cover_carousel block-cover_carousel">
					<section class="carousel-block carousel js-carousel spacer-block-md-bottom carousel--full-width <?php echo 'dark' === $config['theme'] ? 'deepai--dark' : 'deepai--light'; ?>"
							aria-label="<?php esc_attr_e( 'Carousel', 'lumos-deepmind-slider' ); ?>"
							aria-roledescription="carousel"
							data-event-module-name="Carousel"
							id="carousel-<?php echo esc_attr( $unique_id ); ?>"
							data-loop="<?php echo $loop ? 'true' : 'false'; ?>"
							data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>"
							data-autoplay-speed="<?php echo esc_attr( $autoplay_speed ); ?>"
							data-advance-on-video-end="<?php echo $advance_on_video_end ? 'true' : 'false'; ?>"
							role="region">

						<div aria-atomic="true" aria-live="polite" class="sr-only" id="carousel-announcer-<?php echo esc_attr( $unique_id ); ?>">
							<?php printf( esc_html__( 'Slide 1 of %d', 'lumos-deepmind-slider' ), $total_slides ); ?>
						</div>

						<div class="embla is-loop">
							<div class="embla__container">
								<?php
								foreach ( $slides as $index => $slide ) :
									$is_first      = 0 === $index;
									$slide_class   = $is_first ? 'active-slide is-in-view' : 'embla__slide--peeked';
									$layout        = isset( $slide['layout'] ) ? $slide['layout'] : 'top-center';
									$is_top_center = 'top-center' === $layout;
									$title_tag     = isset( $slide['title_tag'] ) && in_array( $slide['title_tag'], array( 'h1', 'h2', 'h3' ), true ) ? $slide['title_tag'] : ( $is_top_center ? 'h1' : 'h2' );
									$scrim_type    = isset( $slide['scrim_type'] ) ? $slide['scrim_type'] : 'scrim-dark';
									$media_type    = isset( $slide['media_type'] ) ? $slide['media_type'] : 'video';
									?>
									<div class="w-block-cover block-cover <?php echo esc_attr( $slide_class ); ?>" data-slide-index="<?php echo esc_attr( $index ); ?>">
										<section class="section-cover <?php echo $is_top_center ? 'section-cover--top' : ''; ?> spacer-md-bottom" <?php echo $custom_height; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
											<div class="section-cover--full-page section-cover--<?php echo esc_attr( $scrim_type ); ?> section-cover--text-color-light <?php echo ! $is_top_center ? 'section-cover--full-page-small-text' : ''; ?>">
												<div class="section-default cover grid <?php echo $is_top_center ? 'cover--headline-size-medium' : 'cover--headline-size-small'; ?> cover--eyebrow-size-medium">
													
													<!-- Content Layer -->
													<div class="cover__content grid__column--span-8-md grid__column--offset-2-md grid__column--span-12-sm grid__column--span-12-xs">
														<?php
														$logo_enabled  = ! empty( $slide['logo_enable'] ) && ! empty( $slide['logo_url'] );
														$logo_position = isset( $slide['logo_position'] ) && 'bottom' === $slide['logo_position'] ? 'bottom' : 'top';
														$logo_align    = $is_top_center ? 'cover__logo--center' : 'cover__logo--left';
														?>

														<?php if ( $logo_enabled && 'top' === $logo_position ) : ?>
															<div class="cover__logo cover__logo--top <?php echo esc_attr( $logo_align ); ?>">
																<img src="<?php echo esc_url( $slide['logo_url'] ); ?>" alt="<?php echo esc_attr( ! empty( $slide['logo_alt'] ) ? $slide['logo_alt'] : ( isset( $slide['title'] ) ? $slide['title'] : 'Logo' ) ); ?>" class="slide-logo-img">
															</div>
														<?php endif; ?>

														<div class="cover__text">
															<<?php echo esc_attr( $title_tag ); ?> class="cover__text--title <?php echo $is_top_center ? 'cover__text--title--top' : ''; ?>">
																<?php echo esc_html( isset( $slide['title'] ) ? $slide['title'] : '' ); ?>
															</<?php echo esc_attr( $title_tag ); ?>>
														</div>

														<?php if ( ! empty( $slide['description'] ) ) : ?>
														<div class="cover__text--description heading-9">
															<p><?php echo esc_html( $slide['description'] ); ?></p>
														</div>
														<?php endif; ?>

														<?php
														$btn1_enabled = ! empty( $slide['button_1_enable'] ) && ! empty( $slide['button_1_text'] );
														$btn2_enabled = ! empty( $slide['button_2_enable'] ) && ! empty( $slide['button_2_text'] );

														if ( $btn1_enabled || $btn2_enabled ) :
															$btn_group_class = $is_top_center ? 'button-group--row_centered' : 'button-group--row button-group--compact compact';
															$wrapper_class   = $is_top_center ? 'cover__buttons spacer-2xl-top deepai--dark cover__buttons--bottom' : 'cover__buttons spacer-2xl-top deepai--dark';
															?>
															<div class="<?php echo esc_attr( $wrapper_class ); ?>">
																<div class="button-group <?php echo esc_attr( $btn_group_class ); ?>">
																	<div class="button-group__buttons">
																		<?php if ( $btn1_enabled ) : ?>
																			<?php $this->render_button( $slide, 1 ); ?>
																		<?php endif; ?>

																		<?php if ( $btn2_enabled ) : ?>
																			<?php $this->render_button( $slide, 2 ); ?>
																		<?php endif; ?>
																	</div>
																</div>
															</div>
														<?php endif; ?>

														<?php if ( $logo_enabled && 'bottom' === $logo_position ) : ?>
															<div class="cover__logo cover__logo--bottom <?php echo esc_attr( $logo_align ); ?>">
																<img src="<?php echo esc_url( $slide['logo_url'] ); ?>" alt="<?php echo esc_attr( ! empty( $slide['logo_alt'] ) ? $slide['logo_alt'] : ( isset( $slide['title'] ) ? $slide['title'] : 'Logo' ) ); ?>" class="slide-logo-img">
															</div>
														<?php endif; ?>
													</div>

													<!-- Media Background Layer -->
													<div class="cover__media">
														<div class="media-content-wrapper">
															<div class="media__container has-default-media spacer-block-none-bottom">
																<figure class="media-video-figure">
																	<div class="media-video-container has-default-media media--aspect-16-9 --scrim" data-height="1080" data-width="1920">
																		<?php if ( 'video' === $media_type && ( ! empty( $slide['video_url'] ) || ! empty( $slide['video_webm_url'] ) ) ) : ?>
																			<div class="video-wrapper">
																				<?php if ( ! empty( $slide['poster_url'] ) ) : ?>
																				<div class="media-video-poster">
																					<div class="media__container has-default-media">
																						<picture class="image --scrim media--aspect-16-9">
																							<img src="<?php echo esc_url( $slide['poster_url'] ); ?>"
																								alt="<?php echo esc_attr( isset( $slide['title'] ) ? $slide['title'] : '' ); ?>"
																								class="picture__image"
																								height="810"
																								loading="lazy"
																								width="1440">
																						</picture>
																					</div>
																				</div>
																				<?php endif; ?>

																				<video class="media-video media-video--custom"
																					autoplay
																					loop
																					muted
																					playsinline
																					preload="auto"
																					<?php if ( ! empty( $slide['poster_url'] ) ) : ?>
																					poster="<?php echo esc_url( $slide['poster_url'] ); ?>"
																					<?php endif; ?>>
																					<?php if ( ! empty( $slide['video_webm_url'] ) ) : ?>
																						<source type="video/webm" src="<?php echo esc_url( $slide['video_webm_url'] ); ?>">
																					<?php endif; ?>
																					<?php if ( ! empty( $slide['video_url'] ) ) : ?>
																						<source type="video/mp4" src="<?php echo esc_url( $slide['video_url'] ); ?>">
																					<?php endif; ?>
																					<?php esc_html_e( 'Your browser does not support the video tag.', 'lumos-deepmind-slider' ); ?>
																				</video>
																			</div>
																		<?php elseif ( ! empty( $slide['image_url'] ) ) : ?>
																			<picture class="image --scrim media--aspect-16-9">
																				<img src="<?php echo esc_url( $slide['image_url'] ); ?>"
																					alt="<?php echo esc_attr( isset( $slide['title'] ) ? $slide['title'] : '' ); ?>"
																					class="picture__image"
																					loading="lazy">
																			</picture>
																		<?php endif; ?>
																	</div>
																</figure>
															</div>
														</div>
													</div>

												</div>
											</div>
										</section>
									</div>
								<?php endforeach; ?>
							</div>
						</div>

						<!-- Pagination / Controls -->
						<div class="carousel__pagination">
							<?php if ( ! empty( $caption ) ) : ?>
							<figcaption class="caption carousel__caption">
								<div class="caption__text text-caption caption--fill"><?php echo esc_html( $caption ); ?></div>
							</figcaption>
							<?php else : ?>
							<div class="caption-spacer"></div>
							<?php endif; ?>

							<div>
								<div class="combo-button-group">
									<button aria-controls="carousel-<?php echo esc_attr( $unique_id ); ?>"
											aria-label="<?php esc_attr_e( 'Previous', 'lumos-deepmind-slider' ); ?>"
											class="js-carousel__button--prev">
										<span aria-hidden="true" class="icon-md-fill">
											<svg viewBox="0 -960 960 960" aria-hidden="true" fill="currentColor" height="24px" role="presentation" width="24px" xmlns="http://www.w3.org/2000/svg">
												<path d="M560.62-253.85 333.85-480.62l226.77-227.15 32.61 32.62-194.15 194.53 194.15 194.16-32.61 32.61Z"></path>
											</svg>
										</span>
									</button>
									<button aria-controls="carousel-<?php echo esc_attr( $unique_id ); ?>"
											aria-label="<?php esc_attr_e( 'Next', 'lumos-deepmind-slider' ); ?>"
											class="js-carousel__button--next">
										<span aria-hidden="true" class="icon-md-fill">
											<svg viewBox="0 -960 960 960" aria-hidden="true" fill="currentColor" height="24px" role="presentation" width="24px" xmlns="http://www.w3.org/2000/svg">
												<path d="M536.92-480.62 342.77-675.15l32.61-32.62 226.77 227.15-226.77 226.77-32.61-32.61 194.15-194.16Z"></path>
											</svg>
										</span>
									</button>
								</div>
							</div>
						</div>

					</section>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Helper to render individual button
	 *
	 * @param array $slide Slide data.
	 * @param int   $btn_num 1 or 2.
	 */
	private function render_button( $slide, $btn_num ) {
		$prefix = "button_{$btn_num}_";
		$text   = isset( $slide[ $prefix . 'text' ] ) ? $slide[ $prefix . 'text' ] : '';
		$url    = isset( $slide[ $prefix . 'url' ] ) ? $slide[ $prefix . 'url' ] : '#';
		$target = isset( $slide[ $prefix . 'target' ] ) ? $slide[ $prefix . 'target' ] : '_self';
		$style  = isset( $slide[ $prefix . 'style' ] ) ? $slide[ $prefix . 'style' ] : ( 1 === $btn_num ? 'primary' : 'protected' );
		$icon   = isset( $slide[ $prefix . 'icon' ] ) ? $slide[ $prefix . 'icon' ] : 'none';

		$btn_classes = array( 'button' );
		if ( 'primary' === $style ) {
			$btn_classes[] = 'button--primary';
		} elseif ( 'protected' === $style ) {
			$btn_classes[] = 'button--protected';
		} elseif ( 'text' === $style ) {
			$btn_classes[] = 'button--text';
		}

		$has_left_icon  = in_array( $icon, array( 'ai_studio', 'sparkle' ), true );
		$has_right_icon = in_array( $icon, array( 'arrow_right', 'external' ), true );

		if ( $has_left_icon ) {
			$btn_classes[] = 'icon-left';
		} elseif ( $has_right_icon ) {
			$btn_classes[] = 'icon-right';
		} else {
			$btn_classes[] = 'icon-none';
		}

		$href_trimmed = trim( $url );
		$href_attr    = ( 0 === strpos( $href_trimmed, '#' ) || 0 === strpos( $href_trimmed, '/' ) ) ? esc_attr( $href_trimmed ) : esc_url( $href_trimmed );
		?>
		<a class="<?php echo esc_attr( implode( ' ', $btn_classes ) ); ?>"
		   href="<?php echo $href_attr; ?>"
		   target="<?php echo esc_attr( $target ); ?>"
		   <?php if ( '_blank' === $target ) : ?>rel="noopener noreferrer"<?php endif; ?>>
			<?php if ( $has_left_icon ) : ?>
				<div class="button__icon">
					<?php $this->render_icon_svg( $icon ); ?>
				</div>
			<?php endif; ?>

			<div class="button__label"><?php echo esc_html( $text ); ?></div>

			<?php if ( $has_right_icon ) : ?>
				<div class="button__icon">
					<?php $this->render_icon_svg( $icon ); ?>
				</div>
			<?php endif; ?>
		</a>
		<?php
	}

	/**
	 * Render SVG for supported button icons
	 *
	 * @param string $icon Icon key.
	 */
	private function render_icon_svg( $icon ) {
		if ( 'ai_studio' === $icon || 'sparkle' === $icon ) {
			?>
			<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
				<path d="M16 11H7.5c-1.93 0-3.5-1.57-3.5-3.5S5.57 4 7.5 4H16c1.93 0 3.5 1.57 3.5 3.5S17.93 11 16 11zm-8 9h8.5c1.93 0 3.5-1.57 3.5-3.5S18.43 13 16.5 13H8c-1.93 0-3.5 1.57-3.5 3.5S6.07 20 8 20z"/>
			</svg>
			<?php
		} elseif ( 'arrow_right' === $icon ) {
			?>
			<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
				<path d="M8.59 16.59L13.17 12L8.59 7.41L10 6L16 12L10 18L8.59 16.59Z"/>
			</svg>
			<?php
		} elseif ( 'external' === $icon ) {
			?>
			<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
				<path d="M19 19H5V5h7V3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
			</svg>
			<?php
		}
	}
}
