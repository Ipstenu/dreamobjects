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

use Aws\S3\S3Client;

class DHDOSET {

	static function get_client() {
		if ( !get_option('dh-do-key') || !get_option('dh-do-secretkey') ) {
			return new WP_Error( 'access_keys_missing', sprintf( __( '<div class="dashicons dashicons-no"></div> Please <a href="%s">set your access keys</a> first.', 'dreamobjects' ), 'admin.php?page=dreamobjects-menu' ) );
		}

        $s3config = array(
		    'key'      => get_option('dh-do-key'),
		    'secret'   => get_option('dh-do-secretkey'),
		    'base_url' => 'https://'.get_option('dh-do-hostname'),
		);

		try {
			$s3 = S3Client::factory( $s3config );
		} catch ( \Aws\S3\Exception\S3Exception $e) {
		    echo $e->getAwsErrorCode() . "\n";
		    echo $e->getMessage() . "\n";
		}
		return $s3;
	}

	static function get_buckets() {
		try {
			$result = DHDOSET::get_client()->listBuckets();
		}
		catch ( Exception $e ) {
			return new WP_Error( 'exception', $e->getMessage() );
		}
		return $result;
	}
	
	static function get_sections() {
		$sections = array(
			'files'    => __('All Files', 'dreamobjects'),
			'database' => __('Database', 'dreamobjects')
		);
		return $sections;
	}
	static function get_schedule() {
		$schedule = array(
			'disabled' => __('Disabled', 'dreamobjects'),
			'daily'    => __('Daily', 'dreamobjects'),
			'weekly'   => __('Weekly', 'dreamobjects'),
			'monthly'  => __('Monthly', 'dreamobjects')
		);
		return $schedule;
	}
	static function get_retain() {
		$retain = array('1','2','5','10','15','30','60','90','all');
		return $retain;
	}
	static function get_notify() {
		$notify = array(
			'disabled' => __('Disabled', 'dreamobjects'),
			'success'  => __('Success', 'dreamobjects'),
			'failure'  => __('Failure', 'dreamobjects'),
			'all'      => __('All', 'dreamobjects')
		);
		return $notify;
	}

    /**
     * Generates the settings page
     *
    */
    // Main Settings Pages
    public static function add_settings_page() {
        add_action('admin_init', array('DHDOSET', 'add_register_settings'));
        add_menu_page(__('DreamObjects Settings', 'dreamobjects'), __('DreamObjects', 'dreamobjects'), 'manage_options', 'dreamobjects-menu', array('DHDOSET', 'settings_page'), 'dashicons-backup' );
        
        if ( get_option('dh-do-key') && get_option('dh-do-secretkey') ) {
            add_submenu_page('dreamobjects-menu', __('Backups', 'dreamobjects'), __('Backups', 'dreamobjects'), 'manage_options', 'dreamobjects-menu-backup', array('DHDOSET', 'backup_page'));  
        }
    }

    // Define Settings Page  
    public static function  settings_page() {
        include_once( DHDO_PLUGIN_DIR . '/admin/settings.php');// Main Settings
    }
    
    // Backup Control Page
    public static function  backup_page() {
        include_once( DHDO_PLUGIN_DIR . '/admin/backups.php'); // Backup Settings
    }

