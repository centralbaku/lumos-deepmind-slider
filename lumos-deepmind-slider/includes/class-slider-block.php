<?php
/**
 * Gutenberg Block Registration
 *
 * @package LumosDeepMindSlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lumos_Slider_Block {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register Gutenberg block
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'lumos-slider-block',
			LUMOS_SLIDER_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			LUMOS_SLIDER_VERSION,
			true
		);

		register_block_type(
			'lumos/deepmind-slider',
			array(
				'editor_script'   => 'lumos-slider-block',
				'render_callback' => array( $this, 'render_block' ),
				'attributes'      => array(
					'caption'        => array(
						'type'    => 'string',
						'default' => 'Explore the latest',
					),
					'autoplay'       => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'autoplay_speed' => array(
						'type'    => 'number',
						'default' => 6000,
					),
					'loop'           => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);
	}

	/**
	 * Server-side render callback for Gutenberg block
	 *
	 * @param array $attributes Block attributes.
	 * @return string HTML output.
	 */
	public function render_block( $attributes ) {
		$shortcode_handler = Lumos_Slider_Shortcode::instance();
		return $shortcode_handler->render_shortcode( $attributes );
	}
}
