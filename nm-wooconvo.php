<?php
/**
 * Plugin Name:       OrderConvo - Vendor & Customer Messages After Orders
 * Plugin URI:        http://www.najeebmedia.com
 * Description:       Order chat, messages and file sharing in WooCommerce.
 * Author:            N-Media
 * Author URI:        https://najeebmedia.com/
 * License:           GPL v2 or later
 * Version:           13.1
 * Text Domain:       wooconvo
 */
 
define('WOOCONVO8_PATH', untrailingslashit(plugin_dir_path( __FILE__ )) );
define('WOOCONVO8_URL', untrailingslashit(plugin_dir_url( __FILE__ )) );
define('WOOCONVO8_VERSION', '13.1' );
define('WOOCONVO8_SHORTNAME', 'wooconvo' );

define('WOOCONVO8_REACT_JS', 'main.20ccad0e.js' );
define('WOOCONVO8_REACT_CSS', 'main.930edd9b.css' );
define('WOOCONVO8_REACT_SINGLE_JS', 'main.d84fa9db.js' );
define('WOOCONVO8_REACT_SINGLE_CSS', 'main.a5195f05.css' );

include_once WOOCONVO8_PATH.'/includes/helper_functions.php';
include_once WOOCONVO8_PATH.'/includes/meta.json.php';
include_once WOOCONVO8_PATH.'/includes/migration.class.php';
include_once WOOCONVO8_PATH.'/includes/order.class.php';
include_once WOOCONVO8_PATH.'/includes/wooconvo.class.php';
// include_once WOOCONVO8_PATH.'/includes/field.class.php';
include_once WOOCONVO8_PATH.'/includes/wprest.class.php';
include_once WOOCONVO8_PATH.'/includes/admin.class.php';
include_once WOOCONVO8_PATH.'/includes/single.rendering.php';

     
function wooconvo_init(){
    
    // $migrate = init_wooconvo_migration();
    // $migrate->migrate_threads();
    
    init_wooconvo_wp_rest();
	init_wooconvo_main();
	init_wooconvo_admin();
	
	$disable_rendering = wooconvo_get_option('disable_each_order_rendering');
	if( !$disable_rendering && is_user_logged_in() ) {
	    init_single_rendering();
	}
	
}


add_action('init', 'wooconvo_init');

/**
 * Flush rewrite rules upon plugin activation
 */
function wooconvo_activate() {
    add_rewrite_endpoint( 'wooconvo-messages', EP_ROOT | EP_PAGES );
    flush_rewrite_rules(); // Flush rewrite rules to apply the new ones
}

register_activation_hook(__FILE__, 'wooconvo_activate');

/**
 * Flush rewrite rules upon plugin deactivation
 */
function wooconvo_deactivate() {
    flush_rewrite_rules(); // Clean up rewrite rules when plugin is deactivated
}

register_deactivation_hook(__FILE__, 'wooconvo_deactivate');