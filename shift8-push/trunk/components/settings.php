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
    register_setting( 'shift8-push-settings-group', 'shift8_push_application_user' );
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
  delete_option('shift8_push_application_user');
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

// Used to validate whether options, plugin enable and user is admin
function shift8_push_check_validation() {
  if (!shift8_push_check_enabled()) return false;
  if (empty(esc_attr(get_option('shift8_push_application_user')))) return false;
  if (empty(esc_attr(get_option('shift8_push_application_password')))) return false;
  if (empty(esc_attr( get_option('shift8_push_src_url')))) return false;
  if (empty(esc_attr( get_option('shift8_push_dst_url')))) return false;
  // If we are on the destination server, return false
  if (parse_url(esc_attr( get_option('shift8_push_dst_url')), PHP_URL_HOST) == 
    parse_url(get_site_url(), PHP_URL_HOST)) return false;
  if (!is_admin()) return false;

  return true;

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

add_action( 'post_submitbox_misc_actions', 'shift8_push_button' );

function shift8_push_button(){
  if (shift8_push_check_validation()) {
    $item_id = get_the_ID();
    $post_type = get_post_type($item_id);
    // Show button on specific post types
    if ($post_type && in_array($post_type, S8PUSH_POSTTYPES)) {
      $html = '<div class="shift8-push-button-container">';
      $html .= '<a id="shift8-push-trigger" href="' . wp_nonce_url( admin_url('admin-ajax.php?action=shift8_push_push&item_id=' . $item_id), 'process') . '"><button class="shift8-push-button shift8-push-button-check">Push to Prod</button></a>';
      $html .= '<div class="shift8-push-spinner"></div>';
      $html .= '</div>';
      echo $html;
    }
  }
}