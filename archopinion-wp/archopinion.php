<?php
/**
 * Plugin Name: ArchOpinion
 * Description: A WordPress plugin to analyze architectural designs using Gemini.
 * Version: 1.0.0
 * Author: Your Name
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'ARCHOPINION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once ARCHOPINION_PLUGIN_DIR . 'includes/class-archopinion.php';

ArchOpinion::get_instance();
