<?php
/**
 * Shift8 Zoom Main Functions
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
        shift8_push_poll('check');
        die();
    // Manual import of webinars from Zoom
    } else if ( wp_verify_nonce($_GET['_wpnonce'], 'process') && $_GET['type'] == 'import') {
        shift8_push_poll('import');
        die();
    } else {
        die();
    } 
}

// Handle the actual GET
function shift8_push_poll($shift8_action) {
    if (current_user_can('administrator')) {
        global $wpdb;
        $current_user = wp_get_current_user();

        $application_password = base64_encode('admin:' . shift8_push_decrypt(esc_attr(get_option('shift8_push_application_password'))));
        $destination_url = esc_attr(get_option('shift8_push_dst_url'));

        // Set headers for WP Remote post
        $headers = array(
            'Content-type: application/json',
            'Authorization' => 'Basic ' . $application_password,
        );
        // Check values with dashboard
        if ($shift8_action == 'check') {
            // Use WP Remote Get to poll the zoom api 
            $response = wp_remote_get( $destination_url . '/wp-json/wp/v2/' ,
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
        } else if ($shift8_action == 'import') {
            // Use WP Remote Get to poll the zoom api 
            $response = wp_remote_get( S8ZOOM_API . '/v2/users/' . $zoom_user_email . '/webinars' . S8ZOOM_WEBINAR_PARAMETERS,
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
                if ($shift8_action == 'import') {         
                    $webinar_data = json_decode($response['body'], true);
                    $webinars_imported = shift8_push_import_webinars($webinar_data);
                    echo json_encode(array(
                        'total_records' => esc_attr(json_decode($response['body'])->total_records),
                        'webinar_data' => json_encode(json_decode($response['body'])->webinars),
                        'webinars_imported' => $webinars_imported,
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
        echo $pinfo;
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

    echo '<strong>' . __( 'WordPress Version: ' ) . '</strong>' . $wp . '<br />';
    echo '<strong>' . __( 'Current WordPress Theme: ' ) . '</strong>' . $themeversion . '<br />';
    echo '<strong>' . __( 'Theme Author: ' ) . '</strong>' . $themeauth . '<br />';
    echo '<strong>' . __( 'Theme URI: ' ) . '</strong>' . $uri . '<br />';
    echo '<strong>' . __( 'PHP Version: ' ) . '</strong>' . $php . '<br />';
    echo '<strong>' . __( 'Active Plugins: ' ) . '</strong>' . $plugins . '<br />';
}

// Function to schedule cron polling interval to import Zoom webinars

// Check user plan options
add_action( 'shift8_push_cron_hook', 'shift8_push_check' );
function shift8_push_check() {
    $zoom_jwt_token = shift8_push_generate_jwt();
    $zoom_user_email = sanitize_email(get_option('shift8_push_user_email'));

     // Set headers for WP Remote post
    $headers = array(
        'Content-type: application/json',
        'Authorization' => 'Bearer ' . $zoom_jwt_token,
    );

    // Use WP Remote Get to poll the zoom api 
    $response = wp_remote_get( S8ZOOM_API . '/v2/users/' . $zoom_user_email . '/webinars' . S8ZOOM_WEBINAR_PARAMETERS,
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
        // Pass the returned webinars to a function to handle the import
        $webinar_data = json_decode($response['body'], true);
        $webinars_imported = shift8_push_import_webinars($webinar_data);
    } else {
        echo 'Error Detected : ';
        if (is_array($response['response'])) {
            echo esc_attr(json_decode($response['body'])->error);

        } else {
            echo 'unknown';
        }
    }
}

// Custom Cron schedules outside of default WP Cron
add_filter( 'cron_schedules', 'shift8_push_add_cron_interval' );
function shift8_push_add_cron_interval( $schedules ) { 
    $schedules['shift8_push_minute'] = array(
        'interval' => 60,
        'display'  => esc_html__( 'Every Sixty Seconds' ), );
    $schedules['shift8_push_halfhour'] = array(
        'interval' => 1800,
        'display'  => esc_html__( 'Every 30 minutes' ), );
    $schedules['shift8_push_twohour'] = array(
        'interval' => 7200,
        'display'  => esc_html__( 'Every two hours' ), );
    $schedules['shift8_push_fourhour'] = array(
        'interval' => 14400,
        'display'  => esc_html__( 'Every four hours' ), );
    return $schedules;
}

// Set the cron task on an hourly basis to check the zoom suffix, only if enabled and all fields populated
if (shift8_push_check_enabled()) {
    if ( ! wp_next_scheduled( 'shift8_push_cron_hook' ) ) {
        wp_schedule_event( time(), esc_attr(get_option('shift8_push_import_frequency')), 'shift8_push_cron_hook' );
    } 
} else {
    wp_clear_scheduled_hook( 'shift8_push_cron_hook' );
}

shift8_push_write_log(wp_next_scheduled( 'shift8_push_cron_hook' ) );

// Generate JWT Token 
function shift8_push_generate_jwt() {
    $key = esc_attr(get_option('shift8_push_api_key'));
    $secret = esc_attr(get_option('shift8_push_api_secret'));
    $header = array(
        'typ' => 'JWT', 
        'alg' => 'HS256'
    );
    $payload = array(
        "iss" => $key,
        "exp" => 1496091964000,
    );
    $jwt = JWT::encode($payload, $secret);
    return $jwt;
}
// Build 
function shift8_push_get_import_frequency_options() {
    $import_frequency = array(
        //'shift8_push_minute' => 'Every minute',
        'hourly' => 'Hourly',
        'twicedaily' => 'Twice Daily',
        'daily' => 'Daily',
        'weekly' => 'Weekly'
    );
    return $import_frequency;
}


// Function to import webinar data
function shift8_push_import_webinars($webinar_data) {
    // Import counter
    $import_count = 0;
    // Obtain the title import filter
    $import_filter = (empty(esc_attr(get_option('shift8_push_filter_title'))) ? false : esc_attr(get_option('shift8_push_filter_title')));

    // WPML Force import to be english as the language is set manually
    if ( function_exists('icl_object_id') ) {
        global $sitepress; 
        $lang='en';
        $sitepress->switch_lang($lang);
    }

    if (is_array($webinar_data) && $webinar_data['webinars']) {
        foreach ($webinar_data['webinars'] as $webinar) {
            // If the filter is present and a match is found in the title, skip
            if ($import_filter && preg_match("/" . $import_filter . "/i", $webinar['topic'])) {
                continue;
            } else {
                // Check if the UUID exists already
                $args = array(  
                    'post_type' => 'shift8_push',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'suppress_filters' => true,
                    'meta_query'     => array(
                        array(
                            'key'       => '_post_shift8_push_id',
                            'value'     => sanitize_text_field($webinar['id']),
                            'compare'   => '='
                        )
                    ),
                    'order' => 'ASC', 
                );
                $query = new WP_Query ( $args );
                // If ID exists, move on
                if ($query->have_posts()) {
                    continue;
                } else {
                    // Create post object
                    $webinar_post = array(
                        'post_title'    => wp_strip_all_tags( $webinar['topic'] ),
                        'post_status'   => 'publish',
                        'post_type'     => 'shift8_push',
                        'post_author'   => 1,
                        //'post_date'     => wp_date(Carbon::create(sanitize_text_field( $webinar['start_time'] ))),
                    );

                    // Have to get the agenda text separately as the list webinar api query limits it to 250 characters
                    $webinar_data = shift8_push_webinar_data(sanitize_text_field($webinar['id']));
                    if (!$webinar_data['agenda']) { 
                        $webinar_data['agenda'] = shift8_push_wp_kses( $webinar['agenda'] );
                    }

                    // Adjust the start time and timezone
                    $webinar_datetime = Carbon::create(sanitize_text_field( $webinar['start_time']))->setTimezone('UTC');
                    $webinar_timezone = strtoupper(CarbonTimeZone::create(sanitize_text_field( $webinar['timezone'] ))->getAbbr());

                    // Insert the post into the database
                    $post_id = wp_insert_post( $webinar_post );
                    update_post_meta( $post_id, "_post_shift8_push_uuid", sanitize_text_field( $webinar['uuid']) );
                    update_post_meta( $post_id, "_post_shift8_push_id", sanitize_text_field( $webinar['id']) );
                    update_post_meta( $post_id, "_post_shift8_push_type", sanitize_text_field( $webinar['type']) );
                    update_post_meta( $post_id, "_post_shift8_push_start", wp_date($webinar_datetime->setTimezone(sanitize_text_field( $webinar['timezone'] ))) );
                    update_post_meta( $post_id, "_post_shift8_push_duration", sanitize_text_field( $webinar['duration'] ) );
                    update_post_meta( $post_id, "_post_shift8_push_timezone", sanitize_text_field( $webinar_timezone ) );
                    update_post_meta( $post_id, "_post_shift8_push_joinurl", $webinar_data['registration_url'] );
                    update_post_meta( $post_id, "_post_shift8_push_agenda_html", $webinar_data['agenda'] );
                    $import_count++;
                }
            }
        }      
    }
    return $import_count;
}

// Get the agenda info because it is truncated in the agenda list API query
function shift8_push_webinar_data($webinar_id) {
    $zoom_jwt_token = shift8_push_generate_jwt();

     // Set headers for WP Remote post
    $headers = array(
        'Content-type: application/json',
        'Authorization' => 'Bearer ' . $zoom_jwt_token,
    );

    // Use WP Remote Get to poll the zoom api 
    $response = wp_remote_get( S8ZOOM_API . '/v2/webinars/' . intval($webinar_id),
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
        // Pass the returned webinars to a function to handle the import
        return array(
            'agenda' => shift8_push_wp_kses(json_decode($response['body'])->agenda),
            'registration_url' => sanitize_url(json_decode($response['body'])->registration_url),
        );

    } else {
        return false;
    }
}

// Centralized function to filter HTML, specifically for the agenda details
function shift8_push_wp_kses($string) {
    $allowed_html = array(
        'a' => array(
            'href' => array(),
            'title' => array()
        ),
        'br' => array(),
        'em' => array(),
        'strong' => array(),
        'ul' => array(),
        'li' => array(),
        'ol' => array(),
        'b' => array(),
        'p' => array(),
    );
    return wp_kses($string, $allowed_html);
}