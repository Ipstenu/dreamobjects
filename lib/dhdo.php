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

if (! defined( 'ABSPATH' ) ) {
	die();
}

use Aws\S3\S3Client;

use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;
use Guzzle\Plugin\Log\LogPlugin; // DEBUGGING ONLY

class DHDO {

	/**
	 * Constants
	 */
	const DIRECTORY_SEPARATORS = '/\\';

	/**
	 * Init.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function init() {

		// The Scheduler
		if ( isset( $_POST['dh-do-schedule'] ) && current_user_can( 'manage_options' ) ) {
			check_admin_referer( 'dh-do-backuper-settings-options' );
			wp_clear_scheduled_hook( 'dh-do-backup' );

			$do_schedule = sanitize_text_field( $_POST['dh-do-schedule'] );

			if ( 'disabled' !== $do_schedule ) {
				wp_schedule_event( current_time( 'timestamp', true ) + 86400, $do_schedule, 'dh-do-backup' );
				$timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'dh-do-schedule' ) ), get_option( 'time_format' ) );
				// translators: %s is type of backup scheduled
				$scheduledbackup = sprintf( __( 'Scheduled %s backup.', 'dreamobjects' ), $do_schedule );
				// translators: %s is time of backup scheduled
				$nextbackup = sprintf( __( 'Next backup: %s', 'dreamobjects' ), $timestamp );
				self::logger( $scheduledbackup . ' ' . $nextbackup );
			}
		}

		// LOGGER: Wipe logger if blank
		if ( current_user_can( 'manage_options' ) && 'off' === get_option( 'dh-do-logging' ) ) {
			self::logger( 'reset' );
		}

		// BACKUP ASAP
		if ( current_user_can( 'manage_options' ) && isset( $_POST['dh-do-backupnow'] ) ) {
			check_admin_referer( 'dh-do-backupnow-settings-options' );
			wp_schedule_single_event( current_time( 'timestamp', true ) + 60, 'dh-do-backupnow' );
			$message = __( 'Scheduled ASAP backup in 60 seconds.', 'dreamobjects' );
			self::logger( $message );
		}

	}

	// Returns the URL of the plugin's folder.
	public static function get_url() {
		return plugins_url() . '/';
	}

	/**
	 * Logging function
	 *
	 */
	public static function logger( $msg ) {
		$file = plugin_dir_path( dirname( __FILE__ ) ) . '/debug.txt';
		if ( 'reset' === $msg ) {
			$fd  = fopen( $file, 'w+' );
			$str = '';
			fwrite( $fd, $str );
			fclose( $fd );
		} elseif ( 'on' === get_option( 'dh-do-logging' ) ) {
			$fd  = fopen( $file, 'a' );
			$str = '[' . date( 'Y/m/d h:i:s', current_time( 'timestamp' ) ) . '] ' . $msg . "\n";
			fwrite( $fd, $str );
			fclose( $fd );
		}
	}

	/**
	 * Status Notifications
	 *
	 * Updates status log with
	 *
	 * @since 4.0
	 * @param type $filename  Nicename of the file being saved
	 * @param type $message   Content of message
	 * @param type $frequency Determination what is shown and when.
	 *
	 */
	public static function notifier( $filename, $message, $frequency ) {

		if ( 'disabled' !== get_option( 'dh-do-notify' ) ) {
			global $wpdb;

			DreamObjects_Core::install();

			$table_name = $wpdb->prefix . 'dreamobjects_backup_log';

			$wpdb->insert(
				$table_name,
				array(
					'filename'  => $filename,
					'frequency' => $frequency,
					'text'      => '[' . date( 'Y/m/d h:i:s', current_time( 'timestamp' ) ) . '] ' . $message,
				)
			);
		}
	}


