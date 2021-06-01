<?php
/**
 * Plugin Name: Shift8 Push
 * Plugin URI: n/a
 * Description: Plugin that allows you to push single posts and pages to an external site
 * Version: 0.0.1
 * Author: Shift8 Web 
 * Author URI: https://www.shift8web.ca
 * License: GPLv3
 */

// Composer dependencies
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
    require __DIR__ . '/vendor/autoload.php';
}

require_once(plugin_dir_path(__FILE__).'shift8-push-rules.php' );
require_once(plugin_dir_path(__FILE__).'components/enqueuing.php' );
require_once(plugin_dir_path(__FILE__).'components/settings.php' );
require_once(plugin_dir_path(__FILE__).'components/functions.php' );

// Admin welcome page
if (!function_exists('shift8_main_page')) {
	function shift8_main_page() {
	?>
	<div class="wrap">
	<h2>Shift8 Plugins</h2>
	Shift8 is a Toronto based web development and design company. We specialize in Wordpress development and love to contribute back to the Wordpress community whenever we can! You can see more about us by visiting <a href="https://www.shift8web.ca" target="_new">our website</a>.
	</div>
	<?php
	}
}

// Admin settings page
function shift8_push_settings_page() {
?>
<div class="wrap">
<h2>Shift8 Push Settings</h2>
<?php if (is_admin()) { 
$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'core_settings';
$plugin_data = get_plugin_data( __FILE__ );
$plugin_name = $plugin_data['TextDomain'];
    ?>
<h2 class="nav-tab-wrapper">
    <a href="?page=<?php echo $plugin_name; ?>%2Fcomponents%2Fsettings.php%2Fcustom&tab=core_settings" class="nav-tab <?php echo $active_tab == 'core_settings' ? 'nav-tab-active' : ''; ?>">Core Settings</a>
    <a href="?page=<?php echo $plugin_name; ?>%2Fcomponents%2Fsettings.php%2Fcustom&tab=support_options" class="nav-tab <?php echo $active_tab == 'support_options' ? 'nav-tab-active' : ''; ?>">Support</a>
</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'shift8-push-settings-group' ); ?>
    <?php do_settings_sections( 'shift8-push-settings-group' ); ?>
    <?php
	$locations = get_theme_mod( 'nav_menu_locations' );
	if (!empty($locations)) {
		foreach ($locations as $locationId => $menuValue) {
			if (has_nav_menu($locationId)) {
				$shift8_push_menu = $locationId;
			}
		}
	}

	?>
    <table class="form-table shift8-push-table">
    <tbody class="<?php echo $active_tab == 'core_settings' ? 'shift8-push-admin-tab-active' : 'shift8-push-admin-tab-inactive'; ?>">
	<tr valign="top">
    <th scope="row">Core Settings</th>
    <?php var_dump(base64_encode('administrator:' .  shift8_push_decrypt(get_option('shift8_push_application_password')))); 
    var_dump(shift8_push_decrypt(get_option('shift8_push_application_password'))); ?>
    <td><span id="shift8-push-notice">
    <?php 
    settings_errors('shift8_push_url');
    settings_errors('shift8_push_api_key');
    settings_errors('shift8_push_api_secret');
    settings_errors('shift8_push_user_email');
    settings_errors('shift8_push_import_frequency');
    settings_errors('shift8_push_filter_title');
    ?>
    </span></td>
	</tr>
    <tr valign="top">
    <th scope="row">Enable Shift8 Push : </th>
    <td>
    <?php
    if (esc_attr( get_option('shift8_push_enabled') ) == 'on') {
        $enabled_checked = "checked";
    } else {
        $enabled_checked = "";
    }
    ?>
    <label class="switch">
    <input type="checkbox" name="shift8_push_enabled" <?php echo $enabled_checked; ?>>
    <div class="slider round"></div>
    </label>
    </td>
    </tr>
    <tr valign="top">
    <th scope="row">Shift8 Push Source URL : </th>
    <td><input type="text" id="shift8_push_source_url_field" name="shift8_push_src_url" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_push_src_url'))) ? '' : esc_attr(get_option('shift8_push_src_url'))); ?>">
    <div class="shift8-push-tooltip"><span class="dashicons dashicons-editor-help"></span>
        <span class="shift8-push-tooltiptext">This is the URL of the source (i.e. staging) server</span>
    </div>
    </td>
    </tr>
    <tr valign="top">
    <th scope="row">Shift8 Push Destination URL : </th>
    <td><input type="text" id="shift8_push_destination_url_field" name="shift8_push_dst_url" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_push_dst_url'))) ? '' : esc_attr(get_option('shift8_push_dst_url'))); ?>">
    <div class="shift8-push-tooltip"><span class="dashicons dashicons-editor-help"></span>
        <span class="shift8-push-tooltiptext">This is the URL of the destination (i.e. production) server</span>
    </div>
    </td>
    </tr>
	<tr valign="top">
    <th scope="row">Shift8 Push Application Password : </th>
    <td><input type="password" id="shift8_push_application_password" name="shift8_push_application_password" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_push_application_password'))) ? '' : shift8_push_decrypt(esc_attr(get_option('shift8_push_application_password')))); ?>">
    <div class="shift8-push-tooltip"><span class="dashicons dashicons-editor-help"></span>
        <span class="shift8-push-tooltiptext">This must be generated on the production site</span>
    </div>
    </td>
	</tr>
    <tr valign="top">
    <td width="226px"><div class="shift8-push-spinner"></div></td>
    <td>
    <?php if (empty(esc_attr(get_option('shift8_push_application_password')))) { ?>
    <div class="shift8-push-prereg-note">Note : You need to register an application password from the production site first to get the above values. Once you save the application password, a check button will appear.</div>
    <?php } ?>
    <ul class="shift8-push-controls">
    <?php if (!empty(esc_attr(get_option('shift8_push_application_password')))) { ?>
    <li>
    <div class="shift8-push-button-container">
    <a id="shift8-push-check" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=shift8_push_push'), 'process'); ?>"><button class="shift8-push-button shift8-push-button-check">Test</button></a>
    </div>
    </li>
    <?php } ?>
    </ul>
    <div class="shift8-push-response">
    </div>
    </td>
    </tr>
    </tbody>
    <!-- SUPPORT TAB -->
    <tbody class="<?php echo $active_tab == 'support_options' ? 'shift8-push-admin-tab-active' : 'shift8-push-admin-tab-inactive'; ?>">
    <tr valign="top">
    <th scope="row">Support</th>
    </tr>
    <tr valign="top">
    <td style="width:500px;">If you are experiencing difficulties, you can receive support if you Visit the <a href="https://wordpress.org/support/plugin/shift8-push/" target="_new">Shift8 zoom Wordpress support page</a> and post your question there.<Br /><Br />
    <strong>Debug Info</strong><br /><br />
    Providing the debug information below to the Shift8 zoom support team may be helpful in them assisting in diagnosing any issues you may be having. <br /><br />
    <div class="shift8-push-button-container">
    </div><button class="shift8-push-button shift8-push-button-copyclipboard" id="button1" onclick="Shift8ZoomCopyToClipboard('shift8zoom-debug')">Copy info below to clipboard</button>
    <br /><br />
    <script type="text/javascript">
        function showDetails(id) {
            document.getElementById(id).style.display = 'block';
        }
        function hideDetails(id) {
            document.getElementById(id).style.display = 'none';
        }
    </script>
    <div class="wrap">
        <div class="postbox" id="shift8push-debug">
            <h2><?php _e('Shift8 Push Debug Info'); ?></h2>
            <p><?php echo shift8_push_debug_version_check(); ?></p>
        </div>
    </div>
    </td>
    </tr>
    </tbody>
    </table>
    <?php 
    if ($active_tab !== 'support_options') {
        submit_button(); 
    }
    ?>
    </form>
</div>
<?php 
	} // is_admin
}


