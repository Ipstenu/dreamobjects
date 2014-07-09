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

use Aws\S3\S3Client as AwsS3DHDOSET;

class DHDOSET {
    /**
     * Generates the settings page
     *
    */

    // Add Settings Pages
    public static function add_settings_page() {
        load_plugin_textdomain(dreamobjects, DHDO_PLUGIN_DIR . 'i18n', 'i18n');
        add_action('admin_init', array('DHDOSET', 'add_register_settings'));
        add_menu_page(__('DreamObjects Settings', 'dreamobjects'), __('DreamObjects', 'dreamobjects'), 'manage_options', 'dreamobjects-menu', array('DHDOSET', 'settings_page'), 'dashicons-backup' );
        
        if ( get_option('dh-do-key') && get_option('dh-do-secretkey') ) {
            add_submenu_page('dreamobjects-menu', __('Backups', 'dreamobjects'), __('Backups', 'dreamobjects'), 'manage_options', 'dreamobjects-menu-backup', array('DHDOSET', 'backup_page'));  
        }
    }

    // Define Settings Pages    
    public static function  settings_page() {
        include_once( DHDO_PLUGIN_DIR . '/admin/settings.php');// Main Settings
    }
    
    // This isn't used yet
    public static function  backup_page() {
        include_once( DHDO_PLUGIN_DIR . '/admin/backups.php'); // Backup Settings
    }

