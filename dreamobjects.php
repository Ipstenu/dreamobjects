<?php

/*
Plugin Name: DreamObjects
Plugin URI: https://github.com/Ipstenu/dreamobjects
Description: Integrate your WordPress install with DreamHost DreamObjects
Version: 1.1
Author: Mika Epstein
Author URI: http://ipstenu.org/

Copyright 2012 Mika Epstein (email: ipstenu@ipstenu.org)

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

/**
 * @package dh-do-backups
 */

require_once dirname(__FILE__) . '/admin/defines.php';
	
class DHDO {
    // INIT - hooking into this lets us run things when a page is hit.

    function init() {
		if ( isset($_POST['dh-do-schedule']) ) {
			wp_clear_scheduled_hook('dh-do-backup');
			if ( $_POST['dh-do-schedule'] != 'disabled' ) {
				wp_schedule_event(time(), $_POST['dh-do-schedule'], 'dh-do-backup'); 
			}
		}

		if ( get_option('dh-do-secretkey') && get_option('dh-do-key') && ( !get_option('dh-do-bucket') || (get_option('dh-do-bucket') == "XXXX") ) && $_GET['page'] ==
'dreamobjects-menu-backup' ) add_action('admin_notices', array('DHDO','newBucketWarning'));

		if ( isset($_GET['settings-updated']) && ( $_GET['page'] ==
'dreamobjects-menu' || $_GET['page'] ==
'dreamobjects-menu-backup' ) ) add_action('admin_notices', array('DHDO','updateMessage'));

        if ( isset($_GET['backup-now']) && $_GET['page'] == 'dreamobjects-menu-backup' ) {
            wp_schedule_single_event( time()+60, 'dh-do-backupnow');
            add_action('admin_notices', array('DHDO','backupMessage'));
        }
        
        if ( wp_next_scheduled( 'dh-do-backupnow' ) && ( $_GET['page'] ==
'dreamobjects-menu' || $_GET['page'] ==
'dreamobjects-menu-backup' ) ) {
            add_action('admin_notices', array('DHDO','backupMessage'));
        }
	}

    // Messages (used by INIT)
	function updateMessage() {
		echo "<div id='message' class='updated fade'><p><strong>".__('Options Updated!', dreamobjects)."</strong></p></div>";
		}
	function backupMessage() {
	   $timestamp = wp_next_scheduled( 'dh-do-backupnow' );
	   $string = sprintf( __('You have an ad-hoc backup scheduled for today at %s (time based on WP time/date settings).', dreamobjects), get_date_from_gmt( date('Y-m-d H:i:s', $timestamp) , 'h:i a' ) );
	   echo "<div id='message' class='updated fade'><p><strong>".$string."</strong></p></div>";
		}


	// Return the filesystem path that the plugin lives in.
	function getPath() {
		return dirname(__FILE__) . '/';
	}
	
	// Returns the URL of the plugin's folder.
	function getURL() {
		return WP_CONTENT_URL.'/plugins/'.basename(dirname(__FILE__)) . '/';
	}

     // Sets up the settings page
	function add_settings_page() {
		load_plugin_textdomain(dreamobjects, DHDO::getPath() . 'i18n');

		global $dreamhost_dreamobjects_settings_page, $dreamhost_dreamobjects_backups_page;
	    $dreamhost_dreamobjects_settings_page = add_menu_page(__('DreamObjects Settings'), __('DreamObjects'), 'manage_options', 'dreamobjects-menu', array('DHDO', 'settings_page'), plugins_url('dreamobjects/images/dreamobj-color.png'));
		$dreamhost_dreamobjects_backups_page = add_submenu_page('dreamobjects-menu', __('Backups'), __('Backups'), 'manage_options', 'dreamobjects-menu-backup', array('DHDO', 'backup_page'));
		// $dreamhost_dreamobjects_cdn_page = add_submenu_page('dreamobjects-menu', __('CDN'), __('CDN'), 'manage_options', 'dreamobjects-menu-cdn', array('DHDO', 'cdn_page'));
	}

	// And now styles
    function stylesheet() {
        wp_register_style( 'dreamobj-style', plugins_url('dreamobjects.css?'. PLUGIN_VERSION, __FILE__) );
        wp_enqueue_style( 'dreamobj-style' );
    }

	
	/**
	 * Generates the settings page
	 *
	 */
	 
	function settings_page() {
	   // Main Settings
		include_once( PLUGIN_DIR . '/admin/settings.php');
	}
	
	function backup_page() {
	   // Backup Settings
    	include_once( PLUGIN_DIR . '/admin/backups.php');
	}
	
	function cdn_page() {
	   // CDN Settings
    	include_once( PLUGIN_DIR . '/admin/cdn.php');
	}
	
	
	function rscandir($base='') {
		$data = array_diff(scandir($base), array('.', '..'));
	
		$subs = array();
		foreach($data as $key => $value) :
			if ( is_dir($base . '/' . $value) ) :
				unset($data[$key]);
				$subs[] = DHDO::rscandir($base . '/' . $value);
			elseif ( is_file($base . '/' . $value) ) :
				$data[$key] = $base . '/' . $value;
			endif;
		endforeach;
	
		foreach ( $subs as $sub ) {
			$data = array_merge($data, $sub);
		}
		return $data;
	}
	
	function backup() {
		global $wpdb;
		require_once('lib/S3.php');
		require_once(ABSPATH . '/wp-admin/includes/class-pclzip.php');

		$sections = get_option('dh-do-backupsection');
		if ( !$sections ) {
			$sections = array();
		}
		
		$file = WP_CONTENT_DIR . '/upgrade/dreamobject-backups.zip';
		$zip = new PclZip($file);
		$backups = array();

		// All me files!
		if ( in_array('files', $sections) ) $backups = array_merge($backups, DHDO::rscandir(ABSPATH));
		
		// And me DB!
		if ( in_array('database', $sections) ) {
		
			$tables = $wpdb->get_col("SHOW TABLES LIKE '" . $wpdb->prefix . "%'");
			$result = shell_exec('mysqldump --single-transaction -h ' . DB_HOST . ' -u ' . DB_USER . ' --password="' . DB_PASSWORD . '" ' . DB_NAME . ' ' . implode(' ', $tables) . ' > ' .  WP_CONTENT_DIR . '/upgrade/dreamobject-db-backup.sql');
			$backups[] = WP_CONTENT_DIR . '/upgrade/dreamobject-db-backup.sql';
		}
		
		if ( !empty($backups) ) {
			$zip->create($backups, '', ABSPATH);
			
			$s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey')); 
			$upload = $s3->inputFile($file);
			$s3->putObject($upload, get_option('dh-do-bucket'), next(explode('//', get_bloginfo('siteurl'))) . '/' . date('Y-m-d-His') . '.zip');
			@unlink($file);
			@unlink(WP_CONTENT_DIR . '/upgrade/dreamobject-db-backup.sql');
		}
	}
	
	function cron_schedules($schedules) {
		$schedules['weekly'] = array('interval'=>604800, 'display' => 'Once Weekly');
		$schedules['monthly'] = array('interval'=>2592000, 'display' => 'Once Monthly');
		return $schedules;
	}
}