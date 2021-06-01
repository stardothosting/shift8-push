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

