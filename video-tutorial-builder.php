<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.1.0
 * @package           VTB
 *
 * @wordpress-plugin
 * Plugin Name:       Video Tutorial Builder
 * Plugin URI:        https://github.com/richardblythe/video-tutorial-builder/
 * GitHub Plugin URI: https://github.com/richardblythe/video-tutorial-builder
 * Description:       Create a customized video tutorial collection for your wordpress admin.
 * Version:           1.1.0
 * Author:            Richard Blythe
 * Author URI:        http://unity3software.com/richardblythe/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       vtb
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-vtb-activator.php
 */
function activate_vtb() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-vtb-activator.php';
	VTB_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-vtb-deactivator.php
 */
function deactivate_vtb() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-vtb-deactivator.php';
	VTB_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_vtb' );
register_deactivation_hook( __FILE__, 'deactivate_vtb' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-vtb.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_vtb() {

	define( 'VTB_DIR',  untrailingslashit( plugin_dir_path( __FILE__ ) ) );
	define( 'VTB_URL',  plugin_dir_url( __FILE__ ) );

	$plugin = new VTB();
	$plugin->run();

}

add_action( 'init', 'run_vtb', 99, 0 );