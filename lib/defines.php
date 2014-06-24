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

// Set up defaults
define( 'DHDO', true);
defined( 'DHDO_PLUGIN_DIR') || define('DHDO_PLUGIN_DIR', realpath(dirname(__FILE__) . '/..'));

// Standard content folder defines.
if ( ! defined( 'WP_CONTENT_DIR' ) )  define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

// Setting Options
if ( !defined('dreamobjects')) {define('dreamobjects','dreamobjects');} // Translation
if ( !get_option('dh-do-key')) {update_option( 'dh-do-key', '' );}
if ( !get_option('dh-do-secretkey')) {update_option( 'dh-do-secretkey', '' );}
if ( !get_option('dh-do-bucket')) {update_option( 'dh-do-bucket', 'XXXX' );}
if ( !get_option('dh-do-schedule')) {update_option( 'dh-do-schedule', 'disabled' );}
if ( !get_option('dh-do-backupsection')) {update_option( 'dh-do-backupsection', '' );}
if ( !get_option('dh-do-retain')) {update_option( 'dh-do-retain', '5' );}
if ( !get_option('dh-do-logging')) {update_option( 'dh-do-logging', 'off' );}
//if ( !get_option('dh-do-boto')) {update_option( 'dh-do-boto', 'no' );}

// For removed features
if ( get_option('dh-do-debugging')) { delete_option( 'dh-do-debugging'); }
if ( get_option('dh-do-bucketup')) { delete_option( 'dh-do-bucketup' ); }
if ( get_option('dh-do-uploader')) { delete_option( 'dh-do-uploader' ); }
if ( get_option('dh-do-uploadview')) { delete_option( 'dh-do-uploadview' ); }

// The Help Screen
function dreamhost_dreamobjects_plugin_help() {
	include_once( DHDO_PLUGIN_DIR . '/admin/help.php' );
}
add_action('contextual_help', 'dreamhost_dreamobjects_plugin_help', 10, 3);

// Filter Cron
add_filter('cron_schedules', array('DHDO', 'cron_schedules'));

// Etc
add_action('admin_menu', array('DHDOSET', 'add_settings_page'));
add_action('dh-do-backup', array('DHDO', 'backup'));
add_action('dh-do-backupnow', array('DHDO', 'backup'));
add_action('init', array('DHDO', 'init'));

if ( isset($_GET['page']) && ( $_GET['page'] == 'dh-do-backup' || $_GET['page'] == 'dh-do-backupnow' ) ) {
	wp_enqueue_script('jquery');
}