    // Register Settings (for forms etc)
    public static function add_register_settings() {

     // Keypair settings
        add_settings_section( 'keypair_id', __('DreamObjects Backups Access Settings', 'dreamobjects'), 'keypair_callback', 'dh-do-keypair_page' );
        
        register_setting( 'dh-do-keypair-settings','dh-do-key');
        add_settings_field( 'key_id', __('Access Key', 'dreamobjects'), 'key_callback', 'dh-do-keypair_page', 'keypair_id' );
        
        register_setting( 'dh-do-keypair-settings','dh-do-secretkey');
        add_settings_field( 'secretkey_id', __('Secret Key', 'dreamobjects'), 'secretkey_callback', 'dh-do-keypair_page', 'keypair_id' );

        function keypair_callback() { 
            echo '<p>'. __("Once you've configured your keypair here, you'll be able to use the features of this plugin.", dreamobjects).'</p>';
            echo '<p><div class="dashicons dashicons-shield"></div>'.__( "Once saved, your keys will not display again for your own security.", dreamobjects ).'</p>';
        }
    	function key_callback() {
        	echo '<input type="text" name="dh-do-key" value="'. get_option('dh-do-key') .'" class="regular-text" autocomplete="off"/>';
    	}
    	function secretkey_callback() {
        	echo '<input type="text" name="dh-do-secretkey" value="'. get_option('dh-do-secretkey') .'" class="regular-text" autocomplete="off" />';
    	}

     // Backup Settings
        add_settings_section( 'backuper_id', __('Settings', 'dreamobjects'), 'backuper_callback', 'dh-do-backuper_page' );
        
        register_setting( 'dh-do-backuper-settings','dh-do-bucket');
        add_settings_field( 'dh-do-bucket_id',  __('Bucket Name', 'dreamobjects'), 'backup_bucket_callback', 'dh-do-backuper_page', 'backuper_id' );

        if ( get_option('dh-do-bucket') && ( !get_option('dh-do-bucket') || (get_option('dh-do-bucket') != "XXXX") ) ) {
            register_setting( 'dh-do-backuper-settings','dh-do-backupsection');
            add_settings_field( 'dh-do-backupsection_id',  __('What to Backup', 'dreamobjects'), 'backup_what_callback', 'dh-do-backuper_page', 'backuper_id' );
            register_setting( 'dh-do-backuper-settings','dh-do-schedule');
            add_settings_field( 'dh-do-schedule_id',  __('Schedule', 'dreamobjects'), 'backup_sched_callback', 'dh-do-backuper_page', 'backuper_id' );
            register_setting( 'dh-do-backuper-settings','dh-do-retain');
            add_settings_field( 'dh-do-backupretain_id',  __('Backup Retention', 'dreamobjects'), 'backup_retain_callback', 'dh-do-backuper_page', 'backuper_id' );
        }
        
        function backuper_callback() { 
            echo 'Configure your site for backups by selecting your bucket, what you want to backup, and when.';
        }
        function backup_bucket_callback() {
        	$s3 = AwsS3DHDOSET::factory(array(
				'key'    => get_option('dh-do-key'),
			    'secret' => get_option('dh-do-secretkey'),
			    'base_url' => 'http://objects.dreamhost.com',
			));
 
            $buckets = $s3->listBuckets();
            
            ?> <select name="dh-do-bucket">
                    <option value="XXXX">(select a bucket)</option>
                    <?php foreach ( $buckets['Buckets'] as $bucket ) : ?>
                    <option <?php if ( $bucket['Name'] == get_option('dh-do-bucket') ) echo 'selected="selected"' ?> ><?php echo $bucket['Name'] ?></option>
                    <?php endforeach; ?>
                </select>
				<p class="description"><?php echo __('Select from pre-existing buckets.', dreamobjects); ?></p>
				<?php if ( get_option('dh-do-bucketup') && ( !get_option('dh-do-bucketup') || (get_option('dh-do-bucketup') != "XXXX") ) ) { 
    				$alreadyusing = sprintf(__('You are currently using the bucket "%s" for Uploads. While you can reuse this bucket, it would be best not to.', dreamobjects), get_option('dh-do-bucketup')  );
    				echo '<p class="description">' . $alreadyusing . '</p>';
                }
    	}

    	function backup_what_callback() {
        	$sections = get_option('dh-do-backupsection');
    		if ( !$sections ) {
    			$sections = array();
    		}
        	?><p><label for="dh-do-backupsection-files">
				<input <?php if ( in_array('files', $sections) ) echo 'checked="checked"' ?> type="checkbox" name="dh-do-backupsection[]" value="files" id="dh-do-backupsection-files" />
				<?php echo __('All Files', dreamobjects); ?>
				</label><br />
				<label for="dh-do-backupsection-database">
				<input <?php if ( in_array('database', $sections) ) echo 'checked="checked"' ?> type="checkbox" name="dh-do-backupsection[]" value="database" id="dh-do-backupsection-database" />
				<?php echo __('Database', dreamobjects); ?>
				</label><br />
				</p>
				<p class="description"><?php echo __('You can select portions of your site to backup.', dreamobjects); ?></p><?php
        }


    	function backup_sched_callback() {
    	
            ?><select name="dh-do-schedule">
				<?php foreach ( array('Disabled','Daily','Weekly','Monthly') as $s ) : ?>
				<option value="<?php echo strtolower($s) ?>" <?php if ( strtolower($s) == get_option('dh-do-schedule') ) echo 'selected="selected"' ?>><?php echo $s ?></option>
				<?php endforeach; ?>
				</select>
				<?php
                  $timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'dh-do-backup' ) ), get_option('date_format').' '.get_option('time_format') );
                  $nextbackup = sprintf(__('Next scheduled backup is at %s', dreamobjects), $timestamp );
            ?>
            <p class="description"><?php echo __('How often do you want to backup your files? Daily is recommended.', dreamobjects); ?></p>
            <?php if ( get_option('dh-do-schedule') != "disabled" && wp_next_scheduled('dh-do-backup') ) { ?>
            <p class="description"><?php echo $nextbackup; ?></p>
            <?php }
    	}
    	

    	function backup_retain_callback() {
            ?><select name="dh-do-retain">
				    <?php foreach ( array('1','2','5','10','15','30','60','90','all') as $s ) : ?>
				        <option value="<?php echo strtolower($s) ?>" <?php if ( strtolower($s) == get_option('dh-do-retain') ) echo 'selected="selected"' ?>><?php echo $s ?></option>
				    <?php endforeach; ?>
				</select>
				<p class="description"><?php echo __('How many many backups do you want to keep? 15 is recommended.', dreamobjects); ?></p>
				<p class="description"><div class="dashicons dashicons-info"></div> <?php echo __('DreamObjects charges you based on diskspace used. Setting to \'All\' will retain your backups forwever, however this can cost you a large sum of money over time. Please use cautiously!', dreamobjects); ?></p>
		<?php
    	}

    // Reset Settings
        register_setting( 'dh-do-reset-settings', 'dh-do-reset');
    // Logging Settings
        register_setting( 'dh-do-logging-settings', 'dh-do-logging');
    // Backup Bucket Settings
        register_setting( 'do-do-new-bucket-settings', 'dh-do-new-bucket');
    }
}