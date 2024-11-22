<?php
/**
 * Plugin Name: Quick Insights
 * Text Domain: quick-insights
 * Domain Path: /languages
 * Description: Gain a comprehensive snapshot of your WordPress site's status and performance with this streamlined dashboard. See your active plugins list and current theme at a glance, alongside the total number of posts and pages on your site. Quickly monitor storage usage to stay on top of capacity and performance.
 * Version: 1.0.0
 * Author: Riad
 * Author URI: https://github.com/aburiad
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Tested up to: 6.6
 */

 if(! defined('ABSPATH') ){
     exit;
 }

 /**
  *  The main plugin class
  */

final class Quick_Insights{

    /**
     *  class construct
    */
    private function __construct() {

    }

    /**
     *  initializes a singletone instance
     * 
     * @return \Quick_insights
     */
     public static function init(){
       static $instance = false;

        if( !$instance ){
            $instance = new self();
        }

        return $instance;
    }
}

/**
 * Initializes the main plugin
 * 
 * @return \Quick_Insights
 */

function quick_insights(){
    return Quick_Insights::init();
}

// kickoff plugin
quick_insights();

// Enqueue React script
function wp_react_kickoff_enqueue_scripts() {
    // Enqueue the React JS file
    wp_enqueue_script(
        'wp-react-kickoff-admin-js',
        plugins_url('/dist/bundle.js', __FILE__), // Path to your React bundle
        ['wp-element'], 
        null,
        true
    );

    // Localize script to pass dynamic site URL to JS
    wp_localize_script('wp-react-kickoff-admin-js', 'siteData', [
        'siteUrl' => get_site_url(),
        'themeName' => wp_get_theme()->get('Name')
    ]);
}
add_action('admin_enqueue_scripts', 'wp_react_kickoff_enqueue_scripts');

// Add dashboard widget for Quick Insights
function quick_dashboard_get() {
    wp_add_dashboard_widget(
        'quick_insights_widget', 
        'Quick Insights',
        'quick_insights_display'
    );
}

function quick_insights_display() {
    // This is where the React component will render
    echo '<div id="my-custom-menu-root"></div>';
}

add_action('wp_dashboard_setup', 'quick_dashboard_get');

// Function to get active plugins count
function get_active_plugins_count(WP_REST_Request $request) {
    $active_plugins = get_option('active_plugins'); // Get list of active plugins
    return rest_ensure_response(['active_plugins' => count($active_plugins)]);
}

// Function to get server storage information
function get_server_storage_info() {
    $total_space = disk_total_space('/'); 
    $free_space = disk_free_space('/'); 

    // Convert bytes to megabytes for readability
    $total_space_mb = round($total_space / 1024 / 1024, 2);
    $free_space_mb = round($free_space / 1024 / 1024, 2);
    $used_space_mb = $total_space_mb - $free_space_mb; 

    return [
        'total' => $total_space_mb,
        'free' => $free_space_mb,
        'used' => $used_space_mb
    ];
}

// Function to get active theme information
function get_active_theme_info(WP_REST_Request $request) {
    $theme = wp_get_theme(); // Get active theme details
    return rest_ensure_response([
        'name' => $theme->get('Name'),
        'theme_directory' => $theme->get('Template'),
        'version' => $theme->get('Version'),
        'author' => $theme->get('Author'),
    ]);
}

// Register custom API endpoints
function register_custom_api_endpoints() {
    register_rest_route('custom-api/v1', '/storage', [
        'methods' => 'GET',
        'callback' => function() {
            return new WP_REST_Response(get_server_storage_info(), 200);
        },
    ]);

    register_rest_route('custom-api/v1', '/active-plugins', [
        'methods' => 'GET',
        'callback' => 'get_active_plugins_count',
    ]);
    
    register_rest_route('custom-api/v1', '/active-theme', [
        'methods' => 'GET',
        'callback' => 'get_active_theme_info',
    ]);
}

add_action('rest_api_init', 'register_custom_api_endpoints');


/**
 *  inclue email notification file
 */
require_once 'inc/email-notification.php';