	/**
	 * Scan the directory and come up with what we need to backup.
	 *
	 * @access public
	 * @param string $base (default: '') - the folder to scan.
	 * @return array
	 */
	public static function rscandir( $base = '' ) {

		$data = array_diff( scandir( $base ), array( '.', '..' ) );
		$omit = array( '\/cache' );
		$subs = array();

		foreach ( $data as $key => $value ) :
			if ( is_dir( $base . '/' . $value ) ) :
				unset( $data[ $key ] );
				$subs[] = self::rscandir( $base . '/' . $value );
			elseif ( is_file( $base . '/' . $value ) ) :
				$data[ $key ] = $base . '/' . $value;
			endif;
		endforeach;

		foreach ( $subs as $sub ) {
			$data = array_merge( $data, $sub );
		}

		// This is broken
		/*
		foreach ( $omit as $omitter ) {
			$data = preg_grep( $omitter, $data, PREG_GREP_INVERT );
		}
		*/

		// You don't need this unless you're debugging a LOT ot stuff.
		//self::logger( print_r( $data ) );

		return $data;
	}


	/**
	 * Run the backup.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function backup() {
		global $wpdb;

		$backupfolder = WP_CONTENT_DIR . '/upgrade/';
		$backuphash   = wp_hash( wp_rand() );
		$file         = $backupfolder . $backuphash . '-dreamobject-backup.zip';
		$fileurl      = content_url() . '/upgrade/dreamobject-backup.zip';
		$bucket       = get_option( 'dh-do-bucket' );
		$homeurl      = home_url();
		$prefix       = explode( '//', $homeurl );
		$prefix       = next( $prefix );
		$filenicename = $prefix . '/' . date_i18n( 'Y-m-d-His', current_time( 'timestamp' ) ) . '.zip';
		$backups      = array();

		// translators: %1$s is the name of the zip.
		$message = sprintf( __( 'Beginning backup for %1$s ...', 'dreamobjects' ), $filenicename );
		self::logger( $message );

		if ( ! is_dir( WP_CONTENT_DIR . '/upgrade/' ) ) {
			$message = __( 'Upgrade folder missing. This will cause serious issues with WP in general, so we will create it for you.', 'dreamobjects' );
			self::logger( $message );
			mkdir( WP_CONTENT_DIR . '/upgrade/' );
		}

		// Pull in data for what to backup.
		$sections = get_option( 'dh-do-backupsection' );
		if ( ! $sections ) {
			$sections = array();
		}

		// Pre-Cleanup.
		// Delete any zip files found in the update folder.
		foreach ( glob( $backupfolder . '*.zip' ) as $oldzip ) {
			// translators: %s is the zip file we're deleting
			$message = sprintf( __( 'Leftover zip file found, deleting %s ...', 'dreamobjects' ), $oldzip );
			self::logger( $message );
			@unlink( $oldzip );
		}

		// Try to make a zip.
		try {
			$zip      = new ZipArchive( $file );
			$zaresult = true;
			$message  = __( 'ZipArchive found and will be used for backups.', 'dreamobjects' );
			self::logger( $message );
		} catch ( Exception $e ) {
			$error_string = $e->getMessage();
			$zip          = new PclZip( $file );
			// translators: %s is the error (from above)
			$message = sprintf( __( 'ZipArchive not found. Error:  %s', 'dreamobjects' ), $error_string );
			self::logger( $message );
			$message = __( 'ZipArchive not found. PclZip will be used for backups.', 'dreamobjects' );
			self::logger( $message );
			require_once ABSPATH . '/wp-admin/includes/class-pclzip.php';
			$zaresult = false;
		}

		// All me files!
		if ( in_array( 'files', $sections ) ) {

			$message = __( 'Calculating backup size...', 'dreamobjects' );
			self::logger( $message );

			$trimdisk  = WP_CONTENT_DIR;
			$diskcmd   = sprintf( 'du -s %s', WP_CONTENT_DIR );
			$diskusage = exec( $diskcmd );
			$diskusage = trim( str_replace( $trimdisk, '', $diskusage ) );

			self::logger( size_format( $diskusage * 1024 ) . ' of diskspace will be processed.' );

			if ( $diskusage < ( 2000 * 1024 ) ) {
				$message = __( 'Scanning folders and files to generate list for backup. This may take a while...', 'dreamobjects' );
				self::logger( $message );
				$backups = array_merge( $backups, self::rscandir( WP_CONTENT_DIR ) );
				// translators: %d is the number of files.
				$message = sprintf( __( 'Scan completed. %d files added to backup list.', 'dreamobjects' ), count( $backups ) );
				self::logger( $message );
			} else {
				$message = __( 'ERROR! PHP is unable to backup your wp-content folder. Please consider cleaning out unused files (like plugins and themes).', 'dreamobjects' );
				self::logger( $message );
			}

			if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
				$backups[] = ABSPATH . 'wp-config.php';
				$message   = __( 'wp-config.php added to backup list.', 'dreamobjects' );
				self::logger( $message );
			}

			if ( file_exists( ABSPATH . '.htaccess' ) ) {
				$backups[] = ABSPATH . '.htaccess';
				$message   = __( 'A copy of .htaccess has been added to backup list.', 'dreamobjects' );
				self::logger( $message );
			}
		} // End Files.

		// And me DB!
		if ( in_array( 'database', $sections ) ) {
			set_time_limit( 300 );

			$sqlfile       = $backupfolder . $backuphash . '-dreamobjects-backup.sql';
			$tables        = $wpdb->get_col( "SHOW TABLES LIKE '" . $wpdb->prefix . "%'" );
			$tables_string = implode( ' ', $tables );

			// Delete any old DB files lying around.
			foreach ( glob( $backupfolder . '*.sql' ) as $oldsql ) {
				// translators: %s is the SQL file we're deleting
				$message = sprintf( __( 'Leftover SQL file found, deleting %s ...', 'dreamobjects' ), $oldsql );
				self::logger( $message );
				@unlink( $oldsql );
			}

			// MySQL dump. Not the best idea, but it's what we have for maximum compatiblity.
			$dbcmd = sprintf( "mysqldump -h'%s' -u'%s' -p'%s' %s %s --single-transaction 2>&1 >> %s", DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, $tables_string, $sqlfile );
			exec( $dbcmd );

			$sqlsize = size_format( @filesize( $sqlfile ) );
			// translators: %1 is the name of the file, %2 is the size
			$message = sprintf( __( 'SQL file created: %1$s (%2$s) ...', 'dreamobjects' ), $sqlfile, $sqlsize );
			self::logger( $message );

			$backups[] = $sqlfile;

			$message = __( 'SQL added to backup list.', 'dreamobjects' );
			self::logger( $message );
		} // End DB.

		// Create the Zip.
		if ( ! empty( $backups ) ) {

			// Increased timeout to 5 minutes. If the zip takes longer than that, I have a problem.
			set_time_limit( 300 );

			if ( ! $zaresult ) {
				$message = __( 'Creating zip file using PclZip.', 'dreamobjects' );
				self::logger( $message );

				$message = __( 'NOTICE: If the log stops here, PHP failed to create a zip of your wp-content folder. Please consider increasing the server\'s PHP memory, RAM or CPU.', 'dreamobjects' );
				self::logger( $message );

				$zip->create( $backups );
			} else {
				$message = __( 'Creating zip file using ZipArchive.', 'dreamobjects' );
				self::logger( $message );

				$message = __( 'NOTICE: If the log stops here, PHP failed to create a zip of your wp-content folder. Please consider increasing the server\'s PHP memory, RAM, or CPU.', 'dreamobjects' );
				self::logger( $message );
				try {
					$zip->open( $file, ZipArchive::CREATE );
					$trimpath = ABSPATH;

					foreach ( $backups as $backupfiles ) {
						if ( false === strpos( $backupfiles, DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR ) ) {
							$zip->addFile( $backupfiles, 'dreamobjects-backup' . str_replace( $trimpath, '/', $backupfiles ) );
							if ( WP_DEBUG ) {
								// This is too large to keep in the log ALL the time.
								$message = __( 'Adding the following files...', 'dreamobjects' );
								self::logger( $message );
								self::logger( $backupfiles );
							}
						}
					}
					$zip->close();
				} catch ( Exception $e ) {
					$error_string = $e->getMessage();
					// translators: %s is the error from above.
					$message = sprintf( __( 'ZipArchive failed to complete: %s', 'dreamobjects' ), $error_string );
					self::logger( $message );
					self::notifier( $filenicename, $message, 'failure' );
				}
			}

			// Calculate file size
			if ( @file_exists( $file ) ) {
				self::logger( 'Calculating zip file size ...' );
				$zipsize = size_format( @filesize( $file ) );
				// translators: %1 is the name of the zip, %2 is the size
				$message = sprintf( __( 'Zip file created: %1$s (%2$s) ...', 'dreamobjects' ), $filenicename, $zipsize );
				self::logger( $message );
			} else {
				@unlink($file);
				$message = __( 'Zip file failed to generate. Nothing will be backed up.', 'dreamobjects' );
				self::logger( $message );
				self::notifier( $filenicename, $message, 'failure' );
			}

			// Delete SQL file.
			if ( file_exists( $sqlfile ) ) {
				@unlink( $sqlfile );
				// translators: %s is the name of the SQL file we're deleting
				$message = sprintf( __( 'Deleting SQL file: %s', 'dreamobjects' ), $sqlfile );
				self::logger( $message );
			}

			// Upload to DreamObjects.
			$message = __( 'Connecting to DreamObjects Server ...', 'dreamobjects' );
			self::logger( $message );
			if ( @file_exists( $file ) ) {
				$s3 = new S3Client( DreamObjects_Core::$s3_options );

				/*
				// https://dreamxtream.wordpress.com/2013/10/29/aws-php-sdk-logging-using-guzzle/
				// This should never be used, but if it has to be commented out, it's a bad day.
				$logPlugin = LogPlugin::getDebugPlugin(TRUE,
				//Don't provide this parameter to show the log in PHP output
					fopen(DHDO_PLUGIN_DIR.'/debug2.txt', 'a' )
				);
				$s3->addSubscriber($logPlugin);
				*/

