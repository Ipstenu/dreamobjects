<?php
/*
    This file is part of DreamObjects, a plugin for WordPress.

    DreamObjects is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    DreamObjects is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with WordPress.  If not, see <http://www.gnu.org/licenses/>.

*/

if (!defined('ABSPATH')) {
    die();
}

if (!defined('dreamobjects')) {
  define('dreamobjects', 'dreamobjects');
}

define( 'DHDO', true);
if (!defined('WP_CONTENT_URL')) {
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
}
if (!defined('WP_CONTENT_DIR')) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

defined('PLUGIN_DIR') || define('PLUGIN_DIR', realpath(dirname(__FILE__) . '/..'));

define( 'PLUGIN_VERSION', '1.0-beta' ); 


add_theme_support( 'hybrid-core-shortcodes' );

// Shortcode
if ( get_option('dh-do-bucketup') && (get_option('dh-do-bucketup') != "XXXX") && !is_null(get_option('dh-do-bucketup')) ) {
    include_once( PLUGIN_DIR . '/lib/shortcode.php');
}
// The Help Screen
function dreamhost_dreamobjects_plugin_help() {
	include_once( PLUGIN_DIR . '/admin/help.php' );
}
add_action('contextual_help', 'dreamhost_dreamobjects_plugin_help', 10, 3);

// Filter Cron
add_filter('cron_schedules', array('DHDO', 'cron_schedules'));

// Etc
add_action('admin_menu', array('DHDO', 'add_settings_page'));

add_action('dh-do-backup', array('DHDO', 'backup'));
add_action('dh-do-backupnow', array('DHDO', 'backup'));
add_action('dh-do-upload', array('DHDO', 'uploader'));

add_action('init', array('DHDO', 'init'));
add_action('admin_print_styles', array('DHDO', 'stylesheet'));


if ( isset($_GET['page']) && ( $_GET['page'] == 'dh-do-backup' || $_GET['page'] == 'dh-do-backupnow' ) ) {
	wp_enqueue_script('jquery');
}

if ( defined('WP_CLI') && WP_CLI ) {
	include( PLUGIN_DIR . '/lib/wp-cli.php' );
}