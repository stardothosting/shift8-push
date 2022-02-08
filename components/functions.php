<?php
/**
 * Shift8 Push Main Functions
 *
 * Collection of functions used throughout the operation of the plugin
 *
 */
use Carbon\Carbon;
use Carbon\CarbonTimeZone;

if ( !defined( 'ABSPATH' ) ) {
    die();
}

// Function to encrypt session data
function shift8_push_encrypt($payload) {
    $key = wp_salt('auth');
    if (!empty($key) && !empty($payload)) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($payload, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    } else {
        return false;
    }
}

// Function to decrypt session data
function shift8_push_decrypt($garble) {
    $key = wp_salt('auth');
    if (!empty($key) && !empty($garble)) {
        list($encrypted_data, $iv) = explode('::', base64_decode($garble), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
    } else {
        return false;
    }
}

// Function to write to log file for debugging
function shift8_push_write_log($log) {
    if (true === WP_DEBUG) {
        if (is_array($log) || is_object($log)) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    }
}
// Handle the ajax trigger
add_action( 'wp_ajax_shift8_push_push', 'shift8_push_push' );
function shift8_push_push() {
    // Test
    if ( wp_verify_nonce($_GET['_wpnonce'], 'process') && $_GET['type'] == 'check') {
        shift8_push_poll('check', null);
        die();
    // Push 
    } else if ( wp_verify_nonce($_GET['_wpnonce'], 'process') && $_GET['type'] == 'push' && is_numeric($_GET['item_id'])) {
        $item_id = sanitize_text_field($_GET['item_id']);
        shift8_push_poll('push', $item_id);
        die();
    } else {
        die();
    } 
}

// Handle the actual GET
function shift8_push_poll($shift8_action, $item_id = null) {
    if (current_user_can('administrator')) {
        global $wpdb;
        $current_user = wp_get_current_user();

        $application_password = base64_encode(esc_attr(get_option('shift8_push_application_user')) . ':' . shift8_push_decrypt(esc_attr(get_option('shift8_push_application_password'))));
        $destination_url = esc_attr(get_option('shift8_push_dst_url'));
        $source_url = esc_attr(get_option('shift8_push_src_url'));

        // Set headers for WP Remote post
        $headers = array(
            'Content-type: application/json',
            'Authorization' => 'Basic ' . $application_password,
        );

        // Check values with dashboard
        if ($shift8_action == 'check') {
            // Use WP Remote Get to poll the zoom api 
            $response = wp_remote_get( $destination_url . S8PUSH_APIBASE ,
                array(
                    'method' => 'GET',
                    'headers' => $headers,
                    'httpversion' => '1.1',
                    'timeout' => '45',
                    'blocking' => true,
                )
            );
            // Deal with the response
            if (is_object(json_decode($response['body']))) {
                // Populate options from response if its a check
                if ($shift8_action == 'check') {
                    echo json_encode(array(
                        'namespace' => esc_attr(json_decode($response['body'])->namespace),
                    ));                  
                }

            } else {
                echo 'Error Detected : ';
                if (is_array($response['response'])) {
                    echo esc_attr(json_decode($response['body'])->error);

                } else {
                    echo 'unknown';
                }
            } 
        } else if ($shift8_action == 'push') {



            // Assign the post type based on item ID provided
            $post_type = null;
            if ( $item_id && get_post_type( $item_id ) == 'post' ) $post_type = 'posts';
            if ( $item_id && get_post_type( $item_id ) == 'page' ) $post_type = 'pages';
            if ( $item_id && get_post_type( $item_id ) == 'traffick-stop' ) $post_type = 'traffick-stop';

            // Only continue if we are dealing with posts or pages
            if ($post_type) {

                // Load up the post data
                $post_data = get_post($item_id);
                $post_meta = get_post_meta($item_id);
                if( $post_data ) {
                    // Now, form our own array
                    $data = array();
                    $meta_data = array();
                    $data['date'] = $post_data->post_date;
                    $data['date_gmt'] = $post_data->post_date_gmt;
                    $data['guid'] = $post_data->guid;
                    $data['modified'] = $post_data->post_modified;
                    $data['modified_gmt'] = $post_data->post_modified_gmt;
                    $data['slug'] = $post_data->post_name;
                    $data['status'] = $post_data->post_status;
                    $data['title'] = $post_data->post_title;
                    $data['content'] = str_replace($source_url, $destination_url, $post_data->post_content);
                    $data['excerpt'] = $post_data->post_excerpt;
                    // Wrap all metadata into array to pass separately to custom REST endpoint
                    if ($post_meta && is_array($post_meta)) {
                        foreach ($post_meta as $key => $value) {
                            $fixed_value = shift8_push_recursive_unserialize_replace($source_url, $destination_url, $value[0]);
                            $meta_data['meta'][$key] = $fixed_value;
                        }
                    }
                }

                // Check if post exists
                $check_exists = wp_remote_get( $destination_url . S8PUSH_APIBASE . $post_type . '?slug=' . $post_data->post_name,
                    array(
                        'method' => 'GET',
                        'headers' => $headers,
                        'httpversion' => '1.1',
                        'timeout' => '45',
                        'blocking' => true,
                    )
                );

                $check_object = json_decode($check_exists['body']);

                // If we found an existing post with slug match
                if (count($check_object) > 0) {
                    $destination_id = esc_attr($check_object[0]->id);
                    $api_response = wp_remote_post( $destination_url . S8PUSH_APIBASE . $post_type . '/' . $destination_id . '/',
                        array(
                            'method' => 'POST',
                            'headers' => $headers,
                            'httpversion' => '1.1',
                            'timeout' => '45',
                            'blocking' => true,
                            'body' => $data,
                            'data_format' => 'body',
                        )
                    );
                    $body = json_decode( $api_response['body'] );
                    if( wp_remote_retrieve_response_message( $api_response ) === 'OK' ) {
                        $meta_data['id'] = $destination_id;
                        $meta_api_response = wp_remote_post( $destination_url . S8PUSH_APICUSTOM . 'meta/', array(
                            'method' => 'POST',
                            'headers' => $headers,
                            'httpversion' => '1.1',
                            'timeout' => '45',
                            'blocking' => true,
                            'body' => $meta_data,
                            'data_format' => 'body',
                            )
                        );
                       $meta_body = json_decode( $meta_api_response['body'] );
                       echo json_encode(array(
                            'message' => 'The post ' . $body->title->rendered . ' has been updated successfully',
                        ));
                    } else {
                        echo json_encode(array(
                            'message' => 'error with api response for meta push update',
                        ));
                    }

                // IF nothing found, create new post on destination
                } else {
                   $api_response = wp_remote_post( $destination_url . S8PUSH_APIBASE . $post_type,
                        array(
                            'method' => 'POST',
                            'headers' => $headers,
                            'httpversion' => '1.1',
                            'timeout' => '45',
                            'blocking' => true,
                            'body' => $data,
                            'data_format' => 'body',
                        )
                    );
                    $body = json_decode( $api_response['body'] );
                    if( wp_remote_retrieve_response_message( $api_response ) === 'Created' ) {
                        $meta_data['id'] = $body->id;
                        $meta_api_response = wp_remote_post( $destination_url . S8PUSH_APICUSTOM . 'meta/',                       array(
                            'method' => 'POST',
                            'headers' => $headers,
                            'httpversion' => '1.1',
                            'timeout' => '45',
                            'blocking' => true,
                            'body' => $meta_data,
                            'data_format' => 'body',
                            )
                        );
                       $meta_body = json_decode( $meta_api_response['body'] );
                       echo json_encode(array(
                            'message' => 'The post ' . $body->title->rendered . ' has been created successfully : ' . $meta_data['id'],
                        ));
                    } else {
                        echo json_encode(array(
                            'message' => 'error with api response for meta push create : ' . $meta_data['id'],
                        ));
                    }
                }

            }
        }
    } 
}

// Functions to produce debugging information
function shift8_push_debug_get_php_info() {
    //retrieve php info for current server
    if (!function_exists('ob_start') || !function_exists('phpinfo') || !function_exists('ob_get_contents') || !function_exists('ob_end_clean') || !function_exists('preg_replace')) {
        echo 'This information is not available.';
    } else {
        ob_start();
        phpinfo();
        $pinfo = ob_get_contents();
        ob_end_clean();

        $pinfo = preg_replace( '%^.*<body>(.*)</body>.*$%ms','$1',$pinfo);
        echo esc_html($pinfo);
    }
}

function shift8_push_debug_version_check() {
    //outputs basic information
    $notavailable = __('This information is not available.');
    if ( !function_exists( 'get_bloginfo' ) ) {
        $wp = $notavailable;
    } else {
        $wp = get_bloginfo( 'version' );
    }

    if ( !function_exists( 'wp_get_theme' ) ) {
        $theme = $notavailable;
    } else {
        $theme = wp_get_theme();
    }

    if ( !function_exists( 'get_plugins' ) ) {
        $plugins = $notavailable;
    } else {
        $plugins_list = get_plugins();
        if( is_array( $plugins_list ) ){
            $active_plugins = '';
            $plugins = '<ul>';
            foreach ( $plugins_list as $plugin ) {
                $version = '' != $plugin['Version'] ? $plugin['Version'] : __( 'Unversioned', 'debug-info' );
                if( !empty( $plugin['PluginURI'] ) ){
                    $plugins .= '<li><a href="' . $plugin['PluginURI'] . '">' . $plugin['Name'] . '</a> (' . $version . ')</li>';
                } else {
                    $plugins .= '<li>' . $plugin['Name'] . ' (' . $version . ')</li>';
                }
            }
            $plugins .= '</ul>';
        }
    }

    if ( !function_exists( 'phpversion' ) ) {
        $php = $notavailable;
    } else {
        $php = phpversion();
    }


    $themeversion   = $theme->get( 'Name' ) . __( ' version ', 'debug-info' ) . $theme->get( 'Version' ) . $theme->get( 'Template' );
    $themeauth      = $theme->get( 'Author' ) . ' - ' . $theme->get( 'AuthorURI' );
    $uri            = $theme->get( 'ThemeURI' );

    echo '<strong>' . __( 'WordPress Version: ' ) . '</strong>' . esc_html($wp) . '<br />';
    echo '<strong>' . __( 'Current WordPress Theme: ' ) . '</strong>' . esc_html($themeversion) . '<br />';
    echo '<strong>' . __( 'Theme Author: ' ) . '</strong>' . esc_html($themeauth) . '<br />';
    echo '<strong>' . __( 'Theme URI: ' ) . '</strong>' . esc_html($uri) . '<br />';
    echo '<strong>' . __( 'PHP Version: ' ) . '</strong>' . esc_html($php) . '<br />';
    echo '<strong>' . __( 'Active Plugins: ' ) . '</strong>' . esc_html($plugins) . '<br />';
}

// Allow all meta fields to be updated via REST API
add_action( 'rest_api_init', 'shift8_push_create_api_posts_meta_field' );

function shift8_push_create_api_posts_meta_field() {
    $page_meta_keys = shift8_push_generate_meta_keys('page');
    $post_meta_keys = shift8_push_generate_meta_keys('post');
    foreach ($page_meta_keys as $page_meta_key) {
        register_rest_field( 'page', $page_meta_key, array(
            'update_callback' => 'shift8_push_update_post_meta_for_api',
            )
        );
    }
    foreach ($post_meta_keys as $post_meta_key) {
        register_rest_field( 'post', $post_meta_key, array(
            'update_callback' => 'shift8_push_update_post_meta_for_api',
            )
        );
    }
    // Traffick stop
    if (post_type_exists('traffick-stop')) {
        $traffick_meta_keys = shift8_push_generate_meta_keys('traffick-stop');
        foreach ($page_meta_keys as $page_meta_key) {
            register_rest_field( 'traffick-stop', $page_meta_key, array(
                'update_callback' => 'shift8_push_update_post_meta_for_api',
                )
            );
        }
    }
}

function shift8_push_update_post_meta_for_api( $value, $object, $field_name ) {
    if ( ! $value ) {
        return;
    }
    return update_post_meta( $object->ID, $field_name, $value );
}

function shift8_push_generate_meta_keys($post_type){
    global $wpdb;
    $query = "
        SELECT DISTINCT($wpdb->postmeta.meta_key) 
        FROM $wpdb->posts 
        LEFT JOIN $wpdb->postmeta 
        ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
        WHERE $wpdb->posts.post_type = '%s' 
        AND $wpdb->postmeta.meta_key != '_fl_builder_history_position' 
    ";
    $meta_keys = $wpdb->get_col($wpdb->prepare($query, esc_attr($post_type)));
    return $meta_keys;
}

// Create custom REST endpoint for dealing with meta values
add_action( 'rest_api_init', function () {
        register_rest_route( 'shift8/v1', '/meta/', array(
                'methods' => 'POST',
                'callback' => 'shift8_push_rest_meta',
        ) );
} );

function shift8_push_rest_meta( $request ) {
    $post_data['id'] = $request['id'];
    $post_data['meta'] = $request['meta'];

    if ($post_data['meta'] && is_array($post_data['meta'])) {
        $response_meta = array();
        foreach ($post_data['meta'] as $key => $value) {
            $update_response = update_post_meta($post_data['id'], $key, maybe_unserialize($value));
            if (!$update_response) {
                $response_meta[] = array(
                    'meta_key' => $key,
                    'meta_value' => $value,
                    'update_response' => $update_response,
                );
            }
        }
    }
    return array(
        'update_response' => true,
        'message' => 'Post meta update succeeded for ID ' . $post_data['id'],
        'post_data' => $post_data,
    );
}

function shift8_push_recursive_array_replace( $find, $replace, $data ) {
    if ( is_array( $data ) ) {
        foreach ( $data as $key => $value ) {
            if ( is_array( $value ) ) {
                recursive_array_replace( $find, $replace, $data[ $key ] );
            } else {
                // have to check if it's string to ensure no switching to string for booleans/numbers/nulls - don't need any nasty conversions
                if ( is_string( $value ) )
                    $data[ $key ] = str_replace( $find, $replace, $value );
            }
        }
    } else {
        if ( is_string( $data ) )
            $data = str_replace( $find, $replace, $data );
    }
}

function shift8_push_recursive_unserialize_replace( $from = '', $to = '', $data = '', $serialised = false ) {

    // some unseriliased data cannot be re-serialised eg. SimpleXMLElements
    try {

        if ( is_string( $data ) && ( $unserialized = @unserialize( $data ) ) !== false ) {
            $data = shift8_push_recursive_unserialize_replace( $from, $to, $unserialized, true );
        }

        elseif ( is_array( $data ) ) {
            $_tmp = array( );
            foreach ( $data as $key => $value ) {
                $_tmp[ $key ] = shift8_push_recursive_unserialize_replace( $from, $to, $value, false );
            }

            $data = $_tmp;
            unset( $_tmp );
        }

        else {
            if ( is_string( $data ) )
                $data = str_replace( $from, $to, $data );
        }

        if ( $serialised )
            return serialize( $data );

    } catch( Exception $error ) {

    }

    return $data;
}

