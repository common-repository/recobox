<?php

/**
 * @link              https://recobox.ru
 * @since             1.0.6
 * @package           Rcb_Comments
 *
 * @wordpress-plugin
 * Plugin Name:       Recobox
 * Plugin URI:        https://recobox.ru
 * Description:       Система комментирования с различными вариантами социальной аутентификации(ВКонтакте, Одноклассники, FaceBook, Google+ и т.д.).
 * Version:           1.0.6
 * Author:            Recobox
 * Author URI:        https://profiles.wordpress.org/deller21
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rcb-comments
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'RCB_COMMENTS_VERSION', '1.0.6' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rcb-comments-activator.php
 */
function rcb_comments_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rcb-comments-activator.php';
	Rcb_Comments_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-rcb-comments-deactivator.php
 */
function rcb_comments_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rcb-comments-deactivator.php';
	Rcb_Comments_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'rcb_comments_activate' );
register_deactivation_hook( __FILE__, 'rcb_comments_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-rcb-comments.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.5
 */
function rcb_comments_run() {

	$plugin = new Rcb_Comments();
	$plugin->run();

}
rcb_comments_run();
