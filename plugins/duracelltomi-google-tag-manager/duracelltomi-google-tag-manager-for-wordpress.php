<?php 
/*
Plugin Name: Google Tag Manager for Wordpress
Version: 1.1.1
Plugin URI: https://duracelltomi.com/google-tag-manager-for-wordpress/
Description: The first Google Tag Manager plugin for WordPress with business goals in mind
Author: Thomas Geiger
Author URI: https://duracelltomi.com/
*/

define( 'GTM4WP_VERSION',    '1.1.1' );
define( 'GTM4WP_PATH',       plugin_dir_path( __FILE__ ) );
define( 'GTM4WP_TEXTDOMAIN', 'gtm4wp-lang' );

$gtp4wp_plugin_url = plugin_dir_url( __FILE__ );
$gtp4wp_plugin_basename = plugin_basename( __FILE__ );
require_once( GTM4WP_PATH."/common/readoptions.php" );

function gtm4wp_init() {
	load_plugin_textdomain( GTM4WP_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	if ( is_admin() ) {
		require_once( GTM4WP_PATH."/admin/admin.php" );
	} else {
		require_once( GTM4WP_PATH."/public/frontend.php" );
	}
}
add_action('plugins_loaded', 'gtm4wp_init');
