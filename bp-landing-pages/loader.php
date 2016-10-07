<?php

/*
  Plugin Name: BuddyPress - custom front page for users & groups
  Plugin URI: http://webdeveloperswall.com/buddypress/buddypress-message-attachment
  Description: Adds multiple widgets to allow users to display those on their profile.
  Version: 0.1
  Author: ckchaudhary
  Author URI: http://webdeveloperswall.com/author/ckchaudhary
  Text Domain: bp-landing-pages
  Domain Path: /languages
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
    exit;

/* ++++++++++++++++++++++++++++++
 * CONSTANTS
  +++++++++++++++++++++++++++++ */
// Directory
if ( !defined( 'BPFPWIDGETS_PLUGIN_DIR' ) ) {
    define( 'BPFPWIDGETS_PLUGIN_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}

// Url
if ( !defined( 'BPFPWIDGETS_PLUGIN_URL' ) ) {
    $plugin_url = trailingslashit( plugin_dir_url( __FILE__ ) );

    // If we're using https, update the protocol.
    if ( is_ssl() )
        $plugin_url = str_replace( 'http://', 'https://', $plugin_url );

    define( 'BPFPWIDGETS_PLUGIN_URL', $plugin_url );
}

/* ______________________________ */

function bpfp_widgets_init () {
    require( BPFPWIDGETS_PLUGIN_DIR . 'includes/main-class.php' );
    return BPFP_Widgets_Plugin::instance();
}

add_action( 'plugins_loaded', 'bpfp_widgets_init' );

/**
 * Returns the main plugin object
 * @return BPFP_Widgets_Plugin
 */
function bpfp_widgets () {
    return BPFP_Widgets_Plugin::instance();
}
