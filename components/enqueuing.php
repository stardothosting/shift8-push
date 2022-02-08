<?php
/**
 * Shift8 Enqueuing Files
 *
 * Function to load styles and front end scripts
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    die();
}

// Register admin scripts for custom fields
function load_shift8_push_wp_admin_style() {
        // admin always last
        wp_enqueue_style( 'shift8_push_css', plugin_dir_url(dirname(__FILE__)) . 'css/shift8_push_admin.css', array(), '0.0.2' );
        wp_enqueue_script( 'shift8_push_script', plugin_dir_url(dirname(__FILE__)) . 'js/shift8_push_admin.js', array(), '0.0.17' );

        wp_localize_script( 'shift8_push_script', 'the_ajax_script', array( 
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( "shift8_push_response_nonce"),
        ));  
}
add_action( 'admin_enqueue_scripts', 'load_shift8_push_wp_admin_style' );
