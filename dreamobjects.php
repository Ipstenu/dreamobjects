<?php

/*
Plugin Name: DreamObjects Connection
Plugin URI: https://github.com/Ipstenu/dreamobjects
Description: Connect your WordPress install to your DreamHost DreamObjects buckets.
Version: 2.3
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

require_once dirname(__FILE__) . '/lib/defines.php';

class DHDO {
    // INIT - hooking into this lets us run things when a page is hit.

    function init() {
        // SCHEDULER
		if ( isset($_POST['dh-do-schedule']) && current_user_can('manage_options') ) {
			wp_clear_scheduled_hook('dh-do-backup');
			if ( $_POST['dh-do-schedule'] != 'disabled' ) {
				wp_schedule_event(current_time('timestamp')+86400, $_POST['dh-do-schedule'], 'dh-do-backup');
				$nextbackup = sprintf(__('Next backup: %s', dreamobjects), date_i18n('F j, Y h:i a', current_time('timestamp')+86400) );
				DHDO::logger('Scheduled '.$_POST['dh-do-schedule'].' backup. ' .$nextbackup);
			}
		}

		// CREATE NEW BUCKET
        if ( current_user_can('manage_options') && isset($_POST['do-do-new-bucket']) && !empty($_POST['do-do-new-bucket']) ) {
        
            check_admin_referer( 'update-options');
	        include_once 'lib/S3.php';
	        $_POST['do-do-new-bucket'] = strtolower($_POST['do-do-new-bucket']);
	        $s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey'));
	        if ($s3->putBucket($_POST['do-do-new-bucket']))
	           {add_action('admin_notices', array('DHDO','newBucketMessage'));}
	        else
	           {add_action('admin_notices', array('DHDO','newBucketError'));}
	       }

		// RESET
        if ( current_user_can('manage_options') && isset($_POST['dhdo-reset']) && $_POST['dhdo-reset'] == 'Y'  ) {
        
            check_admin_referer( 'update-options');
            delete_option( 'dh-do-backupsection' );
            delete_option( 'dh-do-bucket' );
            delete_option( 'dh-do-bucketcdn' );
            delete_option( 'dh-do-bucketup' );
            delete_option( 'dh-do-cdn' );
            delete_option( 'dh-do-key' );
            delete_option( 'dh-do-schedule' );
            delete_option( 'dh-do-secretkey' );
            delete_option( 'dh-do-section' );
            delete_option( 'dh-do-uploader' );
            delete_option( 'dh-do-uploadview' );
            delete_option( 'dh-do-logging' );
            DHDO::logger('reset');
	       }

	    // LOGGER: Wipe logger if blank
	    if ( current_user_can('manage_options') && isset($_POST['dhdo-logchange']) && $_POST['dhdo-logchange'] == 'Y' ) {
            check_admin_referer( 'update-options');
            if ( !isset($_POST['dh-do-logging'])) {
                DHDO::logger('reset');
            }
        }       


        // UPLOADER
        if( current_user_can('manage_options') && isset($_POST['Submit']) && isset($_FILES['theFile']) && $_GET['page'] ==
'dreamobjects-menu-uploader' ){

          check_admin_referer( 'dhdo-uploader');

		  $fileName = sanitize_file_name( $_FILES['theFile']['name']);
		  $fileTempName = $_FILES['theFile']['tmp_name'];
		  $fileType = $_FILES['theFile']['type'];
		  		  
		  require_once('lib/S3.php');
		  $s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey'));
		  if ( get_option('dh-do-uploadpub') != 1 )
		      {if ($s3->putObjectFile($fileTempName, get_option('dh-do-bucketup'), $fileName, S3::ACL_PUBLIC_READ, array(),$fileType))
    		      {add_action('admin_notices', array('DHDO','uploaderMessage'));}
    		  else
		          {add_action('admin_notices', array('DHDO','uploaderError'));}   
		      }
		  else
		      {if ($s3->putObjectFile($fileTempName, get_option('dh-do-bucketup'), $fileName, S3::ACL_PRIVATE, array(),$fileType))
    		      {add_action('admin_notices', array('DHDO','uploaderMessage'));}
    		  else
		          {add_action('admin_notices', array('DHDO','uploaderError'));}   
		      }
        }
        
        // Update messgae
		if ( isset($_GET['settings-updated']) && ( $_GET['page'] == 'dreamobjects-menu' || $_GET['page'] == 'dreamobjects-menu-backup' || $_GET['page'] == 'dreamobjects-menu-uploader' ) ) add_action('admin_notices', array('DHDO','updateMessage'));

        // Backup Now
        if ( current_user_can('manage_options') &&  isset($_GET['backup-now']) && $_GET['page'] == 'dreamobjects-menu-backup' ) {
            check_admin_referer( 'dhdo-backupnow' );
            wp_schedule_single_event( current_time('timestamp')+60, 'dh-do-backupnow');
            add_action('admin_notices', array('DHDO','backupMessage'));
            DHDO::logger('Scheduled ASAP backup.');
        }
        
        // Backup Message
        if ( wp_next_scheduled( 'dh-do-backupnow' ) && ( $_GET['page'] == 'dreamobjects-menu' || $_GET['page'] == 'dreamobjects-menu-backup' ) ) {
            add_action('admin_notices', array('DHDO','backupMessage'));
        }
	}

    // Messages (used by INIT)
	function updateMessage() {
		echo "<div id='message' class='updated fade'><p><strong>".__('Options Updated!', dreamobjects)."</strong></p></div>";
		}

	function backupMessage() {
	   $timestamp = wp_next_scheduled( 'dh-do-backupnow' );
	   $string = sprintf( __('You have an ad-hoc backup scheduled for today at %s. Do not hit refresh on the backups page. You may continue using your site per usual, the backup will run behind the scenes.', dreamobjects), date_i18n('h:i a', $timestamp) );
	   echo "<div id='message' class='updated fade'><p><strong>".$string."</strong></p></div>";
		}

	function uploaderMessage() {
		echo "<div id='message' class='updated fade'><p><strong>".__('Your file was successfully uploaded.', dreamobjects)."</strong></p></div>";
		}
		
	function uploaderError() {
		echo "<div id='message' class='error fade'><p><strong>".__('Error: Something went wrong while uploading your file.', dreamobjects)."</strong></p></div>";
		}

	function newBucketMessage() {
		echo "<div id='message' class='updated fade'><p><strong>".__('Your new bucket has been created.', dreamobjects)."</strong></p></div>";
		}
		
	function newBucketError() {
		echo "<div id='message' class='error fade'><p><strong>".__('Error: Unable to create bucket (it may already exist and/or be owned by someone else)', dreamobjects)."</strong></p></div>";
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
	    
	    if ( get_option('dh-do-key') && get_option('dh-do-secretkey') ) {
 		     $dreamhost_dreamobjects_backups_page = add_submenu_page('dreamobjects-menu', __('Backups'), __('Backups'), 'manage_options', 'dreamobjects-menu-backup', array('DHDO', 'backup_page'));
 		     $dreamhost_dreamobjects_uploader_page = add_submenu_page('dreamobjects-menu', __('Uploader'), __('Uploader'), 'upload_files', 'dreamobjects-menu-uploader', array('DHDO', 'uploader_page'));
 		     // $dreamhost_dreamobjects_cdn_page = add_submenu_page('dreamobjects-menu', __('CDN'), __('CDN'), 'manage_options', 'dreamobjects-menu-cdn', array('DHDO', 'cdn_page'));   	    
	    }
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
		include_once( PLUGIN_DIR . '/admin/settings.php');// Main Settings
	}
	
	function backup_page() {
    	include_once( PLUGIN_DIR . '/admin/backups.php'); // Backup Settings
	}
	
	function cdn_page() {
        include_once( PLUGIN_DIR . '/admin/cdn.php'); // CDN Settings
	}

	function uploader_page() {
    	include_once( PLUGIN_DIR . '/admin/uploader.php'); // Upload Settings
	}

	/**
	 * Logging function
	 *
	 */

    // Acutal logging function
    function logger($msg) {
    
    if ( get_option('dh-do-logging') == 'on' ) {
           $file = PLUGIN_DIR."/debug.txt"; 
    	   if ($msg == "reset") {
        	   $fd = fopen($file, "w+");
        	   $str = "";
    	   }
    	   elseif ( get_option('dh-do-logging') == 'on') {	
        	   $fd = fopen($file, "a");
        	   $str = "[" . date("Y/m/d h:i:s", current_time('timestamp')) . "] " . $msg . "\n";
           }
       	   fwrite($fd, $str);
       	   fclose($fd);
   	   }
	}

	/**
	 * Generate Backups and the functions needed for that to run
	 *
	 */
		
	// Scan folders to collect all the filenames
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
		DHDO::logger('Scanned folders and files to generate list for backup.');
	}
	
	// The actual backup
	function backup() {
		global $wpdb;
		require_once('lib/S3.php');
		require_once(ABSPATH . '/wp-admin/includes/class-pclzip.php');

		// Pull in data for what to backup
		$sections = get_option('dh-do-backupsection');
		if ( !$sections ) {
			$sections = array();
		}
		
		$file = WP_CONTENT_DIR . '/upgrade/dreamobject-backups.zip';
		$zip = new PclZip($file);
		$backups = array();

		// All me files!
		if ( in_array('files', $sections) ) {
		    $backups = array_merge($backups, DHDO::rscandir(ABSPATH));
		    DHDO::logger('List of files added to the zip.');
		} 
		
		// And me DB!
		if ( in_array('database', $sections) ) {
		
			$tables = $wpdb->get_col("SHOW TABLES LIKE '" . $wpdb->prefix . "%'");
			$result = shell_exec('mysqldump --single-transaction -h ' . DB_HOST . ' -u ' . DB_USER . ' --password="' . DB_PASSWORD . '" ' . DB_NAME . ' ' . implode(' ', $tables) . ' > ' .  WP_CONTENT_DIR . '/upgrade/dreamobject-db-backup.sql');
			$sqlfile = WP_CONTENT_DIR . '/upgrade/dreamobject-db-backup.sql';
			$sqlsize = size_format( @filesize($sqlfile) );
			DHDO::logger('SQL file created ('. $sqlsize .').');
			$backups[] = $sqlfile;
			DHDO::logger('SQL filename added to zip.');
		}
		
		if ( !empty($backups) ) {
			$zip->create($backups, '', ABSPATH);
			$zipsize = size_format( @filesize($file) );
			DHDO::logger('Zip generated ('. $zipsize .').');
			
			// Upload
			$s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey')); 
			$upload = $s3->inputFile($file);
			if ($s3->putObject($upload, get_option('dh-do-bucket'), next(explode('//', home_url())) . '/' . date_i18n('Y-m-d-His', current_time('timestamp')) . '.zip') ) {
    			DHDO::logger('Copying backup to DreamObjects.');
            } else {
                DHDO::logger('File failed to copy to DreamObjects.');
            }

			// Cleanup
			@unlink($file);
			DHDO::logger('Deleting zip.');
			@unlink(WP_CONTENT_DIR . '/upgrade/dreamobject-db-backup.sql');
			DHDO::logger('Deleting SQL dump.');
		}
		
		
		// Cleanup Old Backups
		if ( get_option('dh-do-retain') && get_option('dh-do-retain') != 'all' ) {
		    $num_backups = get_option('dh-do-retain');
		    
		    $s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey'));
            if (($backups = $s3->getBucket(get_option('dh-do-bucket'), next(explode('//', home_url())) ) ) !== false) {
                krsort($backups);
                $count = 0;
                foreach ($backups as $object) {
                    if ( ++$count > $num_backups ) {
                        $s3->deleteObject(get_option('dh-do-bucket'), $object['name']);
                        DHDO::logger('Removed backup '. $object['name'] .' from DreamObjects, per user retention choice.');
                    }    
                }
            }
		}
	}
	function cron_schedules($schedules) {
		$schedules['daily'] = array('interval'=>86400, 'display' => 'Once Daily');
		$schedules['weekly'] = array('interval'=>604800, 'display' => 'Once Weekly');
		$schedules['monthly'] = array('interval'=>2592000, 'display' => 'Once Monthly');
		return $schedules;
	}
}