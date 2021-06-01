<?php
/**
 * Shift8 Zoom Settings
 *
 * Declaration of plugin settings used throughout
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    die();
}

add_action('admin_head', 'shift8_push_custom_favicon');
function shift8_push_custom_favicon() {
  echo '
    <style>
    .dashicons-shift8 {
        background-image: url("'. plugin_dir_url(dirname(__FILE__)) .'/img/shift8pluginicon.png");
        background-repeat: no-repeat;
        background-position: center; 
    }
    </style>
  '; 
}

// create custom plugin settings menu
add_action('admin_menu', 'shift8_push_create_menu');
function shift8_push_create_menu() {
        //create new top-level menu
        if ( empty ( $GLOBALS['admin_page_hooks']['shift8-settings'] ) ) {
                add_menu_page('Shift8 Settings', 'Shift8', 'administrator', 'shift8-settings', 'shift8_main_page' , 'dashicons-shift8' );
        }
        add_submenu_page('shift8-settings', 'Push Settings', 'Push Settings', 'manage_options', __FILE__.'/custom', 'shift8_push_settings_page');
        //call register settings function
        add_action( 'admin_init', 'register_shift8_push_settings' );
}

// Register admin settings
function register_shift8_push_settings() {
    //Register our settings
    register_setting( 'shift8-push-settings-group', 'shift8_push_enabled' );
    register_setting( 'shift8-push-settings-group', 'shift8_push_src_url' );
    register_setting( 'shift8-push-settings-group', 'shift8_push_dst_url' );
    register_setting( 'shift8-push-settings-group', 'shift8_push_application_password' );
}

// Activation hook
function shift8_push_plugin_activation() {
}
register_activation_hook( S8PUSH_FILE, 'shift8_push_plugin_activation' );
 

// Uninstall hook
function shift8_push_uninstall_hook() {
  // Delete setting values
  delete_option('shift8_push_enabled');
  delete_option('shift8_push_src_url');
  delete_option('shift8_push_dst_url');
  delete_option('shift8_push_application_password');
}
register_uninstall_hook( S8PUSH_FILE, 'shift8_push_uninstall_hook' );

// Deactivation hook
function shift8_push_deactivation() {
}
register_deactivation_hook( S8PUSH_FILE, 'shift8_push_deactivation' );

// Validate admin options
function shift8_push_check_enabled() {
  // If enabled is not set
  if(esc_attr( get_option('shift8_push_enabled') ) != 'on') return false;
  if(empty(esc_attr(get_option('shift8_push_src_url') ))) return false;
  if(empty(esc_attr(get_option('shift8_push_dst_url') ))) return false;
  return true;
}

// Process all options and return array
function shift8_push_check_options() {
  $shift8_options = array();
  $shift8_options['push_src_url'] = esc_attr( get_option('shift8_push_src_url') );
  $shift8_options['push_dst_url'] = esc_attr( get_option('shift8_push_dst_url') );
  
  return $shift8_options;
}

// Trigger function when application password is updated to encrypt it properly
add_action('init', 'shift8_push_init');
function shift8_push_init() {
  add_filter( 'pre_update_option_shift8_push_application_password', 'shift8_push_update_application_password', 10, 2 );
}

function shift8_push_update_application_password($new_value, $old_value) {
  $current_value = shift8_push_decrypt(get_option('shift8_push_application_password'));
  if ($current_value != $new_value) {
    $new_value = shift8_push_encrypt($new_value);
    return $new_value;
  }
  return shift8_push_encrypt($current_value);
}