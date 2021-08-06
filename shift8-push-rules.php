<?php
/**
 * Shift8 Push Define rules
 *
 * Defined rules used throughout the plugin operations
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    die();
}

define( 'S8PUSH_FILE', 'shift8-push/shift8-push.php' );

if ( !defined( 'S8PUSH_DIR' ) )
    define( 'S8PUSH_DIR', realpath( dirname( __FILE__ ) ) );

if ( !defined( 'S8PUSH_APIBASE' ) )
    define( 'S8PUSH_APIBASE', '/wp-json/wp/v2/' );

if ( !defined( 'S8PUSH_APICUSTOM' ) )
    define ( 'S8PUSH_APICUSTOM', '/wp-json/shift8/v1/' );

if ( !defined( 'S8PUSH_POSTTYPES' ) )
    define ( 'S8PUSH_POSTTYPES', array( 'page', 'post', 'traffick-stop' ) );
