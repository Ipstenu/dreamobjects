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

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

use Aws\S3\S3Client;


/**
 * DreamObjects_Settings class.
 */
class DreamObjects_Settings {

	/**
	 * Construction
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'add_register_settings' ) );
	}

	/**
	 * Get the client data.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_client() {
		if ( ! get_option( 'dh-do-key' ) || ! get_option( 'dh-do-secretkey' ) ) {
			// translators: %s is the URL to the DreamObjects admin page
			return new WP_Error( 'access_keys_missing', sprintf( __( '<div class="dashicons dashicons-no"></div> Please <a href="%s">set your access keys</a> first.', 'dreamobjects' ), 'admin.php?page=dreamobjects-menu' ) );
		}

		try {
			$s3 = new S3Client( DreamObjects_Core::$s3_options );
		} catch ( \Aws\S3\Exception\S3Exception $e ) {
			$s3  = $e->getAwsErrorCode() . "\n";
			$s3 .= $e->getMessage() . "\n";
		}

		return $s3;
	}

	/**
	 * Get the buckets.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_buckets() {
		try {
			$result = self::get_client()->listBuckets();
		} catch ( Exception $e ) {
			return new WP_Error( 'exception', $e->getMessage() );
		}
		return $result;
	}

	/**
	 * Get the sections.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_sections() {
		$sections = array(
			'files'    => __( 'All Files', 'dreamobjects' ),
			'database' => __( 'Database', 'dreamobjects' ),
		);
		return $sections;
	}

	/**
	 * Get the possible schedules.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_schedule() {
		$schedule = array(
			'disabled' => __( 'Disabled', 'dreamobjects' ),
			'daily'    => __( 'Daily', 'dreamobjects' ),
			'weekly'   => __( 'Weekly', 'dreamobjects' ),
			'monthly'  => __( 'Monthly', 'dreamobjects' ),
		);
		return $schedule;
	}

	/**
	 * Get the possible retention periods.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_retain() {
		$retain = array( '1', '2', '5', '10', '15', '30', '60', '90', 'all' );
		return $retain;
	}

	/**
	 * Get the notify options.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_notify() {
		$notify = array(
			'disabled' => __( 'Disabled (None)', 'dreamobjects' ),
			'success'  => __( 'Success', 'dreamobjects' ),
			'failure'  => __( 'Failure', 'dreamobjects' ),
			'all'      => __( 'All', 'dreamobjects' ),
		);
		return $notify;
	}

	/**
	 * Add settings pages.
	 *
	 * @access public
	 * @return void
	 */
	public function add_settings_page() {
		add_menu_page( __( 'DreamObjects Settings', 'dreamobjects' ), __( 'DreamObjects', 'dreamobjects' ), 'manage_options', 'dreamobjects-menu', array( $this, 'settings_page' ), 'dashicons-backup' );

		if ( get_option( 'dh-do-key' ) && get_option( 'dh-do-secretkey' ) ) {
			add_submenu_page( 'dreamobjects-menu', __( 'Backups', 'dreamobjects' ), __( 'Backups', 'dreamobjects' ), 'manage_options', 'dreamobjects-menu-backup', array( $this, 'backup_page' ) );
		}
	}

