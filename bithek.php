<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/sboo
 * @since             1.0.0
 * @package           Bithek
 *
 * @wordpress-plugin
 * Plugin Name:       BiThek
 * Plugin URI:        bithek
 * Description:       Plugin zum importieren und durchsuchen des Bestandes aus BiThek
 * Version:           1.0.0
 * Author:            Ramon Ackermann
 * Author URI:        https://github.com/sboo
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bithek
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-bithek-activator.php
 */
function activate_bithek() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bithek-activator.php';
	Bithek_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-bithek-deactivator.php
 */
function deactivate_bithek() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bithek-deactivator.php';
	Bithek_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_bithek' );
register_deactivation_hook( __FILE__, 'deactivate_bithek' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-bithek.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_bithek() {

	$plugin = new Bithek();
	$plugin->run();

}
run_bithek();
