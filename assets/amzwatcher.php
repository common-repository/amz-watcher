<?php

/**
 * AMZWatcher
 *
 *
 * @package   AMZWatcher
 * @author    AMZWatcher
 * @license   GPL-3.0
 * @link      https://amzwatcher.com
 * @copyright 2024 Graitch LLC
 *
 * @wordpress-plugin
 * Plugin Name:       AMZ Watcher
 * Plugin URI:        https://amzwatcher.com/tools/wordpress-plugin/
 * Description:       AMZ Watcher is tool for Amazon Affiliates that allows you to find a fix broken Amazon Links on your website. This is companion plugin.
 * Version:           1.0.8
 * Author:            amzwatcher
 * Author URI:        https://amzwatcher.com
 * Text Domain:       amzwatcher
 * License:           GPL-3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:       /languages
 */

namespace AMZWatcher\WPR;

// If this file is called directly, abort.

if ( ! defined( 'WPINC' ) ) {

	die;

}


define( 'AMZWATCHER_VERSION', '1.0.8' );



/**
 * Autoloader
 *
 * @param string $class The fully-qualified class name.
 * @return void
 *
 *  * @since 1.0.0
 */

spl_autoload_register(function ($class) {

    // project-specific namespace prefix

    $prefix = __NAMESPACE__;



    // base directory for the namespace prefix

    $base_dir = __DIR__ . '/includes/';



    // does the class use the namespace prefix?

    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {

        // no, move to the next registered autoloader

        return;

    }



    // get the relative class name

     $relative_class = substr($class, $len);





    // replace the namespace prefix with the base directory, replace namespace

    // separators with directory separators in the relative class name, append

    // with .php

    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';



    // if the file exists, require it

    if (file_exists($file)) {

        require $file;

    }

});



/**

 * Initialize Plugin

 *

 * @since 1.0.0

 */

function init() {

	$wpr = Plugin::get_instance();

	// $wpr_shortcode = Shortcode::get_instance();

	$wpr_admin = Admin::get_instance();

    $wpr_rest_api_key = Endpoint\ApiKey::get_instance();

    $wpr_rest_settings = Endpoint\Settings::get_instance();

    $wpr_rest_user = Endpoint\User::get_instance();

    $wpr_rest_site = Endpoint\Site::get_instance();

    $wpr_rest_jobs = Endpoint\Jobs::get_instance();

    $wpr_rest_analytics = Endpoint\Analytics::get_instance();

    $wpr_rest_products = Endpoint\Products::get_instance();

    $wpr_rest_refresh = Endpoint\Refresh::get_instance();

}

add_action( 'plugins_loaded', 'AMZWatcher\\WPR\\init' );


/**
 * Register the widget
 *
 * @since 1.0.0
 */

// function widget_init() {

// 	return register_widget( new Widget );

// }

// add_action( 'widgets_init', 'AMZWatcher\\WPR\\widget_init' );



/**

 * Register activation and deactivation hooks

 */

register_activation_hook( __FILE__, array( 'AMZWatcher\\WPR\\Plugin', 'activate' ) );

register_deactivation_hook( __FILE__, array( 'AMZWatcher\\WPR\\Plugin', 'deactivate' ) );

