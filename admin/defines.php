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

define( 'DHDO', true);
define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

defined('PLUGIN_DIR') || define('PLUGIN_DIR', realpath(dirname(__FILE__) . '/..'));

define( 'PLUGIN_VERSION', '1.0-beta' ); 


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

add_action('init', array('DHDO', 'init'));
add_action('admin_print_styles', array('DHDO', 'stylesheet'));

if ( $_GET['page'] == 'dh-do-backup' || $_GET['page'] == 'dh-do-backupnow' ) {
	wp_enqueue_script('jquery');
}

if ( defined('WP_CLI') && WP_CLI ) {
	include( PLUGIN_DIR . '/lib/wp-cli.php' );
}