<?php
/**
 * Default sample slides and default options
 *
 * @package LumosDeepMindSlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lumos_Slider_Defaults {

	/**
	 * Get default plugin settings
	 *
	 * @return array
	 */
	public static function get_default_settings() {
		return array(
			'caption'              => __( 'Explore the latest', 'lumos-deepmind-slider' ),
			'autoplay'             => false,
			'autoplay_speed'       => 6000,
			'loop'                 => true,
			'advance_on_video_end' => true,
			'slide_width'          => '93.5%',
			'slide_height'         => 'clamp(520px, 82svh, 800px)',
			'theme'                => 'dark',
		);
	}

	/**
	 * Get default demo slides matching Google DeepMind Gemini Robotics
	 *
	 * @return array
	 */
	public static function get_default_slides() {
		return array(
			array(
				'id'              => 1,
				'active'          => true,
				'title'           => 'Gemini Robotics 2',
				'title_tag'       => 'h1',
				'layout'          => 'top-center',
				'description'     => 'The intelligence layer to power any kind of robot',
				'media_type'      => 'video',
				'video_url'       => 'https://storage.googleapis.com/gdm-deepmind-com-prod-public/media/rC8sZkug1fhkL2as/gemini-robotics__page-cover__hero__20.webm#t=0.1',
				'video_webm_url'  => '',
				'poster_url'      => 'https://lh3.googleusercontent.com/fOGx4E2i7mgcHrRbNvVYjkfQIrU1Wy9_HHmHeTDTPY9xZ_4BWdhuoEsHiV1HvQQD42qeP9o8whhoAxmqZNDVUBBsJ7zewDsEwk7S3Stl_CEKSR7DOg=w1440-h810-n-nu',
				'image_url'       => '',
				'scrim_type'      => 'scrim-dark',
				'scrim_opacity'   => '0.45',
				'button_1_enable' => true,
				'button_1_text'   => 'Try Gemini Robotics ER 2',
				'button_1_url'    => 'https://aistudio.google.com/prompts/new_chat?model=gemini-robotics-er-2-preview',
				'button_1_target' => '_blank',
				'button_1_style'  => 'primary',
				'button_1_icon'   => 'ai_studio',
				'button_2_enable' => false,
				'button_2_text'   => '',
				'button_2_url'    => '',
				'button_2_target' => '_self',
				'button_2_style'  => 'protected',
				'button_2_icon'   => 'none',
				'logo_enable'     => false,
				'logo_url'        => '',
				'logo_position'   => 'top',
				'logo_alt'        => '',
			),
			array(
				'id'              => 2,
				'active'          => true,
				'title'           => 'Gemini Robotics 2',
				'title_tag'       => 'h2',
				'layout'          => 'top-left',
				'description'     => 'Our most advanced VLA model: capable of intelligently controlling any type of robot, from bi-arms to full humanoids',
				'media_type'      => 'video',
				'video_url'       => 'https://storage.googleapis.com/gdm-deepmind-com-prod-public/media/MVCfTE-4h3AwDVPZ/gemini-robotics__page-cover__vla.webm#t=0.1',
				'video_webm_url'  => '',
				'poster_url'      => 'https://lh3.googleusercontent.com/mWKuxNu0Ecmt2mgrcNrbL6U5vNI9-H87ra9VUMd_baMkwnXy6L5Fwxu-1RtXhZ8cnpMXWeWSMUNMtwtMSMMqEAQ70nJ5G_EGRnirjEP6Dg58xQO5BA=w1440-h810-n-nu',
				'image_url'       => '',
				'scrim_type'      => 'scrim-dark',
				'scrim_opacity'   => '0.45',
				'button_1_enable' => true,
				'button_1_text'   => 'Learn more',
				'button_1_url'    => 'https://deepmind.google/models/gemini-robotics/vla/',
				'button_1_target' => '_blank',
				'button_1_style'  => 'protected',
				'button_1_icon'   => 'none',
				'button_2_enable' => false,
				'button_2_text'   => '',
				'button_2_url'    => '',
				'button_2_target' => '_self',
				'button_2_style'  => 'text',
				'button_2_icon'   => 'none',
				'logo_enable'     => false,
				'logo_url'        => '',
				'logo_position'   => 'top',
				'logo_alt'        => '',
			),
			array(
				'id'              => 3,
				'active'          => true,
				'title'           => 'Gemini Robotics ER 2',
				'title_tag'       => 'h2',
				'layout'          => 'top-left',
				'description'     => 'Our embodied reasoning model: capable of real-world understanding and complex, multi-step planning',
				'media_type'      => 'video',
				'video_url'       => 'https://storage.googleapis.com/gdm-deepmind-com-prod-public/media/rC8sZkug1fhkL2as/gemini-robotics__page-cover__er.webm#t=0.1',
				'video_webm_url'  => '',
				'poster_url'      => 'https://lh3.googleusercontent.com/9zg2J_n0cld-LnixaQiHRjA8bORzbUjRFXi3lSx_akPTXqgF6PR1vX2935QjiTC48HP7Zc62zES-9y6sZjTEGdRrxMm6iJIWjFK4y_5yl8u1ABuG=w1440-h810-n-nu',
				'image_url'       => '',
				'scrim_type'      => 'scrim-dark',
				'scrim_opacity'   => '0.45',
				'button_1_enable' => true,
				'button_1_text'   => 'Learn more',
				'button_1_url'    => 'https://deepmind.google/models/gemini-robotics/embodied-reasoning/',
				'button_1_target' => '_blank',
				'button_1_style'  => 'protected',
				'button_1_icon'   => 'none',
				'button_2_enable' => true,
				'button_2_text'   => 'Try Gemini Robotics ER 2',
				'button_2_url'    => 'https://aistudio.google.com/prompts/new_chat?model=gemini-robotics-er-2-preview',
				'button_2_target' => '_blank',
				'button_2_style'  => 'text',
				'button_2_icon'   => 'arrow_right',
				'logo_enable'     => false,
				'logo_url'        => '',
				'logo_position'   => 'top',
				'logo_alt'        => '',
			),
			array(
				'id'              => 4,
				'active'          => true,
				'title'           => 'Gemini Robotics On-Device 2',
				'title_tag'       => 'h2',
				'layout'          => 'top-left',
				'description'     => 'Our most efficient VLA model, optimized to run locally on robotic devices',
				'media_type'      => 'image',
				'video_url'       => '',
				'video_webm_url'  => '',
				'poster_url'      => '',
				'image_url'       => 'https://lh3.googleusercontent.com/1OBfM3QyH93IeOIbVqsGjHNj8AZyOlJti8lZqIiTb4hrbN4-t_EYHeaVBhaGqAWfqs3WwZlvTzE4VNxdLNSb_69GopywJb_OdsPjX4kNVSzRxAUqTGM=w1920-h1080-n-nu',
				'scrim_type'      => 'scrim-dark',
				'scrim_opacity'   => '0.45',
				'button_1_enable' => true,
				'button_1_text'   => 'Learn more',
				'button_1_url'    => 'https://deepmind.google/models/gemini-robotics/on-device/',
				'button_1_target' => '_blank',
				'button_1_style'  => 'protected',
				'button_1_icon'   => 'none',
				'button_2_enable' => false,
				'button_2_text'   => '',
				'button_2_url'    => '',
				'button_2_target' => '_self',
				'button_2_style'  => 'text',
				'button_2_icon'   => 'none',
				'logo_enable'     => true,
				'logo_url'        => LUMOS_SLIDER_URL . 'assets/images/deepmind-sparkle.svg',
				'logo_position'   => 'top',
				'logo_alt'        => 'Gemini Sparkle',
			),
		);
	}
}