				// Uploading
				set_time_limit( 180 );
				$message = __( 'Beginning upload.', 'dreamobjects' );
				self::logger( $message );

				// Check the size of the file before we upload, in order to compensate for large files
				if ( @filesize( $file ) >= ( 100 * 1024 * 1024 ) ) {

					// Files larger than 100megs go through Multipart
					$message = __( 'File size is over 100megs, using Multipart uploader.', 'dreamobjects' );
					self::logger( $message );

					// High Level
					$message = __( 'Preparing the upload parameters and upload parts in 25M chunks.', 'dreamobjects' );
					self::logger( $message );

					$uploader = new MultipartUploader( $s3, $file, [
						'bucket'      => $bucket,
						'key'         => $filenicename,
						'part_size'   => 25 * 1024 * 1024,
						'acl'         => 'private',
						'concurrency' => 3,
					]);

					try {
						$message = __( 'Beginning Multipart upload to the cloud. This may take a while (5 minutes for every 75 megs or so).', 'dreamobjects' );
						self::logger( $message );
						set_time_limit( 180 );
						$uploader->upload();
						$message = __( 'SUCCESS: Multipart upload to the cloud complete!', 'dreamobjects' );
						self::logger( $message );
							self::notifier( $filenicename, $message, 'success' );
					} catch ( MultipartUploadException $e ) {
						$uploader->abort();
						// translators: %s is the error
						$message = sprintf( __( 'FAILURE: Multipart upload to the cloud failed: %s', 'dreamobjects' ), $e->getMessage() );
						self::logger( $message );
						self::notifier( $filenicename, $message, 'failure' );
					}
				} else {
					// If it's under 100megs, do it the old way
					$message = __( 'File size is under 100megs. This will be less spammy.', 'dreamobjects' );
					self::logger( $message );

					set_time_limit( 180 ); // 3 min
					try {
						$result  = $s3->putObject( array(
							'Bucket'      => $bucket,
							'Key'         => $filenicename,
							'SourceFile'  => $file,
							'ContentType' => 'application/zip',
							'ACL'         => 'private',
							'Metadata'    => array(
								'UploadedBy'   => 'DreamObjectsBackupPlugin',
								'UploadedDate' => date_i18n( 'Y-m-d-His', current_time( 'timestamp' ) ),
							),
						) );
						$message = __( 'SUCCESS: Upload to the cloud complete!', 'dreamobjects' );
						self::logger( $message );
						self::notifier( $filenicename, $message, 'success' );
					} catch ( S3Exception $e ) {
						// translators: %s is the error from the cloud upload
						$message = sprintf( __( 'FAILURE: Upload to the cloud failed: %s', 'dreamobjects' ), $e->getMessage() );
						self::logger( $message );
						self::notifier( $filenicename, $message, 'failure' );
					}
				}