	/**
	 * Define settings pages.
	 *
	 * @access public
	 * @return void
	 */
	public function settings_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . '/admin/settings.php';// Main Settings
	}

	/**
	 * Define backup pages.
	 *
	 * @access public
	 * @return void
	 */
	public function backup_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . '/admin/backups.php'; // Backup Settings
	}

	/**
	 * Register Settings
	 *
	 * @access public
	 * @return void
	 */
	public function add_register_settings() {

		$s3 = new S3Client( DreamObjects_Core::$s3_options );

		// Keypair settings.
		add_settings_section( 'keypair_id', __( 'Access Keys', 'dreamobjects' ), array( $this, 'keypair_callback' ), 'dh-do-keypair_page' );

		register_setting( 'dh-do-keypair-settings', 'dh-do-key', array( $this, 'key_validation' ) );
		add_settings_field( 'key_id', __( 'Key', 'dreamobjects' ), array( $this, 'key_callback' ), 'dh-do-keypair_page', 'keypair_id' );

		register_setting( 'dh-do-keypair-settings', 'dh-do-secretkey', array( $this, 'secretkey_validation' ) );
		add_settings_field( 'secretkey_id', __( 'Secret Key', 'dreamobjects' ), array( $this, 'secretkey_callback' ), 'dh-do-keypair_page', 'keypair_id' );

		// Logging Settings (these show ONLY when the keypair stuff is handled).
		add_settings_section( 'logging_id', __( 'Debug Logging', 'dreamobjects' ), array( $this, 'logging_callback' ), 'dh-do-logging_page' );

		register_setting( 'dh-do-logging-settings', 'dh-do-logging', array( $this, 'logging_validation' ) );
		add_settings_field( 'dh-do-logging_id', __( 'Enable Logging', 'dreamobjects' ), array( $this, 'logging_settings_callback' ), 'dh-do-logging_page', 'logging_id' );

		// Backup Settings.
		add_settings_section( 'backuper_id', __( 'Settings', 'dreamobjects' ), array( $this, 'backuper_callback' ), 'dh-do-backuper_page' );

		register_setting( 'dh-do-backuper-settings', 'dh-do-bucket', array( $this, 'backup_bucket_validation' ) );
		add_settings_field( 'dh-do-bucket_id', __( 'Bucket Name', 'dreamobjects' ), array( $this, 'backup_bucket_callback' ), 'dh-do-backuper_page', 'backuper_id' );

		// This will only show if the rest of the setup is complete.
		if ( get_option( 'dh-do-bucket' ) !== 'XXXX' ) {
			register_setting( 'dh-do-backuper-settings', 'dh-do-backupsection', array( $this, 'backup_what_validation' ) );
			add_settings_field( 'dh-do-backupsection_id', __( 'What to Backup', 'dreamobjects' ), array( $this, 'backup_what_callback' ), 'dh-do-backuper_page', 'backuper_id' );
			register_setting( 'dh-do-backuper-settings', 'dh-do-schedule', array( $this, 'backup_sched_validation' ) );
			add_settings_field( 'dh-do-schedule_id', __( 'Schedule', 'dreamobjects' ), array( $this, 'backup_sched_callback' ), 'dh-do-backuper_page', 'backuper_id' );
			register_setting( 'dh-do-backuper-settings', 'dh-do-retain', array( $this, 'backup_retain_validation' ) );
			add_settings_field( 'dh-do-backupretain_id', __( 'Backup Retention', 'dreamobjects' ), array( $this, 'backup_retain_callback' ), 'dh-do-backuper_page', 'backuper_id' );
			register_setting( 'dh-do-backuper-settings', 'dh-do-notify', array( $this, 'backup_notify_validation' ) );
			add_settings_field( 'dh-do-backupnotify_id', __( 'Status Notifications', 'dreamobjects' ), array( $this, 'backup_notify_callback' ), 'dh-do-backuper_page', 'backuper_id' );
		}

		// Backup NOW Settings
		add_settings_section( 'backupnow_id', __( 'Immediate Backup', 'dreamobjects' ), array( $this, 'backupnow_callback' ), 'dh-do-backupnow_page' );
		register_setting( 'dh-do-backupnow-settings', 'dh-do-backupnow', array( $this, 'backupnow_validation' ) );

		// RESET SETTINGS SECTION
		add_settings_section( 'resetplugin_id', __( 'Reset Plugin', 'dreamobjects' ), array( $this, 'resetplugin_callback' ), 'dh-do-resetplugin_page' );
		register_setting( 'dh-do-resetplugin-settings', 'dh-do-resetplugin', array( $this, 'resetplugin_validation' ) );
	}

	/**
	 * keypair_callback function.
	 *
	 * This is empty on purpose
	 *
	 * @access public
	 * @return void
	 */
	public function keypair_callback() {
		// Nothing here
	}

	/**
	 * key_callback function.
	 *
	 * @access public
	 * @return void
	 */
	public function key_callback() {
		?>
		<input type="text" id="dh-do-key" name="dh-do-key" value="<?php echo esc_html( get_option( 'dh-do-key' ) ); ?>" class="regular-text"  size="50" autocomplete="off"/>
		<?php
	}

	/**
	 * key_validation function.
	 *
	 * @access public
	 * @param mixed $input - string
	 * @return string
	 */
	public function key_validation( $input ) {
		$key = sanitize_text_field( $input );

		if ( $input !== $key ) {
			$error  = true;
			$string = __( 'Your key is invalid.', 'dreamobjects' );
		}
		if ( is_null( $key ) || empty( $key ) || '' === $key ) {
			$error  = true;
			$string = __( 'Your key is empty.', 'dreamobjects' );
		}

		if ( isset( $error ) && true === $error ) {
			add_settings_error( 'dh-do-key', 'key-field-error', $string, 'error' );
		} else {
			return $key;
		}
	}

	/**
	 * Secret Key callback.
	 *
	 * @access public
	 * @return void
	 */
	public function secretkey_callback() {
		if ( get_option( 'dh-do-secretkey' ) === '' || ! get_option( 'dh-do-secretkey' ) ) {
			$secretkey = '';
			$message   = __( 'Your secret key will not display again for your own safety.', 'dreamobjects' );
		} else {
			$secretkey = '-- not shown --';
			$message   = __( 'Your secret key does not display for security reasons.', 'dreamobjects' );
		}

		?>
		<input type="text" id="dh-do-secretkey" name="dh-do-secretkey" value="<?php echo esc_html( $secretkey ); ?>" class="regular-text"  size="50" autocomplete="off"/>
		<p><div class="dashicons dashicons-shield"></div><?php echo esc_html( $message ) ; ?></p>
		<?php
	}

	/**
	 * Secret Key validation.
	 *
	 * @access public
	 * @param mixed $input - string
	 * @return string
	 */
	public function secretkey_validation( $input ) {
		$secretkey = sanitize_text_field( $input );
		$error     = false;

		if ( $input !== $secretkey ) {
			$error  = true;
			$string = __( 'Your secret key is invalid.', 'dreamobjects' );
		}

		if ( is_null( $secretkey ) || empty( $secretkey ) || '' === $secretkey ) {
			$error  = true;
			$string = __( 'Your secret key is empty.', 'dreamobjects' );
		}

		if ( $error ) {
			add_settings_error( 'dh-do-secretkey', 'secretkey-field-error', $string, 'error' );
		} else {
			return $secretkey;
		}
	}

	/**
	 * logging_callback function.
	 *
	 * @access public
	 * @return void
	 */
	public function logging_callback() {
		?>
		<p><?php echo esc_html__( 'If you\'re trying to troubleshoot problems, like why backups only work for SQL, you can turn on logging to see what\'s being kicked off and when. Generally you should not leave this on all the time since it\'s publicly accessible and reveals the location of your secret zip file. When you turn off logging, the file will wipe itself out for your protection.', 'dreamobjects' ); ?></p>
		<?php
	}

	/**
	 * logging_settings_callback function.
	 *
	 * @access public
	 * @return void
	 */
	public function logging_settings_callback() {
		?>
		<p><input type="checkbox" name="dh-do-logging" <?php checked( get_option( 'dh-do-logging' ) === 'on', true ); ?> /> <?php echo esc_html__( 'Enable logging (if checked)', 'dreamobjects' ); ?> <?php
		if ( get_option( 'dh-do-logging' ) === 'on' ) {
			?>
			&mdash; <span class="description"><?php echo esc_html__( 'Your log file is located at ', 'dreamobjects' ); ?><a href="<?php echo esc_url( plugins_url( 'debug.txt?nocache', dirname( __FILE__ ) ) ); ?>"><?php echo esc_url( plugins_url( 'debug.txt', dirname( __FILE__ ) ) ); ?></a></span></p>
			<?php
		}
	}

	/**
	 * logging_validation function.
	 *
	 * @access public
	 * @param mixed $input - string
	 * @return string
	 */
	public function logging_validation( $input ) {

		$logging = ( isset( $input ) && 'on' === $input ) ? 'on' : 'off';

		switch ( $logging ) {
			case 'on':
				$string = __( 'Logging is enabled.', 'dreamobjects' );
				break;
			case 'off':
				$string = __( 'Logging is disabled.', 'dreamobjects' );
				break;
		}

		if ( get_option( 'dh-do-logging' ) !== $logging ) {
			add_settings_error( 'dh-do-logging', 'logging-field-updated', $string, 'updated' );
		}

		return $logging;
	}

	/**
	 * backuper_callback function.
	 *
	 * @access public
	 * @return void
	 */
	public function backuper_callback() {
		echo '<p>' . esc_html__( 'Configure your site for backups by selecting your bucket, what you want to backup, and when.', 'dreamobjects' ) . '</p>';

		$buckets = self::get_buckets();

		echo '<p>';
		if ( get_option( 'dh-do-bucket' ) === 'XXXX' || empty( $buckets['Buckets'] ) ) {
			// translators: %s is the URL to DreamHost Panel
			printf( wp_kses_post( __( 'To create a bucket, go to your <a href="%s" target="_new">DreamObjects Panel for DreamObjects</a> and click the "Add Buckets" button. Give the bucket a name and click "Save." Once you have a bucket, come back to this configuration page and select the bucket you just created.', 'dreamobjects' ) ), 'https://panel.dreamhost.com/index.cgi?tree=cloud.objects&' );
		}
		echo '</p>';
	}

	/**
	 * backup_bucket_callback function.
	 *
	 * @access public
	 * @return void
	 */
	public function backup_bucket_callback() {
		$buckets = self::get_buckets();
		?>
		<select name="dh-do-bucket">
			<option value="XXXX">(select a bucket)</option>
			<?php
			foreach ( $buckets['Buckets'] as $bucket ) {
				$selected = ( get_option( 'dh-do-bucket' ) === $bucket['Name'] ) ? 'selected="selected"' : '';
				echo '<option ' . esc_html( $selected ) . '>' . esc_attr( $bucket['Name'] ) . '</option>';
			}
			?>
		</select>
		<p class="description">
			<?php
			if ( ! empty( $buckets['Buckets'] ) ) {
				// translators: %s is the hostname
				printf( esc_html__( 'Select from pre-existing buckets in %s.', 'dreamobjects' ), esc_html( get_option( 'dh-do-hostname' ) ) );
			} else {
				// translators: %s is the panel link for help
				printf( wp_kses_post( __( 'You need to <a href="%s" target="_new">create a bucket</a> before you can perform any backups.', 'dreamobjects' ) ), 'https://panel.dreamhost.com/index.cgi?tree=cloud.objects&' );

				if ( DreamObjects_Core::datacenter_move_east( 'deadline' ) && ! DreamObjects_Core::datacenter_move_east( 'toolate' ) ) {
					echo ' <strong>' . esc_html__( 'NOTICE!', 'dreamobjects' ) . '</strong> ';
					// translators: %s is the help doc
					printf( wp_kses_post( __( 'You\'re seeing this message because you have no buckets on the new datacenter. All of your buckets should have been replicated but there was an error. Please <a href="%s" target="_new">review the cluster migration procedure</a> to resolve this.', 'dreamobjects' ) ), 'https://help.dreamhost.com/hc/en-us/articles/360002135871-Cluster-migration-procedure' );
				}
			}
			?>
		</p>
		<?php
	}

	/**
	 * backup_bucket_validation function.
	 *
	 * @access public
	 * @param mixed $input
	 * @return void
	 */
	public function backup_bucket_validation( $input ) {
		$buckets     = self::get_buckets();
		$goodbuckets = array_map( function( $bname ) {
			return $bname['Name'];
		}, $buckets['Buckets']);
		$thisbucket  = sanitize_file_name( $input );

		if ( $input !== $thisbucket || ! in_array( $thisbucket, $goodbuckets, true ) ) {
			$error  = true;
			$string = __( 'Invalid bucket choice.', 'dreamobjects' );
		}

		if ( isset( $error ) && true === $error ) {
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

	/**
	 * backup_what_callback function.
	 *
	 * @access public
	 * @return void
	 */
	public function backup_what_callback() {
		$mysections = get_option( 'dh-do-backupsection' );
		if ( ! $mysections ) {
			$mysections = array();
		}
		$availablesections = self::get_sections();

		?>
		<p><label for="dh-do-backupsections">
			<?php
			foreach ( $availablesections as $key => $value ) {
				$checked = ( in_array( $key, $mysections ) ) ? 'checked="checked"' : '';
				echo '<input ' . esc_html( $checked ) . ' type="checkbox" name="dh-do-backupsection[]" value="' . esc_attr( $key ) . '" id="dh-do-backupsection-' . esc_attr( $key ) . '" />';
				echo esc_html( $value );
				echo '<br />';
			}
			?>
		</label></p>
		<p class="description"><?php echo esc_html__( 'You can select portions of your site to backup.', 'dreamobjects' ); ?></p>
		<?php
	}

	/**
	 * backup_what_validation function.
	 *
	 * @access public
	 * @param mixed $input
	 * @return void
	 */
	public function backup_what_validation( $input ) {
		$availablesections = self::get_sections();
		$thesesections     = array();

		foreach ( $input as $key => $value ) {
			$thissection = sanitize_text_field( $value );
			if ( $input[ $key ] !== $thissection || ! array_key_exists( $thissection, $availablesections ) ) {
				$error = true;
			} else {
				$thesesections[ $key ] = $thissection;
			}
		}

		if ( isset( $error ) && true === $error ) {
			$string = __( 'Invalid section choice.', 'dreamobjects' );
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

	/**
	 * backup_sched_callback function.
	 *
	 * @access public
	 * @return void
	 */
	public function backup_sched_callback() {
		?>
		<select name="dh-do-schedule">
			<?php
			$schedules = self::get_schedule();
			foreach ( $schedules as $s ) {
				$selected = ( strtolower( $s ) === get_option( 'dh-do-schedule' ) ) ? 'selected="selected"' : '';
				echo '<option value=" ' . esc_html( strtolower( $s ) ) . '" ' . esc_html( $selected ) . '>' . esc_html( $s ) . '</option>';
			}
			?>
		</select>
		<?php

		$timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'dh-do-backup' ) ), get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
		// translators: %s is the time of the backup
		$nextbackup = sprintf( __( 'Next scheduled backup is at %s', 'dreamobjects' ), $timestamp );
		?>
		<p class="description"><?php echo esc_html__( 'How often do you want to backup your files? Daily is recommended.', 'dreamobjects' ); ?></p>
		<?php
		if ( 'disabled' !== get_option( 'dh-do-schedule' ) && wp_next_scheduled( 'dh-do-backup' ) ) {
			?>
			<p class="description"><?php echo wp_kses_post( $nextbackup ); ?></p>
			<?php
		}
	}

	/**
	 * backup_sched_validation function.
	 *
	 * @access public
	 * @param mixed $input
	 * @return void
	 */
	public function backup_sched_validation( $input ) {
		$availabletimes = self::get_schedule();
		$thistime       = sanitize_text_field( $input );
		if ( $input !== $thistime || ! array_key_exists( $thistime, $availabletimes ) ) {
			$error  = true;
			$string = __( 'Invalid scheduling choice.', 'dreamobjects' );
		}

		if ( isset( $error ) && true === $error ) {
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

	/**
	 * backup_retain_callback function.
	 *
	 * @access public
	 * @return void
	 */
	public function backup_retain_callback() {
		$retainarray = self::get_retain();
		echo '<select name="dh-do-retain">';
		foreach ( $retainarray as $s ) {
			$selected = ( strtolower( $s ) === get_option( 'dh-do-retain' ) ) ? 'selected="selected"' : '';
			echo '<option value=" ' . esc_html( strtolower( $s ) ) . '" ' . esc_html( $selected ) . '>' . esc_html( $s ) . '</option>';
		}
		echo '</select>';
		?>
		<p class="description"><?php echo esc_html__( 'How many many backups do you want to keep? 15 is recommended.', 'dreamobjects' ); ?></p>
		<p class="description"><?php echo esc_html__( 'DreamObjects charges you based on disk space used. Setting to \'All\' will retain your backups forever, however this can cost you a large sum of money over time. Please use cautiously!', 'dreamobjects' ); ?></p>
		<?php
	}

	/**
	 * backup_retain_validation function.
	 *
	 * @access public
	 * @param mixed $input
	 * @return void
	 */
	public function backup_retain_validation( $input ) {
		$retainarray = self::get_retain();
		$retain      = sanitize_text_field( $input );

		if ( $input !== $retain || ! in_array( $retain, $retainarray ) ) {
			$error  = true;
			$string = __( 'Invalid retention option.', 'dreamobjects' );
		}

		if ( isset( $error ) && true === $error ) {
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

	/**
	 * backup_notify_callback function.
	 *
	 * @access public
	 * @return void
	 */
	public function backup_notify_callback() {
		$notifyarray = self::get_notify();
		echo '<select name="dh-do-notify">';
		foreach ( $notifyarray as $s ) {
			$selected = ( strtolower( $s ) === get_option( 'dh-do-notify' ) ) ? 'selected="selected"' : '';
			echo '<option value=" ' . esc_html( strtolower( $s ) ) . '" ' . esc_html( $selected ) . '>' . esc_html( $s ) . '</option>';
		}
		echo '</select>';
		?>
		<p class="description"><?php echo esc_html__( 'Select what status notifications you want to see below. DreamObjects will always log all your activity, but only show you what you want.', 'dreamobjects' ); ?></p>
		<?php
	}

	/**
	 * backup_notify_validation function.
	 *
	 * @access public
	 * @param mixed $input
	 * @return void
	 */
	public function backup_notify_validation( $input ) {
		$notifyarray = self::get_notify();
		$notify      = sanitize_text_field( $input );

		if ( $input !== $notify || ! array_key_exists( $notify, $notifyarray ) ) {
			$error  = true;
			$string = __( 'Invalid notification option.', 'dreamobjects' );
		}

		if ( isset( $error ) && true === $error ) {
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

	/**
	 * backupnow_callback function.
	 *
	 * @access public
	 * @return void
	 */
	public function backupnow_callback() {
		echo '<p>' . esc_html__( 'Oh you really want to do a backup right now? Schedule your backup to start in a minute. Be careful! This may take a while, and slow your site down, if you have a big site. Also if you made any changes to your settings, go back and click "Update Options" before running this.', 'dreamobjects' ) . '</p>';

		$timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'dh-do-backup' ) ), get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
		// translators: %s is the time of the next backup
		$nextbackup = sprintf( __( 'Keep in mind, your next scheduled backup is at %s', 'dreamobjects' ), $timestamp );
		if ( 'disabled' === get_option( 'dh-do-schedule' ) && wp_next_scheduled( 'dh-do-backup' ) ) {
			echo '<p>' . esc_html( $nextbackup ) . '</p>';
		}

		echo '<input type="hidden" name="dh-do-backupnow" value="Y" />';
	}

	/**
	 * backupnow_validation function.
	 *
	 * @access public
	 * @param mixed $input
	 * @return void
	 */
	public function backupnow_validation( $input ) {
		$backup = ( isset( $input ) && 'Y' === $input ) ? 'Y' : 'N';

		if ( 'Y' === $backup ) {
			$timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'dh-do-backupnow' ) ), get_option( 'time_format' ) );
			// translators: %s is the time of the backup
			$string = sprintf( __( 'You have an ad-hoc backup scheduled for today at %s. You may continue using your site per usual, the backup will run behind the scenes.', 'dreamobjects' ), '<strong>' . $timestamp . '</strong>' );
			add_settings_error( 'dh-do-backup', 'backup-field-updated', $string, 'updated' );
		}
	}

	/**
	 * resetplugin_callback function.
	 *
	 * @access public
	 * @return void
	 */
	public function resetplugin_callback() {
		echo esc_html__( 'If you have made a mistake in setting up the plugin, or need to change to new keys, you can reset here. Keep in mind, resetting the plugin <strong>will not</strong> delete any backups, however it will delete logs.', 'dreamobjects' );
		echo '<input type="hidden" name="dh-do-resetplugin" value="Y" />';
	}

	/**
	 * resetplugin_validation function.
	 *
	 * @access public
	 * @param mixed $input
	 * @return void
	 */
	public function resetplugin_validation( $input ) {
			add_settings_error( 'dh-do-resetplugin', 'logging-field-updated', __( 'Your plugin has been reset.', 'dreamobjects' ), 'updated' );
			DreamObjects_Core::kill_it_all();
	}
}

new DreamObjects_Settings();