    /**
     * Register Settings
     *
    */
    public static function add_register_settings() {

		// S3
		$s3config = array(
		    'key'     => get_option('dh-do-key'),
		    'secret'  => get_option('dh-do-secretkey'),
		    'base_url' => 'https://'.get_option('dh-do-hostname'),
		);

    		// Keypair settings
        add_settings_section( 'keypair_id', __('Access Keys', 'dreamobjects'), 'keypair_callback', 'dh-do-keypair_page' );
        
        register_setting( 'dh-do-keypair-settings', 'dh-do-key', 'key_validation');
        add_settings_field( 'key_id', __('Key', 'dreamobjects'), 'key_callback', 'dh-do-keypair_page', 'keypair_id' );
        
        register_setting( 'dh-do-keypair-settings', 'dh-do-secretkey', 'secretkey_validation');
        add_settings_field( 'secretkey_id', __('Secret Key', 'dreamobjects'), 'secretkey_callback', 'dh-do-keypair_page', 'keypair_id' );

        function keypair_callback() { 
			// Nothing here
        }
	    	function key_callback() {
	        ?><input type="text" id="dh-do-key" name="dh-do-key" value="<?php echo get_option('dh-do-key'); ?>" class="regular-text"  size="50" autocomplete="off"/><?php
	    	}
	    	function key_validation( $input ) {
		    	$key = sanitize_text_field($input);
		    
			if ( $input != $key ) {
				$error = TRUE;
				$string = __('Your key is invalid.', 'dreamobjects');
			}
			if ( is_null( $key ) || empty($key) || $key === '' ) {
				$error = TRUE;
				$string = __('Your key is empty.', 'dreamobjects');
			}

			if ( $error === TRUE ) {
			    add_settings_error(
			      'dh-do-key',
			      'key-field-error',
			      $string,
			      'error'
			    );
			} else {
	        		return $key;
	        	}
	    	}
	    	function secretkey_callback() {
		    	
		    	if (get_option('dh-do-secretkey') === '' || !get_option('dh-do-secretkey') ) {
			    	$secretkey = '';
		    	} else {
			    	$secretkey = '-- not shown --';
		    	}
		    	
		    ?><input type="text" id="dh-do-secretkey" name="dh-do-secretkey" value="<?php echo $secretkey ?>" class="regular-text"  size="50" autocomplete="off"/>
		    <p><div class="dashicons dashicons-shield"></div><?php _e( 'Your secret key will not display for your own security.', 'dreamobjects' ); ?></p>
		    <?php
	    	}
	    	function secretkey_validation( $input ) {
		    	$secretkey = sanitize_text_field($input);
		    	
			if ( $input != $secretkey ) {
				$error = TRUE;
				$string = __('Your secret key is invalid.', 'dreamobjects');
			}
			if ( is_null( $secretkey ) || empty($secretkey) || $secretkey === '' ) {
				$error = TRUE;
				$string = __('Your secret key is empty.', 'dreamobjects');
			}

			if ( $error === TRUE ) {
			    add_settings_error(
			      'dh-do-secretkey',
			      'secretkey-field-error',
			      $string,
			      'error'
			    );
			} else {
	        		return $secretkey;
	        	}
	    	}

		// Logging Settings (these show ONLY when the keypair stuff is handled)
		add_settings_section( 'logging_id', __('Debug Logging', 'dreamobjects'), 'logging_callback', 'dh-do-logging_page' );

		register_setting( 'dh-do-logging-settings', 'dh-do-logging', 'logging_validation');
		add_settings_field( 'dh-do-logging_id', __('Enable Logging', 'dreamobjects'), 'logging_settings_callback', 'dh-do-logging_page', 'logging_id' );

	    	function logging_callback() {
		    	?>
		    	<p><?php echo __('If you\'re trying to troubleshoot problems, like why backups only work for SQL, you can turn on logging to see what\'s being kicked off and when. Generally you should not leave this on all the time since it\'s publicly accessible and reveals the location of your secret zip file. When you turn off logging, the file will wipe itself out for your protection.', 'dreamobjects'); ?></p>
			<?php
	    	}
	    	
	    	function logging_settings_callback() {
		    	?><p><input type="checkbox" name="dh-do-logging" <?php checked( get_option('dh-do-logging') == 'on',true); ?> /> <?php echo __('Enable logging (if checked)', 'dreamobjects'); ?> <?php
				if ( get_option('dh-do-logging') == 'on' ) { ?>&mdash; <span class="description"><?php echo __('Your log file is located at ', 'dreamobjects'); ?><a href="<?php echo plugins_url( 'debug.txt?nocache' , dirname(__FILE__) );?>"><?php echo plugins_url( 'debug.txt' , dirname(__FILE__) );?></a></span></p>
				<?php }
		}
		function logging_validation( $input ) {
			$logging = ( isset( $input ) && true == $input ? 'on' : 'off' );
			
			if ( $logging === 'on' ) {
				$string = __('Logging is enabled.', 'dreamobjects');
			} elseif ( $logging === 'off' ) {
				$string = __('Logging is disabled.', 'dreamobjects');
			}

			if ( $logging !== get_option('dh-do-logging') ) {
			    add_settings_error(
			      'dh-do-logging',
			      'logging-field-updated',
			      $string,
			      'updated'
			    );
		    }

			return $logging;
		}

		// Backup Settings
        add_settings_section( 'backuper_id', __('Settings', 'dreamobjects'), 'backuper_callback', 'dh-do-backuper_page' );
        
        register_setting( 'dh-do-backuper-settings', 'dh-do-bucket', 'backup_bucket_validation' );
        add_settings_field( 'dh-do-bucket_id',  __('Bucket Name', 'dreamobjects'), 'backup_bucket_callback', 'dh-do-backuper_page', 'backuper_id' );

        if ( get_option('dh-do-bucket') != "XXXX" ) {
            register_setting( 'dh-do-backuper-settings', 'dh-do-backupsection', 'backup_what_validation' );
            add_settings_field( 'dh-do-backupsection_id',  __('What to Backup', 'dreamobjects'), 'backup_what_callback', 'dh-do-backuper_page', 'backuper_id' );
            register_setting( 'dh-do-backuper-settings', 'dh-do-schedule', 'backup_sched_validation' );
            add_settings_field( 'dh-do-schedule_id',  __('Schedule', 'dreamobjects'), 'backup_sched_callback', 'dh-do-backuper_page', 'backuper_id' );
            register_setting( 'dh-do-backuper-settings', 'dh-do-retain', 'backup_retain_validation' );
            add_settings_field( 'dh-do-backupretain_id',  __('Backup Retention', 'dreamobjects'), 'backup_retain_callback', 'dh-do-backuper_page', 'backuper_id' );
            register_setting( 'dh-do-backuper-settings', 'dh-do-notify', 'backup_notify_validation' );
            add_settings_field( 'dh-do-backupnotify_id',  __('Status Notifications', 'dreamobjects'), 'backup_notify_callback', 'dh-do-backuper_page', 'backuper_id' );
        }
        
        function backuper_callback() { 
            echo '<p>'.__( 'Configure your site for backups by selecting your bucket, what you want to backup, and when.', 'dreamobjects').'</p>';
            
			$buckets = DHDOSET::get_buckets();

			echo '<p>';
			if ( get_option('dh-do-bucket') == 'XXXX' && empty($buckets['Buckets']) ) {	
				printf( __( 'To create a bucket, go to your <a href="%s" target="_new">DreamObjects Panel for DreamObjects</a> and click the "Add Buckets" button. Give the bucket a name and click "Save." Once you have a bucket, come back to this configuration page and select the bucket you just created.', 'dreamobjects' ), 'https://panel.dreamhost.com/index.cgi?tree=cloud.objects&' );
			}
			echo '</p>';
        }
        function backup_bucket_callback() {
            $buckets = DHDOSET::get_buckets();
                                    
            ?> <select name="dh-do-bucket">
                    <option value="XXXX">(select a bucket)</option>
                    <?php foreach ( $buckets['Buckets'] as $bucket ) { ?>
						<option <?php if ( $bucket['Name'] == get_option('dh-do-bucket') ) echo 'selected="selected"' ?> ><?php echo esc_attr( $bucket['Name'] ) ?></option>
                    <?php } ?>
                </select>
				<p class="description"><?php 
					if ( get_option('dh-do-bucket') !== 'XXXX' && !empty($buckets['Buckets']) ) {
						echo __('Select from pre-existing buckets.', 'dreamobjects');
					} else {
						printf( __( 'You need to <a href="%s" target="_new">create a bucket</a> before you can perform any backups.', 'dreamobjects' ), 'https://panel.dreamhost.com/index.cgi?tree=cloud.objects&' );
					}
				?></p><?php
		}
	    function backup_bucket_validation( $input ) {
		    	$buckets = DHDOSET::get_buckets();		    	
		    	$goodbuckets = array_map(function($bname) {
				return $bname['Name'];
			}, $buckets['Buckets']);
		    	$thisbucket  = sanitize_file_name($input);
		    	
		    	if ( $input !== $thisbucket || !in_array( $thisbucket, $goodbuckets )  ) {
			    	$error = true;
			    	$string = __('Invalid bucket choice.', 'dreamobjects');
		    	}
	
			if ( $error === true ) {
			    add_settings_error(
			      'dh-do-bucket',
			      'bucket-field-error',
			      $string,
			      'error'
			    );
			} else {
	        		return $thisbucket;
	        	}
	    }
	
	    	function backup_what_callback() {
	        	$mysections = get_option('dh-do-backupsection');
	    		if ( !$mysections ) {
	    			$mysections = array();
	    		}
	    		$availablesections = DHDOSET::get_sections();
	    		
	        	?><p><label for="dh-do-backupsections">

			<?php foreach ( $availablesections as $key => $value ) {
				?>
				<input <?php if ( in_array( $key, $mysections) ) echo 'checked="checked"' ?> type="checkbox" name="dh-do-backupsection[]" value="<?php echo esc_attr($key) ?>" id="dh-do-backupsection-<?php echo esc_attr($key) ?>" />
				<?php echo $value ?>
				</label><br />
				<?php
			}			

			?>
			<p class="description"><?php echo __('You can select portions of your site to backup.', 'dreamobjects'); ?></p><?php
	    }
		function backup_what_validation( $input ) {
			$availablesections = DHDOSET::get_sections();
			$thesesections = array();

			foreach ( $input as $key => $value ) {
				$thissection = sanitize_text_field($value);
				
				if ( $input[$key] !== $thissection || !array_key_exists( $thissection, $availablesections )  ) {
					$error = true;
				} else {
					$thesesections[$key] = $thissection;
				}
			}
	
			if ( $error === true ) {
				$string = __('Invalid section choice.', 'dreamobjects');
			    add_settings_error(
			      'dh-do-backupsection',
			      'backupsection-field-error',
			      $string,
			      'error'
			    );
			} else {
	        		return $thesesections;
	        	}
		}

    		function backup_sched_callback() {
            ?><select name="dh-do-schedule">
				<?php 
				$schedules = DHDOSET::get_schedule();	
				foreach ( $schedules as $s ) { ?>
				<option value="<?php echo strtolower($s) ?>" <?php if ( strtolower($s) == get_option('dh-do-schedule') ) echo 'selected="selected"' ?>><?php echo $s ?></option>
				<?php } ?>
				</select>
				<?php
                  $timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'dh-do-backup' ) ), get_option('date_format').' '.get_option('time_format') );
                  $nextbackup = sprintf(__('Next scheduled backup is at %s', 'dreamobjects'), $timestamp );
            ?>
            <p class="description"><?php echo __('How often do you want to backup your files? Daily is recommended.', 'dreamobjects'); ?></p>
            <?php if ( get_option('dh-do-schedule') != "disabled" && wp_next_scheduled('dh-do-backup') ) { ?>
            		<p class="description"><?php echo $nextbackup; ?></p>
            <?php }
		}
		function backup_sched_validation( $input ) {
			$availabletimes = DHDOSET::get_schedule();
			$thistime = sanitize_text_field($input);
				
			if ( $input !== $thistime || !array_key_exists( $thistime, $availabletimes )  ) {
				$error = true;
				$string = __('Invalid scheduling choice.', 'dreamobjects');
			}
			
			if ( $error === true ) {
			    add_settings_error(
			      'dh-do-schedule',
			      'schedule-field-error',
			      $string,
			      'error'
			    );
			} else {
	        		return $thistime;
	        	}
		}
    	
    		function backup_retain_callback() {
	    		$retainarray = DHDOSET::get_retain();
	    		
	    		?><select name="dh-do-retain">
				<?php foreach ( $retainarray as $s ) : ?>
			        <option value="<?php echo strtolower($s) ?>" <?php if ( strtolower($s) == get_option('dh-do-retain') ) echo 'selected="selected"' ?>><?php echo $s ?></option>
			    <?php endforeach; ?>
			</select>
			<p class="description"><?php echo __('How many many backups do you want to keep? 15 is recommended.', 'dreamobjects'); ?></p>
			<p class="description"><?php echo __('DreamObjects charges you based on disk space used. Setting to \'All\' will retain your backups forever, however this can cost you a large sum of money over time. Please use cautiously!', 'dreamobjects'); ?></p>
			<?php
	    	}
		function backup_retain_validation( $input ) {
			$retainarray = DHDOSET::get_retain();
			$retain = sanitize_text_field($input);
				
			if ( $input !== $retain || !in_array( $retain, $retainarray )  ) {
				$error = true;
				$string = __('Invalid retention option.', 'dreamobjects');
			}
			
			if ( $error === true ) {
			    add_settings_error(
			      'dh-do-retain',
			      'retain-field-error',
			      $string,
			      'error'
			    );
			} else {
	        		return $retain;
	        	}
		}
    	
	    	function backup_notify_callback() {
		    	$notifyarray = DHDOSET::get_notify();
		    ?><select name="dh-do-notify">
		    	<?php foreach ( $notifyarray as $s ) : ?>
				<option value="<?php echo strtolower($s) ?>" <?php if ( strtolower($s) == get_option('dh-do-notify') ) echo 'selected="selected"' ?>><?php echo $s ?></option>
			<?php endforeach; ?>
			</select>
			
	        <p class="description"><?php echo __('Select what status notifications you want to see below. DreamObjects will always log all your activity, but only show you what you want.', 'dreamobjects'); ?></p>
	        <?php
	    	}
		function backup_notify_validation( $input ) {
			$notifyarray = DHDOSET::get_notify();
			$notify = sanitize_text_field($input);
				
			if ( $input !== $notify || !array_key_exists( $notify, $notifyarray )  ) {
				$error = true;
				$string = __('Invalid notification option.', 'dreamobjects');
			}
			
			if ( $error === true ) {
			    add_settings_error(
			      'dh-do-notify',
			      'notify-field-error',
			      $string,
			      'error'
			    );
			} else {
	        		return $notify;
	        	}
		}

		// Backup NOW Settings
        add_settings_section( 'backupnow_id', __('Immediate Backup', 'dreamobjects'), 'backupnow_callback', 'dh-do-backupnow_page' );
        
        register_setting( 'dh-do-backupnow-settings', 'dh-do-backupnow', 'backupnow_validation');
        
        function backupnow_callback() { 
            echo __('Oh you really want to do a backup right now? Schedule your backup to start in a minute. Be careful! This may take a while, and slow your site down, if you have a big site. Also if you made any changes to your settings, go back and click "Update Options" before running this.', 'dreamobjects');
            
            $timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'dh-do-backup' ) ), get_option('date_format').' '.get_option('time_format') );
            $nextbackup = sprintf(__('Keep in mind, your next scheduled backup is at %s', 'dreamobjects'), $timestamp );            
            if ( get_option('dh-do-schedule') != "disabled" && wp_next_scheduled('dh-do-backup') ) {
	            echo '<p>'.$nextbackup.'</p>';
	        }
	        
	        echo '<input type="hidden" name="dh-do-backupnow" value="Y" />';
        }

		function backupnow_validation( $input ) {
			$backup = ( isset( $input ) && 'Y' === $input ? 'Y' : 'N' );
			
			if ( $backup === 'Y' ) {

		        $timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'dh-do-backupnow' ) ), get_option('time_format') );
		        $string = sprintf( __('You have an ad-hoc backup scheduled for today at %s. You may continue using your site per usual, the backup will run behind the scenes.', 'dreamobjects'), '<strong>'.$timestamp.'</strong>' );

			    add_settings_error(
			      'dh-do-backup',
			      'backup-field-updated',
			      $string,
			      'updated'
			    );
		    }
		}

    }
}