				// https://dreamxtream.wordpress.com/2013/10/29/aws-php-sdk-logging-using-guzzle/ .
				// $s3->getEventDispatcher()->removeSubscriber($logPlugin);
			} else {
				$message = __( 'FAILURE: Nothing to upload.', 'dreamobjects' );
				self::logger( $message );
				self::notifier( $filenicename, $message, 'failure' );
			}

			// Cleanup
			if ( file_exists( $file ) ) {
				@unlink( $file );
				// translators: %s is the name of the zip file
				$message = sprintf( __( 'Deleting zip file: %s', 'dreamobjects' ), $file );
				self::logger( $message );
			}

			if ( file_exists( $sqlfile ) ) {
				@unlink( $sqlfile );
				// translators: %s is the name of the SQL file
				$message = sprintf( __( 'Deleting sql file: %s', 'dreamobjects' ), $sqlfile );
				self::logger( $message );
			}
		}

		// Cleanup Old Backups
		$message = __( 'Checking for backups to be deleted from the cloud.', 'dreamobjects' );
		self::logger( $message );
		if ( get_option( 'dh-do-retain' ) && 'all' !== get_option( 'dh-do-retain' ) ) {
			$num_backups = get_option( 'dh-do-retain' );
			$s3          = new S3Client( DreamObjects_Core::$s3_options );
			$bucket      = get_option( 'dh-do-bucket' );
			$parseurl    = parse_url( trim( home_url() ) );
			$prefixurl   = $parseurl['host'];

			if ( isset( $parseurl['path'] ) ) {
				$prefixurl .= $parseurl['path'];
			}

			$backups = $s3->listObjectsV2( array(
				'Bucket' => $bucket,
				'Prefix' => $prefixurl,
			) );

			if ( false !== $backups ) {
				$backups = $backups->toArray();

				if ( empty( $backups ) || ! array_key_exists( 'Contents', $backups ) || count( $backups['Contents'] ) <= 1 ) {
					$message = __( 'No backups found that are eligible for deletion.', 'dreamobjects' );
					self::logger( $message );
				} else {
					krsort( $backups['Contents'] );
					$count = 0;
					foreach ( $backups['Contents'] as $object ) {
						if ( ++$count > $num_backups && substr( $object['Key'], -1 ) !== '/' ) {
							$s3->deleteObject(
								array(
									'Bucket' => $bucket,
									'Key'    => $object['Key'],
								)
							);
							// translators: %s is the name of the backup that was removed
							$message = sprintf( __( 'Removed backup %s from the cloud, per user retention choice', 'dreamobjects' ), $object['Key'] );
							self::logger( $message );
						}
					}
				}
			}
		} else {
			$message = __( 'Per user retention choice, not deleting a single old backup.', 'dreamobjects' );
			self::logger( $message );
		}
		$message = __( 'Backup Complete.', 'dreamobjects' );
		self::logger( $message );
		self::logger( '' );
		delete_option( 'dh-do-backupnow' );
	}


	/**
	 * Cron Schedule
	 *
	 * @access public
	 * @static
	 * @param mixed $schedules
	 * @return void
	 */
	public static function cron_schedules( $schedules ) {
		$schedules['daily']   = array(
			'interval' => 86400,
			'display'  => 'Once Daily',
		);
		$schedules['weekly']  = array(
			'interval' => 604800,
			'display'  => 'Once Weekly',
		);
		$schedules['monthly'] = array(
			'interval' => 2592000,
			'display'  => 'Once Monthly',
		);
		return $schedules;
	}
}
