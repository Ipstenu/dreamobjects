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

global $dreamobjects_db_version;
$dreamobjects_db_version = '4.0.2';

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
 
// function to create the DB / Options / Defaults					
function dreamobjects_install() {
	global $wpdb, $dreamobjects_db_version;
   	
	$dreamobjects_table_name = $wpdb->prefix . 'dreamobjects_backup_log';
   	
	// create the database table
	if( $wpdb->get_var("show tables like '$dreamobjects_table_name'") != $dreamobjects_table_name ) {

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $dreamobjects_table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			filename tinytext NOT NULL,
			text text NOT NULL,
			frequency tinytext NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";
 
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	} 
}
// run the install scripts upon plugin activation
register_activation_hook( __FILE__ , 'dreamobjects_install' );

// Update check
function dreamobjects_update_db_check() {
    global $dreamobjects_db_version;
    if ( !get_option('dh-do-version') || get_option( 'dh-do-version' ) != $dreamobjects_db_version ) {
        dreamobjects_install();
        update_option( 'dh-do-version', $dreamobjects_db_version );
        // NB - If there's ever a need to update the requirements (see /lib/defines), do it here.
    }
}
add_action( 'plugins_loaded', 'dreamobjects_update_db_check' );