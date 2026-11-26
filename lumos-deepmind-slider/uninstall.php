<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 * Removes the plugin's stored settings and slides. Media Library files are kept.
 *
 * @package LumosDeepMindSlider
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'lumos_slider_slides' );
delete_option( 'lumos_slider_settings' );
